-- Fix: reemplaza mes::text por nombre de mes en el bloque TXD del SP consolidado
-- Problema: TXD insertaba "8" mientras VENTAS insertaba "Agosto"
-- Ejecutar en: smartanalytic (servidor 172.16.1.23)

CREATE OR REPLACE FUNCTION public.automatizacion_sp_reporte_consolidado()
RETURNS integer
LANGUAGE plpgsql
AS $function$
DECLARE
    v_inicio  timestamp := clock_timestamp();
    v_filas   integer := 0;
    v_total   integer := 0;
BEGIN
    TRUNCATE TABLE automatizacion_pla_reporte_consolidado;

    -- -----------------------------------------------------------------
    -- ORIGEN = VENTAS
    -- -----------------------------------------------------------------
    INSERT INTO automatizacion_pla_reporte_consolidado (
        origen, tipo_fila, fecha, dia_semana, semana, dia_equivalente, mes,
        marca, canal, corner, categoria, subcategoria, linea, linea_2, linea_2_2,
        sublinea, descripcion_padre, coleccion, temporada, supervisor, filtro_sss2,
        pvp, meta, meta_contribucion, gm_meta, cubicaje, area,
        vta_retail_act, vta_retail_hst,
        vta_act, vta_hst, vta_unds_act, vta_unds_hst, costo_act, costo_hst,
        inv_unds_act, inv_unds_hst, inv_costo_act, inv_costo_hst, nro_tickets,
        v_empresa, v_codigo_padre, v_marca_2, v_marca_temporada, v_proveedor,
        v_tipo, v_origen, v_localidad, v_temporada_2, v_temporada_3,
        v_sucursal_2, v_sucursal_3, v_tipo_doc, v_serie, v_documento, v_serie_documento,
        v_unidades_hst, v_importe_subtotal_hst, v_costo_venta_neta_hst,
        v_meta_venta_faro, v_meta_contribucion_faro, v_filtro_sss, v_mall, v_condicion,
        v_nro_tickets_hst_1, v_nro_tickets_hst, v_dias_estancia, v_dias_estancia_tda,
        v_meses_estancia, v_meses_estancia_tda, v_vta_retail_hst_1, v_conteo, v_conteo_hst,
        v_temporada_4, v_sucursal_2_1, v_sucursal_3_1, v_mes_coleccion, v_largo,
        v_material, v_tdas_liquidadoras, v_flag_tickets_act, v_flag_tickets_hst, v_fecha_carga
    )
    SELECT
        'VENTAS', tipo_fila, fecha_documento, dia_semana, semana, dia_equivalente, mes,
        marca, grupo_canal, sucursal, categoria, subcategoria, linea, linea_2, linea_2_2,
        sublinea, descripcion_padre, coleccion, temporada, supervisor, filtro_sss2,
        pvp, meta_venta, meta_contribucion, NULL, cubicaje, area,
        vta_retail_act, vta_retail_hst,
        importe_subtotal, importe_subtotal_hst_1, unidades, unidades_hst_1,
        costo_venta_neta, costo_venta_neta_hst_1,
        inv_unds_act, inv_unds_hst, inv_costo_act, inv_costo_hst, nro_tickets,
        empresa, codigo_padre, marca_2, marca_temporada, proveedor,
        tipo, origen, localidad, temporada_2, temporada_3,
        sucursal_2, sucursal_3, tipo_doc, serie, documento, serie_documento,
        unidades_hst, importe_subtotal_hst, costo_venta_neta_hst,
        meta_venta_faro, meta_contribucion_faro, filtro_sss, mall, condicion,
        nro_tickets_hst_1, nro_tickets_hst, dias_estancia, dias_estancia_tda,
        meses_estancia, meses_estancia_tda, vta_retail_hst_1, conteo, conteo_hst,
        temporada_4, sucursal_2_1, sucursal_3_1, _mes_coleccion, largo,
        material, tdas_liquidadoras, flag_tickets_act, flag_tickets_hst, fecha_carga
    FROM automatizacion_pla_reporte_ventas;

    GET DIAGNOSTICS v_filas = ROW_COUNT;
    v_total := v_total + v_filas;

    -- -----------------------------------------------------------------
    -- ORIGEN = TXD
    -- -----------------------------------------------------------------
    INSERT INTO automatizacion_pla_reporte_consolidado (
        origen, tipo_fila, fecha, dia_semana, semana, dia_equivalente, mes,
        marca, canal, corner, categoria, subcategoria, linea, linea_2, linea_2_2,
        sublinea, descripcion_padre, coleccion, temporada, supervisor, filtro_sss2,
        pvp, meta, meta_contribucion, gm_meta, cubicaje, area,
        vta_retail_act, vta_retail_hst,
        vta_act, vta_hst, vta_unds_act, vta_unds_hst, costo_act, costo_hst,
        rebate_act, rebate_hst, inv_unds_act, inv_unds_hst, inv_soles_act, inv_soles_hst,
        t_txd, t_anio, t_dia, t_sku_txd, t_sku_padre_txd, t_desc_padre_txd,
        t_sku_sb, t_sku_padre_sb, t_color, t_talla, t_tipo_producto,
        t_temporada_txd, t_temporada_txd2, t_temporada_sb, t_temporada_sb2,
        t_costo_si_act, t_vta_soles_si_act, t_costo_si_hst, t_vta_soles_si_hst,
        t_temporada_, t_mes_coleccion_, t_zona, t_coleccion_2, t_coleccion_3
    )
    SELECT
        'TXD', tipo_fila, fecha, dia_semana, semana, dia_equivalente,
        -- FIX: nombre de mes consistente con VENTAS (antes era mes::text = "8")
        CASE mes
            WHEN 1  THEN 'Enero'
            WHEN 2  THEN 'Febrero'
            WHEN 3  THEN 'Marzo'
            WHEN 4  THEN 'Abril'
            WHEN 5  THEN 'Mayo'
            WHEN 6  THEN 'Junio'
            WHEN 7  THEN 'Julio'
            WHEN 8  THEN 'Agosto'
            WHEN 9  THEN 'Setiembre'
            WHEN 10 THEN 'Octubre'
            WHEN 11 THEN 'Noviembre'
            WHEN 12 THEN 'Diciembre'
            ELSE mes::text
        END,
        marca, canal, corner, categoria, subcategoria, linea, linea_2, linea_2_2,
        sublinea, descripcion_padre, coleccion, temporada_txd, supervisor, filtro_sss2,
        pvp, meta, meta_contribucion, gm_meta, cubicaje, area,
        vta_retail_act, vta_retail_hst,
        vta_act, vta_hst, vta_unds_act, vta_unds_hst, vta_costo_act, vta_costo_hst,
        rebate_act, rebate_hst, inv_unds_act, inv_unds_hst, inv_soles_act, inv_soles_hst,
        txd, anio, dia, sku_txd, sku_padre_txd, desc_padre_txd,
        sku_sb, sku_padre_sb, color, talla, tipo_producto,
        temporada_txd, temporada_txd2, temporada_sb, temporada_sb2,
        costo_si_act, vta_soles_si_act, costo_si_hst, vta_soles_si_hst,
        temporada_, mes_coleccion_, zona, coleccion_2, coleccion_3
    FROM automatizacion_pla_reporte_txd;

    GET DIAGNOSTICS v_filas = ROW_COUNT;
    v_total := v_total + v_filas;

    -- -----------------------------------------------------------------
    -- Log
    -- -----------------------------------------------------------------
    INSERT INTO automatizacion_control_ejecucion_txd
        (tipo_ejecucion, filas_insertadas, duracion_segundos, estado)
    VALUES
        ('CONSOLIDADO', v_total,
         EXTRACT(EPOCH FROM (clock_timestamp() - v_inicio)), 'OK');

    RETURN v_total;

EXCEPTION WHEN OTHERS THEN
    INSERT INTO automatizacion_control_ejecucion_txd
        (tipo_ejecucion, filas_insertadas, duracion_segundos, estado, mensaje_error)
    VALUES
        ('CONSOLIDADO', v_total,
         EXTRACT(EPOCH FROM (clock_timestamp() - v_inicio)), 'ERROR', SQLERRM);
    RAISE;
END;
$function$;
