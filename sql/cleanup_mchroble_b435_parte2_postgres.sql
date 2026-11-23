-- ============================================================
-- MCHROBLE B435-1 a B435-20: eliminar del motor + cursor
-- Ejecutar INMEDIATAMENTE después de la Parte 1
-- ============================================================

BEGIN;

DELETE FROM fe_auditoria_estados
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'MCHROBLE'
    AND serie_numero_bizlinks LIKE 'B435-%'
    AND fecha_venta < '2026-11-13'
);

DELETE FROM fe_historial_errores
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'MCHROBLE'
    AND serie_numero_bizlinks LIKE 'B435-%'
    AND fecha_venta < '2026-11-13'
);

DELETE FROM fe_notificaciones
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'MCHROBLE'
    AND serie_numero_bizlinks LIKE 'B435-%'
    AND fecha_venta < '2026-11-13'
);

DELETE FROM fe_historial_reintentos
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'MCHROBLE'
    AND serie_numero_bizlinks LIKE 'B435-%'
    AND fecha_venta < '2026-11-13'
);

DELETE FROM fe_control_registros
WHERE codigo_tienda = 'MCHROBLE'
  AND serie_numero_bizlinks LIKE 'B435-%'
  AND fecha_venta < '2026-11-13';

-- Resetear cursor: B435-1 tiene IDTRANSACCION=1 → cursor=0
-- Los B435-21+ ya están P, firstOrCreate los saltea
UPDATE fe_tiendas
SET ultimo_idtransaccion_capturado = 0
WHERE codigo_tienda = 'MCHROBLE';

-- Verificar
SELECT COUNT(*) AS quedaron_viejos FROM fe_control_registros
WHERE codigo_tienda = 'MCHROBLE'
  AND serie_numero_bizlinks LIKE 'B435-%'
  AND fecha_venta < '2026-11-13';

SELECT ultimo_idtransaccion_capturado FROM fe_tiendas WHERE codigo_tienda = 'MCHROBLE';

COMMIT;
