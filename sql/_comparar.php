<?php
$conn = pg_connect('host=172.16.1.23 port=5432 dbname=smartanalytic user=postgres password=theodenx');

// Column info for pla_reporte_ventas
$r = pg_query($conn, "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'pla_reporte_ventas' ORDER BY ordinal_position");
echo "=== pla_reporte_ventas columns ===\n";
while ($row = pg_fetch_assoc($r)) {
    echo $row['column_name'] . " (" . $row['data_type'] . ")\n";
}

// Sample row from pla_reporte_ventas
echo "\n=== Sample row ===\n";
$r2 = pg_query($conn, "SELECT * FROM pla_reporte_ventas LIMIT 1");
$row2 = pg_fetch_assoc($r2);
foreach ($row2 as $k => $v) {
    echo $k . ': ' . substr((string)$v, 0, 80) . "\n";
}

// Count by year
echo "\n=== Counts ===\n";
$r3 = pg_query($conn, "SELECT EXTRACT(YEAR FROM fecha_documento) as anio, COUNT(*) as cnt FROM pla_reporte_ventas GROUP BY EXTRACT(YEAR FROM fecha_documento) ORDER BY anio");
while ($row3 = pg_fetch_assoc($r3)) {
    echo $row3['anio'] . ': ' . $row3['cnt'] . "\n";
}

// Check if pla_reporte_ventas has tipo_fila or similar
$r4 = pg_query($conn, "SELECT COUNT(DISTINCT tipo_fila) as cnt FROM pla_reporte_ventas");
$row4 = pg_fetch_assoc($r4);
echo "\nDistinct tipo_fila in pla_reporte_ventas: " . $row4['cnt'] . "\n";

// Check if it has tipo_fila column at all
$r5 = pg_query($conn, "SELECT column_name FROM information_schema.columns WHERE table_name = 'pla_reporte_ventas' AND column_name LIKE '%tipo%'");
echo "Tipo columns in pla_reporte_ventas:\n";
while ($row5 = pg_fetch_assoc($r5)) {
    echo "  " . $row5['column_name'] . "\n";
}

pg_close($conn);
echo "Done.\n";
?>