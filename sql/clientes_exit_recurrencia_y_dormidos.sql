-- ============================================================
-- CLIENTES EXIT: Alta recurrencia (4 meses) y dormidos (1 año)
-- Ejecutar desde el SQL Server central (172.16.1.3)
-- Base de datos: soluflex_faro (verificar nombre si es distinto)
--
-- Tiendas EXIT incluidas (idsucursal):
-- 194 Caminos del Inca, 196 LR San Borja, 197 Jockey Plaza,
-- 200 RP Salaverry, 203 RP Chiclayo, 205 MP Trujillo,
-- 206 Plaza Norte, 207 Plaza Lima Sur, 209 RP Primavera,
-- 213 MP Arequipa, 275 Mall del Sur, 279 Online,
-- 297 RP Piura, 298 Falabella, 304 RP Cusco,
-- 306 La Molina, 315 MA San Juan de Lurigancho, 334 RP Puruchuco
--
-- Exclusiones:
-- * Notas de crédito (flag_notacredito = 'S')
-- * Clientes sin NUMERO_IDENTIDAD (anónimos / genéricos)
-- * Nombres genéricos conocidos
-- ============================================================

DECLARE @idsucursales_exit TABLE (idsucursal INT);
INSERT INTO @idsucursales_exit VALUES
  (194),(196),(197),(200),(203),(205),(206),(207),
  (209),(213),(275),(279),(297),(298),(304),(306),(315),(334);

DECLARE @fecha_hoy   DATE = CAST(GETDATE() AS DATE);
DECLARE @hace_4meses DATE = DATEADD(MONTH, -4, @fecha_hoy);
DECLARE @hace_1anio  DATE = DATEADD(MONTH, -12, @fecha_hoy);

-- ============================================================
-- QUERY 1: ALTA RECURRENCIA — últimos 4 meses
-- Clientes con 3 o más visitas de compra distintas
-- ============================================================
SELECT
  p.PERSONA                                             AS cliente,
  p.NUMERO_IDENTIDAD                                    AS dni_ruc,
  p.TIPO_IDENTIDAD                                      AS tipo_doc,
  p.EMAIL1                                              AS email,
  p.MOVIL                                               AS celular,
  cd.IDPERSONA                                          AS id_cliente,
  COUNT(DISTINCT CAST(cd.NUMERO_SERIE AS BIGINT) * 1000000
        + cd.NUMERO_DOCUMENTO)                          AS nro_visitas,
  MIN(CAST(cd.FECHA_DOCUMENTO AS DATE))                 AS primera_compra_periodo,
  MAX(CAST(cd.FECHA_DOCUMENTO AS DATE))                 AS ultima_compra
FROM CABECERA_DOCUMENTO cd
  INNER JOIN @idsucursales_exit s ON cd.idsucursal  = s.idsucursal
  INNER JOIN M_PERSONAS          p ON cd.IDPERSONA  = p.IDPERSONA
WHERE cd.FECHA_DOCUMENTO >= @hace_4meses
  AND cd.FECHA_DOCUMENTO <  DATEADD(DAY, 1, @fecha_hoy)
  AND cd.flag_notacredito = 'N'
  AND p.NUMERO_IDENTIDAD IS NOT NULL
  AND p.NUMERO_IDENTIDAD <> ''
  AND p.PERSONA NOT LIKE '%EXTRANJERO%'
  AND p.PERSONA NOT LIKE '%SIN IDENTIFICAR%'
  AND p.PERSONA NOT LIKE '%CONSUMIDOR FINAL%'
  AND p.PERSONA NOT LIKE '%VARIOS%'
GROUP BY
  p.PERSONA, p.NUMERO_IDENTIDAD, p.TIPO_IDENTIDAD, p.EMAIL1, p.MOVIL, cd.IDPERSONA
HAVING
  COUNT(DISTINCT CAST(cd.NUMERO_SERIE AS BIGINT) * 1000000 + cd.NUMERO_DOCUMENTO) >= 3
ORDER BY
  nro_visitas DESC;


-- ============================================================
-- QUERY 2: DORMIDAS — compraron 1 sola vez en el último año
-- y no han vuelto (su única compra fue hace más de 30 días)
-- ============================================================
SELECT
  p.PERSONA                                             AS cliente,
  p.NUMERO_IDENTIDAD                                    AS dni_ruc,
  p.TIPO_IDENTIDAD                                      AS tipo_doc,
  p.EMAIL1                                              AS email,
  p.MOVIL                                               AS celular,
  cd.IDPERSONA                                          AS id_cliente,
  MAX(CAST(cd.FECHA_DOCUMENTO AS DATE))                 AS fecha_unica_compra,
  DATEDIFF(DAY, MAX(cd.FECHA_DOCUMENTO), @fecha_hoy)    AS dias_sin_comprar
FROM CABECERA_DOCUMENTO cd
  INNER JOIN @idsucursales_exit s ON cd.idsucursal  = s.idsucursal
  INNER JOIN M_PERSONAS          p ON cd.IDPERSONA  = p.IDPERSONA
WHERE cd.FECHA_DOCUMENTO >= @hace_1anio
  AND cd.FECHA_DOCUMENTO <  DATEADD(DAY, 1, @fecha_hoy)
  AND cd.flag_notacredito = 'N'
  AND p.NUMERO_IDENTIDAD IS NOT NULL
  AND p.NUMERO_IDENTIDAD <> ''
  AND p.PERSONA NOT LIKE '%EXTRANJERO%'
  AND p.PERSONA NOT LIKE '%SIN IDENTIFICAR%'
  AND p.PERSONA NOT LIKE '%CONSUMIDOR FINAL%'
  AND p.PERSONA NOT LIKE '%VARIOS%'
GROUP BY
  p.PERSONA, p.NUMERO_IDENTIDAD, p.TIPO_IDENTIDAD, p.EMAIL1, p.MOVIL, cd.IDPERSONA
HAVING
  COUNT(DISTINCT CAST(cd.NUMERO_SERIE AS BIGINT) * 1000000 + cd.NUMERO_DOCUMENTO) = 1
  AND DATEDIFF(DAY, MAX(cd.FECHA_DOCUMENTO), @fecha_hoy) > 30
ORDER BY
  dias_sin_comprar DESC;
