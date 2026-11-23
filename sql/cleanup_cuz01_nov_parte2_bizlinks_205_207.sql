-- ============================================================
-- CUZ01 B410-205, 206, 207: desatascar de Bizlinks (L)
-- Ejecutar desde servidor central (172.16.1.3)
-- Servidor CUZ01: 10.20.0.141
--
-- Estos tienen fecha Nov 10 (2 días), SUNAT debe aceptarlos.
-- Limpiar Bizlinks + resetear en motor para que reintente.
-- ============================================================

-- Paso 1: limpiar Bizlinks para los 3 documentos
DELETE [10.20.0.141].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEDETAIL]
WHERE [serieNumero] IN ('B410-00000205','B410-00000206','B410-00000207');

DELETE [10.20.0.141].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER_ADD]
WHERE [serieNumero] IN ('B410-00000205','B410-00000206','B410-00000207');

DELETE [10.20.0.141].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER]
WHERE [serieNumero] IN ('B410-00000205','B410-00000206','B410-00000207');
