<?php
$conn = pg_connect('host=172.16.1.23 port=5432 dbname=smartanalytic user=postgres password=theodenx');
if (!$conn) { die("CONNECTION FAILED\n"); }

// Read and execute the view creation SQL
$sql = file_get_contents('C:\laragon\www\gerencial\sql\fase_txd_create_view.sql');

// Remove comment lines
$lines = explode("\n", $sql);
$clean = array();
foreach ($lines as $l) {
    $t = trim($l);
    if (strpos($t, '--') === 0) continue;
    $clean[] = $l;
}
$clean_sql = implode("\n", $clean);

// Execute
$result = pg_query($conn, $clean_sql);
if (!$result) {
    echo "ERROR: " . pg_last_error($conn) . "\n";
} else {
    echo "View created successfully\n";
}

// Verify row counts
$check = pg_query($conn, "SELECT COUNT(*) as total FROM automatizacion_vista_reporte_txd");
$row = pg_fetch_assoc($check);
echo "Total rows in view: " . $row['total'] . "\n";

// Verify by origin and year
$check2 = pg_query($conn, "SELECT origen_reporte, EXTRACT(YEAR FROM fecha_documento) as anio, COUNT(*) as cnt FROM automatizacion_vista_reporte_txd GROUP BY origen_reporte, EXTRACT(YEAR FROM fecha_documento) ORDER BY origen_reporte, anio");
echo "\nBreakdown:\n";
while ($r = pg_fetch_assoc($check2)) {
    echo "  " . $r['origen_reporte'] . " " . $r['anio'] . ": " . $r['cnt'] . " rows\n";
}

// Performance test
$start = microtime(true);
$perf = pg_query($conn, "SELECT COUNT(*) FROM automatizacion_vista_reporte_txd WHERE fecha_documento BETWEEN '2026-08-01' AND '2026-08-31'");
$elapsed = round((microtime(true) - $start) * 1000, 1);
echo "\nFiltered query time: " . $elapsed . "ms\n";

pg_close($conn);
echo "Done.\n";
?>