-- ============================================================
-- Boletas serie antigua B205 (EXITPLS) y B231 (MCHPLS): eliminar del motor
-- Series <400, no se pueden reenviar. Fecha 28-oct (>5 días)
--
-- IMPORTANTE: estas tienen estado_bizlinks=L (en cola en Bizlinks).
-- Limpiar también en SQL Server desde 172.16.1.3:
--
-- DELETE [10.20.0.xxx].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEDETAIL]
--   WHERE [serieNumero] IN ('B205-00034780','B205-00034781','B205-00034782',
--     'B205-00034783','B205-00034784','B205-00034785','B205-00034786','B205-00034787');
-- DELETE [10.20.0.xxx].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER_ADD]
--   WHERE [serieNumero] IN ('B205-00034780','B205-00034781','B205-00034782',
--     'B205-00034783','B205-00034784','B205-00034785','B205-00034786','B205-00034787');
-- DELETE [10.20.0.xxx].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER]
--   WHERE [serieNumero] IN ('B205-00034780','B205-00034781','B205-00034782',
--     'B205-00034783','B205-00034784','B205-00034785','B205-00034786','B205-00034787');
--
-- (Reemplazar xxx con la IP de EXITPLS y MCHPLS)
-- ============================================================

BEGIN;

DELETE FROM fe_auditoria_estados    WHERE id_control_registro IN (13066,13067,13069,13070,13071,13073,13074,13075,12440,12441);
DELETE FROM fe_historial_errores    WHERE id_control_registro IN (13066,13067,13069,13070,13071,13073,13074,13075,12440,12441);
DELETE FROM fe_notificaciones       WHERE id_control_registro IN (13066,13067,13069,13070,13071,13073,13074,13075,12440,12441);
DELETE FROM fe_historial_reintentos WHERE id_control_registro IN (13066,13067,13069,13070,13071,13073,13074,13075,12440,12441);
DELETE FROM fe_control_registros    WHERE id IN (13066,13067,13069,13070,13071,13073,13074,13075,12440,12441);

-- Verificar: debe devolver 0
SELECT COUNT(*) AS quedaron FROM fe_control_registros
WHERE id IN (13066,13067,13069,13070,13071,13073,13074,13075,12440,12441);

COMMIT;
