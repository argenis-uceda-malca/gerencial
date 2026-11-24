-- Restaurar: CUZ01 F410-00000007 — devolver FECHA_DOCUMENTO a 2026-11-19
-- Ejecutar SOLO después de que el documento llegue a estado P (aceptado por SUNAT)
--
-- Servidor: 10.20.0.141 (CUZ01 - EXIT RP CUSCO, SOLUFLEX_FARO)
-- idtransaccion: 344 | serie: F410 | numero: 7

-- Verificar que ya está en P antes de restaurar:
-- SELECT estado_bizlinks, mensaje_bizlinks FROM fe_control_registros
-- WHERE codigo_tienda='CUZ01' AND serie_numero_bizlinks='F410-00000007';

-- Restaurar:
UPDATE [10.20.0.141].[SOLUFLEX_FARO].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = '2026-11-19'
WHERE IDTRANSACCION = 344;

-- Verificar:
SELECT IDTRANSACCION, NUMERO_SERIE, NUMERO_DOCUMENTO, FECHA_DOCUMENTO, FECHA_CREACION
FROM [10.20.0.141].[SOLUFLEX_FARO].[dbo].[CABECERA_DOCUMENTO]
WHERE IDTRANSACCION = 344;
