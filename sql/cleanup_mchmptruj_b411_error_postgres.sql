-- ============================================================
-- MCHMPTRUJ B411-452, 453, 477, 496: resetear motor
-- Ejecutar DESPUÉS del script SQL Server
-- ============================================================

BEGIN;

UPDATE fe_control_registros
SET estado = 'ERROR_CAPTURA',
    estado_bizlinks = NULL
WHERE codigo_tienda = 'MCHMPTRUJ'
  AND serie_numero_bizlinks IN (
    'B411-00000452', 'B411-00000453',
    'B411-00000477', 'B411-00000496'
  );

-- Verificar
SELECT serie_numero_bizlinks, estado, estado_bizlinks
FROM fe_control_registros
WHERE codigo_tienda = 'MCHMPTRUJ'
  AND serie_numero_bizlinks IN (
    'B411-00000452', 'B411-00000453',
    'B411-00000477', 'B411-00000496'
  );

COMMIT;
