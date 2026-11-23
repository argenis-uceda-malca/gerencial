-- ============================================================
-- Resetear los 3 documentos con error en el motor para reintento
-- Ejecutar DESPUÉS de los scripts SQL Server
-- ============================================================

BEGIN;

UPDATE fe_control_registros
SET estado = 'ERROR_CAPTURA',
    estado_bizlinks = NULL
WHERE (codigo_tienda = 'CUZ01'    AND serie_numero_bizlinks = 'F410-00000001')
   OR (codigo_tienda = 'MCHLR'    AND serie_numero_bizlinks = 'B421-00000238')
   OR (codigo_tienda = 'MCHPIURA' AND serie_numero_bizlinks = 'B409-00000168');

-- Verificar
SELECT codigo_tienda, serie_numero_bizlinks, estado, estado_bizlinks
FROM fe_control_registros
WHERE (codigo_tienda = 'CUZ01'    AND serie_numero_bizlinks = 'F410-00000001')
   OR (codigo_tienda = 'MCHLR'    AND serie_numero_bizlinks = 'B421-00000238')
   OR (codigo_tienda = 'MCHPIURA' AND serie_numero_bizlinks = 'B409-00000168');

COMMIT;
