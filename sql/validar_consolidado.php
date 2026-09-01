<?php
$conn = pg_connect('host=172.16.1.23 port=5432 dbname=smartanalytic user=postgres password=theodenx');
if (!$conn) { echo "CONNECTION FAILED\n"; exit(1); }
echo "Connected successfully\n\n";

// Read the SQL file and execute it
$sql = file_get_contents('C:\laragon\www\gerencial\sql\fase_txd_consolidado_reporte.sql');

// Remove comment lines
$lines = explode("\n", $sql);
$clean_lines = array();
foreach ($lines as $line) {
    $trimmed = trim($line);
    if (strpos($trimmed, '--') === 0) continue;
    $clean_lines[] = $line;
}
$clean_sql = implode("\n", $clean_lines);

// Remove the trailing semicolon if any (UNION ALL already has semicolon)
$clean_sql = trim($clean_sql);
if (substr($clean_sql, -1) === ';') {
    $clean_sql = substr($clean_sql, 0, -1);
}

// Split by semicolon and execute the UNION ALL query (last statement)
$statements = explode(';', $clean_sql);
foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;
    if (strpos($stmt, 'SELECT') !== 0 && strpos($stmt, 'select') !== 0) continue;

    $start = microtime(true);
    $result = pg_query($conn, $stmt);
    $elapsed = microtime(true) - $start;

    if (!$result) {
        echo "ERROR: " . pg_last_error($conn) . "\n";
        continue;
    }

    $num_fields = pg_num_fields($result);
    $num_rows = pg_num_rows($result);
    echo "Query OK. Fields: $num_fields, Rows: $num_rows, Time: " . round($elapsed*1000, 1) . " ms\n\n";

    // Print headers
    $headers = array();
    for ($i = 0; $i < $num_fields; $i++) {
        $headers[] = pg_field_name($result, $i);
    }
    echo "Columns (" . count($headers) . "): " . implode(', ', $headers) . "\n\n";

    // Print first 3 rows
    for ($r = 0; $r < min(3, $num_rows); $r++) {
        $row = pg_fetch_assoc($result);
        $vals = array();
        // Show key columns only
        $key_cols = array('origen_reporte', 'tipo_fila', 'importe_subtotal_ventas', 'importe_subtotal_txd', 'meta_venta_ventas', 'meta_venta_txd', 'inv_unds_act_ventas', 'inv_unds_act_txd');
        foreach ($key_cols as $k) {
            if (isset($row[$k])) {
                $vals[] = "$k=" . (is_null($row[$k]) ? 'NULL' : $row[$k]);
            }
        }
        echo "Row " . ($r+1) . ": " . implode(' | ', $vals) . "\n";
    }

    // Count non-NULL values per key column
    pg_result_seek($result, 0);
    $non_null = array();
    for ($i = 0; $i < $num_fields; $i++) {
        $fname = pg_field_name($result, $i);
        $non_null[$fname] = 0;
    }
    while ($row = pg_fetch_assoc($result)) {
        foreach ($row as $k => $v) {
            if (!is_null($v)) $non_null[$k]++;
        }
    }
    echo "\nNon-NULL counts per column:\n";
    foreach ($non_null as $k => $v) {
        if ($v > 0) echo "  $k: $v\n";
    }
}

pg_close($conn);
echo "\nDone.\n";
?>