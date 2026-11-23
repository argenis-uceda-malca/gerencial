-- ============================================================
-- MILKBRASI B426: restaurar FECHA_DOCUMENTO original
-- Ejecutar desde servidor central (172.16.1.3)
-- Servidor: 10.20.0.164
--
-- REQUISITO: ejecutar SOLO después de verificar que todos los
-- documentos estén en estado P (aceptados por SUNAT)
--
-- Verificar antes en Postgres:
-- SELECT serie_numero_bizlinks, estado_bizlinks FROM fe_control_registros
-- WHERE codigo_tienda = 'MILKBRASI'
--   AND serie_numero_bizlinks IN (
--     'B426-00000001','B426-00000002','B426-00000003','B426-00000004',
--     'B426-00000023','F426-00000001');
-- -- Todos deben ser P
-- ============================================================

-- Verificar estado actual antes de restaurar
SELECT
  NUMERO_DOCUMENTO,
  CONVERT(char(8), FECHA_DOCUMENTO, 112) AS fecha_actual,
  CONVERT(char(8), FECHA_CREACION, 112) AS fecha_original
FROM [10.20.0.164].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 426
  AND NUMERO_DOCUMENTO IN (1, 2, 3, 4, 23)
  AND FECHA_DOCUMENTO = CONVERT(char(8), GETDATE(), 112);

-- Restaurar usando FECHA_CREACION
UPDATE [10.20.0.164].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = CONVERT(char(8), FECHA_CREACION, 112)
WHERE NUMERO_SERIE = 426
  AND NUMERO_DOCUMENTO IN (1, 2, 3, 4, 23)
  AND FECHA_DOCUMENTO = CONVERT(char(8), GETDATE(), 112);

-- Verificar resultado
SELECT
  NUMERO_DOCUMENTO,
  CONVERT(char(8), FECHA_DOCUMENTO, 112) AS fecha_restaurada
FROM [10.20.0.164].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 426
  AND NUMERO_DOCUMENTO IN (1, 2, 3, 4, 23)
ORDER BY NUMERO_DOCUMENTO;
