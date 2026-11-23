-- ============================================================
-- MILKBRASI B426: cambiar fecha + limpiar Bizlinks
-- Servidor: 10.20.0.164
-- Ejecutar desde servidor central (172.16.1.3)
--
-- Documentos a corregir:
--   B426-1,2,3,4 (Nov 10): 7 días → error 1079
--   B426-23      (Nov 13): 4 días → vence mañana
--   F426-1       (Nov 12): rechazada por fuera de fecha
-- ============================================================

-- Paso 1: actualizar FECHA_DOCUMENTO a hoy solo para los documentos stuck
UPDATE [10.20.0.164].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = CONVERT(char(8), GETDATE(), 112)
WHERE NUMERO_SERIE = 426
  AND NUMERO_DOCUMENTO IN (1, 2, 3, 4, 23)
  AND FECHA_DOCUMENTO < CONVERT(char(8), GETDATE(), 112);

-- Verificar boletas actualizadas
SELECT NUMERO_DOCUMENTO, CONVERT(char(8), FECHA_DOCUMENTO, 112) AS fecha_nueva
FROM [10.20.0.164].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 426 AND NUMERO_DOCUMENTO IN (1, 2, 3, 4, 23);

-- Paso 2: actualizar F426-1 (factura — puede tener NUMERO_SERIE diferente)
-- Si F426 usa el mismo NUMERO_SERIE=426, esto la cubre también;
-- si no, ajustar el NUMERO_SERIE según corresponda
UPDATE [10.20.0.164].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = CONVERT(char(8), GETDATE(), 112)
WHERE NUMERO_SERIE = 426
  AND NUMERO_DOCUMENTO = 1
  AND CODIGO_DOCUMENTO IN ('FAC','FEL','F')   -- ajustar si el código es diferente
  AND FECHA_DOCUMENTO < CONVERT(char(8), GETDATE(), 112);

-- Paso 3: limpiar Bizlinks para esos documentos
DELETE [10.20.0.164].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEDETAIL]
WHERE [serieNumero] IN (
  'B426-00000001','B426-00000002','B426-00000003','B426-00000004',
  'B426-00000023','F426-00000001'
);

DELETE [10.20.0.164].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER_ADD]
WHERE [serieNumero] IN (
  'B426-00000001','B426-00000002','B426-00000003','B426-00000004',
  'B426-00000023','F426-00000001'
);

DELETE [10.20.0.164].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER]
WHERE [serieNumero] IN (
  'B426-00000001','B426-00000002','B426-00000003','B426-00000004',
  'B426-00000023','F426-00000001'
);
