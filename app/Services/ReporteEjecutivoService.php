<?php

namespace App\Services;

use App\Mail\ReporteEjecutivoMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Reporte ejecutivo de ventas (correo con Excel + imágenes).
 *
 * Fuente: automatizacion_pla_reporte_ventas (la misma de /dashboard/reporte):
 *   - Vta Neta 26  = importe_subtotal      (tipo_fila = ventas_act)
 *   - Vta Neta 25  = importe_subtotal_hst_1 (tipo_fila = ventas_hst, por día equivalente)
 *   - Meta Vta     = meta_venta            (tipo_fila = metas_std)
 *   - Unid / GM / Tickets = unidades, importe-costo, flag_tickets_act
 *
 * Código compatible con PHP 7.3 (producción).
 */
class ReporteEjecutivoService
{
    const KEYS  = ['vta26', 'vta25', 'meta', 'unds26', 'gm26', 'tickets26'];
    const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const HEAD  = ['Vta Neta 26', 'Vta Neta 25', 'Meta Vta', '%Cumpl Meta', '%Var vs 25', 'Unid 26', '%GM 26', 'Tickets'];
    const FMT   = ['#,##0', '#,##0', '#,##0', '0.0%', '+0.0%;-0.0%;0.0%', '#,##0', '0.0%', '#,##0'];
    const NAVY  = '1F2A44';

    /* ══════════════════════════════════════════════════════════
       Rango por defecto: 1-ene del año hasta AYER
    ══════════════════════════════════════════════════════════ */
    public function rangoPorDefecto()
    {
        $fin = Carbon::yesterday();
        return [$fin->copy()->startOfYear()->toDateString(), $fin->toDateString()];
    }

    /* ══════════════════════════════════════════════════════════
       Orquestador: datos → imágenes → Excel → correo
    ══════════════════════════════════════════════════════════ */
    public function ejecutar($ini, $fin, array $destinatarios, $soloGenerar = false)
    {
        $rows = $this->datos($ini, $fin);
        $an   = $this->analizar($rows, $ini, $fin);
        if ($an['total']['vta26'] <= 0) {
            throw new \RuntimeException("Sin ventas entre $ini y $fin; no se genera el reporte.");
        }

        $dir = storage_path('app/reporte_ejecutivo/' . date('Ymd_His'));
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $imgs  = $this->generarImagenes($an, $dir);
        $xlsx  = $dir . '/Resumen_Ventas_' . substr($fin, 0, 4) . '_al_' . $fin . '.xlsx';
        $this->generarExcel($rows, $an, $xlsx, $imgs);

        if (!$soloGenerar) {
            Mail::to($destinatarios)->send(new ReporteEjecutivoMail($an, $imgs, $xlsx));
            $this->limpiar($dir);
        }

        return ['dir' => $dir, 'excel' => $xlsx, 'imagenes' => $imgs, 'analisis' => $an];
    }

    private function limpiar($dir)
    {
        foreach (glob($dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($dir);
    }

    /* ══════════════════════════════════════════════════════════
       DATOS (diario x tienda)
    ══════════════════════════════════════════════════════════ */
    public function datos($ini, $fin)
    {
        $T = 'automatizacion_pla_reporte_ventas';
        $sql = "
WITH act AS (
  SELECT fecha_documento AS fecha, sucursal AS tienda, MAX(sucursal_3_1) AS canal, MAX(marca) AS marca,
         SUM(importe_subtotal) vta26, SUM(importe_subtotal - costo_venta_neta) gm26,
         SUM(unidades) unds26, SUM(flag_tickets_act) tickets26
  FROM $T
  WHERE tipo_fila='ventas_act' AND fecha_documento BETWEEN :ini AND :fin
  GROUP BY 1,2),
hst AS (
  SELECT fe.fecha_equivalente AS fecha, h.sucursal AS tienda, MAX(h.sucursal_3_1) AS canal, MAX(h.marca) AS marca,
         SUM(h.importe_subtotal_hst_1) vta25
  FROM $T h JOIN pla_fechas_equivalentes fe ON fe.fecha = h.fecha_documento
  WHERE h.tipo_fila='ventas_hst' AND fe.fecha_equivalente BETWEEN :ini2 AND :fin2
  GROUP BY 1,2),
met AS (
  SELECT fecha_documento AS fecha, sucursal AS tienda, MAX(sucursal_3_1) AS canal, MAX(marca) AS marca,
         SUM(meta_venta) meta
  FROM $T
  WHERE tipo_fila='metas_std' AND fecha_documento BETWEEN :ini3 AND :fin3
  GROUP BY 1,2),
llaves AS (SELECT fecha, tienda FROM act UNION SELECT fecha, tienda FROM hst UNION SELECT fecha, tienda FROM met)
SELECT l.fecha, l.tienda,
       COALESCE(a.canal, h.canal, m.canal) canal, COALESCE(a.marca, h.marca, m.marca) marca,
       COALESCE(a.vta26,0) vta26, COALESCE(h.vta25,0) vta25, COALESCE(m.meta,0) meta,
       COALESCE(a.unds26,0) unds26, COALESCE(a.gm26,0) gm26, COALESCE(a.tickets26,0) tickets26
FROM llaves l
LEFT JOIN act a USING (fecha, tienda)
LEFT JOIN hst h USING (fecha, tienda)
LEFT JOIN met m USING (fecha, tienda)
ORDER BY l.tienda, l.fecha";

        $res = DB::connection('pgsql')->select($sql, [
            'ini' => $ini, 'ini2' => $ini, 'ini3' => $ini,
            'fin' => $fin, 'fin2' => $fin, 'fin3' => $fin,
        ]);

        $out = [];
        foreach ($res as $r) {
            if ($r->tienda === null || $r->tienda === '') {
                continue;
            }
            $out[] = [
                'fecha'     => substr((string)$r->fecha, 0, 10),
                'tienda'    => $r->tienda,
                'canal'     => (string)$r->canal,
                'marca'     => (string)$r->marca,
                'vta26'     => (float)$r->vta26,
                'vta25'     => (float)$r->vta25,
                'meta'      => (float)$r->meta,
                'unds26'    => (float)$r->unds26,
                'gm26'      => (float)$r->gm26,
                'tickets26' => (float)$r->tickets26,
            ];
        }
        return $out;
    }

    /* ══════════════════════════════════════════════════════════
       ANÁLISIS (agregados)
    ══════════════════════════════════════════════════════════ */
    private function vacio()
    {
        return ['vta26' => 0.0, 'vta25' => 0.0, 'meta' => 0.0, 'unds26' => 0.0, 'gm26' => 0.0, 'tickets26' => 0.0];
    }

    private function sumar(array &$a, array $r)
    {
        foreach (self::KEYS as $k) {
            $a[$k] += $r[$k];
        }
    }

    public function analizar(array $rows, $ini, $fin)
    {
        // Canal y marca "dominantes" de cada tienda
        $cc = [];
        $mc = [];
        foreach ($rows as $r) {
            if ($r['canal'] !== '') {
                $cc[$r['tienda']][$r['canal']] = (isset($cc[$r['tienda']][$r['canal']]) ? $cc[$r['tienda']][$r['canal']] : 0) + 1;
            }
            if ($r['marca'] !== '') {
                $mc[$r['tienda']][$r['marca']] = (isset($mc[$r['tienda']][$r['marca']]) ? $mc[$r['tienda']][$r['marca']] : 0) + 1;
            }
        }
        $canalDe = [];
        $marcaDe = [];
        foreach ($rows as $r) {
            $t = $r['tienda'];
            if (!isset($canalDe[$t])) {
                if (isset($cc[$t])) { arsort($cc[$t]); $canalDe[$t] = key($cc[$t]); } else { $canalDe[$t] = '(sin canal)'; }
                if (isset($mc[$t])) { arsort($mc[$t]); $marcaDe[$t] = key($mc[$t]); } else { $marcaDe[$t] = ''; }
            }
        }

        $total = $this->vacio();
        $meses = [];
        $canales = [];
        $tiendas = [];
        $tm = [];
        $dias = [];
        foreach ($rows as $r) {
            $t = $r['tienda'];
            $m = (int)substr($r['fecha'], 5, 2);
            $c = $canalDe[$t];
            $this->sumar($total, $r);
            if (!isset($meses[$m]))       { $meses[$m] = $this->vacio(); }
            if (!isset($canales[$c]))     { $canales[$c] = $this->vacio(); }
            if (!isset($tiendas[$t]))     { $tiendas[$t] = $this->vacio(); }
            if (!isset($tm[$t][$m]))      { $tm[$t][$m] = $this->vacio(); }
            if (!isset($dias[$r['fecha']])) { $dias[$r['fecha']] = $this->vacio(); }
            $this->sumar($meses[$m], $r);
            $this->sumar($canales[$c], $r);
            $this->sumar($tiendas[$t], $r);
            $this->sumar($tm[$t][$m], $r);
            $this->sumar($dias[$r['fecha']], $r);
        }
        ksort($meses);
        ksort($dias);
        uasort($canales, function ($a, $b) { return $b['vta26'] <=> $a['vta26']; });
        uasort($tiendas, function ($a, $b) { return $b['vta26'] <=> $a['vta26']; });

        // tiendas con meta y venta (para el conteo de cumplimiento)
        $conMeta = 0;
        $cumplen = 0;
        foreach ($tiendas as $a) {
            if ($a['meta'] > 0 && $a['vta26'] > 0) {
                $conMeta++;
                if ($a['vta26'] / $a['meta'] >= 1) { $cumplen++; }
            }
        }

        return [
            'ini' => $ini, 'fin' => $fin,
            'total' => $total, 'meses' => $meses, 'canales' => $canales,
            'tiendas' => $tiendas, 'tienda_mes' => $tm, 'dias' => $dias,
            'canal_de' => $canalDe, 'marca_de' => $marcaDe,
            'tiendas_con_meta' => $conMeta, 'tiendas_cumplen' => $cumplen,
            'n_tiendas' => count($tiendas),
        ];
    }

    /** [cumpl, var, gm] (null si no aplica) */
    public function derivados(array $a)
    {
        $cumpl = ($a['meta'] > 0 && $a['vta26'] > 0) ? $a['vta26'] / $a['meta'] : null;
        $var   = ($a['vta25'] > 0 && $a['vta26'] > 0) ? $a['vta26'] / $a['vta25'] - 1 : null;
        $gm    = $a['vta26'] > 0 ? $a['gm26'] / $a['vta26'] : null;
        return [$cumpl, $var, $gm];
    }

    private function celdas(array $a)
    {
        list($c, $v, $g) = $this->derivados($a);
        return [round($a['vta26'], 2), round($a['vta25'], 2), round($a['meta'], 2), $c, $v,
                (int)round($a['unds26']), $g, (int)round($a['tickets26'])];
    }

    public function nombreMes($n)
    {
        return self::MESES[$n - 1];
    }

    /* ══════════════════════════════════════════════════════════
       EXCEL
    ══════════════════════════════════════════════════════════ */
    public function generarExcel(array $rows, array $an, $path, array $imgs)
    {
        @ini_set('memory_limit', '1024M');
        $periodo = Carbon::parse($an['ini'])->format('d/m/Y') . ' al ' . Carbon::parse($an['fin'])->format('d/m/Y');

        $wb = new Spreadsheet();

        /* ---- 1. Resumen ---- */
        $ws = $wb->getActiveSheet();
        $ws->setTitle('Resumen');
        $ws->setShowGridLines(false);
        $ws->setCellValue('A1', 'Smart Brands - Resumen de ventas ' . substr($an['fin'], 0, 4));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FF' . self::NAVY);
        $ws->setCellValue('A2', 'Periodo: ' . $periodo . '  |  Vta Neta 25 comparada por día equivalente');
        $ws->getStyle('A2')->getFont()->setItalic(true)->getColor()->setARGB('FF667085');

        $ws->setCellValue('A4', 'TOTAL EMPRESA');
        $ws->getStyle('A4')->getFont()->setBold(true)->getColor()->setARGB('FF' . self::NAVY);
        $this->cabecera($ws, 5, self::HEAD, 1);
        $this->bloque($ws, 6, 1, [$this->celdas($an['total'])]);
        $ws->getStyle('A6:H6')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A6:H6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE8ECF4');
        $this->condicionales($ws, 'D6', 'E6');

        $ws->setCellValue('A8', 'POR MES');
        $ws->getStyle('A8')->getFont()->setBold(true)->getColor()->setARGB('FF' . self::NAVY);
        $this->cabecera($ws, 9, array_merge(['Mes'], self::HEAD), 1);
        $data = [];
        foreach ($an['meses'] as $m => $a) {
            $data[] = array_merge([self::MESES[$m - 1]], $this->celdas($a));
        }
        $this->bloque($ws, 10, 1, $data, 1);
        $ultMes = 9 + count($data);
        $this->condicionales($ws, "E10:E$ultMes", "F10:F$ultMes");

        $r0 = $ultMes + 2;
        $ws->setCellValue("A$r0", 'POR CANAL');
        $ws->getStyle("A$r0")->getFont()->setBold(true)->getColor()->setARGB('FF' . self::NAVY);
        $this->cabecera($ws, $r0 + 1, array_merge(['Canal'], self::HEAD), 1);
        $data = [];
        foreach ($an['canales'] as $c => $a) {
            $data[] = array_merge([$c], $this->celdas($a));
        }
        $this->bloque($ws, $r0 + 2, 1, $data, 1);
        $ultCan = $r0 + 1 + count($data);
        $this->condicionales($ws, 'E' . ($r0 + 2) . ":E$ultCan", 'F' . ($r0 + 2) . ":F$ultCan");

        $ws->getColumnDimension('A')->setWidth(22);
        for ($i = 2; $i <= 9; $i++) {
            $ws->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(14);
        }
        $this->imagen($ws, isset($imgs['meses']) ? $imgs['meses'] : null, 'K4', 640);
        $this->imagen($ws, isset($imgs['top']) ? $imgs['top'] : null, 'K24', 640);

        /* ---- 2. Por tienda ---- */
        $w2 = $wb->createSheet();
        $w2->setTitle('Por tienda');
        $this->cabecera($w2, 1, array_merge(['Tienda', 'Canal', 'Marca'], self::HEAD), 1);
        $data = [];
        foreach ($an['tiendas'] as $t => $a) {
            $data[] = array_merge([$t, $an['canal_de'][$t], $an['marca_de'][$t]], $this->celdas($a));
        }
        $data[] = array_merge(['TOTAL', '', ''], $this->celdas($an['total']));
        $this->bloque($w2, 2, 1, $data, 3);
        $ult = 1 + count($data);
        $w2->getStyle("A$ult:K$ult")->getFont()->setBold(true);
        $w2->getStyle("A$ult:K$ult")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE8ECF4');
        $this->condicionales($w2, "G2:G$ult", "H2:H$ult");
        $w2->freezePane('D2');
        $w2->setAutoFilter('A1:K' . ($ult - 1));
        $w2->getColumnDimension('A')->setWidth(30);
        $w2->getColumnDimension('B')->setWidth(18);
        $w2->getColumnDimension('C')->setWidth(22);
        for ($i = 4; $i <= 11; $i++) {
            $w2->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(14);
        }

        /* ---- 3 y 4. Matrices tienda x mes ---- */
        $meses = array_keys($an['meses']);
        $this->matriz($wb, 'Vta Neta x Mes', $an, $meses, false);
        $this->matriz($wb, '%Cumpl x Mes', $an, $meses, true);

        /* ---- 5. Diario total ---- */
        $w5 = $wb->createSheet();
        $w5->setTitle('Diario total');
        $this->cabecera($w5, 1, array_merge(['Fecha', 'Día'], self::HEAD), 1);
        $dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $data = [];
        foreach ($an['dias'] as $f => $a) {
            if ($a['vta26'] <= 0 && $a['meta'] <= 0) {
                continue;
            }
            $ts = strtotime($f . ' 00:00:00 UTC');
            $data[] = array_merge([XlDate::PHPToExcel($ts), $dias[(int)gmdate('N', $ts) - 1]], $this->celdas($a));
        }
        $this->bloque($w5, 2, 1, $data, 2);
        $u5 = 1 + count($data);
        $w5->getStyle("A2:A$u5")->getNumberFormat()->setFormatCode('dd-mmm-yy');
        $this->condicionales($w5, "F2:F$u5", "G2:G$u5");
        $w5->freezePane('C2');
        $w5->getColumnDimension('A')->setWidth(12);
        $w5->getColumnDimension('B')->setWidth(7);
        for ($i = 3; $i <= 10; $i++) {
            $w5->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(14);
        }

        /* ---- 6. Detalle diario x tienda ---- */
        $w6 = $wb->createSheet();
        $w6->setTitle('Detalle diario x tienda');
        $this->cabecera($w6, 1, array_merge(['Fecha', 'Tienda', 'Canal'], self::HEAD), 1);
        usort($rows, function ($x, $y) {
            $c = strcmp($x['fecha'], $y['fecha']);
            return $c !== 0 ? $c : strcmp($x['tienda'], $y['tienda']);
        });
        $data = [];
        foreach ($rows as $r) {
            if (!($r['vta26'] || $r['vta25'] || $r['meta'])) {
                continue;
            }
            $a = $this->vacio();
            $this->sumar($a, $r);
            $ts = strtotime($r['fecha'] . ' 00:00:00 UTC');
            $data[] = array_merge([XlDate::PHPToExcel($ts), $r['tienda'], $an['canal_de'][$r['tienda']]], $this->celdas($a));
        }
        $this->bloque($w6, 2, 1, $data, 3);
        $u6 = 1 + count($data);
        $w6->getStyle("A2:A$u6")->getNumberFormat()->setFormatCode('dd-mmm-yy');
        $w6->freezePane('D2');
        $w6->setAutoFilter("A1:K$u6");
        $w6->getColumnDimension('A')->setWidth(12);
        $w6->getColumnDimension('B')->setWidth(30);
        $w6->getColumnDimension('C')->setWidth(18);
        for ($i = 4; $i <= 11; $i++) {
            $w6->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(14);
        }

        $wb->setActiveSheetIndex(0);
        $writer = new Xlsx($wb);
        $writer->save($path);
        $wb->disconnectWorksheets();
        unset($wb);
    }

    private function matriz($wb, $titulo, array $an, array $meses, $esCumpl)
    {
        $w = $wb->createSheet();
        $w->setTitle($titulo);
        $head = ['Tienda', 'Canal'];
        foreach ($meses as $m) { $head[] = self::MESES[$m - 1]; }
        $head[] = 'Total';
        $this->cabecera($w, 1, $head, 1);

        $data = [];
        $pick = function ($a) use ($esCumpl) {
            if ($esCumpl) { $d = $this->derivados($a); return $d[0]; }
            return $a['vta26'] ? round($a['vta26'], 2) : null;
        };
        foreach ($an['tiendas'] as $t => $aTot) {
            $fila = [$t, $an['canal_de'][$t]];
            foreach ($meses as $m) {
                $fila[] = isset($an['tienda_mes'][$t][$m]) ? $pick($an['tienda_mes'][$t][$m]) : null;
            }
            $fila[] = $pick($aTot);
            $data[] = $fila;
        }
        $fila = ['TOTAL', ''];
        foreach ($meses as $m) { $fila[] = $pick($an['meses'][$m]); }
        $fila[] = $pick($an['total']);
        $data[] = $fila;

        $w->fromArray($data, null, 'A2', true);
        $ult = 1 + count($data);
        $ncol = count($head);
        $rango = 'C2:' . Coordinate::stringFromColumnIndex($ncol) . $ult;
        $w->getStyle($rango)->getNumberFormat()->setFormatCode($esCumpl ? '0.0%' : '#,##0');
        $w->getStyle("A$ult:" . Coordinate::stringFromColumnIndex($ncol) . $ult)->getFont()->setBold(true);
        if ($esCumpl) {
            $this->condicionales($w, $rango, null);
        }
        $w->freezePane('C2');
        $w->getColumnDimension('A')->setWidth(30);
        $w->getColumnDimension('B')->setWidth(18);
        for ($i = 3; $i <= $ncol; $i++) {
            $w->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(12);
        }
    }

    private function cabecera($ws, $fila, array $labels, $col0)
    {
        $c1 = Coordinate::stringFromColumnIndex($col0);
        $c2 = Coordinate::stringFromColumnIndex($col0 + count($labels) - 1);
        $ws->fromArray([$labels], null, $c1 . $fila);
        $ws->getStyle("$c1$fila:$c2$fila")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . self::NAVY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $ws->getRowDimension($fila)->setRowHeight(30);
    }

    /** Escribe filas y aplica formato a las 8 columnas de métricas. $prefijo = nº de columnas antes de las métricas. */
    private function bloque($ws, $fila0, $col0, array $data, $prefijo = 0)
    {
        if (!$data) {
            return;
        }
        $ws->fromArray($data, null, Coordinate::stringFromColumnIndex($col0) . $fila0, true);
        $fila1 = $fila0 + count($data) - 1;
        foreach (self::FMT as $i => $f) {
            $col = Coordinate::stringFromColumnIndex($col0 + $prefijo + $i);
            $ws->getStyle("$col$fila0:$col$fila1")->getNumberFormat()->setFormatCode($f);
        }
    }

    /** Rojo si cumpl < 90 %, verde si >= 100 %; var: rojo si < 0, verde si > 0. */
    private function condicionales($ws, $rangoCumpl, $rangoVar)
    {
        $mk = function ($op, $val, $argb) {
            $c = new Conditional();
            $c->setConditionType(Conditional::CONDITION_CELLIS);
            $c->setOperatorType($op);
            $c->addCondition($val);
            $c->getStyle()->getFont()->getColor()->setARGB($argb);
            return $c;
        };
        $rojo = 'FFC0392B';
        $verde = 'FF1E8449';
        $ws->getStyle($rangoCumpl)->setConditionalStyles([
            $mk(Conditional::OPERATOR_LESSTHAN, '0.9', $rojo),
            $mk(Conditional::OPERATOR_GREATERTHANOREQUAL, '1', $verde),
        ]);
        if ($rangoVar) {
            $ws->getStyle($rangoVar)->setConditionalStyles([
                $mk(Conditional::OPERATOR_LESSTHAN, '0', $rojo),
                $mk(Conditional::OPERATOR_GREATERTHAN, '0', $verde),
            ]);
        }
    }

    private function imagen($ws, $path, $celda, $anchoPx)
    {
        if (!$path || !is_file($path)) {
            return;
        }
        $d = new Drawing();
        $d->setPath($path);
        $d->setCoordinates($celda);
        $d->setResizeProportional(true);
        $d->setWidth($anchoPx);
        $d->setWorksheet($ws);
    }

    /* ══════════════════════════════════════════════════════════
       IMÁGENES (GD)
    ══════════════════════════════════════════════════════════ */
    public function generarImagenes(array $an, $dir)
    {
        return [
            'kpi'   => $this->imgKpi($an, $dir . '/1_resumen.png'),
            'meses' => $this->imgMeses($an, $dir . '/2_ventas_por_mes.png'),
            'top'   => $this->imgTop($an, $dir . '/3_top_tiendas.png'),
            'canal' => $this->imgCanal($an, $dir . '/4_por_canal.png'),
        ];
    }

    public function fmtM($v)
    {
        if (abs($v) >= 1000000) {
            return 'S/ ' . number_format($v / 1000000, 2) . ' M';
        }
        return 'S/ ' . number_format($v / 1000, 0) . ' K';
    }

    private function pct($v, $signo = false)
    {
        if ($v === null) {
            return '-';
        }
        return ($signo && $v > 0 ? '+' : '') . number_format($v * 100, 1) . '%';
    }

    private function colCumpl($c)
    {
        if ($c === null) { return '667085'; }
        if ($c >= 1) { return '1E8449'; }
        if ($c >= 0.9) { return 'D68910'; }
        return 'C0392B';
    }

    private function colVar($v)
    {
        if ($v === null) { return '667085'; }
        return $v < 0 ? 'C0392B' : '1E8449';
    }

    private function lienzo($w, $h)
    {
        $im = imagecreatetruecolor($w, $h);
        imagealphablending($im, true);
        imagefilledrectangle($im, 0, 0, $w, $h, $this->hex($im, 'FFFFFF'));
        return $im;
    }

    private function hex($im, $hex)
    {
        $hex = ltrim($hex, '#');
        return imagecolorallocate($im, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
    }

    private function txt($im, $size, $x, $y, $hex, $text, $bold = false, $align = 'l')
    {
        $f = resource_path('fonts/' . ($bold ? 'DejaVuSans-Bold.ttf' : 'DejaVuSans.ttf'));
        $bb = imagettfbbox($size, 0, $f, $text);
        $w = $bb[2] - $bb[0];
        if ($align === 'r') { $x -= $w; } elseif ($align === 'c') { $x -= $w / 2; }
        imagettftext($im, $size, 0, (int)$x, (int)$y, $this->hex($im, $hex), $f, $text);
        return $w;
    }

    private function recortar($s, $max)
    {
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max - 1) . '…' : $s;
    }

    private function guardar($im, $path)
    {
        imagepng($im, $path, 6);
        imagedestroy($im);
        return $path;
    }

    private function titulo($im, $an, $titulo)
    {
        $this->txt($im, 17, 24, 38, self::NAVY, $titulo, true);
        $per = Carbon::parse($an['ini'])->format('d/m/Y') . ' al ' . Carbon::parse($an['fin'])->format('d/m/Y');
        $this->txt($im, 10, 24, 60, '667085', 'Smart Brands  |  ' . $per);
    }

    private function imgKpi(array $an, $path)
    {
        $im = $this->lienzo(1000, 330);
        $this->titulo($im, $an, 'Resumen de ventas ' . substr($an['fin'], 0, 4));
        list($c, $v, $g) = $this->derivados($an['total']);
        $t = $an['total'];
        $tiles = [
            ['Vta Neta 26', $this->fmtM($t['vta26']), self::NAVY],
            ['Vta Neta 25', $this->fmtM($t['vta25']), self::NAVY],
            ['Meta Vta', $this->fmtM($t['meta']), self::NAVY],
            ['% Cumpl Meta', $this->pct($c), $this->colCumpl($c)],
            ['% Var vs 25', $this->pct($v, true), $this->colVar($v)],
            ['Unid 26', number_format($t['unds26']), self::NAVY],
            ['% GM 26', $this->pct($g), self::NAVY],
            ['Tickets', number_format($t['tickets26']), self::NAVY],
        ];
        foreach ($tiles as $i => $tl) {
            $x = 24 + ($i % 4) * 242;
            $y = 84 + (int)floor($i / 4) * 108;
            imagefilledrectangle($im, $x, $y, $x + 226, $y + 94, $this->hex($im, 'F4F6FA'));
            imagefilledrectangle($im, $x, $y, $x + 4, $y + 94, $this->hex($im, $tl[2] === self::NAVY ? '2F6FED' : $tl[2]));
            $this->txt($im, 10, $x + 18, $y + 30, '667085', $tl[0]);
            $this->txt($im, 19, $x + 18, $y + 68, $tl[2], $tl[1], true);
        }
        $this->txt($im, 8, 24, 318, '98A2B3', 'Vta Neta 25 comparada por día equivalente. Tickets y %GM de tiendas propias.');
        return $this->guardar($im, $path);
    }

    private function imgMeses(array $an, $path)
    {
        $W = 1000; $H = 460;
        $im = $this->lienzo($W, $H);
        $this->titulo($im, $an, 'Venta neta por mes: 2026 vs 2025 vs Meta');
        $L = 70; $R = 24; $T = 100; $B = 50;
        $pw = $W - $L - $R; $ph = $H - $T - $B;
        $max = 0;
        foreach ($an['meses'] as $a) { $max = max($max, $a['vta26'], $a['vta25'], $a['meta']); }
        $paso = pow(10, floor(log10(max($max, 1)))) / 2;
        $tope = ceil($max * 1.05 / $paso) * $paso;
        for ($v = 0; $v <= $tope + 0.001; $v += $paso) {
            $y = $T + $ph - ($v / $tope) * $ph;
            imageline($im, $L, (int)$y, $W - $R, (int)$y, $this->hex($im, 'E4E7EC'));
            $this->txt($im, 8, $L - 8, $y + 4, '667085', number_format($v / 1000000, 1) . ' M', false, 'r');
        }
        $n = max(count($an['meses']), 1);
        $gw = $pw / $n;
        $bw = $gw * 0.24;
        $col = ['vta26' => '2F6FED', 'vta25' => 'B8C0CC', 'meta' => 'F5A623'];
        $i = 0;
        foreach ($an['meses'] as $m => $a) {
            $x0 = $L + $i * $gw + $gw * 0.12;
            $k = 0;
            foreach (['vta26', 'vta25', 'meta'] as $key) {
                $h = ($a[$key] / $tope) * $ph;
                $x = $x0 + $k * $bw;
                imagefilledrectangle($im, (int)$x, (int)($T + $ph - $h), (int)($x + $bw - 2), $T + $ph, $this->hex($im, $col[$key]));
                $k++;
            }
            $this->txt($im, 9, $L + $i * $gw + $gw / 2, $T + $ph + 22, '344054', self::MESES[$m - 1], false, 'c');
            $i++;
        }
        $lx = $W - $R;
        foreach (array_reverse(['vta26' => 'Vta 2026', 'vta25' => 'Vta 2025', 'meta' => 'Meta'], true) as $key => $lab) {
            $w = $this->txt($im, 9, $lx, 38, '344054', $lab, false, 'r');
            imagefilledrectangle($im, (int)($lx - $w - 18), 28, (int)($lx - $w - 6), 40, $this->hex($im, $col[$key]));
            $lx -= $w + 34;
        }
        return $this->guardar($im, $path);
    }

    private function imgTop(array $an, $path)
    {
        $top = array_slice($an['tiendas'], 0, 10, true);
        $n = count($top);
        $H = 120 + $n * 38;
        $im = $this->lienzo(1000, $H);
        $this->titulo($im, $an, 'Top 10 tiendas por venta neta');
        $max = 0;
        foreach ($top as $a) { $max = max($max, $a['vta26']); }
        $x0 = 250; $bwMax = 330;
        $y = 92;
        foreach ($top as $t => $a) {
            list($c, $v) = $this->derivados($a);
            $this->txt($im, 9, 24, $y + 16, '344054', $this->recortar($t, 28));
            $len = $max > 0 ? ($a['vta26'] / $max) * $bwMax : 0;
            imagefilledrectangle($im, $x0, $y, (int)($x0 + $len), $y + 22, $this->hex($im, $this->colCumpl($c)));
            $this->txt($im, 9, (int)($x0 + $len + 10), $y + 16, self::NAVY, $this->fmtM($a['vta26']), true);
            $this->txt($im, 9, 850, $y + 16, $this->colCumpl($c), 'Cumpl ' . $this->pct($c), true, 'r');
            $this->txt($im, 9, 976, $y + 16, $this->colVar($v), 'vs 25 ' . $this->pct($v, true), false, 'r');
            $y += 38;
        }
        $this->txt($im, 8, 24, $H - 14, '98A2B3', 'Color de la barra = cumplimiento de meta: verde >= 100%, ámbar 90-99.9%, rojo < 90%.');
        return $this->guardar($im, $path);
    }

    private function imgCanal(array $an, $path)
    {
        $n = count($an['canales']);
        $H = 150 + $n * 44;
        $im = $this->lienzo(1000, $H);
        $this->titulo($im, $an, 'Resumen por canal');
        $cols = [['Canal', 24, 'l'], ['Vta Neta 26', 400, 'r'], ['Meta Vta', 560, 'r'], ['% Cumpl', 710, 'r'], ['% Var vs 25', 860, 'r'], ['% GM', 976, 'r']];
        $y = 98;
        imagefilledrectangle($im, 24, $y - 22, 976, $y + 8, $this->hex($im, self::NAVY));
        foreach ($cols as $c) {
            $this->txt($im, 9, $c[1] + ($c[2] === 'l' ? 8 : -8), $y, 'FFFFFF', $c[0], true, $c[2]);
        }
        $y += 40;
        $filas = $an['canales'];
        $filas['TOTAL'] = $an['total'];
        foreach ($filas as $nom => $a) {
            list($c, $v, $g) = $this->derivados($a);
            $esTot = ($nom === 'TOTAL');
            if ($esTot) { imagefilledrectangle($im, 24, $y - 24, 976, $y + 12, $this->hex($im, 'E8ECF4')); }
            $this->txt($im, 10, 32, $y, self::NAVY, $nom, true);
            $this->txt($im, 10, 392, $y, '344054', $this->fmtM($a['vta26']), $esTot, 'r');
            $this->txt($im, 10, 552, $y, '344054', $this->fmtM($a['meta']), $esTot, 'r');
            $this->txt($im, 10, 702, $y, $this->colCumpl($c), $this->pct($c), true, 'r');
            $this->txt($im, 10, 852, $y, $this->colVar($v), $this->pct($v, true), true, 'r');
            $this->txt($im, 10, 968, $y, $g !== null && $g < 0 ? 'C0392B' : '344054', $this->pct($g), $esTot, 'r');
            $y += 44;
        }
        return $this->guardar($im, $path);
    }
}
