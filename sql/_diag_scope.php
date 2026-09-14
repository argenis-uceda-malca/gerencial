<?php
$c = pg_connect('host=172.16.1.23 port=5432 dbname=smartanalytic user=postgres password=theodenx');
$ini='2026-08-24'; $fin='2026-08-30';
$hst = pg_query($c, "SELECT corner, SUM(vta_hst) vta25 FROM automatizacion_pla_reporte_consolidado
        WHERE origen='VENTAS' AND tipo_fila='ventas_hst'
          AND fecha IN (SELECT fecha FROM pla_fechas_equivalentes WHERE fecha_equivalente BETWEEN '$ini' AND '$fin')
        GROUP BY corner") or die(pg_last_error($c));

$hstMap=[]; while($x=pg_fetch_assoc($hst)) $hstMap[$x['corner']]=round((float)$x['vta25'],2);

// Por cada corner del HST, ¿tiene ventas_act en 2026 (cualquier mes)? ¿está en dm_sucursales_activas(activa)?
echo "corner | vta25_2025 | en_activas(activa) | ventas_act_2026_any | ventas_act_2026_agosto\n";
foreach($hstMap as $corner=>$v25){
  $enAct  = pg_fetch_result(pg_query($c,"SELECT count(*) FROM automatizacion_dm_sucursales_activas WHERE sucursal='".pg_escape_string($c,$corner)."' AND activa=TRUE"),0,0);
  $v26Any = pg_fetch_result(pg_query($c,"SELECT COALESCE(SUM(vta_act),0) FROM automatizacion_pla_reporte_consolidado WHERE corner='".pg_escape_string($c,$corner)."' AND origen='VENTAS' AND tipo_fila='ventas_act' AND fecha BETWEEN '2026-01-01' AND '2026-12-31'"),0,0);
  $v26Aug = pg_fetch_result(pg_query($c,"SELECT COALESCE(SUM(vta_act),0) FROM automatizacion_pla_reporte_consolidado WHERE corner='".pg_escape_string($c,$corner)."' AND origen='VENTAS' AND tipo_fila='ventas_act' AND fecha BETWEEN '$ini' AND '$fin'"),0,0);
  printf("%-30s %10.2f | %4s | %12.2f | %12.2f\n",$corner,$v25,$enAct=='1'?'SI':'no',(float)$v26Any,(float)$v26Aug);
}
pg_close($c);
