<?php
$conn = pg_connect("host=172.16.1.23 port=5432 dbname=smartanalytic user=postgres password=theodenx");
if (!$conn) { echo "ERR: " . pg_last_error($conn); exit(1); }

$rs = pg_query($conn, "SELECT pg_get_functiondef(p.oid) FROM pg_proc p JOIN pg_namespace n ON n.oid = p.pronamespace WHERE n.nspname = 'public' AND p.proname = 'automatizacion_sp_reporte_ventas'");

if ($rs && pg_num_rows($rs) > 0) {
    $row = pg_fetch_row($rs);
    echo $row[0];
} else {
    echo "NOT FOUND\n";
    $rs2 = pg_query($conn, "SELECT proname FROM pg_proc p JOIN pg_namespace n ON n.oid = p.pronamespace WHERE n.nspname = 'public' AND p.proname LIKE '%reporte%ventas%'");
    while ($r = pg_fetch_assoc($rs2)) { echo $r['proname'] . "\n"; }
}
pg_close($conn);
