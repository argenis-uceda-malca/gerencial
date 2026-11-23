-- ============================================================
-- CUZ01 B410-105 a B410-170 (Nov 1-6): cambiar fecha a hoy
-- Ejecutar desde servidor central (172.16.1.3)
-- Servidor CUZ01: 10.20.0.141
--
-- NOTA: estos registros son PENDIENTE/null en el motor (no llegaron
-- a Bizlinks aún). Solo se necesita cambiar la fecha en SQL Server.
-- El motor los sincronizará automáticamente con la fecha nueva.
-- NO se necesita limpiar Bizlinks ni resetear cursor.
-- ============================================================

-- Cambiar FECHA_DOCUMENTO a hoy para B410-105 a B410-170
UPDATE [10.20.0.141].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = CONVERT(char(8), GETDATE(), 112)
WHERE NUMERO_SERIE = 410
  AND NUMERO_DOCUMENTO BETWEEN 105 AND 170;

-- Verificar
SELECT COUNT(*) AS actualizados, MIN(NUMERO_DOCUMENTO) AS desde, MAX(NUMERO_DOCUMENTO) AS hasta
FROM [10.20.0.141].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 410
  AND NUMERO_DOCUMENTO BETWEEN 105 AND 170
  AND FECHA_DOCUMENTO = CONVERT(char(8), GETDATE(), 112);
