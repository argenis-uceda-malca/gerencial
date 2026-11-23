-- ============================================================
-- MCHMPTRUJ B411-452, 453, 477, 496: limpiar Bizlinks (error E)
-- Ejecutar desde servidor central (172.16.1.3)
-- Servidor MCHMPTRUJ: 10.20.0.130
--
-- Sin cambio de fecha (1-2 días de antigüedad, no es error 1079)
-- ============================================================

DELETE [10.20.0.130].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEDETAIL]
WHERE [serieNumero] LIKE 'B411-%'
  AND CAST(SUBSTRING([serieNumero], 6, 8) AS INT) IN (452, 453, 477, 496);

DELETE [10.20.0.130].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER_ADD]
WHERE [serieNumero] LIKE 'B411-%'
  AND CAST(SUBSTRING([serieNumero], 6, 8) AS INT) IN (452, 453, 477, 496);

DELETE [10.20.0.130].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER]
WHERE [serieNumero] LIKE 'B411-%'
  AND CAST(SUBSTRING([serieNumero], 6, 8) AS INT) IN (452, 453, 477, 496);
