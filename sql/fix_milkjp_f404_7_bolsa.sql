-- Fix F404-00000007 MILKJP (10.20.0.158) — error SUNAT 3277
-- La bolsa (081171170133, S/0.01, IGV=0) se clasifica como exonerada
-- → totalValorVenta=203.37 ≠ totalValorVentaNetoOpGravadas=203.36 → 3277.
-- Solución: absorber el S/0.01 en línea BTTMLJN008AZU31 (59.31→59.32)
-- y poner en cero la línea de bolsa. Total cabecera no cambia (239.98).
-- Ejecutar desde servidor CENTRAL.

-- ============================================================
-- PASO 1: Verificar estado actual antes de modificar
-- ============================================================
SELECT IDTRANSACCION, SECUENCIA, CODIGO_PRODUCTO,
       IMPORTE_SUBTOTAL, IMPORTE_IMPUESTO, IMPORTE_TOTAL
FROM [10.20.0.158].[SOLUFLEX_FARO].[dbo].[DETALLE_DOCUMENTO]
WHERE IDTRANSACCION = 208
ORDER BY IMPORTE_SUBTOTAL DESC;

-- ============================================================
-- PASO 2a: Absorber S/0.01 en línea BTTMLJN008AZU31
--   59.31 + 0.01 = 59.32 subtotal (IGV sigue 10.68, total 70.00)
--   59.32 + 10.68 = 70.00 ✓   |   sum subtotales: 76.26+67.79+59.32=203.37 ✓
-- ============================================================
UPDATE [10.20.0.158].[SOLUFLEX_FARO].[dbo].[DETALLE_DOCUMENTO]
SET IMPORTE_SUBTOTAL = 59.32,
    IMPORTE_TOTAL    = 70.00
WHERE IDTRANSACCION    = 208
  AND CODIGO_PRODUCTO  = 'BTTMLJN008AZU31'
  AND IMPORTE_SUBTOTAL = 59.31;

-- ============================================================
-- PASO 2b: Dejar en cero la línea de bolsa 081171170133
-- ============================================================
UPDATE [10.20.0.158].[SOLUFLEX_FARO].[dbo].[DETALLE_DOCUMENTO]
SET IMPORTE_SUBTOTAL = 0.00,
    IMPORTE_TOTAL    = 0.00
WHERE IDTRANSACCION    = 208
  AND CODIGO_PRODUCTO  = '081171170133'
  AND IMPORTE_SUBTOTAL = 0.01;

-- ============================================================
-- PASO 3: Limpiar SPE de Bizlinks para reinserción limpia
-- ============================================================
DELETE FROM [10.20.0.158].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEDETAIL]
WHERE serieNumero = 'F404-00000007';

DELETE FROM [10.20.0.158].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER_ADD]
WHERE serieNumero = 'F404-00000007';

DELETE FROM [10.20.0.158].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER]
WHERE serieNumero = 'F404-00000007';

BEGIN TRY
    DELETE FROM [10.20.0.158].[BIZLINKS_PROD].[dbo].[SPE_EINVOICE_RESPONSE]
    WHERE serieNumero = 'F404-00000007';
END TRY BEGIN CATCH END CATCH;

BEGIN TRY
    DELETE FROM [10.20.0.158].[BIZLINKS_PROD].[dbo].[SPE_ERROR_LOG]
    WHERE serieNumero = 'F404-00000007';
END TRY BEGIN CATCH END CATCH;

-- ============================================================
-- PASO 4: Verificar resultado
-- ============================================================
SELECT IDTRANSACCION, SECUENCIA, CODIGO_PRODUCTO,
       IMPORTE_SUBTOTAL, IMPORTE_IMPUESTO, IMPORTE_TOTAL
FROM [10.20.0.158].[SOLUFLEX_FARO].[dbo].[DETALLE_DOCUMENTO]
WHERE IDTRANSACCION = 208
ORDER BY IMPORTE_SUBTOTAL DESC;

-- Deben quedar: 76.26 / 67.79 / 59.32 / 0.00
-- Sum subtotales = 203.37 = CABECERA.IMPORTE_SUBTOTAL ✓
-- Sum totales    = 89.99 + 79.99 + 70.00 + 0.00 = 239.98 ✓

SELECT COUNT(*) AS spe_header_debe_ser_0
FROM [10.20.0.158].[BIZLINKS_PROD].[dbo].[SPE_EINVOICEHEADER]
WHERE serieNumero = 'F404-00000007';
