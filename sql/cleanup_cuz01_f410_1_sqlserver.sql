-- ============================================================
-- CUZ01 F410-00000001 (Factura, 26 oct): cambiar fecha + limpiar Bizlinks
-- Ejecutar desde servidor central (172.16.1.3)
-- Servidor CUZ01: 10.20.0.141
--
-- Fecha original: 2026-10-26 → restaurar después con FECHA_CREACION
-- ============================================================

-- Paso 1: cambiar FECHA_DOCUMENTO a hoy
-- (NUMERO_SERIE=410, NUMERO_DOCUMENTO=1, tipo Factura)
UPDATE [10.20.0.141].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = CONVERT(char(8), GETDATE(), 112)
WHERE NUMERO_SERIE = 410
  AND NUMERO_DOCUMENTO = 1
  AND FECHA_DOCUMENTO = '20261026';

-- Verificar
SELECT NUMERO_DOCUMENTO, CONVERT(char(8), FECHA_DOCUMENTO, 112) AS fecha_actual,
       CONVERT(char(8), FECHA_CREACION, 112) AS fecha_creacion
FROM [10.20.0.141].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 410 AND NUMERO_DOCUMENTO = 1;

-- Paso 2: limpiar Bizlinks
DELETE [10.20.0.141].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEDETAIL]
WHERE [serieNumero] = 'F410-00000001';

DELETE [10.20.0.141].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER_ADD]
WHERE [serieNumero] = 'F410-00000001';

DELETE [10.20.0.141].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER]
WHERE [serieNumero] = 'F410-00000001';
