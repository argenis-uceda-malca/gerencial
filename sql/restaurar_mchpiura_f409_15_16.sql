-- ============================================================
-- MCHPIURA F409-15 y F409-16: restaurar FECHA_DOCUMENTO original
-- Ejecutar desde servidor central (172.16.1.3)
-- Servidor MCHPIURA: 10.20.0.129
--
-- Fecha original: 2026-11-14
-- Fix aplicado: 2026-11-23 (FECHA_DOCUMENTO → '20261123')
--
-- REQUISITO: ejecutar SOLO después de verificar que ambas
-- estén en estado P (aceptadas por SUNAT)
--
-- Verificar antes:
-- SELECT serie_numero_bizlinks, estado_bizlinks FROM fe_control_registros
-- WHERE codigo_tienda='MCHPIURA'
--   AND serie_numero_bizlinks IN ('F409-00000015','F409-00000016');
-- -- Ambas deben devolver 'P'
-- ============================================================

-- Restaurar usando FECHA_CREACION
UPDATE [10.20.0.129].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = CONVERT(char(8), FECHA_CREACION, 112)
WHERE NUMERO_SERIE = 409
  AND NUMERO_DOCUMENTO BETWEEN 15 AND 16
  AND FECHA_DOCUMENTO = '20261123';

-- Verificar: debe mostrar 20261114
SELECT NUMERO_DOCUMENTO,
       CONVERT(char(8), FECHA_DOCUMENTO, 112) AS fecha_restaurada,
       CONVERT(char(8), FECHA_CREACION, 112)  AS fecha_creacion
FROM [10.20.0.129].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 409 AND NUMERO_DOCUMENTO BETWEEN 15 AND 16;
