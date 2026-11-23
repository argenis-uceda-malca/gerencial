-- ============================================================
-- CUZ01 B410-1 a B410-93: restaurar FECHA_DOCUMENTO original
-- Ejecutar desde servidor central (172.16.1.3)
-- Servidor CUZ01: 10.20.0.141
--
-- REQUISITO: ejecutar SOLO después de verificar que todos los
-- B410-1 a B410-93 estén en estado P (aceptados por SUNAT)
--
-- Verificar antes:
-- SELECT COUNT(*) FROM fe_control_registros
-- WHERE codigo_tienda='CUZ01' AND serie_numero_bizlinks LIKE 'B410-%'
-- AND CAST(SUBSTRING(serie_numero_bizlinks,7,8) AS INT) BETWEEN 1 AND 93
-- AND estado_bizlinks = 'P';
-- -- Debe devolver 93
-- ============================================================

-- Verificar estado actual antes de restaurar
SELECT
  CONVERT(char(8), FECHA_DOCUMENTO, 112) AS fecha_actual,
  COUNT(*) AS cantidad,
  MIN(NUMERO_DOCUMENTO) AS desde,
  MAX(NUMERO_DOCUMENTO) AS hasta
FROM [10.20.0.141].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 410
  AND NUMERO_DOCUMENTO BETWEEN 1 AND 93
GROUP BY CONVERT(char(8), FECHA_DOCUMENTO, 112)
ORDER BY 1;

-- Restaurar usando FECHA_CREACION (preserva la fecha original de la transacción)
UPDATE [10.20.0.141].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = CONVERT(char(8), FECHA_CREACION, 112)
WHERE NUMERO_SERIE = 410
  AND NUMERO_DOCUMENTO BETWEEN 1 AND 93
  AND FECHA_DOCUMENTO = '20261111';  -- fecha en que se aplicó el fix

-- Verificar resultado: debe mostrar las fechas originales (22-30 oct)
SELECT
  CONVERT(char(8), FECHA_DOCUMENTO, 112) AS fecha_restaurada,
  COUNT(*) AS cantidad,
  MIN(NUMERO_DOCUMENTO) AS desde,
  MAX(NUMERO_DOCUMENTO) AS hasta
FROM [10.20.0.141].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 410
  AND NUMERO_DOCUMENTO BETWEEN 1 AND 93
GROUP BY CONVERT(char(8), FECHA_DOCUMENTO, 112)
ORDER BY 1;
