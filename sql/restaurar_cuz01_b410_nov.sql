-- ============================================================
-- CUZ01 B410-105 a B410-170: restaurar FECHA_DOCUMENTO original
-- Ejecutar desde servidor central (172.16.1.3)
-- Servidor CUZ01: 10.20.0.141
--
-- Fechas originales (confirmadas del motor):
--   B410-105 a B410-107 → 2026-11-01
--   B410-108 a B410-119 → 2026-11-02
--   B410-120 a B410-133 → 2026-11-03
--   B410-134 a B410-144 → 2026-11-04
--   B410-145 a B410-154 → 2026-11-05
--   B410-155 a B410-170 → 2026-11-06
--
-- REQUISITO: ejecutar SOLO después de verificar que todos los
-- B410-105 a B410-170 estén en estado P (aceptados por SUNAT)
--
-- Verificar antes:
-- SELECT COUNT(*) FROM fe_control_registros
-- WHERE codigo_tienda='CUZ01' AND serie_numero_bizlinks LIKE 'B410-%'
-- AND CAST(SUBSTRING(serie_numero_bizlinks,6,8) AS INT) BETWEEN 105 AND 170
-- AND estado_bizlinks = 'P';
-- -- Debe devolver 66
-- ============================================================

-- Verificar estado actual antes de restaurar
SELECT
  CONVERT(char(8), FECHA_DOCUMENTO, 112) AS fecha_actual,
  COUNT(*) AS cantidad,
  MIN(NUMERO_DOCUMENTO) AS desde,
  MAX(NUMERO_DOCUMENTO) AS hasta
FROM [10.20.0.141].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 410
  AND NUMERO_DOCUMENTO BETWEEN 105 AND 170
GROUP BY CONVERT(char(8), FECHA_DOCUMENTO, 112)
ORDER BY 1;

-- Restaurar usando FECHA_CREACION (preserva la fecha original de la transacción)
UPDATE [10.20.0.141].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = CONVERT(char(8), FECHA_CREACION, 112)
WHERE NUMERO_SERIE = 410
  AND NUMERO_DOCUMENTO BETWEEN 105 AND 170
  AND FECHA_DOCUMENTO = '20261112';

-- Verificar resultado: debe mostrar las fechas originales (1-6 nov)
SELECT
  CONVERT(char(8), FECHA_DOCUMENTO, 112) AS fecha_restaurada,
  COUNT(*) AS cantidad,
  MIN(NUMERO_DOCUMENTO) AS desde,
  MAX(NUMERO_DOCUMENTO) AS hasta
FROM [10.20.0.141].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 410
  AND NUMERO_DOCUMENTO BETWEEN 105 AND 170
GROUP BY CONVERT(char(8), FECHA_DOCUMENTO, 112)
ORDER BY 1;
