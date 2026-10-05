-- =====================================================================
-- fase_monitoreo_duplicados.sql
-- Monitoreo periodico de duplicados en automatizacion_pla_reporte_ventas
-- Se ejecuta en la base smartanalytic. Es de solo lectura (SELECT).
-- Uso sugerido: despues de cada corrida ETL y de forma mensual.
-- =====================================================================

-- 1) Duplicados exactos por tipo_fila (misma firma en todas las columnas,
--    ignorando fecha_carga). Si el bloque no aparece => sin duplicados.
SELECT tipo_fila,
       count(*)                                                      AS total,
       count(DISTINCT md5(concat_ws('|',
         empresa, grupo_canal, fecha_documento, dia_semana, semana, dia_equivalente,
         mes, categoria, subcategoria, linea, linea_2, linea_2_2, sublinea,
         codigo_padre, descripcion_padre, coleccion, marca, marca_2, marca_temporada,
         proveedor, tipo, origen, localidad, temporada, temporada_2, temporada_3,
         sucursal, sucursal_2, sucursal_3, tipo_doc, serie, documento, serie_documento,
         unidades_hst_1, importe_subtotal_hst_1, costo_venta_neta_hst_1,
         unidades_hst, importe_subtotal_hst, costo_venta_neta_hst,
         unidades, importe_subtotal, costo_venta_neta,
         meta_venta, meta_contribucion, meta_venta_faro, meta_contribucion_faro,
         filtro_sss, filtro_sss2, mall, condicion,
         nro_tickets_hst_1, nro_tickets_hst, nro_tickets,
         dias_estancia, dias_estancia_tda, meses_estancia, meses_estancia_tda,
         inv_unds_act, inv_costo_act, inv_unds_hst, inv_costo_hst,
         pvp, vta_retail_act, vta_retail_hst, vta_retail_hst_1,
         cubicaje, area, conteo, conteo_hst,
         temporada_4, supervisor, sucursal_2_1, sucursal_3_1, _mes_coleccion,
         largo, material, tdas_liquidadoras, flag_tickets_act, flag_tickets_hst,
         tipo_fila)))                                               AS uniq,
       count(*) - count(DISTINCT md5(concat_ws('|',
         empresa, grupo_canal, fecha_documento, dia_semana, semana, dia_equivalente,
         mes, categoria, subcategoria, linea, linea_2, linea_2_2, sublinea,
         codigo_padre, descripcion_padre, coleccion, marca, marca_2, marca_temporada,
         proveedor, tipo, origen, localidad, temporada, temporada_2, temporada_3,
         sucursal, sucursal_2, sucursal_3, tipo_doc, serie, documento, serie_documento,
         unidades_hst_1, importe_subtotal_hst_1, costo_venta_neta_hst_1,
         unidades_hst, importe_subtotal_hst, costo_venta_neta_hst,
         unidades, importe_subtotal, costo_venta_neta,
         meta_venta, meta_contribucion, meta_venta_faro, meta_contribucion_faro,
         filtro_sss, filtro_sss2, mall, condicion,
         nro_tickets_hst_1, nro_tickets_hst, nro_tickets,
         dias_estancia, dias_estancia_tda, meses_estancia, meses_estancia_tda,
         inv_unds_act, inv_costo_act, inv_unds_hst, inv_costo_hst,
         pvp, vta_retail_act, vta_retail_hst, vta_retail_hst_1,
         cubicaje, area, conteo, conteo_hst,
         temporada_4, supervisor, sucursal_2_1, sucursal_3_1, _mes_coleccion,
         largo, material, tdas_liquidadoras, flag_tickets_act, flag_tickets_hst,
         tipo_fila)))                                               AS exceso
FROM public.automatizacion_pla_reporte_ventas
GROUP BY tipo_fila
HAVING count(*) <> count(DISTINCT md5(concat_ws('|',
  empresa, grupo_canal, fecha_documento, dia_semana, semana, dia_equivalente,
  mes, categoria, subcategoria, linea, linea_2, linea_2_2, sublinea,
  codigo_padre, descripcion_padre, coleccion, marca, marca_2, marca_temporada,
  proveedor, tipo, origen, localidad, temporada, temporada_2, temporada_3,
  sucursal, sucursal_2, sucursal_3, tipo_doc, serie, documento, serie_documento,
  unidades_hst_1, importe_subtotal_hst_1, costo_venta_neta_hst_1,
  unidades_hst, importe_subtotal_hst, costo_venta_neta_hst,
  unidades, importe_subtotal, costo_venta_neta,
  meta_venta, meta_contribucion, meta_venta_faro, meta_contribucion_faro,
  filtro_sss, filtro_sss2, mall, condicion,
  nro_tickets_hst_1, nro_tickets_hst, nro_tickets,
  dias_estancia, dias_estancia_tda, meses_estancia, meses_estancia_tda,
  inv_unds_act, inv_costo_act, inv_unds_hst, inv_costo_hst,
  pvp, vta_retail_act, vta_retail_hst, vta_retail_hst_1,
  cubicaje, area, conteo, conteo_hst,
  temporada_4, supervisor, sucursal_2_1, sucursal_3_1, _mes_coleccion,
  largo, material, tdas_liquidadoras, flag_tickets_act, flag_tickets_hst,
  tipo_fila)))
ORDER BY tipo_fila;

-- 2) Auxiliar: duplicados por (fecha_documento, sucursal) en cada bloque
--    (detecta multiplicidad anomala ademas de la firma exacta).
SELECT tipo_fila,
       to_char(fecha_documento, 'YYYY-MM')        AS mes,
       count(*)                                   AS filas,
       count(DISTINCT (fecha_documento, sucursal)) AS grupos
FROM public.automatizacion_pla_reporte_ventas
WHERE tipo_fila IN ('ventas_act', 'ventas_hst', 'metas_std', 'poken_act', 'poken_hst')
GROUP BY tipo_fila, to_char(fecha_documento, 'YYYY-MM')
HAVING count(*) > count(DISTINCT (fecha_documento, sucursal))
ORDER BY tipo_fila, mes;