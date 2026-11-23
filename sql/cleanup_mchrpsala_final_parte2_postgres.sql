-- ============================================================
-- MCHRPSALA B419 FINAL: eliminar del motor + cursor
-- Ejecutar INMEDIATAMENTE después de la Parte 1
--
-- IMPORTANTE: ejecutar este script rápido, sin pausa entre pasos,
-- para evitar que el motor sobreescriba el cursor antes de que
-- empiece el siguiente ciclo desde 195.
-- ============================================================

BEGIN;

-- Eliminar TODOS los B419 del motor con fechas viejas (< Nov 6)
DELETE FROM fe_auditoria_estados
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'MCHRPSALA'
    AND serie_numero_bizlinks LIKE 'B419-%'
    AND fecha_venta < '2026-11-06'
);

DELETE FROM fe_historial_errores
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'MCHRPSALA'
    AND serie_numero_bizlinks LIKE 'B419-%'
    AND fecha_venta < '2026-11-06'
);

DELETE FROM fe_notificaciones
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'MCHRPSALA'
    AND serie_numero_bizlinks LIKE 'B419-%'
    AND fecha_venta < '2026-11-06'
);

DELETE FROM fe_historial_reintentos
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'MCHRPSALA'
    AND serie_numero_bizlinks LIKE 'B419-%'
    AND fecha_venta < '2026-11-06'
);

DELETE FROM fe_control_registros
WHERE codigo_tienda = 'MCHRPSALA'
  AND serie_numero_bizlinks LIKE 'B419-%'
  AND fecha_venta < '2026-11-06';

-- Resetear cursor a 195 (B419-1 tiene IDTRANSACCION=196)
UPDATE fe_tiendas
SET ultimo_idtransaccion_capturado = 195
WHERE codigo_tienda = 'MCHRPSALA';

-- Verificar
SELECT COUNT(*) AS quedaron_b419_viejos FROM fe_control_registros
WHERE codigo_tienda = 'MCHRPSALA'
  AND serie_numero_bizlinks LIKE 'B419-%'
  AND fecha_venta < '2026-11-06';

SELECT COUNT(*) AS total_b419 FROM fe_control_registros
WHERE codigo_tienda = 'MCHRPSALA'
  AND serie_numero_bizlinks LIKE 'B419-%';

SELECT ultimo_idtransaccion_capturado FROM fe_tiendas WHERE codigo_tienda = 'MCHRPSALA';

COMMIT;
