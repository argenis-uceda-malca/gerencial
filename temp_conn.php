<?php
$conn = pg_connect("host=172.16.1.23 port=5432 dbname=smartanalytic user=postgres password=theodenx");
if (!$conn) { echo "ERR: " . pg_last_error($conn); exit(1); }
echo "CONNECTED_OK";
pg_close($conn);
