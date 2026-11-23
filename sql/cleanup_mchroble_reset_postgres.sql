-- ============================================================
-- MCHROBLE: eliminar duplicados del motor + resetear cursor
-- Los 65 registros eran de MILKBRASI (IP 10.20.0.164), no de MCHROBLE
-- Ahora MCHROBLE tiene IP correcta 10.20.0.125
-- ============================================================

BEGIN;

DELETE FROM fe_auditoria_estados
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros WHERE codigo_tienda = 'MCHROBLE'
);

DELETE FROM fe_historial_errores
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros WHERE codigo_tienda = 'MCHROBLE'
);

DELETE FROM fe_notificaciones
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros WHERE codigo_tienda = 'MCHROBLE'
);

DELETE FROM fe_historial_reintentos
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros WHERE codigo_tienda = 'MCHROBLE'
);

DELETE FROM fe_control_registros WHERE codigo_tienda = 'MCHROBLE';

-- Resetear cursor: capturará desde IDTRANSACCION=1 en 10.20.0.125
UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 0 WHERE codigo_tienda = 'MCHROBLE';

-- Verificar
SELECT COUNT(*) AS quedaron FROM fe_control_registros WHERE codigo_tienda = 'MCHROBLE';
SELECT codigo_tienda, servidor_host, ultimo_idtransaccion_capturado FROM fe_tiendas WHERE codigo_tienda = 'MCHROBLE';

COMMIT;
