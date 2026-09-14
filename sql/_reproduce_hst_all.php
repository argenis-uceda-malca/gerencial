<?php
$c = pg_connect('host=172.16.1.23 port=5432 dbname=smartanalytic user=postgres password=theodenx');
$ini='2026-08-24'; $fin='2026-08-30';
// HST como lo hace pivot pero SIN la restriccion whereIn corner (ventas_act)
$sql = "SELECT corner, SUM(vta_hst) vta25
        FROM automatizacion_pla_reporte_consolidado
        WHERE origen='VENTAS' AND tipo_fila='ventas_hst'
          AND fecha IN (SELECT fecha FROM pla_fechas_equivalentes WHERE fecha_equivalente BETWEEN '$ini' AND '$fin')
        GROUP BY corner ORDER BY vta25 DESC";
$r=pg_query($c,$sql);
echo "== HST semana 35 2025 (SIN restriccion ventas_act) ==\n";
while($x=pg_fetch_assoc($r)) printf("%-30s %s\n",$x['corner'], round((float)$x['vta25'],2));
pg_close($c);
