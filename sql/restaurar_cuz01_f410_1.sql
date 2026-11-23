-- ============================================================
-- CUZ01 F410-00000001: restaurar FECHA_DOCUMENTO original
-- Ejecutar desde servidor central (172.16.1.3)
-- Servidor CUZ01: 10.20.0.141
--
-- Fecha original: 2026-10-26
-- Fix aplicado: 2026-11-17 (FECHA_DOCUMENTO → '20261117')
--
-- REQUISITO: ejecutar SOLO después de verificar que F410-00000001
-- esté en estado P (aceptado por SUNAT)
--
-- Verificar antes:
-- SELECT estado_bizlinks FROM fe_control_registros
-- WHERE codigo_tienda='CUZ01' AND serie_numero_bizlinks='F410-00000001';
-- -- Debe devolver 'P'
-- ============================================================

-- Restaurar usando FECHA_CREACION
UPDATE [10.20.0.141].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
SET FECHA_DOCUMENTO = CONVERT(char(8), FECHA_CREACION, 112)
WHERE NUMERO_SERIE = 410
  AND NUMERO_DOCUMENTO = 1
  AND FECHA_DOCUMENTO = '20261117';

-- Verificar: debe mostrar 20261026
SELECT NUMERO_DOCUMENTO,
       CONVERT(char(8), FECHA_DOCUMENTO, 112) AS fecha_restaurada,
       CONVERT(char(8), FECHA_CREACION, 112)  AS fecha_creacion
FROM [10.20.0.141].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
WHERE NUMERO_SERIE = 410 AND NUMERO_DOCUMENTO = 1;
