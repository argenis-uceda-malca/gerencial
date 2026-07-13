<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TempDebugCommand extends Command
{
    protected $signature = 'temp:debug';
    protected $description = 'Debug temporal';

    public function handle()
    {
        $db = DB::connection('pgsql');

        $this->line("=== datamart_ventas_actual ===");
        $r1 = $db->select("SELECT canal, sucursal, ROUND(SUM(importe_subtotal)::numeric, 2) AS total FROM datamart_ventas_actual WHERE fecha_documento = '2026-07-10' AND sucursal LIKE '%EXIT LR SAN BORJA%' GROUP BY canal, sucursal ORDER BY canal, sucursal");
        foreach ($r1 as $r) $this->line("canal: $r->canal, sucursal: $r->sucursal, total: $r->total");

        $this->line("");
        $this->line("=== automatizacion_pla_reporte_ventas (ventas_act) ===");
        $r2 = $db->select("SELECT sucursal_3_1 AS canal, sucursal, ROUND(SUM(importe_subtotal)::numeric, 2) AS total FROM automatizacion_pla_reporte_ventas WHERE fecha_documento = '2026-07-10' AND sucursal LIKE '%EXIT LR SAN BORJA%' AND tipo_fila = 'ventas_act' GROUP BY sucursal_3_1, sucursal ORDER BY sucursal_3_1, sucursal");
        foreach ($r2 as $r) $this->line("canal: $r->canal, sucursal: $r->sucursal, total: $r->total");

        $this->line("");
        $this->line("=== Registros en datamart pero NO en reporte ===");
        $r3 = $db->select("SELECT dv.tipo_doc, dv.serie, dv.numero, dv.importe_subtotal, dv.canaldv.canal, dv.sucursal FROM datamart_ventas_actual dv LEFT JOIN automatizacion_pla_reporte_ventas ar ON ar.fecha_documento = dv.fecha_documento AND ar.sucursal = dv.sucursal AND ar.importe_subtotal = dv.importe_subtotal AND ar.tipo_fila = 'ventas_act' WHERE dv.fecha_documento = '2026-07-10' AND dv.sucursal LIKE '%EXIT LR SAN BORJA%' AND ar.id IS NULL ORDER BY dv.importe_subtotal DESC");
        foreach ($r3 as $r) $this->line("tipo_doc: $r->tipo_doc, serie: $r->serie, num: $r->numero, monto: $r->importe_subtotal");

        $this->line("");
        $this->line("=== Total por tipo_doc en datamart ===");
        $r4 = $db->select("SELECT tipo_doc, SUM(importe_subtotal) FROM datamart_ventas_actual WHERE fecha_documento = '2026-07-10' AND sucursal LIKE '%EXIT LR SAN BORJA%' GROUP BY tipo_doc");
        foreach ($r4 as $r) $this->line("tipo_doc: $r->tipo_doc, total: $r->sum");

        $this->line("");
        $this->line("=== Total por tipo_fila en reporte ===");
        $r5 = $db->select("SELECT tipo_fila, SUM(importe_subtotal) FROM automatizacion_pla_reporte_ventas WHERE fecha_documento = '2026-07-10' AND sucursal LIKE '%EXIT LR SAN BORJA%' GROUP BY tipo_fila");
        foreach ($r5 as $r) $this->line("tipo_fila: $r->tipo_fila, total: $r->sum");
    }
}
