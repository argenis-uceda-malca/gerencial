<?php
\ = 'theodenx';
\ = pg_connect('host=172.16.1.23 port=5432 dbname=smartanalytic user=postgres password=' . \);
if (!\) { echo 'ERROR: ' . pg_last_error(); exit(1); }

\ = pg_query(\, "
SELECT pg_get_functiondef(p.oid)
FROM pg_proc p
JOIN pg_namespace n ON n.oid = p.pronamespace
WHERE n.nspname = 'public'
  AND p.proname = 'automatizacion_sp_reporte_ventas'
");

if (\ && pg_num_rows(\) > 0) {
    \ = pg_fetch_row(\);
    echo \[0];
} else {
    echo 'Not found with pg_get_functiondef. Trying direct prosrc...\n';
    \ = pg_query(\, "
    SELECT pg_get_functiondef(p.oid) as def
    FROM pg_proc p
    JOIN pg_namespace n ON n.oid = p.pronamespace
    WHERE p.proname LIKE '%reporte_ventas%'
      AND n.nspname = 'public'
    ");
    while (\ = pg_fetch_assoc(\)) {
        echo \['def'] . "\n---\n";
    }
}
pg_close(\);
