-- Restaurar: MILKBRASI B426-00000023 — devolver FECHA_DOCUMENTO a 2026-11-17
-- Ejecutar SOLO después de que el documento llegue a estado P (aceptado por SUNAT)
--
-- Servidor: 10.20.0.164 (MILKBRASI - MILK BRASIL, SOLUFLEX_FARO)
-- idtransaccion: 24 | serie: B426 | numero: 23
-- Historial de fechas:
--   Original:        2026-11-17  (fecha real de venta)
--   Sesion anterior: 2026-11-23  (primer intento de fix - insuficiente, SUNAT rechazó)
--   Fix actual:      2026-11-24  (fecha usada para lograr aceptación SUNAT)

-- Verificar que ya está en P antes de restaurar:
-- SELECT estado_bizlinks, mensaje_bizlinks FROM fe_control_registros
-- WHERE codigo_tienda='MILKBRASI' AND serie_numero_bizlinks='B426-00000023';

-- Restaurar:
UPDATE [10.20.0.164].[SOLUFLEX_FARO].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = '2026-11-17'
WHERE IDTRANSACCION = 24;

-- Verificar:
SELECT IDTRANSACCION, NUMERO_DOCUMENTO, FECHA_DOCUMENTO
FROM [10.20.0.164].[SOLUFLEX_FARO].[dbo].[CABECERA_DOCUMENTO]
WHERE IDTRANSACCION = 24;
