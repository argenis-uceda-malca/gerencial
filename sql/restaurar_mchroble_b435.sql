-- ============================================================
-- MCHROBLE B435-1 a B435-20: restaurar FECHA_DOCUMENTO original
-- Ejecutar desde servidor central (172.16.1.3)
-- Servidor MCHROBLE: 10.20.0.125
--
-- Fechas originales:
--   B435-1  a B435-7:  2026-11-10
--   B435-8  a B435-20: 2026-11-11
-- Fix aplicado: 2026-11-18 (FECHA_DOCUMENTO → '20261118')
--
-- REQUISITO: ejecutar SOLO después de verificar que todos
-- B435-1 a B435-20 estén en estado P (aceptados por SUNAT)
--
-- Verificar antes:
-- SELECT COUNT(*) FROM fe_control_registros
-- WHERE codigo_tienda='MCHROBLE' AND serie_numero_bizlinks LIKE 'B435-%'
-- AND CAST(SUBSTRING(serie_numero_bizlinks,6,8) AS INT) BETWEEN 1 AND 20
-- AND estado_bizlinks = 'P';
-- -- Debe devolver 20
-- ============================================================

-- Restaurar usando FECHA_CREACION
UPDATE [10.20.0.125].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = CONVERT(char(8), FECHA_CREACION, 112)
WHERE NUMERO_SERIE = 435
  AND NUMERO_DOCUMENTO BETWEEN 1 AND 20
  AND FECHA_DOCUMENTO = '20261118';

-- Verificar: debe mostrar Nov 10 y Nov 11
SELECT
  CONVERT(char(8), FECHA_DOCUMENTO, 112) AS fecha_restaurada,
  COUNT(*) AS cantidad,
  MIN(NUMERO_DOCUMENTO) AS desde,
  MAX(NUMERO_DOCUMENTO) AS hasta
FROM [10.20.0.125].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 435
  AND NUMERO_DOCUMENTO BETWEEN 1 AND 20
GROUP BY CONVERT(char(8), FECHA_DOCUMENTO, 112)
ORDER BY 1;
