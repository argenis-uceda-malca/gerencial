-- ============================================================
-- CUZ01 B410-1 a B410-93 (22-30 oct): cambiar fecha + limpiar Bizlinks
-- Ejecutar desde servidor central (172.16.1.3)
-- Servidor CUZ01: 10.20.0.141
-- ============================================================

-- Paso 1: actualizar FECHA_DOCUMENTO a hoy SOLO para los 93 pendientes (B410-1 a B410-93)
-- Los B410-94 en adelante ya están aceptados (P) — NO tocarlos
UPDATE [10.20.0.141].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = CONVERT(char(8), GETDATE(), 112)
WHERE NUMERO_SERIE = 410
  AND NUMERO_DOCUMENTO BETWEEN 1 AND 93;

-- Verificar cuántas se actualizaron
SELECT COUNT(*) AS actualizados, MIN(NUMERO_DOCUMENTO) AS desde, MAX(NUMERO_DOCUMENTO) AS hasta
FROM [10.20.0.141].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 410
  AND NUMERO_DOCUMENTO BETWEEN 1 AND 93
  AND FECHA_DOCUMENTO = CONVERT(char(8), GETDATE(), 112);

-- Paso 2: limpiar Bizlinks SOLO para B410-1 a B410-93
DELETE [10.20.0.141].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEDETAIL]
WHERE [serieNumero] LIKE 'B410-%'
  AND CAST(SUBSTRING([serieNumero], 6, 8) AS INT) BETWEEN 1 AND 93;

DELETE [10.20.0.141].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER_ADD]
WHERE [serieNumero] LIKE 'B410-%'
  AND CAST(SUBSTRING([serieNumero], 6, 8) AS INT) BETWEEN 1 AND 93;

DELETE [10.20.0.141].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER]
WHERE [serieNumero] LIKE 'B410-%'
  AND CAST(SUBSTRING([serieNumero], 6, 8) AS INT) BETWEEN 1 AND 93;
