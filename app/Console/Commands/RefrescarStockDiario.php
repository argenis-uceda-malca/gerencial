<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefrescarStockDiario extends Command
{
    protected $signature = 'etl:stock-diario
                            {--desde= : Fecha inicial YYYY-MM-DD (default: primer dia pendiente)}
                            {--hasta= : Fecha final YYYY-MM-DD (default: ayer)}
                            {--recalcular=3 : Dias hacia atras que se vuelven a calcular por movimientos con fecha atrasada}
                            {--solo-resumen : No recalcula el stock por SKU, solo el resumen de los dias que ya existen}
                            {--completo : Fuerza el recalculo completo en todos los dias del rango (no solo domingos)}';

    protected $description = 'Stock diario: incremental dia por dia, recalculo completo los domingos, y resumen por almacen/marca/categoria';

    public function handle()
    {
        $db = DB::connection('pgsql');

        $hasta = $this->option('hasta')
            ? Carbon::parse($this->option('hasta'))->startOfDay()
            : now()->subDay()->startOfDay();

        $soloResumen = (bool) $this->option('solo-resumen');

        if ($this->option('desde')) {
            $desde = Carbon::parse($this->option('desde'))->startOfDay();
        } else {
            // Ultimo dia con stock por SKU, menos los dias a recalcular
            $ultimo = $db->selectOne('SELECT MAX(fecha) AS f FROM automatizacion_costo_stk WHERE fecha <= ?::date', [$hasta->toDateString()]);
            if (!$ultimo || !$ultimo->f) {
                $this->error('No hay ninguna base en automatizacion_costo_stk. Ejecutar primero automatizacion_sp_insertar_stock_semana para una fecha.');
                return Command::FAILURE;
            }
            $desde = Carbon::parse($ultimo->f)->subDays(max(0, (int) $this->option('recalcular') - 1))->startOfDay();
        }

        if ($desde->gt($hasta)) {
            $this->info('Nada pendiente.');
            return Command::SUCCESS;
        }

        Log::info('etl:stock-diario INICIO', ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString(), 'solo_resumen' => $soloResumen]);
        $this->info("Stock diario: {$desde->toDateString()} -> {$hasta->toDateString()}" . ($soloResumen ? ' (solo resumen)' : ''));

        // Una llamada por dia: cada consulta debe terminar en menos de 1 minuto.
        for ($d = $desde->copy(); $d->lte($hasta); $d->addDay()) {
            $f = $d->toDateString();

            if ($soloResumen) {
                $existe = $db->selectOne('SELECT 1 AS x FROM automatizacion_stock_semanal WHERE fecha = ?::date LIMIT 1', [$f]);
                if (!$existe) {
                    $this->line("  {$f}  sin stock por SKU, omitido");
                    continue;
                }
                $segStock = 0;
            } else {
                // Domingos: recalculo completo desde el saldo inicial del año, para
                // recoger los movimientos registrados con fecha atrasada que el
                // incremental no ve. El resto de dias: incremental sobre el dia anterior.
                $completo = $d->isSunday() || $this->option('completo');
                $modo = $completo ? 'completo' : 'incremental';

                $t = microtime(true);
                if ($completo) {
                    $db->select('SELECT automatizacion_sp_insertar_stock_semana(?::date, NULL)', [$f]);
                } else {
                    $db->select('SELECT automatizacion_sp_insertar_stock_diario(?::date, ?::date)', [$f, $f]);
                }
                $segStock = round(microtime(true) - $t, 1);

                // El SP no falla si un dia sale mal (solo avisa): se verifica aqui.
                $existe = $db->selectOne('SELECT 1 AS x FROM automatizacion_stock_semanal WHERE fecha = ?::date LIMIT 1', [$f]);
                if (!$existe) {
                    $this->error("  {$f}  el stock por SKU no se genero ({$segStock}s). Se detiene para no encadenar dias sobre una base incompleta.");
                    Log::error('etl:stock-diario dia sin datos', ['fecha' => $f]);
                    return Command::FAILURE;
                }
            }

            $t = microtime(true);
            $r = $db->selectOne('SELECT automatizacion_sp_stock_diario_resumen(?::date, ?::date) AS filas', [$f, $f]);
            $segRes = round(microtime(true) - $t, 1);

            // El recalculo completo semanal tiene un limite aceptado de 90 s.
            $limite = (!$soloResumen && $completo) ? 90 : 60;
            $aviso = ($segStock > $limite || $segRes > 60) ? "  <-- supera {$limite}s" : '';
            $etiqueta = $soloResumen ? 'stock -' : "stock {$modo} {$segStock}s";
            $this->line("  {$f}  {$etiqueta}  resumen {$segRes}s  filas {$r->filas}{$aviso}");

        }

        // Foto detallada por producto que lee el Excel (bloque stock_act del
        // reporte): se renueva en cada corrida con el ultimo dia procesado, para
        // que el Excel muestre siempre el ultimo stock disponible. No se toca
        // cuando la corrida es un relleno de dias antiguos.
        if (!$soloResumen && $hasta->gte(now()->subDays(2)->startOfDay())) {
            $f = $hasta->toDateString();
            $t = microtime(true);
            $db->transaction(function () use ($db, $f) {
                $db->statement("DELETE FROM automatizacion_pla_reporte_ventas WHERE tipo_fila = 'stock_act'");
                $db->select('SELECT automatizacion_sp_reporte_ventas(?::date, ?::date, FALSE)', [$f, $f]);
            });
            $segFoto = round(microtime(true) - $t, 1);
            $this->line("  {$f}  foto detallada del reporte (stock_act) {$segFoto}s" . ($segFoto > 60 ? '  <-- supera 60s' : ''));
        }

        Log::info('etl:stock-diario FIN OK');
        $this->info('Stock diario actualizado.');

        return Command::SUCCESS;
    }
}
