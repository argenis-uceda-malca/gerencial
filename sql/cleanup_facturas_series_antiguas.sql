-- ============================================================
-- Facturas serie antigua (F107/F126/F127/F159/F195/F231): eliminar del motor
-- Series <400, no se pueden reenviar. Todas con estado_bizlinks=R
-- IDs: 12037,12130,12330,12346,12354,12443,12816,13238,13240,13243
-- ============================================================

BEGIN;

DELETE FROM fe_auditoria_estados    WHERE id_control_registro IN (12037,12130,12330,12346,12354,12443,12816,13238,13240,13243);
DELETE FROM fe_historial_errores    WHERE id_control_registro IN (12037,12130,12330,12346,12354,12443,12816,13238,13240,13243);
DELETE FROM fe_notificaciones       WHERE id_control_registro IN (12037,12130,12330,12346,12354,12443,12816,13238,13240,13243);
DELETE FROM fe_historial_reintentos WHERE id_control_registro IN (12037,12130,12330,12346,12354,12443,12816,13238,13240,13243);
DELETE FROM fe_control_registros    WHERE id IN (12037,12130,12330,12346,12354,12443,12816,13238,13240,13243);

-- Verificar: debe devolver 0
SELECT COUNT(*) AS quedaron FROM fe_control_registros
WHERE id IN (12037,12130,12330,12346,12354,12443,12816,13238,13240,13243);

COMMIT;
