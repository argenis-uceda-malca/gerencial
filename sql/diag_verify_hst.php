<?php
$conn = pg_connect('host=172.16.1.23 port=5432 dbname=smartanalytic user=postgres password=theodenx');

$sql = "SELECT COUNT(*) as total FROM automatizacion_pla_reporte_consolidado WHERE v_importe_subtotal_hst IS NOT NULL AND v_importe_subtotal_hst != 0";
$result = pg_query($conn, $sql);
$row = pg_fetch_assoc($result);
echo "Rows with v_importe_subtotal_hst != 0: " . $row['total'] . "\n";

$sql2 = "SELECT COUNT(*) as total FROM automatizacion_pla_reporte_consolidado WHERE vta_hst IS NOT NULL AND vta_hst != 0";
$result2 = pg_query($conn, $sql2);
$row2 = pg_fetch_assoc($result2);
echo "Rows with vta_hst != 0: " . $row2['total'] . "\n";

// Also check which tipo_fila populates vta_hst
$sql3 = "SELECT origen, tipo_fila, COUNT(*) as filas, SUM(COALESCE(vta_hst,0)) as sum_vta_hst FROM automatizacion_pla_reporte_consolidado WHERE vta_hst IS NOT NULL AND vta_hst != 0 GROUP BY origen, tipo_fila ORDER BY origen, tipo_fila";
$result3 = pg_query($conn, $sql3);
echo "\n=== tipo_fila that populate vta_hst ===\n";
while ($row3 = pg_fetch_assoc($result3)) {
    echo implode(' | ', $row3) . "\n";
}

pg_close($conn);
?>