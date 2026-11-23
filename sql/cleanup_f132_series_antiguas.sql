-- ============================================================
-- Facturas F132 (MILKJP 10.20.0.158): eliminar del motor
-- Serie antigua <400. estado_bizlinks=L → limpiar Bizlinks primero
-- ============================================================

-- PASO 1: Ejecutar en SQL Server desde 172.16.1.3
-- DELETE [10.20.0.158].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEDETAIL]
--   WHERE [serieNumero] IN ('F132-00000001','F132-00000002','F132-00000003');
-- DELETE [10.20.0.158].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER_ADD]
--   WHERE [serieNumero] IN ('F132-00000001','F132-00000002','F132-00000003');
-- DELETE [10.20.0.158].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER]
--   WHERE [serieNumero] IN ('F132-00000001','F132-00000002','F132-00000003');

-- PASO 2: Ejecutar en Postgres (pgAdmin)
BEGIN;

DELETE FROM fe_auditoria_estados    WHERE id_control_registro IN (12783,12789,12794);
DELETE FROM fe_historial_errores    WHERE id_control_registro IN (12783,12789,12794);
DELETE FROM fe_notificaciones       WHERE id_control_registro IN (12783,12789,12794);
DELETE FROM fe_historial_reintentos WHERE id_control_registro IN (12783,12789,12794);
DELETE FROM fe_control_registros    WHERE id IN (12783,12789,12794);

-- Verificar: debe devolver 0
SELECT COUNT(*) AS quedaron FROM fe_control_registros
WHERE id IN (12783,12789,12794);

COMMIT;
