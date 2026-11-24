-- Fix B426-00000023 MILKBRASI -- PASO 2 (Postgres)
-- Ejecutar después del parte1 en SQL Server

UPDATE fe_control_registros
SET estado                = 'ERROR_CAPTURA',
    estado_bizlinks       = NULL,
    mensaje_bizlinks      = NULL,
    codigo_error_bizlinks = NULL,
    fecha_sync_bizlinks   = NULL
WHERE codigo_tienda         = 'MILKBRASI'
  AND serie_numero_bizlinks = 'B426-00000023';

-- Verificar
SELECT codigo_tienda, serie_numero_bizlinks, estado, estado_bizlinks
FROM fe_control_registros
WHERE codigo_tienda = 'MILKBRASI' AND serie_numero_bizlinks = 'B426-00000023';
