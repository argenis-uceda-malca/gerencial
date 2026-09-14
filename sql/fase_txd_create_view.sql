-- =============================================================================
-- FASE TXD - VISTA PARA EXCEL
-- =============================================================================
-- Proyecto : Smart Brands Gerencial
-- BD       : smartanalytic @ 172.16.1.23:5432
-- Objetivo : Vista consolidada VENTAS + TXD para Excel.
--            Excel querya la vista como tabla normal.
-- =============================================================================

DROP VIEW IF EXISTS "smartanalytic"."public"."automatizacion_vista_reporte_txd";

CREATE VIEW "smartanalytic"."public"."automatizacion_vista_reporte_txd" AS
SELECT
    origen                  AS origen_reporte,
    t_txd                   AS canal_txd,
    fecha                   AS fecha_documento,
    dia_semana,
    semana,
    dia_equivalente,
    mes,
    categoria,
    subcategoria,
    linea_2,
    linea_2_2,
    sublinea,
    descripcion_padre,
    coleccion,
    marca,
    v_marca_2              AS marca_2,
    v_marca_temporada      AS marca_temporada,
    v_tipo                  AS tipo,
    v_origen               AS origen,
    v_localidad            AS localidad,
    temporada,
    v_temporada_2          AS temporada_2,
    v_temporada_3          AS temporada_3,
    v_temporada_4          AS temporada_4,
    canal                   AS grupo_canal,
    corner                  AS sucursal,
    COALESCE(v_sucursal_2_1, t_txd || ' - ' || marca) AS sucursal_2_1,
    COALESCE(v_sucursal_3_1, t_txd)                   AS sucursal_3_1,
    v_codigo_padre         AS codigo_padre,
    v_condicion            AS condicion,
    v_mall                  AS mall,
    v_mes_coleccion       AS "_mes_coleccion",
    v_tdas_liquidadoras   AS tdas_liquidadoras,
    filtro_sss2,
    v_filtro_sss           AS filtro_sss,
    cubicaje,
    area,
    v_dias_estancia        AS dias_estancia,
    v_dias_estancia_tda    AS dias_estancia_tda,
    CASE WHEN origen = 'VENTAS' AND tipo_fila = 'ventas_act'  THEN vta_act              ELSE NULL END AS importe_subtotal_ventas,
    CASE WHEN origen = 'TXD'  AND tipo_fila = 'VENTA'          THEN vta_act              ELSE NULL END AS importe_subtotal_txd,
    CASE WHEN origen = 'VENTAS' AND tipo_fila = 'ventas_hst'   THEN vta_hst              ELSE NULL END AS importe_subtotal_hst_1_ventas,
    CASE WHEN origen = 'TXD'  AND tipo_fila = 'VENTA'          THEN vta_hst              ELSE NULL END AS importe_subtotal_hst_1_txd,
    CASE WHEN origen = 'VENTAS' AND tipo_fila = 'ventas_act'   THEN vta_unds_act         ELSE NULL END AS unidades_ventas,
    CASE WHEN origen = 'TXD'  AND tipo_fila = 'VENTA'          THEN vta_unds_act         ELSE NULL END AS unidades_txd,
    CASE WHEN origen = 'VENTAS' AND tipo_fila = 'ventas_hst'   THEN vta_unds_hst         ELSE NULL END AS unidades_hst_1_ventas,
    CASE WHEN origen = 'TXD'  AND tipo_fila = 'VENTA'          THEN vta_unds_hst         ELSE NULL END AS unidades_hst_1_txd,
    CASE WHEN origen = 'VENTAS' AND tipo_fila = 'ventas_act'   THEN costo_act            ELSE NULL END AS costo_venta_neta_ventas,
    CASE WHEN origen = 'TXD'  AND tipo_fila = 'VENTA'          THEN costo_act            ELSE NULL END AS costo_venta_neta_txd,
    CASE WHEN origen = 'VENTAS' AND tipo_fila = 'ventas_hst'   THEN costo_hst            ELSE NULL END AS costo_venta_neta_hst_1_ventas,
    CASE WHEN origen = 'TXD'  AND tipo_fila = 'VENTA'          THEN costo_hst            ELSE NULL END AS costo_venta_neta_hst_1_txd,
    CASE WHEN origen = 'VENTAS' AND tipo_fila = 'metas_std'    THEN meta                 ELSE NULL END AS meta_venta_ventas,
    CASE WHEN origen = 'TXD'  AND tipo_fila = 'META'           THEN meta                 ELSE NULL END AS meta_venta_txd,
    CASE WHEN origen = 'VENTAS' AND tipo_fila = 'metas_std'    THEN meta_contribucion    ELSE NULL END AS meta_contribucion_ventas,
    CASE WHEN origen = 'TXD'  AND tipo_fila = 'META'           THEN meta_contribucion    ELSE NULL END AS meta_contribucion_txd,
    vta_retail_act,
    vta_retail_hst,
    nro_tickets,
    CASE WHEN origen = 'VENTAS' AND tipo_fila = 'ventas_hst'   THEN v_nro_tickets_hst_1  ELSE NULL END AS nro_tickets_hst_1_ventas,
    CASE WHEN origen = 'TXD'  AND tipo_fila = 'VENTA'          THEN v_nro_tickets_hst_1  ELSE NULL END AS nro_tickets_hst_1_txd,
    v_flag_tickets_act      AS flag_tickets_act_ventas,
    v_flag_tickets_hst      AS flag_tickets_hst_ventas,
    CAST(NULL AS integer)                    AS flag_tickets_act_txd,
    CAST(NULL AS integer)                    AS flag_tickets_hst_txd,
    CASE WHEN origen = 'VENTAS' AND tipo_fila = 'stock_act'    THEN inv_unds_act         ELSE NULL END AS inv_unds_act_ventas,
    CASE WHEN origen = 'TXD'  AND tipo_fila = 'VENTA'          THEN inv_unds_act         ELSE NULL END AS inv_unds_act_txd,
    CASE WHEN origen = 'VENTAS' AND tipo_fila = 'stock_act'    THEN inv_unds_hst         ELSE NULL END AS inv_unds_hst_ventas,
    CASE WHEN origen = 'TXD'  AND tipo_fila = 'VENTA'          THEN inv_unds_hst         ELSE NULL END AS inv_unds_hst_txd,
    CASE WHEN origen = 'VENTAS' AND tipo_fila = 'stock_act'    THEN inv_costo_act        ELSE NULL END AS inv_costo_act_ventas,
    CASE WHEN origen = 'TXD'  AND tipo_fila = 'VENTA'          THEN inv_costo_act        ELSE NULL END AS inv_costo_act_txd,
    CASE WHEN origen = 'VENTAS' AND tipo_fila = 'stock_act'    THEN inv_costo_hst        ELSE NULL END AS inv_costo_hst_ventas,
    CASE WHEN origen = 'TXD'  AND tipo_fila = 'VENTA'          THEN inv_costo_hst        ELSE NULL END AS inv_costo_hst_txd
FROM "smartanalytic"."public"."automatizacion_pla_reporte_consolidado"
WHERE (fecha_documento BETWEEN '2025-08-01' AND '2025-08-31'
    OR fecha_documento BETWEEN '2026-08-01' AND '2026-08-31');
