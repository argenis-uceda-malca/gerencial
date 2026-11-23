-- ============================================================
-- JKY01 B402-3 a B402-14: eliminar del motor
-- Ejecutar en Postgres DESPUÉS de la Parte 1
-- ============================================================

BEGIN;

DELETE FROM fe_auditoria_estados   WHERE id_control_registro IN (13664,13665,13666,13667,13668,13669,13670,13671,13672,13673,13674,13675);
DELETE FROM fe_historial_errores   WHERE id_control_registro IN (13664,13665,13666,13667,13668,13669,13670,13671,13672,13673,13674,13675);
DELETE FROM fe_notificaciones      WHERE id_control_registro IN (13664,13665,13666,13667,13668,13669,13670,13671,13672,13673,13674,13675);
DELETE FROM fe_historial_reintentos WHERE id_control_registro IN (13664,13665,13666,13667,13668,13669,13670,13671,13672,13673,13674,13675);
DELETE FROM fe_control_registros   WHERE id IN (13664,13665,13666,13667,13668,13669,13670,13671,13672,13673,13674,13675);

-- Verificar: debe devolver 0
SELECT COUNT(*) AS quedaron FROM fe_control_registros
WHERE id IN (13664,13665,13666,13667,13668,13669,13670,13671,13672,13673,13674,13675);

COMMIT;
