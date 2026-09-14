<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Centraliza las queries y la lógica de detección de anomalías de ventas.
 * Usado tanto por MonitorearVentasCommand (alertas por email) como por
 * MonitorVentasController (vista web).
 */
class MonitoreoVentasService
{
    // ── Umbrales ────────────────────────────────────────────────────────────
    public const ETL_MAX_HORAS  = 1;    // máx horas sin ETL antes de alertar
    public const UMBRAL_ALTO    = 2.5;  // ratio venta hoy/promedio → alerta alta
    public const UMBRAL_BAJO    = 0.30; // ratio venta hoy/promedio → alerta baja
    public const HORA_INICIO    = 10;   // tiendas abren ~10am; exigir ventas desde esta hora
    public const HORA_TIENDAS   = 11;   // revisar tiendas silenciosas desde esta hora
    public const HORA_BAJA      = 16;   // alertar por venta baja solo después de esta hora

    // ── Punto de entrada ────────────────────────────────────────────────────

    public function analizar(): array
    {
        $ahora = Carbon::now('America/Lima');
        $hoy   = $ahora->toDateString();
        $hora  = $ahora->hour;

        $etl     = $this->getEtlStatus();
        $ventas  = $this->getVentasHoy($hoy);
        $stats   = $this->getStats30d($hoy);
        $tiendas = $this->getTiendasResumen($hoy);
        $logEtl  = $this->getLogEtl(10);
        $alertasPendientes = $this->getAlertasPendientes();

        $alertas = $this->detectarAnomalias($etl, $ventas, $stats, $tiendas, $hora, $hoy);

        return compact(
            'ahora', 'hoy', 'hora',
            'etl', 'ventas', 'stats', 'tiendas',
            'logEtl', 'alertas', 'alertasPendientes'
        );
    }

    // ── Fetchers ────────────────────────────────────────────────────────────

    public function getEtlStatus(): array
    {
        try {
            $row = DB::connection('pgsql')->selectOne("
                SELECT
                    fecha_ejecucion AT TIME ZONE 'America/Lima' AS fecha_lima,
                    EXTRACT(EPOCH FROM (NOW() - fecha_ejecucion)) / 3600.0 AS horas
                FROM automatizacion_control_ejecucion
                WHERE estado = 'OK'
                ORDER BY id DESC LIMIT 1
            ");
        } catch (\Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage(), 'horas' => null, 'fecha_lima' => null];
        }

        if (!$row) {
            return ['ok' => false, 'horas' => null, 'fecha_lima' => null, 'error' => 'Sin ejecuciones OK'];
        }

        $horas = round((float) $row->horas, 1);
        return [
            'ok'        => $horas < self::ETL_MAX_HORAS,
            'horas'     => $horas,
            'fecha_lima'=> Carbon::parse($row->fecha_lima)->format('d/m/Y H:i'),
            'error'     => null,
        ];
    }

    public function getVentasHoy(string $hoy): array
    {
        try {
            $row = DB::connection('pgsql')->selectOne("
                SELECT COUNT(*)::int AS filas,
                       COALESCE(SUM(importe_subtotal), 0)::float AS total
                FROM automatizacion_pla_reporte_ventas
                WHERE tipo_fila = 'ventas_act'
                  AND fecha_documento = :hoy::date
            ", ['hoy' => $hoy]);
            return ['total' => (float)$row->total, 'filas' => (int)$row->filas, 'error' => null];
        } catch (\Exception $e) {
            return ['total' => 0, 'filas' => 0, 'error' => $e->getMessage()];
        }
    }

    public function getStats30d(string $hoy): array
    {
        try {
            $row = DB::connection('pgsql')->selectOne("
                WITH diario AS (
                    SELECT fecha_documento, SUM(importe_subtotal) AS total
                    FROM automatizacion_pla_reporte_ventas
                    WHERE tipo_fila = 'ventas_act'
                      AND fecha_documento >= :hoy::date - INTERVAL '35 days'
                      AND fecha_documento  < :hoy2::date
                    GROUP BY fecha_documento
                    HAVING SUM(importe_subtotal) > 1000
                )
                SELECT AVG(total)::float    AS promedio,
                       STDDEV(total)::float AS std,
                       COUNT(*)::int        AS dias
                FROM diario
            ", ['hoy' => $hoy, 'hoy2' => $hoy]);

            return [
                'promedio' => (float)($row->promedio ?? 0),
                'std'      => (float)($row->std ?? 0),
                'dias'     => (int)($row->dias ?? 0),
                'error'    => null,
            ];
        } catch (\Exception $e) {
            return ['promedio' => 0, 'std' => 0, 'dias' => 0, 'error' => $e->getMessage()];
        }
    }

    public function getTiendasResumen(string $hoy): array
    {
        try {
            return DB::connection('pgsql')->select("
                WITH dias AS (
                    SELECT sucursal_2, marca,
                           fecha_documento,
                           SUM(importe_subtotal) AS total_dia
                    FROM automatizacion_pla_reporte_ventas
                    WHERE tipo_fila = 'ventas_act'
                      AND fecha_documento BETWEEN :hoy::date - INTERVAL '7 days'
                                              AND :hoy2::date
                    GROUP BY sucursal_2, marca, fecha_documento
                )
                SELECT
                    sucursal_2,
                    MAX(marca) AS marca,
                    COALESCE(MAX(CASE WHEN fecha_documento = :hoy3::date THEN total_dia END), 0)::float AS venta_hoy,
                    COUNT(DISTINCT CASE WHEN fecha_documento < :hoy4::date THEN fecha_documento END)::int AS dias_semana,
                    COALESCE(AVG(CASE WHEN fecha_documento < :hoy5::date THEN total_dia END), 0)::float   AS promedio_diario
                FROM dias
                WHERE sucursal_2 IS NOT NULL
                GROUP BY sucursal_2
                HAVING COUNT(DISTINCT CASE WHEN fecha_documento < :hoy6::date THEN fecha_documento END) >= 2
                ORDER BY MAX(marca), sucursal_2
            ", ['hoy'=>$hoy,'hoy2'=>$hoy,'hoy3'=>$hoy,'hoy4'=>$hoy,'hoy5'=>$hoy,'hoy6'=>$hoy]);
        } catch (\Exception) {
            return [];
        }
    }

    public function getLogEtl(int $limite = 10): array
    {
        try {
            return DB::connection('pgsql')->select("
                SELECT tipo_ejecucion, p_fecha_ini, p_fecha_fin,
                       ROUND(duracion_segundos::numeric, 1) AS seg,
                       estado, mensaje_error,
                       fecha_ejecucion AT TIME ZONE 'America/Lima' AS fecha_lima
                FROM automatizacion_control_ejecucion
                ORDER BY id DESC LIMIT :lim
            ", ['lim' => $limite]);
        } catch (\Exception) {
            return [];
        }
    }

    public function getAlertasPendientes(): array
    {
        try {
            return DB::connection('pgsql')->select("
                SELECT * FROM automatizacion_alertas WHERE atendida = FALSE ORDER BY id DESC
            ");
        } catch (\Exception) {
            return [];
        }
    }

    // ── Detección de anomalías ───────────────────────────────────────────────

    public function detectarAnomalias(
        array $etl, array $ventas, array $stats, array $tiendas,
        int $hora, string $hoy
    ): array {
        $alertas = [];

        // ETL congelado
        if (!$etl['ok']) {
            $horasStr = $etl['horas'] !== null ? "{$etl['horas']}h" : 'desconocido';
            $alertas[] = [
                'tipo'    => 'etl_congelado',
                'nivel'   => 'critico',
                'titulo'  => "ETL sin ejecutarse hace {$horasStr}",
                'detalle' => $etl['horas'] !== null
                    ? "Última ejecución exitosa: {$etl['fecha_lima']}. Revisar automatizacion_alertas y el job de pg_cron."
                    : ($etl['error'] ?? 'Sin datos'),
            ];
        }

        // Sin ventas hoy (después de HORA_INICIO)
        if ($hora >= self::HORA_INICIO && $ventas['total'] == 0) {
            $alertas[] = [
                'tipo'    => 'sin_ventas',
                'nivel'   => 'critico',
                'titulo'  => "Sin ventas registradas para hoy ({$hoy})",
                'detalle' => "El ETL corrió pero no hay filas de ventas_act para hoy. "
                           . "Verificar volcado desde SQL Server.",
            ];
        }

        // Venta anormal vs promedio histórico
        if ($stats['dias'] >= 5 && $stats['promedio'] > 0 && $hora >= self::HORA_INICIO) {
            $ratio    = $ventas['total'] / $stats['promedio'];
            $ratioFmt = number_format($ratio, 2);
            $hoyFmt   = number_format($ventas['total'], 2, '.', ',');
            $promFmt  = number_format($stats['promedio'], 2, '.', ',');

            if ($ratio > self::UMBRAL_ALTO) {
                $alertas[] = [
                    'tipo'    => 'venta_alta',
                    'nivel'   => 'warning',
                    'titulo'  => "Venta ALTA: {$ratioFmt}× el promedio histórico",
                    'detalle' => "Hoy S/ {$hoyFmt} vs promedio 30d S/ {$promFmt}. "
                               . "Posible duplicación de datos o congelamiento previo del servidor.",
                ];
            } elseif ($ratio < self::UMBRAL_BAJO && $hora >= self::HORA_BAJA) {
                $alertas[] = [
                    'tipo'    => 'venta_baja',
                    'nivel'   => 'warning',
                    'titulo'  => "Venta BAJA: {$ratioFmt}× el promedio histórico",
                    'detalle' => "Hoy S/ {$hoyFmt} vs promedio 30d S/ {$promFmt}. "
                               . "Verificar tiendas y el estado del ETL.",
                ];
            }
        }

        // Tiendas silenciosas
        if ($hora >= self::HORA_TIENDAS) {
            $silenciosas = array_filter(
                (array) $tiendas,
                fn($t) => (float)$t->venta_hoy == 0 && (int)$t->dias_semana >= 3
            );

            if (!empty($silenciosas)) {
                $nombres = implode(', ', array_map(fn($t) => $t->sucursal_2, $silenciosas));
                $n = count($silenciosas);
                $alertas[] = [
                    'tipo'    => 'tiendas_silenciosas',
                    'nivel'   => 'warning',
                    'titulo'  => "{$n} tienda(s) sin ventas hoy (activas la semana pasada)",
                    'detalle' => $nombres,
                ];
            }
        }

        return $alertas;
    }
}
