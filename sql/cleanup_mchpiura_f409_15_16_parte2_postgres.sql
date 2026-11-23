-- ============================================================
-- MCHPIURA F409-15 y F409-16: resetear motor para reintento
-- Ejecutar DESPUÉS de la Parte 1
-- ============================================================

BEGIN;

UPDATE fe_control_registros
SET estado = 'ERROR_CAPTURA',
    estado_bizlinks = NULL
WHERE codigo_tienda = 'MCHPIURA'
  AND serie_numero_bizlinks IN ('F409-00000015', 'F409-00000016');

-- Verificar
SELECT serie_numero_bizlinks, estado, estado_bizlinks, fecha_venta
FROM fe_control_registros
WHERE codigo_tienda = 'MCHPIURA'
  AND serie_numero_bizlinks IN ('F409-00000015', 'F409-00000016');

COMMIT;
