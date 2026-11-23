-- ============================================================
-- CUZ01: resetear B410-205, 206, 207 en el motor para reintento
-- Ejecutar DESPUÉS de la Parte 2 (Bizlinks)
-- ============================================================

BEGIN;

-- Resetear estado_bizlinks para que el motor los reenvíe a Bizlinks
UPDATE fe_control_registros
SET estado_bizlinks = NULL,
    estado = 'PENDIENTE'
WHERE codigo_tienda = 'CUZ01'
  AND serie_numero_bizlinks IN ('B410-00000205','B410-00000206','B410-00000207');

-- Verificar
SELECT serie_numero_bizlinks, estado, estado_bizlinks, fecha_venta::date
FROM fe_control_registros
WHERE codigo_tienda = 'CUZ01'
  AND serie_numero_bizlinks IN ('B410-00000205','B410-00000206','B410-00000207');

COMMIT;
