-- ============================================================
-- MCHPIURA F409-15 y F409-16: cambiar fecha + limpiar Bizlinks
-- Ejecutar desde servidor central (172.16.1.3)
-- Servidor MCHPIURA: 10.20.0.129
--
-- Fechas originales (guardar para restaurar después):
--   F409-15 y F409-16: 2026-11-14
-- ============================================================

-- Paso 1: cambiar FECHA_DOCUMENTO a hoy
UPDATE [10.20.0.129].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = CONVERT(char(8), GETDATE(), 112)
WHERE NUMERO_SERIE = 409
  AND NUMERO_DOCUMENTO BETWEEN 15 AND 16
  AND FECHA_DOCUMENTO = '20261114';

-- Verificar
SELECT NUMERO_DOCUMENTO, CONVERT(char(8), FECHA_DOCUMENTO, 112) AS fecha_doc,
       CONVERT(char(8), FECHA_CREACION, 112) AS fecha_creacion
FROM [10.20.0.129].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 409 AND NUMERO_DOCUMENTO BETWEEN 15 AND 16;

-- Paso 2: limpiar Bizlinks para F409-15 y F409-16
DELETE [10.20.0.129].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEDETAIL]
WHERE [serieNumero] LIKE 'F409-%'
  AND CAST(SUBSTRING([serieNumero], 6, 8) AS INT) BETWEEN 15 AND 16;

DELETE [10.20.0.129].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER_ADD]
WHERE [serieNumero] LIKE 'F409-%'
  AND CAST(SUBSTRING([serieNumero], 6, 8) AS INT) BETWEEN 15 AND 16;

DELETE [10.20.0.129].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER]
WHERE [serieNumero] LIKE 'F409-%'
  AND CAST(SUBSTRING([serieNumero], 6, 8) AS INT) BETWEEN 15 AND 16;
