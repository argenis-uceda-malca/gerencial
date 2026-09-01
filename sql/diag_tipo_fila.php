<?php
$conn = pg_connect('host=172.16.1.23 port=5432 dbname=smartanalytic user=postgres password=theodenx');
if (!$conn) { echo "CONNECTION FAILED\n"; exit(1); }
echo "Connected successfully\n";

// Query 1: tipo_fila breakdown for fecha = 2026-08-15
echo "\n=== Query 1: tipo_fila breakdown (fecha = 2026-08-15) ===\n";
$sql1 = "SELECT origen, tipo_fila, COUNT(*) as filas, 
    SUM(COALESCE(meta,0)) as sum_meta, 
    SUM(COALESCE(meta_contribucion,0)) as sum_meta_contribucion, 
    SUM(COALESCE(inv_unds_act,0)) as sum_inv_unds, 
    SUM(COALESCE(inv_costo_act,0)) as sum_inv_costo, 
    SUM(COALESCE(vta_act,0)) as sum_vta_act, 
    SUM(COALESCE(vta_hst,0)) as sum_vta_hst, 
    SUM(COALESCE(v_importe_subtotal_hst,0)) as sum_imp_hst, 
    SUM(COALESCE(v_importe_subtotal_hst,0)) as sum_imp_hst2,
    SUM(COALESCE(costo_act,0)) as sum_costo_act, 
    SUM(COALESCE(costo_hst,0)) as sum_costo_hst,
    SUM(COALESCE(v_nro_tickets_hst_1,0)) as sum_nro_tickets_hst_1,
    SUM(COALESCE(v_unidades_hst,0)) as sum_unidades_hst,
    SUM(COALESCE(v_costo_venta_neta_hst,0)) as sum_costo_venta_neta_hst
FROM automatizacion_pla_reporte_consolidado 
WHERE fecha = '2026-08-15' 
GROUP BY origen, tipo_fila 
ORDER BY origen, tipo_fila";

$result1 = pg_query($conn, $sql1);
if (!$result1) { echo "QUERY 1 FAILED: " . pg_last_error($conn) . "\n"; } else {
    while ($row = pg_fetch_assoc($result1)) {
        echo implode(' | ', $row) . "\n";
    }
}

// Query 2: Check ventas_hst tipo_fila for 2025 dates
echo "\n=== Query 2: ventas_hst check (fecha in 2025 range) ===\n";
$sql2 = "SELECT origen, tipo_fila, COUNT(*) as filas, 
    SUM(COALESCE(vta_hst,0)) as sum_vta_hst,
    SUM(COALESCE(v_importe_subtotal_hst,0)) as sum_imp_hst,
    SUM(COALESCE(v_costo_venta_neta_hst,0)) as sum_costo_venta_neta_hst,
    SUM(COALESCE(v_unidades_hst,0)) as sum_unidades_hst,
    SUM(COALESCE(v_nro_tickets_hst_1,0)) as sum_nro_tickets_hst_1
FROM automatizacion_pla_reporte_consolidado 
WHERE fecha BETWEEN '2025-08-01' AND '2025-08-31'
GROUP BY origen, tipo_fila 
ORDER BY origen, tipo_fila";

$result2 = pg_query($conn, $sql2);
if (!$result2) { echo "QUERY 2 FAILED: " . pg_last_error($conn) . "\n"; } else {
    while ($row = pg_fetch_assoc($result2)) {
        echo implode(' | ', $row) . "\n";
    }
}

// Query 3: TXD historical check
echo "\n=== Query 3: TXD historical (fecha in 2025 range) ===\n";
$sql3 = "SELECT origen, tipo_fila, COUNT(*) as filas, 
    SUM(COALESCE(vta_hst,0)) as sum_vta_hst,
    SUM(COALESCE(vta_act,0)) as sum_vta_act,
    SUM(COALESCE(meta,0)) as sum_meta,
    SUM(COALESCE(inv_unds_act,0)) as sum_inv_unds,
    SUM(COALESCE(inv_costo_act,0)) as sum_inv_costo
FROM automatizacion_pla_reporte_consolidado 
WHERE origen = 'TXD' AND fecha BETWEEN '2025-08-01' AND '2025-08-31'
GROUP BY origen, tipo_fila 
ORDER BY origen, tipo_fila";

$result3 = pg_query($conn, $sql3);
if (!$result3) { echo "QUERY 3 FAILED: " . pg_last_error($conn) . "\n"; } else {
    while ($row = pg_fetch_assoc($result3)) {
        echo implode(' | ', $row) . "\n";
    }
}

// Query 4: All distinct tipo_fila values
echo "\n=== Query 4: All distinct tipo_fila values ===\n";
$sql4 = "SELECT DISTINCT origen, tipo_fila FROM automatizacion_pla_reporte_consolidado ORDER BY origen, tipo_fila";
$result4 = pg_query($conn, $sql4);
if (!$result4) { echo "QUERY 4 FAILED: " . pg_last_error($conn) . "\n"; } else {
    while ($row = pg_fetch_assoc($result4)) {
        echo implode(' | ', $row) . "\n";
    }
}

pg_close($conn);
echo "\nDone.\n";
?>