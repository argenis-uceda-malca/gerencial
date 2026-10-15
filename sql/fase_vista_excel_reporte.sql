-- ============================================================================
-- fase_vista_excel_reporte.sql
-- Vista para la dinámica de Excel: automatizacion_vw_excel_reporte
--
-- VENTANA: mes actual + mes anterior (y los mismos dos meses del año pasado
--   para el comparativo), igual que el proceso manual. Traer el año completo
--   son 1.4 millones de filas y Excel tarda demasiado en recibirlas.
--
-- BLOQUES 1 a 3: la consulta del Excel con sus mismas 80 columnas y filtros,
--   limitada a esa ventana. Las dos fotos de stock (bloques 2 y 3) salen SIN
--   fecha, mes, semana ni día, igual que en el proceso manual: así aparecen
--   en la dinámica con cualquier filtro de periodo. inv_unds_act / inv_costo_act siguen
--   siendo UNA sola foto con detalle de producto, así que las dinámicas que ya
--   existen no cambian.
--
-- BLOQUE 4 (nuevo): stock diario del mes actual y el anterior, hasta categoría
--   (día × almacén × marca × categoría). Usa columnas NUEVAS, que valen 0 en
--   los bloques 1 a 3, para no mezclarse con las medidas existentes:
--
--     tipo_almacen          TIENDA, ECOMMERCE, CONSIGNACION EN TIENDA, TXD,
--                           CD, TRANSITO, MAYORISTA, MERMA, OTROS
--     inv_unds_dia          stock de cada día  -> usar con la FECHA o el DÍA
--     inv_costo_dia            en filas, columnas o filtro (un día por celda)
--     inv_unds_cierre_sem   stock del último día de la semana -> usar con SEMANA
--     inv_costo_cierre_sem
--     inv_unds_cierre_mes   stock del último día del mes      -> usar con MES
--     inv_costo_cierre_mes
--
--   El inventario no se suma en el tiempo. Cada columna da el valor correcto
--   mientras la celda cubra UN solo periodo de su tipo (un día, una semana,
--   un mes). El total general de varios periodos suma los cierres: ese total
--   no debe leerse.
--
-- Uso en Excel: reemplazar el texto de la consulta por
--     SELECT * FROM automatizacion_vw_excel_reporte
-- Las columnas existentes conservan nombre, orden y tipo; las nuevas van al
-- final. No modifica ninguna tabla.
-- ============================================================================

-- DROP + CREATE (no CREATE OR REPLACE): así los tipos de columna quedan
-- exactamente iguales a los de la tabla aunque la vista ya exista.
DROP VIEW IF EXISTS public.automatizacion_vw_excel_reporte;

CREATE VIEW public.automatizacion_vw_excel_reporte AS

-- BLOQUE 1: ventas, metas, tráfico, área y cubicajes (igual que hoy)
SELECT empresa, grupo_canal, fecha_documento, dia_semana, semana, dia_equivalente, mes, categoria,
       subcategoria, linea, linea_2, linea_2_2, sublinea, codigo_padre, descripcion_padre, coleccion,
       marca, marca_2, marca_temporada, proveedor, tipo, origen, localidad, temporada, temporada_2,
       temporada_3, sucursal, sucursal_2, sucursal_3, tipo_doc, serie, documento, serie_documento,
       unidades_hst_1, importe_subtotal_hst_1, costo_venta_neta_hst_1, unidades_hst,
       importe_subtotal_hst, costo_venta_neta_hst, unidades, importe_subtotal, costo_venta_neta,
       meta_venta, meta_contribucion, meta_venta_faro, meta_contribucion_faro, filtro_sss, filtro_sss2,
       mall, condicion, nro_tickets_hst_1, nro_tickets_hst, nro_tickets, dias_estancia,
       dias_estancia_tda, meses_estancia, meses_estancia_tda, inv_unds_act, inv_costo_act,
       inv_unds_hst, inv_costo_hst, pvp, vta_retail_act, vta_retail_hst, vta_retail_hst_1, cubicaje,
       area, conteo, conteo_hst, temporada_4, supervisor, sucursal_2_1, sucursal_3_1, "_mes_coleccion",
       largo, material, tdas_liquidadoras, flag_tickets_act, flag_tickets_hst, anio_analisis,
       NULL::varchar(30) AS tipo_almacen,
       0::numeric AS inv_unds_dia,        0::numeric AS inv_costo_dia,
       0::numeric AS inv_unds_cierre_sem, 0::numeric AS inv_costo_cierre_sem,
       0::numeric AS inv_unds_cierre_mes, 0::numeric AS inv_costo_cierre_mes
FROM public.automatizacion_pla_reporte_ventas
WHERE tipo_fila NOT IN ('metas_faro', 'stock_act', 'stock_hst')
  AND NOT (tipo_fila = 'metas_std' AND fecha_documento < '2026-01-01')
  -- Ventana: mes actual + mes anterior, y los mismos dos meses del año pasado
  -- (como el proceso manual). Las filas sin fecha (área, cubicajes) entran siempre.
  AND (   fecha_documento IS NULL
       OR (    fecha_documento >= (date_trunc('month', CURRENT_DATE) - INTERVAL '1 month')::date
           AND fecha_documento <  (date_trunc('month', CURRENT_DATE) + INTERVAL '1 month')::date)
       OR (    fecha_documento >= (date_trunc('month', CURRENT_DATE) - INTERVAL '13 months')::date
           AND fecha_documento <  (date_trunc('month', CURRENT_DATE) - INTERVAL '11 months')::date)
      )

UNION ALL

-- BLOQUE 2: última foto de stock actual con detalle de producto (igual que hoy)
SELECT empresa, grupo_canal, NULL::date, NULL::varchar(20), NULL::integer, NULL::numeric,
       NULL::varchar(25), categoria, subcategoria, linea, linea_2, linea_2_2, sublinea, codigo_padre,
       descripcion_padre, coleccion, marca, marca_2, marca_temporada, proveedor, tipo, origen,
       localidad, temporada, temporada_2, temporada_3, sucursal, sucursal_2, sucursal_3, tipo_doc,
       serie, documento, serie_documento, unidades_hst_1, importe_subtotal_hst_1,
       costo_venta_neta_hst_1, unidades_hst, importe_subtotal_hst, costo_venta_neta_hst, unidades,
       importe_subtotal, costo_venta_neta, meta_venta, meta_contribucion, meta_venta_faro,
       meta_contribucion_faro, filtro_sss, filtro_sss2, mall, condicion, nro_tickets_hst_1,
       nro_tickets_hst, nro_tickets, dias_estancia, dias_estancia_tda, meses_estancia,
       meses_estancia_tda, inv_unds_act, inv_costo_act, inv_unds_hst, inv_costo_hst, pvp,
       vta_retail_act, vta_retail_hst, vta_retail_hst_1, cubicaje, area, conteo, conteo_hst,
       temporada_4, supervisor, sucursal_2_1, sucursal_3_1, "_mes_coleccion", largo, material,
       tdas_liquidadoras, flag_tickets_act, flag_tickets_hst, anio_analisis,
       NULL::varchar(30),
       0::numeric, 0::numeric, 0::numeric, 0::numeric, 0::numeric, 0::numeric
FROM public.automatizacion_pla_reporte_ventas
WHERE tipo_fila = 'stock_act'
  AND fecha_documento = (
      SELECT MAX(fecha_documento) FROM public.automatizacion_pla_reporte_ventas
      WHERE tipo_fila = 'stock_act' AND fecha_documento < CURRENT_DATE
  )

UNION ALL

-- BLOQUE 3: foto de stock del año anterior (igual que hoy)
SELECT empresa, grupo_canal, NULL::date, NULL::varchar(20), NULL::integer, NULL::numeric,
       NULL::varchar(25), categoria, subcategoria, linea, linea_2, linea_2_2, sublinea, codigo_padre,
       descripcion_padre, coleccion, marca, marca_2, marca_temporada, proveedor, tipo, origen,
       localidad, temporada, temporada_2, temporada_3, sucursal, sucursal_2, sucursal_3, tipo_doc,
       serie, documento, serie_documento, unidades_hst_1, importe_subtotal_hst_1,
       costo_venta_neta_hst_1, unidades_hst, importe_subtotal_hst, costo_venta_neta_hst, unidades,
       importe_subtotal, costo_venta_neta, meta_venta, meta_contribucion, meta_venta_faro,
       meta_contribucion_faro, filtro_sss, filtro_sss2, mall, condicion, nro_tickets_hst_1,
       nro_tickets_hst, nro_tickets, dias_estancia, dias_estancia_tda, meses_estancia,
       meses_estancia_tda, inv_unds_act, inv_costo_act, inv_unds_hst, inv_costo_hst, pvp,
       vta_retail_act, vta_retail_hst, vta_retail_hst_1, cubicaje, area, conteo, conteo_hst,
       temporada_4, supervisor, sucursal_2_1, sucursal_3_1, "_mes_coleccion", largo, material,
       tdas_liquidadoras, flag_tickets_act, flag_tickets_hst, anio_analisis,
       NULL::varchar(30),
       0::numeric, 0::numeric, 0::numeric, 0::numeric, 0::numeric, 0::numeric
FROM public.automatizacion_pla_reporte_ventas
WHERE tipo_fila = 'stock_hst'
  AND fecha_documento = (
      SELECT MAX(fecha_documento) FROM public.automatizacion_pla_reporte_ventas
      WHERE tipo_fila = 'stock_hst' AND fecha_documento < CURRENT_DATE - INTERVAL '1 year'
  )

UNION ALL

-- BLOQUE 4: stock diario resumido (nuevo)
SELECT
    'SMART BRANDS S.A.C.'::varchar(100)                                   AS empresa,
    (CASE WHEN s.tipo_almacen = 'MAYORISTA' THEN 'MAYORISTAS'
          ELSE 'PUBLICO GENERAL' END)::varchar                       AS grupo_canal,
    s.fecha                                                          AS fecha_documento,
    MAX(c.dia_semana)::varchar(20)                                       AS dia_semana,
    s.semana::integer                                                AS semana,
    EXTRACT(DAY FROM s.fecha)::numeric                               AS dia_equivalente,
    s.mes::varchar(25)                                                   AS mes,
    s.categoria::varchar(100)                                             AS categoria,
    NULL::varchar(100), NULL::varchar(100), NULL::varchar(100), NULL::text, -- subcategoria, linea, linea_2, linea_2_2
    NULL::varchar(100), NULL::varchar(20), NULL::varchar(300), NULL::text, -- sublinea, codigo_padre, descripcion_padre, coleccion
    s.marca::varchar(50)                                                 AS marca,
    (CASE WHEN s.marca IN ('FINA','WILLKA') THEN 'FINA'
          WHEN s.marca IN ('MENTHA TWENTIES','MENTHA & CHOCOLATE') THEN 'MENTHA & CHOCOLATE'
          ELSE s.marca END)::varchar(50)                                 AS marca_2,
    'NO CORRIENTE'::text                                             AS marca_temporada,
    NULL::varchar(100), NULL::varchar(100), NULL::varchar(100),      -- proveedor, tipo, origen
    s.localidad::text                                                AS localidad,
    NULL::varchar(100), NULL::varchar(100), NULL::varchar(100),      -- temporada, temporada_2, temporada_3
    s.sucursal::varchar(200)                                              AS sucursal,
    s.sucursal_2_1::varchar(200)                                          AS sucursal_2,
    s.sucursal_3_1::varchar(200)                                          AS sucursal_3,
    NULL::varchar(5), NULL::integer, NULL::integer, NULL::text,      -- tipo_doc, serie, documento, serie_documento
    0::numeric, 0::numeric, 0::numeric,                              -- unidades_hst_1, importe_subtotal_hst_1, costo_venta_neta_hst_1
    0::integer, 0::integer, 0::integer,                              -- unidades_hst, importe_subtotal_hst, costo_venta_neta_hst
    0::numeric, 0::numeric, 0::numeric,                              -- unidades, importe_subtotal, costo_venta_neta
    0::numeric, 0::numeric, 0::numeric, 0::numeric,                  -- meta_venta, meta_contribucion, meta_venta_faro, meta_contribucion_faro
    s.filtro_sss::text                                               AS filtro_sss,
    (CASE WHEN s.filtro_sss LIKE '%SSS%' THEN 'SSS'
          WHEN s.filtro_sss LIKE '%NUEVO%' THEN 'NUEVO'
          WHEN s.filtro_sss LIKE '%CIERRE%' THEN 'CIERRE'
          ELSE s.filtro_sss END)::text                               AS filtro_sss2,
    NULL::varchar(100), NULL::text,                                  -- mall, condicion
    0::bigint, 0::integer, 0::bigint,                                -- nro_tickets_hst_1, nro_tickets_hst, nro_tickets
    NULL::integer, NULL::integer, NULL::integer, NULL::integer,      -- dias_estancia, dias_estancia_tda, meses_estancia, meses_estancia_tda
    0::numeric, 0::numeric, 0::numeric, 0::numeric,                  -- inv_unds_act, inv_costo_act, inv_unds_hst, inv_costo_hst
    0::numeric,                                                      -- pvp
    0::numeric, 0::integer, 0::numeric,                              -- vta_retail_act, vta_retail_hst, vta_retail_hst_1
    0::numeric, 0::numeric, 0::integer, 0::integer,                  -- cubicaje, area, conteo, conteo_hst
    NULL::text, NULL::varchar(100),                                  -- temporada_4, supervisor
    s.sucursal_2_1::varchar(200)                                          AS sucursal_2_1,
    s.sucursal_3_1::varchar(200)                                          AS sucursal_3_1,
    NULL::text, NULL::text, NULL::text, NULL::text,                  -- _mes_coleccion, largo, material, tdas_liquidadoras
    0::integer, 0::integer,                                          -- flag_tickets_act, flag_tickets_hst
    s.anio::integer                                                  AS anio_analisis,
    s.tipo_almacen::varchar(30)                                          AS tipo_almacen,
    SUM(s.inv_unds)                                                  AS inv_unds_dia,
    SUM(s.inv_costo)                                                 AS inv_costo_dia,
    SUM(CASE WHEN s.es_cierre_semana THEN s.inv_unds  ELSE 0 END)    AS inv_unds_cierre_sem,
    SUM(CASE WHEN s.es_cierre_semana THEN s.inv_costo ELSE 0 END)    AS inv_costo_cierre_sem,
    SUM(CASE WHEN s.es_cierre_mes    THEN s.inv_unds  ELSE 0 END)    AS inv_unds_cierre_mes,
    SUM(CASE WHEN s.es_cierre_mes    THEN s.inv_costo ELSE 0 END)    AS inv_costo_cierre_mes
FROM public.automatizacion_vw_stock_diario s
LEFT JOIN public.datamart_calendario c ON c.fecha = s.fecha
WHERE s.fecha >= (date_trunc('month', CURRENT_DATE) - INTERVAL '1 month')::date
GROUP BY s.fecha, s.semana, s.mes, s.anio, s.tipo_almacen, s.sucursal,
         s.sucursal_2_1, s.sucursal_3_1, s.localidad, s.filtro_sss, s.marca, s.categoria;


-- ============================================================================
-- VALIDACIÓN (solo lectura)
-- ============================================================================

-- V1. Totales de los bloques 1 a 3 (ventana de dos meses). Deben coincidir con
--     las mismas sumas sobre la consulta original filtrada a esos meses.
/*
SELECT COUNT(*) AS filas,
       SUM(importe_subtotal) AS venta, SUM(importe_subtotal_hst_1) AS venta_hst,
       SUM(meta_venta) AS meta, SUM(inv_unds_act) AS inv_act, SUM(inv_unds_hst) AS inv_hst
FROM automatizacion_vw_excel_reporte
WHERE tipo_almacen IS NULL;
*/

-- V2. Bloque 4: stock por tipo de almacén al cierre de cada mes.
/*
SELECT mes, tipo_almacen, SUM(inv_unds_cierre_mes) AS unds, ROUND(SUM(inv_costo_cierre_mes)) AS costo
FROM automatizacion_vw_excel_reporte
WHERE tipo_almacen IS NOT NULL
GROUP BY 1,2 ORDER BY MIN(fecha_documento), 2;
*/
