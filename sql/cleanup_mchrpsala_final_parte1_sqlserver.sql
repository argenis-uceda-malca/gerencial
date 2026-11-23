-- ============================================================
-- MCHRPSALA B419 FINAL: actualizar TODAS las boletas con fecha < hoy
-- Ejecutar desde servidor central (172.16.1.3)
--
-- Actualiza FECHA_DOCUMENTO a hoy para TODOS los B419 con fecha vieja
-- (incluye 1-114 que ya se actualizaron antes y 115 en adelante)
-- Limpia Bizlinks para un rango amplio (1-500)
-- ============================================================

-- Paso 1: actualizar TODAS las fechas viejas de la serie 419 a hoy
UPDATE [10.20.0.132].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = CONVERT(char(8), GETDATE(), 112)
WHERE NUMERO_SERIE = 419
  AND FECHA_DOCUMENTO < CONVERT(char(8), GETDATE(), 112);

-- Verificar cuántas se actualizaron
SELECT COUNT(*) AS actualizados, MIN(NUMERO_DOCUMENTO) AS desde, MAX(NUMERO_DOCUMENTO) AS hasta
FROM [10.20.0.132].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 419
  AND FECHA_DOCUMENTO = CONVERT(char(8), GETDATE(), 112);

-- Paso 2: limpiar Bizlinks para B419-1 a B419-500 (cubre todo el backlog)
DELETE [10.20.0.132].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEDETAIL]
WHERE [serieNumero] LIKE 'B419-%'
  AND CAST(SUBSTRING([serieNumero], 6, 8) AS INT) BETWEEN 1 AND 500;

DELETE [10.20.0.132].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER_ADD]
WHERE [serieNumero] LIKE 'B419-%'
  AND CAST(SUBSTRING([serieNumero], 6, 8) AS INT) BETWEEN 1 AND 500;

DELETE [10.20.0.132].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER]
WHERE [serieNumero] LIKE 'B419-%'
  AND CAST(SUBSTRING([serieNumero], 6, 8) AS INT) BETWEEN 1 AND 500;
