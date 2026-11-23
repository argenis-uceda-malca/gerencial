-- ============================================================
-- FACTURAS oct28-30: eliminar del motor para recaptura
-- Ejecutar en Postgres DESPUÉS de la Parte 1 (SQL Server)
-- IDs: 13093,12675,12745,12848,12497,12954,12962,12963,12965,
--      12259,12303,12861,12144
-- ============================================================

BEGIN;

DELETE FROM fe_auditoria_estados WHERE id_control_registro IN (
  13093,12675,12745,12848,12497,12954,12962,12963,12965,12259,12303,12861,12144
);

DELETE FROM fe_historial_errores WHERE id_control_registro IN (
  13093,12675,12745,12848,12497,12954,12962,12963,12965,12259,12303,12861,12144
);

DELETE FROM fe_notificaciones WHERE id_control_registro IN (
  13093,12675,12745,12848,12497,12954,12962,12963,12965,12259,12303,12861,12144
);

DELETE FROM fe_historial_reintentos WHERE id_control_registro IN (
  13093,12675,12745,12848,12497,12954,12962,12963,12965,12259,12303,12861,12144
);

DELETE FROM fe_control_registros WHERE id IN (
  13093,12675,12745,12848,12497,12954,12962,12963,12965,12259,12303,12861,12144
);

-- Verificar: debe devolver 0
SELECT COUNT(*) AS quedaron FROM fe_control_registros
WHERE id IN (13093,12675,12745,12848,12497,12954,12962,12963,12965,12259,12303,12861,12144);

COMMIT;
