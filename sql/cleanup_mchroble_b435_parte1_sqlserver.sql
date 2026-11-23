-- ============================================================
-- MCHROBLE B435-1 a B435-20 (Nov 10-11): cambiar fecha + limpiar Bizlinks
-- Ejecutar desde servidor central (172.16.1.3)
-- Servidor MCHROBLE: 10.20.0.125
--
-- Fechas originales (guardar para restaurar después):
--   B435-1  a B435-7:  2026-11-10
--   B435-8  a B435-20: 2026-11-11
-- ============================================================

-- Paso 1: cambiar FECHA_DOCUMENTO a hoy para los 20 documentos
UPDATE [10.20.0.125].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = CONVERT(char(8), GETDATE(), 112)
WHERE NUMERO_SERIE = 435
  AND NUMERO_DOCUMENTO BETWEEN 1 AND 20;

-- Verificar
SELECT COUNT(*) AS actualizados, MIN(NUMERO_DOCUMENTO) AS desde, MAX(NUMERO_DOCUMENTO) AS hasta
FROM [10.20.0.125].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 435
  AND NUMERO_DOCUMENTO BETWEEN 1 AND 20
  AND FECHA_DOCUMENTO = CONVERT(char(8), GETDATE(), 112);

-- Paso 2: limpiar Bizlinks para B435-1 a B435-20
DELETE [10.20.0.125].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEDETAIL]
WHERE [serieNumero] LIKE 'B435-%'
  AND CAST(SUBSTRING([serieNumero], 6, 8) AS INT) BETWEEN 1 AND 20;

DELETE [10.20.0.125].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER_ADD]
WHERE [serieNumero] LIKE 'B435-%'
  AND CAST(SUBSTRING([serieNumero], 6, 8) AS INT) BETWEEN 1 AND 20;

DELETE [10.20.0.125].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER]
WHERE [serieNumero] LIKE 'B435-%'
  AND CAST(SUBSTRING([serieNumero], 6, 8) AS INT) BETWEEN 1 AND 20;
