-- ============================================================
-- MCHRPSALA F419-11 y JKY01 F402-11: resetear motor
-- Ejecutar DESPUÉS de la Parte 1
-- ============================================================

BEGIN;

UPDATE fe_control_registros
SET estado = 'ERROR_CAPTURA',
    estado_bizlinks = NULL
WHERE (codigo_tienda = 'MCHRPSALA' AND serie_numero_bizlinks = 'F419-00000011')
   OR (codigo_tienda = 'JKY01'     AND serie_numero_bizlinks = 'F402-00000011');

-- Verificar
SELECT codigo_tienda, serie_numero_bizlinks, estado, estado_bizlinks
FROM fe_control_registros
WHERE (codigo_tienda = 'MCHRPSALA' AND serie_numero_bizlinks = 'F419-00000011')
   OR (codigo_tienda = 'JKY01'     AND serie_numero_bizlinks = 'F402-00000011');

COMMIT;
