-- ============================================================
-- Reporte OSE Bizlinks — todos los documentos enviados por local
-- Incluye series antiguas y nuevas (400+)
-- Ejecutar en la base smartanalytic (Postgres)
-- Exportar resultado a Excel
-- ============================================================

SELECT
  fcr.codigo_tienda                                                AS "Local",
  ft.nombre_tienda                                                 AS "Nombre Tienda",
  SUBSTRING(fcr.serie_numero_bizlinks, 1,
    POSITION('-' IN fcr.serie_numero_bizlinks) - 1)               AS "Serie",
  CASE
    WHEN LEFT(fcr.serie_numero_bizlinks, 1) = 'B' THEN 'Boleta'
    WHEN LEFT(fcr.serie_numero_bizlinks, 1) = 'F' THEN 'Factura'
    ELSE LEFT(fcr.serie_numero_bizlinks, 1)
  END                                                              AS "Tipo",
  CAST(SUBSTRING(fcr.serie_numero_bizlinks,
    POSITION('-' IN fcr.serie_numero_bizlinks) + 1,
    20) AS INT)                                                    AS "Nro Documento",
  fcr.serie_numero_bizlinks                                        AS "Serie-Numero",
  fcr.fecha_venta::date                                            AS "Fecha Venta",
  CASE fcr.estado_bizlinks
    WHEN 'P' THEN 'Aceptado'
    WHEN 'L' THEN 'En cola'
    WHEN 'R' THEN 'Rechazado'
    WHEN 'E' THEN 'Error'
    ELSE COALESCE(fcr.estado_bizlinks, 'Sin sincronizar')
  END                                                              AS "Estado SUNAT",
  fcr.estado                                                       AS "Estado Motor",
  fcr.fecha_captura::date                                          AS "Fecha Captura",
  fcr.fecha_sync_bizlinks::date                                    AS "Fecha Envio SUNAT",
  fcr.fecha_ultima_actualizacion::date                             AS "Ultima Act."

FROM fe_control_registros fcr
LEFT JOIN fe_tiendas ft ON ft.codigo_tienda = fcr.codigo_tienda

ORDER BY
  fcr.codigo_tienda,
  SUBSTRING(fcr.serie_numero_bizlinks, 1,
    POSITION('-' IN fcr.serie_numero_bizlinks) - 1),
  CAST(SUBSTRING(fcr.serie_numero_bizlinks,
    POSITION('-' IN fcr.serie_numero_bizlinks) + 1,
    20) AS INT);
