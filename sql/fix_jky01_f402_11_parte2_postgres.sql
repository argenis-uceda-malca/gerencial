-- Después de aplicar el fix en SQL Server y limpiar Bizlinks SPE,
-- resetear en Postgres para que el motor reintente F402-00000011

UPDATE fe_control_registros
SET estado          = 'ERROR_CAPTURA',
    estado_bizlinks = NULL,
    mensaje_bizlinks      = NULL,
    codigo_error_bizlinks = NULL,
    fecha_sync_bizlinks   = NULL
WHERE codigo_tienda          = 'JKY01'
  AND serie_numero_bizlinks  = 'F402-00000011';

-- Verificar
SELECT codigo_tienda, serie_numero_bizlinks, estado, estado_bizlinks
FROM fe_control_registros
WHERE codigo_tienda = 'JKY01' AND serie_numero_bizlinks = 'F402-00000011';
