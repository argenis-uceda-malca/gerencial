<?php
$conn = pg_connect("host=172.16.1.23 port=5432 dbname=smartanalytic user=postgres password=theodenx");
if (!$conn) { echo "ERROR: " . pg_last_error(); exit(1); }
$rs = pg_query($conn, "SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'automatizacion_pla_reporte_ventas' ORDER BY ordinal_position");
$cols = array();
while ($r = pg_fetch_assoc($rs)) { $cols[] = $r['column_name']; echo $r['column_name'] . "\n"; }
echo "\n---Total: " . count($cols) . " columns---\n";
pg_close($conn);
