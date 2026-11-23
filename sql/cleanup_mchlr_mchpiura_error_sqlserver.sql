-- ============================================================
-- MCHLR B421-238 (Nov 12) + MCHPIURA B409-168 (Nov 13): limpiar Bizlinks
-- Ejecutar desde servidor central (172.16.1.3)
-- Fechas recientes — no necesitan cambio de fecha
-- ============================================================

-- MCHLR (10.20.0.134) — B421-00000238
DELETE [10.20.0.134].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEDETAIL]
WHERE [serieNumero] = 'B421-00000238';

DELETE [10.20.0.134].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER_ADD]
WHERE [serieNumero] = 'B421-00000238';

DELETE [10.20.0.134].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER]
WHERE [serieNumero] = 'B421-00000238';

-- MCHPIURA (10.20.0.129) — B409-00000168
DELETE [10.20.0.129].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEDETAIL]
WHERE [serieNumero] = 'B409-00000168';

DELETE [10.20.0.129].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER_ADD]
WHERE [serieNumero] = 'B409-00000168';

DELETE [10.20.0.129].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER]
WHERE [serieNumero] = 'B409-00000168';
