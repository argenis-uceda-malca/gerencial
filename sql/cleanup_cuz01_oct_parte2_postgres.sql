-- ============================================================
-- CUZ01 B410-1 a B410-93 (22-30 oct): eliminar del motor + cursor
-- Ejecutar DESPUÉS de la Parte 1 (SQL Server)
--
-- IMPORTANTE: ejecutar rápido sin pausa entre pasos para evitar
-- que el motor sobreescriba el cursor antes del siguiente ciclo.
-- ============================================================

BEGIN;

-- Eliminar TODOS los B410 del motor con fechas viejas (< Nov 6)
DELETE FROM fe_auditoria_estados
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'CUZ01'
    AND serie_numero_bizlinks LIKE 'B410-%'
    AND fecha_venta < '2026-11-06'
);

DELETE FROM fe_historial_errores
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'CUZ01'
    AND serie_numero_bizlinks LIKE 'B410-%'
    AND fecha_venta < '2026-11-06'
);

DELETE FROM fe_notificaciones
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'CUZ01'
    AND serie_numero_bizlinks LIKE 'B410-%'
    AND fecha_venta < '2026-11-06'
);

DELETE FROM fe_historial_reintentos
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'CUZ01'
    AND serie_numero_bizlinks LIKE 'B410-%'
    AND fecha_venta < '2026-11-06'
);

DELETE FROM fe_control_registros
WHERE codigo_tienda = 'CUZ01'
  AND serie_numero_bizlinks LIKE 'B410-%'
  AND fecha_venta < '2026-11-06';

-- Verificar: debe devolver 0
SELECT COUNT(*) AS quedaron_b410_viejos FROM fe_control_registros
WHERE codigo_tienda = 'CUZ01'
  AND serie_numero_bizlinks LIKE 'B410-%'
  AND fecha_venta < '2026-11-06';

SELECT COUNT(*) AS total_b410 FROM fe_control_registros
WHERE codigo_tienda = 'CUZ01'
  AND serie_numero_bizlinks LIKE 'B410-%';

COMMIT;
