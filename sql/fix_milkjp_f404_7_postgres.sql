-- Fix F404-00000007 MILKJP — PASO Postgres
-- Ejecutar después del fix en SQL Server y limpieza SPE

UPDATE fe_control_registros
SET estado                = 'ERROR_CAPTURA',
    estado_bizlinks       = NULL,
    mensaje_bizlinks      = NULL,
    codigo_error_bizlinks = NULL,
    fecha_sync_bizlinks   = NULL
WHERE codigo_tienda         = 'MILKJP'
  AND serie_numero_bizlinks = 'F404-00000007';

-- Verificar
SELECT codigo_tienda, serie_numero_bizlinks, estado, estado_bizlinks
FROM fe_control_registros
WHERE codigo_tienda = 'MILKJP' AND serie_numero_bizlinks = 'F404-00000007';
