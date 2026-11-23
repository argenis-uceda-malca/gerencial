-- =====================================================================
-- fase_fix_metas_std_mes_nuevo.sql   (2026-11-18)
-- Problema: automatizacion_sp_reporte_ventas solo recargaba el bloque
--   metas_std con p_forzar_todo=TRUE o si estaba vacío. Las corridas cada
--   30 min usan FALSE, así que las cuotas de un mes nuevo nunca entraban
--   (noviembre 2026 no aparecía en /dashboard/reporte).
-- Fix: también recarga si MAX(fecha) de datamart_ventas_cuota_postgre
--   es mayor que la última fecha de metas_std en el reporte.
-- Único cambio respecto a la versión vigente: la condición IF de metas_std.
-- =====================================================================

-- 0) Verificación previa (esperado: src_max 2026-11-30 > rep_max 2026-10-31)
SELECT (SELECT MAX(fecha::date) FROM datamart_ventas_cuota_postgre) AS src_max,
       (SELECT MAX(fecha_documento) FROM automatizacion_pla_reporte_ventas WHERE tipo_fila='metas_std') AS rep_max;

-- 1) Reemplazar la función
CREATE OR REPLACE FUNCTION public.automatizacion_sp_reporte_ventas(p_fecha_ini date DEFAULT NULL::date, p_fecha_fin date DEFAULT NULL::date, p_forzar_todo boolean DEFAULT false)
 RETURNS void
 LANGUAGE plpgsql
AS $function$


DECLARE


    v_fecha_ini         DATE;


    v_fecha_fin         DATE;


    v_fecha_ini_hst     DATE;


    v_fecha_fin_hst     DATE;


    v_anio_act          INTEGER;


    v_anio_ant          INTEGER;


    v_ts_inicio         TIMESTAMP;


BEGIN


    PERFORM pg_advisory_xact_lock(613200);


    v_ts_inicio := clock_timestamp();


    v_fecha_ini     := COALESCE(p_fecha_ini, current_date - INTERVAL '7 days');


    v_fecha_fin     := COALESCE(p_fecha_fin, current_date);


    v_fecha_ini_hst := v_fecha_ini - INTERVAL '1 year';


    v_fecha_fin_hst := v_fecha_fin - INTERVAL '1 year';


    v_anio_act := EXTRACT(YEAR FROM current_date)::INTEGER;


    v_anio_ant := v_anio_act - 1;





    





    DELETE FROM public.automatizacion_pla_reporte_ventas


    WHERE tipo_fila = 'ventas_act'


      AND fecha_documento BETWEEN v_fecha_ini AND v_fecha_fin;





    INSERT INTO public.automatizacion_pla_reporte_ventas (


        empresa,


        grupo_canal,


        fecha_documento,


        dia_semana,


        semana,


        dia_equivalente,


        mes,


        categoria,


        subcategoria,


        linea,


        linea_2,


        linea_2_2,


        sublinea,


        codigo_padre,


        descripcion_padre,


        coleccion,


        marca,


        marca_2,


        marca_temporada,


        proveedor,


        tipo,


        origen,


        localidad,


        temporada,


        temporada_2,


        temporada_3,


        sucursal,


        sucursal_2,


        sucursal_3,


        tipo_doc,


        serie,


        documento,


        serie_documento,


        unidades_hst_1,


        importe_subtotal_hst_1,


        costo_venta_neta_hst_1,


        unidades_hst,


        importe_subtotal_hst,


        costo_venta_neta_hst,


        unidades,


        importe_subtotal,


        costo_venta_neta,


        meta_venta,


        meta_contribucion,


        meta_venta_faro,


        meta_contribucion_faro,


        filtro_sss,


        filtro_sss2,


        mall,


        condicion,


        nro_tickets_hst_1,


        nro_tickets_hst,


        nro_tickets,


        dias_estancia,


        dias_estancia_tda,


        meses_estancia,


        meses_estancia_tda,


        inv_unds_act,


        inv_costo_act,


        inv_unds_hst,


        inv_costo_hst,


        pvp,


        vta_retail_act,


        vta_retail_hst,


        vta_retail_hst_1,


        cubicaje,


        area,


        conteo,


        conteo_hst,


        temporada_4,


        supervisor,


        sucursal_2_1,


        sucursal_3_1,


        _mes_coleccion,


        largo,


        material,


        tdas_liquidadoras,


        flag_tickets_act,


        flag_tickets_hst,


        tipo_fila


    )


    SELECT


        v.empresa,


        v.grupo_canal,


        v.fecha_documento,


        c.dia_semana,


        c.semana,


        CAST(to_char(c.fecha,'dd') AS NUMERIC),


        v.mes,


        v.categoria,


        v.subcategoria,


        v.linea,


        CASE WHEN v.linea IN ('BERMUDA','SHORT') THEN 'SHORT'


            WHEN v.linea IN ('BUFANDA','CHALINA','PASHMINA','PASHIMINA') THEN 'PASHMINA'


            WHEN v.linea IN ('BVD','TOP','POLO') THEN 'POLO'


            WHEN v.linea IN ('JUMPER','ENTERIZO') THEN 'ENTERIZO'


            WHEN v.linea IN ('CARDIGAN','CHOMPA') THEN 'CHOMPA'


            ELSE v.linea END,


        CASE WHEN v.linea IN ('BERMUDA','SHORT','FALDA') THEN 'SHORT+FALDA'


            WHEN v.linea IN ('BUFANDA','CHALINA','PASHMINA','PASHIMINA') THEN 'PASHMINA'


            WHEN v.linea IN ('BVD','TOP','POLO','BLUSA') THEN 'BLUSA+POLO'


            WHEN v.linea IN ('JUMPER','ENTERIZO','VESTIDO') THEN 'VESTIDO+ENTERIZO'


            WHEN v.linea IN ('CARDIGAN','CHOMPA','CASACA','CHALECO','ABRIGO') THEN 'CASACA+CHOMPA+ABRIGO'


            WHEN v.linea IN ('PANTALON','JEAN') THEN 'PANTALON+JEAN'


            ELSE 'OTROS' END,


        v.sublinea,


        COALESCE(eq.codigo_padre_destino, v.codigo_padre),


        COALESCE(pf.producto, v.descripcion_padre),


        trim(v.coleccion),


        v.marca,


        CASE WHEN v.marca IN ('FINA','WILLKA') THEN 'FINA'


            WHEN v.marca IN ('MENTHA TWENTIES','MENTHA & CHOCOLATE') THEN 'MENTHA & CHOCOLATE'


            ELSE v.marca END,


        CASE


            WHEN v.sucursal = 'MCH ASIA' AND v.marca = 'MENTHA & CHOCOLATE' THEN 'MENTHA & CHOCOLATE'


            WHEN v.sucursal IN ('EXIT CAMINOS DEL INCA','EXIT EL ROBLE','EXIT JOCKEY PLAZA','EXIT LARCOMAR',


                'EXIT LR SAN BORJA','EXIT MA SANTA ANITA','EXIT MALL DEL SUR','EXIT MP AREQUIPA',


                'EXIT MP TRUJILLO','EXIT OP ANGAMOS','EXIT OP PIURA','EXIT PLAZA LIMA SUR',


                'EXIT PLAZA NORTE','EXIT RP CHICLAYO','EXIT RP CUSCO','EXIT RP PIURA',


                'EXIT RP PRIMAVERA','EXIT RP SALAVERRY','EXIT RP TRUJILLO','EXIT ONLINE',


                'EXIT LA MOLINA','MCH ASIA') AND v.marca = 'EXIT' THEN 'EXIT'


            WHEN v.sucursal IN ('MILK MP TRUJILLO','MILK JOCKEY PLAZA','MILK RP CHICLAYO',


                'MILK OP PIURA','MILK PLAZA NORTE','MILK LR SAN BORJA','MILK RP PIURA',


                'MILK RP TRUJILLO','MILK MALL DEL SUR','MILK MP AREQUIPA','MILK LR BRASIL',


                'MILK MGP CHORRILLOS','MILK MA SANTA ANITA','MILK OP ANGAMOS','MILK EL ROBLE',


                'MILK RP CENTRO CIVICO','EXIT RP CUSCO','KORDA MA IQUITOS')


                AND v.marca = 'MILK' THEN 'MILK'


            WHEN v.sucursal IN ('OUTLET VULCANO II','OUTLET VULCANO MAYORISTA','OUTLET MINKA',


                'OUTLET OP ATOCONGO','OUTLET SAN JUAN DE LURIGANCHO','OUTLET CHIMBOTE',


                'OUTLET SANTA CLARA','OUTLET EL DERBY','OUTLET DINET','OUTLET CHORRILLOS',


                'OUTLET ARAUCO AREQUIPA','OUTLET PLAZA VEA LURIN')


                AND v.marca = 'MILK'


                AND v.temporada IN ('PRIMAVERA 2025','VERANO 2025','INVIERNO 2024',


                    'OTONO 2024','BASICO TODAS TEMPORADAS','VERANO 2026') THEN 'MILK'


            WHEN v.sucursal IN ('MILK MP TRUJILLO','MILK JOCKEY PLAZA','MILK RP CHICLAYO',


                'MILK OP PIURA','MILK PLAZA NORTE','MILK LR SAN BORJA','MILK RP PIURA',


                'MILK RP TRUJILLO','MILK MALL DEL SUR','MILK MP AREQUIPA','MILK LR BRASIL',


                'MILK MGP CHORRILLOS','MILK MA SANTA ANITA','MILK OP ANGAMOS','MILK EL ROBLE',


                'MILK RP CENTRO CIVICO','KORDA MA IQUITOS') AND v.marca = 'KORDA' THEN 'KORDA'


            WHEN v.sucursal IN ('OUTLET VULCANO II','OUTLET VULCANO MAYORISTA','OUTLET MINKA',


                'OUTLET OP ATOCONGO','OUTLET SAN JUAN DE LURIGANCHO','OUTLET CHIMBOTE',


                'OUTLET SANTA CLARA','OUTLET EL DERBY','OUTLET DINET','OUTLET CHORRILLOS',


                'OUTLET ARAUCO AREQUIPA','OUTLET PLAZA VEA LURIN')


                AND v.marca = 'KORDA'


                AND v.temporada IN ('PRIMAVERA 2025','VERANO 2025','INVIERNO 2024',


                    'OTONO 2024','BASICO TODAS TEMPORADAS','VERANO 2026') THEN 'KORDA'


            WHEN v.sucursal IN ('MILK RP CHICLAYO','MILK LR SAN BORJA','MILK MP AREQUIPA',


                'MILK MGP CHORRILLOS','MILK ONLINE','MILK PLAZA NORTE','MILK RP TRUJILLO',


                'MILK RP PIURA') AND v.marca = 'BLUES BY MILK' THEN 'BLUES BY MILK'


            ELSE 'NO CORRIENTE' END,


        v.proveedor,


        v.tipo_producto,


        v.origen,


        COALESCE(cl.localidad_override,


            CASE WHEN v.sucursal LIKE '%TRUJILLO' OR v.sucursal LIKE '%PIURA%'


                   OR v.sucursal LIKE '%CHICLAYO%' OR v.sucursal LIKE '%IQUITOS%' THEN 'Norte'


                 WHEN v.sucursal LIKE '%CUSCO' OR v.sucursal LIKE '%AREQUIPA%' THEN 'Sur'


                 ELSE 'Lima' END),


        v.temporada,


        CASE WHEN v.temporada LIKE 'PRIMAVERA%' OR v.temporada LIKE 'VERANO%'


                THEN 'PV ' || right(v.temporada,4)


            WHEN v.temporada LIKE 'OTONO%' OR v.temporada LIKE 'INVIERNO%'


                THEN 'OI ' || right(v.temporada,4)


            ELSE v.temporada END,


        CASE WHEN v.temporada LIKE 'PRIMAVERA%' OR v.temporada LIKE 'VERANO%' THEN 'PV'


            WHEN v.temporada LIKE 'OTONO%' OR v.temporada LIKE 'INVIERNO%' THEN 'OI'


            WHEN v.temporada LIKE 'BASICO TODAS TEMPORADA%' THEN 'BTT'


            ELSE v.temporada END,


        COALESCE(sc.sucursal_destino, v.sucursal),


        CASE WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%ONLINE%' THEN 'WEB'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MCH%' THEN 'BOUTIQUES MCH'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MILK%' THEN 'BOUTIQUES MILK'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%FINA%' THEN 'BOUTIQUES FINA'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


            ELSE COALESCE(sc.sucursal_destino, v.sucursal) END,


        COALESCE(sc.sucursal_3_destino, CASE


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%ONLINE%' THEN 'WEB'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MCH%' OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%EXIT%'


              OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MILK%' OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%FINA%'


              OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%KORDA%' OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%BLUES%' THEN 'BOUTIQUES'


            ELSE COALESCE(sc.sucursal_destino, v.sucursal) END),


        v.tipo_doc,


        v.serie,


        v.documento,


        v.serie_documento,


        0,


        0,


        0,


        0,


        0,


        0,


        SUM(v.unidades),


        SUM(v.importe_subtotal),


        SUM(v.costo_venta_neta),


        0,


        0,


        0,


        0,


        tp.filtro_sss,


        CASE WHEN tp.filtro_sss LIKE '%SSS%' THEN 'SSS' WHEN tp.filtro_sss LIKE '%NUEVO%' THEN 'NUEVO' WHEN tp.filtro_sss LIKE '%CIERRE%' THEN 'CIERRE' ELSE tp.filtro_sss END,


        v.mall,


        v.condicion,


        0,


        0,


        ROW_NUMBER() OVER (PARTITION BY v.nro_tickets ORDER BY v.nro_tickets),


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        v.pvp,


        SUM(COALESCE(v.unidades*v.pvp,0)),


        0,


        0,


        0,


        0,


        0,


        0,


        CASE WHEN right(v.temporada,2) IN ('21','22','23','24','25','26')


                THEN CASE WHEN v.temporada LIKE 'PRIMAVERA%' OR v.temporada LIKE 'VERANO%'


                         THEN 'PV ' || right(v.temporada,2)


                     WHEN v.temporada LIKE 'OTONO%' OR v.temporada LIKE 'INVIERNO%'


                         THEN 'OI ' || right(v.temporada,2)


                     ELSE 'TT' END


            WHEN v.temporada LIKE 'BASICO TODAS TEMPORADA%' THEN 'TT'


            ELSE 'TPASD' END,


        ps.supervisor,


        CASE WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%ONLINE%' THEN 'WEB'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MCH%' THEN 'BOUTIQUES MCH'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MILK%' THEN 'BOUTIQUES MILK'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%FINA%' THEN 'BOUTIQUES FINA'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


            ELSE COALESCE(sc.sucursal_destino, v.sucursal) END,


        COALESCE(sc.sucursal_3_destino, CASE


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%ONLINE%' THEN 'WEB'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MCH%' OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%EXIT%'


              OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MILK%' OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%FINA%'


              OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%KORDA%' OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%BLUES%' THEN 'BOUTIQUES'


            ELSE COALESCE(sc.sucursal_destino, v.sucursal) END),


        mc.mes_coleccionx,


        mc.largo_prendax,


        mc.materialx,


        cl.tdas_liquidadoras,


        CASE WHEN ROW_NUMBER() OVER (PARTITION BY v.nro_tickets ORDER BY v.nro_tickets) = 1 AND v.tipo_doc NOT IN ('NCE','NDE','NC') THEN 1 ELSE 0 END,


        0,


        'ventas_act'


    FROM public.automatizacion_pla_ventas_diarias v


    JOIN public.automatizacion_dm_sucursales_activas sa


        ON v.idsucursal = sa.idsucursal AND sa.activa = TRUE


    LEFT JOIN public.pla_fechas_equivalentes f ON v.fecha_documento = f.fecha


    LEFT JOIN public.datamart_calendario c


        ON COALESCE(f.fecha_equivalente, v.fecha_documento) = c.fecha


    LEFT JOIN public.automatizacion_pla_sucursal_filtro tp


        ON v.idsucursal = tp.idsucursal AND c.semana = tp.semana AND c.dia_semana = tp.dia_semana


    LEFT JOIN public.automatizacion_dm_sucursal_cambios sc


        ON v.sucursal = sc.sucursal_origen


       AND v.fecha_documento >= sc.fecha_desde


       AND (sc.fecha_hasta IS NULL OR v.fecha_documento <= sc.fecha_hasta)


    LEFT JOIN public.automatizacion_dm_sucursal_clasificacion cl ON v.idsucursal = cl.idsucursal


    LEFT JOIN public.pla_supervisores ps ON COALESCE(sc.sucursal_destino, v.sucursal) = ps.sucursal


    LEFT JOIN public.automatizacion_dm_codigos_equivalencia eq ON v.codigo_padre = eq.codigo_padre_origen


    LEFT JOIN public.mes_coleccion_1 mc


        ON COALESCE(eq.codigo_padre_destino, v.codigo_padre) = mc.codigo_padrex


    LEFT JOIN public.productos_faltantes_2 pf


        ON v.codigo_padre = pf.codigo_producto AND v.descripcion_padre IS NULL


    WHERE v.fecha_documento BETWEEN v_fecha_ini AND v_fecha_fin


      AND v.sucursal <> 'SEDE CENTRAL'


      AND v.categoria <> 'SERVICIOS GENERALES'


      AND v.marca <> 'SERVICIOS'


      AND v.linea NOT IN ('BOLSAS','BOLSA','ADMINISTRATIVO','ACCESORIOS PUBLICITARIOS','CINTA','COLGADOR','PAPELERIA','TARJETA')


      AND (v.grupo_canal IN ('PUBLICO GENERAL','OUTLET','E-COMMERCE','CONCESIONARIO')


           OR (v.grupo_canal = 'TIENDAS POR DEPARTAMENTO' AND v.marca = 'PARFOIS'))


    GROUP BY v.empresa, v.grupo_canal, v.fecha_documento,


        c.dia_semana, c.semana, c.fecha, v.mes,


        v.categoria, v.subcategoria, v.linea, v.sublinea,


        v.codigo_padre, v.descripcion_padre, v.coleccion,


        v.marca, v.proveedor, v.tipo_producto, v.origen,


        v.temporada, v.sucursal, v.tipo_doc, v.serie,


        v.documento, v.serie_documento,


        tp.filtro_sss, v.mall, v.condicion, v.nro_tickets,


        v.pvp, v.idsucursal, ps.supervisor,


        mc.mes_coleccionx, mc.largo_prendax, mc.materialx,


        cl.tdas_liquidadoras, cl.localidad_override,


        sc.sucursal_destino, sc.sucursal_2_destino,


        sc.sucursal_3_destino, eq.codigo_padre_destino, pf.producto;





    DELETE FROM public.automatizacion_pla_reporte_ventas


    WHERE tipo_fila = 'ventas_hst'


      AND fecha_documento BETWEEN v_fecha_ini_hst AND v_fecha_fin_hst;





    INSERT INTO public.automatizacion_pla_reporte_ventas (


        empresa,


        grupo_canal,


        fecha_documento,


        dia_semana,


        semana,


        dia_equivalente,


        mes,


        categoria,


        subcategoria,


        linea,


        linea_2,


        linea_2_2,


        sublinea,


        codigo_padre,


        descripcion_padre,


        coleccion,


        marca,


        marca_2,


        marca_temporada,


        proveedor,


        tipo,


        origen,


        localidad,


        temporada,


        temporada_2,


        temporada_3,


        sucursal,


        sucursal_2,


        sucursal_3,


        tipo_doc,


        serie,


        documento,


        serie_documento,


        unidades_hst_1,


        importe_subtotal_hst_1,


        costo_venta_neta_hst_1,


        unidades_hst,


        importe_subtotal_hst,


        costo_venta_neta_hst,


        unidades,


        importe_subtotal,


        costo_venta_neta,


        meta_venta,


        meta_contribucion,


        meta_venta_faro,


        meta_contribucion_faro,


        filtro_sss,


        filtro_sss2,


        mall,


        condicion,


        nro_tickets_hst_1,


        nro_tickets_hst,


        nro_tickets,


        dias_estancia,


        dias_estancia_tda,


        meses_estancia,


        meses_estancia_tda,


        inv_unds_act,


        inv_costo_act,


        inv_unds_hst,


        inv_costo_hst,


        pvp,


        vta_retail_act,


        vta_retail_hst,


        vta_retail_hst_1,


        cubicaje,


        area,


        conteo,


        conteo_hst,


        temporada_4,


        supervisor,


        sucursal_2_1,


        sucursal_3_1,


        _mes_coleccion,


        largo,


        material,


        tdas_liquidadoras,


        flag_tickets_act,


        flag_tickets_hst,


        tipo_fila


    )


    SELECT


        v.empresa,


        v.grupo_canal,


        v.fecha_documento,


        c.dia_semana,


        c.semana,


        CAST(to_char(c.fecha,'dd') AS NUMERIC),


        v.mes,


        v.categoria,


        v.subcategoria,


        v.linea,


        CASE WHEN v.linea IN ('BERMUDA','SHORT') THEN 'SHORT'


            WHEN v.linea IN ('BUFANDA','CHALINA','PASHMINA','PASHIMINA') THEN 'PASHMINA'


            WHEN v.linea IN ('BVD','TOP','POLO') THEN 'POLO'


            WHEN v.linea IN ('JUMPER','ENTERIZO') THEN 'ENTERIZO'


            WHEN v.linea IN ('CARDIGAN','CHOMPA') THEN 'CHOMPA'


            ELSE v.linea END,


        CASE WHEN v.linea IN ('BERMUDA','SHORT','FALDA') THEN 'SHORT+FALDA'


            WHEN v.linea IN ('BUFANDA','CHALINA','PASHMINA','PASHIMINA') THEN 'PASHMINA'


            WHEN v.linea IN ('BVD','TOP','POLO','BLUSA') THEN 'BLUSA+POLO'


            WHEN v.linea IN ('JUMPER','ENTERIZO','VESTIDO') THEN 'VESTIDO+ENTERIZO'


            WHEN v.linea IN ('CARDIGAN','CHOMPA','CASACA','CHALECO','ABRIGO') THEN 'CASACA+CHOMPA+ABRIGO'


            WHEN v.linea IN ('PANTALON','JEAN') THEN 'PANTALON+JEAN'


            ELSE 'OTROS' END,


        v.sublinea,


        COALESCE(eq.codigo_padre_destino, v.codigo_padre),


        COALESCE(pf.producto, v.descripcion_padre),


        trim(v.coleccion),


        v.marca,


        CASE WHEN v.marca IN ('FINA','WILLKA') THEN 'FINA'


            WHEN v.marca IN ('MENTHA TWENTIES','MENTHA & CHOCOLATE') THEN 'MENTHA & CHOCOLATE'


            ELSE v.marca END,


        'NO CORRIENTE',


        v.proveedor,


        v.tipo_producto,


        v.origen,


        COALESCE(cl.localidad_override,


            CASE WHEN v.sucursal LIKE '%TRUJILLO' OR v.sucursal LIKE '%PIURA%'


                   OR v.sucursal LIKE '%CHICLAYO%' OR v.sucursal LIKE '%IQUITOS%' THEN 'Norte'


                 WHEN v.sucursal LIKE '%CUSCO' OR v.sucursal LIKE '%AREQUIPA%' THEN 'Sur'


                 ELSE 'Lima' END),


        v.temporada,


        CASE WHEN v.temporada LIKE 'PRIMAVERA%' OR v.temporada LIKE 'VERANO%'


                THEN 'PV ' || right(v.temporada,4)


            WHEN v.temporada LIKE 'OTONO%' OR v.temporada LIKE 'INVIERNO%'


                THEN 'OI ' || right(v.temporada,4)


            ELSE v.temporada END,


        CASE WHEN v.temporada LIKE 'PRIMAVERA%' OR v.temporada LIKE 'VERANO%' THEN 'PV'


            WHEN v.temporada LIKE 'OTONO%' OR v.temporada LIKE 'INVIERNO%' THEN 'OI'


            WHEN v.temporada LIKE 'BASICO TODAS TEMPORADA%' THEN 'BTT'


            ELSE v.temporada END,


        COALESCE(sc.sucursal_destino, v.sucursal),


        CASE WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%ONLINE%' THEN 'WEB'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MCH%' THEN 'BOUTIQUES MCH'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MILK%' THEN 'BOUTIQUES MILK'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%FINA%' THEN 'BOUTIQUES FINA'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


            ELSE COALESCE(sc.sucursal_destino, v.sucursal) END,


        COALESCE(sc.sucursal_3_destino, CASE


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%ONLINE%' THEN 'WEB'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MCH%' OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%EXIT%'


              OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MILK%' OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%FINA%'


              OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%KORDA%' OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%BLUES%' THEN 'BOUTIQUES'


            ELSE COALESCE(sc.sucursal_destino, v.sucursal) END),


        v.tipo_doc,


        v.serie,


        v.documento,


        v.serie_documento,


        SUM(v.unidades),


        SUM(v.importe_subtotal),


        SUM(v.costo_venta_neta),


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        tp.filtro_sss,


        CASE WHEN tp.filtro_sss LIKE '%SSS%' THEN 'SSS' WHEN tp.filtro_sss LIKE '%NUEVO%' THEN 'NUEVO' WHEN tp.filtro_sss LIKE '%CIERRE%' THEN 'CIERRE' ELSE tp.filtro_sss END,


        v.mall,


        v.condicion,


        ROW_NUMBER() OVER (PARTITION BY v.nro_tickets ORDER BY v.nro_tickets),


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        v.pvp,


        0,


        0,


        SUM(COALESCE(v.unidades*v.pvp,0)),


        0,


        0,


        0,


        0,


        CASE WHEN right(v.temporada,2) IN ('21','22','23','24','25','26')


                THEN CASE WHEN v.temporada LIKE 'PRIMAVERA%' OR v.temporada LIKE 'VERANO%'


                         THEN 'PV ' || right(v.temporada,2)


                     WHEN v.temporada LIKE 'OTONO%' OR v.temporada LIKE 'INVIERNO%'


                         THEN 'OI ' || right(v.temporada,2)


                     ELSE 'TT' END


            WHEN v.temporada LIKE 'BASICO TODAS TEMPORADA%' THEN 'TT'


            ELSE 'TPASD' END,


        ps.supervisor,


        CASE WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%ONLINE%' THEN 'WEB'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MCH%' THEN 'BOUTIQUES MCH'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MILK%' THEN 'BOUTIQUES MILK'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%FINA%' THEN 'BOUTIQUES FINA'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


            ELSE COALESCE(sc.sucursal_destino, v.sucursal) END,


        COALESCE(sc.sucursal_3_destino, CASE


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%ONLINE%' THEN 'WEB'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MCH%' OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%EXIT%'


              OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%MILK%' OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%FINA%'


              OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%KORDA%' OR COALESCE(sc.sucursal_destino, v.sucursal) LIKE '%BLUES%' THEN 'BOUTIQUES'


            ELSE COALESCE(sc.sucursal_destino, v.sucursal) END),


        mc.mes_coleccionx,


        mc.largo_prendax,


        mc.materialx,


        cl.tdas_liquidadoras,


        0,


        CASE WHEN ROW_NUMBER() OVER (PARTITION BY v.nro_tickets ORDER BY v.nro_tickets) = 1 AND v.tipo_doc NOT IN ('NCE','NDE','NC') THEN 1 ELSE 0 END,


        'ventas_hst'


    FROM public.automatizacion_pla_ventas_diarias v


    JOIN public.automatizacion_dm_sucursales_activas sa


        ON v.idsucursal = sa.idsucursal AND sa.activa = TRUE


    LEFT JOIN public.pla_fechas_equivalentes f ON v.fecha_documento = f.fecha


    LEFT JOIN public.datamart_calendario c


        ON COALESCE(f.fecha_equivalente, v.fecha_documento) = c.fecha


    LEFT JOIN public.automatizacion_pla_sucursal_filtro tp


        ON v.idsucursal = tp.idsucursal AND c.semana = tp.semana AND c.dia_semana = tp.dia_semana


    LEFT JOIN public.automatizacion_dm_sucursal_cambios sc


        ON v.sucursal = sc.sucursal_origen


       AND v.fecha_documento >= sc.fecha_desde


       AND (sc.fecha_hasta IS NULL OR v.fecha_documento <= sc.fecha_hasta)


    LEFT JOIN public.automatizacion_dm_sucursal_clasificacion cl ON v.idsucursal = cl.idsucursal


    LEFT JOIN public.pla_supervisores ps ON COALESCE(sc.sucursal_destino, v.sucursal) = ps.sucursal


    LEFT JOIN public.automatizacion_dm_codigos_equivalencia eq ON v.codigo_padre = eq.codigo_padre_origen


    LEFT JOIN public.mes_coleccion_1 mc


        ON COALESCE(eq.codigo_padre_destino, v.codigo_padre) = mc.codigo_padrex


    LEFT JOIN public.productos_faltantes_2 pf


        ON v.codigo_padre = pf.codigo_producto AND v.descripcion_padre IS NULL


    WHERE v.fecha_documento BETWEEN v_fecha_ini_hst AND v_fecha_fin_hst


      AND v.sucursal <> 'SEDE CENTRAL'


      AND v.categoria <> 'SERVICIOS GENERALES'


      AND v.marca <> 'SERVICIOS'


      AND v.linea NOT IN ('BOLSAS','BOLSA','ADMINISTRATIVO','ACCESORIOS PUBLICITARIOS','CINTA','COLGADOR','PAPELERIA','TARJETA')


      AND (v.grupo_canal IN ('PUBLICO GENERAL','OUTLET','E-COMMERCE','CONCESIONARIO')


           OR (v.grupo_canal = 'TIENDAS POR DEPARTAMENTO' AND v.marca = 'PARFOIS'))


    GROUP BY v.empresa, v.grupo_canal, v.fecha_documento,


        c.dia_semana, c.semana, c.fecha, v.mes,


        v.categoria, v.subcategoria, v.linea, v.sublinea,


        v.codigo_padre, v.descripcion_padre, v.coleccion,


        v.marca, v.proveedor, v.tipo_producto, v.origen,


        v.temporada, v.sucursal, v.tipo_doc, v.serie,


        v.documento, v.serie_documento,


        tp.filtro_sss, v.mall, v.condicion, v.nro_tickets,


        v.pvp, v.idsucursal, ps.supervisor,


        mc.mes_coleccionx, mc.largo_prendax, mc.materialx,


        cl.tdas_liquidadoras, cl.localidad_override,


        sc.sucursal_destino, sc.sucursal_2_destino,


        sc.sucursal_3_destino, eq.codigo_padre_destino, pf.producto;





    IF p_forzar_todo OR NOT EXISTS (


        SELECT 1 FROM public.automatizacion_pla_reporte_ventas


        WHERE tipo_fila = 'stock_act' LIMIT 1


    ) THEN


        DELETE FROM public.automatizacion_pla_reporte_ventas WHERE tipo_fila = 'stock_act';





        INSERT INTO public.automatizacion_pla_reporte_ventas (


            empresa, grupo_canal, fecha_documento, dia_semana, semana,


            dia_equivalente, mes, categoria, subcategoria, linea, linea_2, linea_2_2,


            sublinea, codigo_padre, descripcion_padre, coleccion, marca, marca_2,


            marca_temporada, proveedor, tipo, origen, localidad, temporada, temporada_2,


            temporada_3, sucursal, sucursal_2, sucursal_3, tipo_doc, serie, documento,


            serie_documento, unidades_hst_1, importe_subtotal_hst_1, costo_venta_neta_hst_1,


            unidades_hst, importe_subtotal_hst, costo_venta_neta_hst, unidades,


            importe_subtotal, costo_venta_neta, meta_venta, meta_contribucion,


            meta_venta_faro, meta_contribucion_faro, filtro_sss, filtro_sss2, mall,


            condicion, nro_tickets_hst_1, nro_tickets_hst, nro_tickets, dias_estancia,


            dias_estancia_tda, meses_estancia, meses_estancia_tda, inv_unds_act,


            inv_costo_act, inv_unds_hst, inv_costo_hst, pvp, vta_retail_act,


            vta_retail_hst, vta_retail_hst_1, cubicaje, area, conteo, conteo_hst,


            temporada_4, supervisor, sucursal_2_1, sucursal_3_1, _mes_coleccion,


            largo, material, tdas_liquidadoras, flag_tickets_act, flag_tickets_hst, tipo_fila


        )


        SELECT


            stk.empresa,


            stk.grupo_canal,


            stk.fecha                AS fecha_documento,


            stk.dia_semana,


            stk.semana,


            CAST(to_char(stk.fecha, 'dd') AS NUMERIC),  -- dia_equivalente


        	MAX(c.mes_nombre) AS mes,                               -- mes


            stk.categoria,


            stk.subcategoria,


            stk.linea,


            CASE WHEN stk.linea IN ('BERMUDA','SHORT') THEN 'SHORT'


                WHEN stk.linea IN ('BUFANDA','CHALINA','PASHMINA','PASHIMINA') THEN 'PASHMINA'


                WHEN stk.linea IN ('BVD','TOP','POLO') THEN 'POLO'


                WHEN stk.linea IN ('JUMPER','ENTERIZO') THEN 'ENTERIZO'


                WHEN stk.linea IN ('CARDIGAN','CHOMPA') THEN 'CHOMPA'


                ELSE stk.linea END,


            CASE WHEN stk.linea IN ('BERMUDA','SHORT','FALDA') THEN 'SHORT+FALDA'


                WHEN stk.linea IN ('BUFANDA','CHALINA','PASHMINA','PASHIMINA') THEN 'PASHMINA'


                WHEN stk.linea IN ('BVD','TOP','POLO','BLUSA') THEN 'BLUSA+POLO'


                WHEN stk.linea IN ('JUMPER','ENTERIZO','VESTIDO') THEN 'VESTIDO+ENTERIZO'


                WHEN stk.linea IN ('CARDIGAN','CHOMPA','CASACA','CHALECO','ABRIGO') THEN 'CASACA+CHOMPA+ABRIGO'


                WHEN stk.linea IN ('PANTALON','JEAN') THEN 'PANTALON+JEAN'


                ELSE 'OTROS' END,


            stk.sublinea,


            stk.codigo_padre,


            stk.descripcion_padre,


            trim(stk.coleccion),


            stk.marca,


            CASE WHEN stk.marca IN ('FINA','WILLKA') THEN 'FINA'


                WHEN stk.marca IN ('MENTHA TWENTIES','MENTHA & CHOCOLATE') THEN 'MENTHA & CHOCOLATE'


                ELSE stk.marca END,


            'NO CORRIENTE',


            stk.proveedor,


            stk.tipo,


            stk.origen,


            CASE WHEN stk.sucursal LIKE '%TRUJILLO' OR stk.sucursal LIKE '%PIURA%'


                      OR stk.sucursal LIKE '%CHICLAYO%' OR stk.sucursal LIKE '%IQUITOS%' THEN 'Norte'


                 WHEN stk.sucursal LIKE '%CUSCO' OR stk.sucursal LIKE '%AREQUIPA%' THEN 'Sur'


                 ELSE 'Lima' END,


            stk.temporada,


            CASE WHEN stk.temporada LIKE 'PRIMAVERA%' OR stk.temporada LIKE 'VERANO%'


                    THEN 'PV ' || right(stk.temporada,4)


                WHEN stk.temporada LIKE 'OTONO%' OR stk.temporada LIKE 'INVIERNO%'


                    THEN 'OI ' || right(stk.temporada,4)


                ELSE stk.temporada END,


            CASE WHEN stk.temporada LIKE 'PRIMAVERA%' OR stk.temporada LIKE 'VERANO%' THEN 'PV'


                WHEN stk.temporada LIKE 'OTONO%' OR stk.temporada LIKE 'INVIERNO%' THEN 'OI'


                WHEN stk.temporada LIKE 'BASICO TODAS TEMPORADA%' THEN 'BTT'


                ELSE stk.temporada END,


            CASE WHEN stk.sucursal LIKE '%ON WEB'


                    THEN replace(stk.sucursal,'ON WEB','ONLINE')


                WHEN stk.sucursal LIKE '%- CONSIGNACION'


                    THEN trim(replace(stk.sucursal,'- CONSIGNACION',''))


                ELSE stk.sucursal END,


            CASE WHEN stk.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


                WHEN stk.sucursal LIKE '%ON WEB%' OR stk.sucursal LIKE '%ONLINE%' THEN 'WEB'


                WHEN stk.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


                WHEN stk.sucursal LIKE '%MCH%' THEN 'BOUTIQUES MCH'


                WHEN stk.sucursal LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


                WHEN stk.sucursal LIKE '%MILK%' THEN 'BOUTIQUES MILK'


                WHEN stk.sucursal LIKE '%FINA%' THEN 'BOUTIQUES FINA'


                WHEN stk.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


                ELSE stk.sucursal END,


            CASE WHEN stk.sucursal LIKE '%ON WEB%' OR stk.sucursal LIKE '%ONLINE%' THEN 'WEB'


                WHEN stk.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


                WHEN stk.sucursal LIKE '%MCH%' OR stk.sucursal LIKE '%EXIT%'


                  OR stk.sucursal LIKE '%MILK%' OR stk.sucursal LIKE '%FINA%'


                  OR stk.sucursal LIKE '%KORDA%' OR stk.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES'


                ELSE stk.sucursal END,


            NULL::varchar, NULL::integer, NULL::integer, NULL::text,


            0, 0, 0,    -- unidades_hst_1, importe_subtotal_hst_1, costo_venta_neta_hst_1


            0, 0, 0,    -- unidades_hst, importe_subtotal_hst, costo_venta_neta_hst


            0, 0, 0,    -- unidades, importe_subtotal, costo_venta_neta


            0, 0, 0, 0, -- meta_venta, meta_contribucion, meta_venta_faro, meta_contribucion_faro


            tp.filtro_sss,


            CASE WHEN tp.filtro_sss LIKE '%SSS%' THEN 'SSS'


                WHEN tp.filtro_sss LIKE '%NUEVO%' THEN 'NUEVO'


                WHEN tp.filtro_sss LIKE '%CIERRE%' THEN 'CIERRE'


                ELSE tp.filtro_sss END,


            su.centro_comercial,  -- mall


            NULL::text,           -- condicion


            0, 0, 0,              -- nro_tickets_hst_1, nro_tickets_hst, nro_tickets


            stk.estancia,         -- dias_estancia


            stk.estancia_tda,     -- dias_estancia_tda


            stk.edad_dias,        -- meses_estancia


            stk.edad_tda,         -- meses_estancia_tda


            SUM(stk.stock),       -- inv_unds_act


            SUM(stk.costo_mn),    -- inv_costo_act


            0, 0,                 -- inv_unds_hst, inv_costo_hst


            0,                    -- pvp


            0, 0, 0,              -- vta_retail_act, vta_retail_hst, vta_retail_hst_1


            0, 0, 0, 0,           -- cubicaje, area, conteo, conteo_hst


            NULL::text,           -- temporada_4 (no aplica en stock)


            NULL::varchar,        -- supervisor


            CASE WHEN stk.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


                WHEN stk.sucursal LIKE '%ON WEB%' OR stk.sucursal LIKE '%ONLINE%' THEN 'WEB'


                WHEN stk.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


                WHEN stk.sucursal LIKE '%MCH%' THEN 'BOUTIQUES MCH'


                WHEN stk.sucursal LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


                WHEN stk.sucursal LIKE '%MILK%' THEN 'BOUTIQUES MILK'


                WHEN stk.sucursal LIKE '%FINA%' THEN 'BOUTIQUES FINA'


                WHEN stk.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


                ELSE stk.sucursal END,    -- sucursal_2_1


            CASE WHEN stk.sucursal LIKE '%ON WEB%' OR stk.sucursal LIKE '%ONLINE%' THEN 'WEB'


                WHEN stk.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


                WHEN stk.sucursal LIKE '%MCH%' OR stk.sucursal LIKE '%EXIT%'


                  OR stk.sucursal LIKE '%MILK%' OR stk.sucursal LIKE '%FINA%'


                  OR stk.sucursal LIKE '%KORDA%' OR stk.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES'


                ELSE stk.sucursal END,    -- sucursal_3_1


            NULL::text,  -- _mes_coleccion


            NULL::text,  -- largo


            NULL::text,  -- material


            NULL::text,  -- tdas_liquidadoras


            0,           -- flag_tickets_act


            0,           -- flag_tickets_hst


            'stock_act'


        FROM public.automatizacion_stock_semanal stk


		LEFT JOIN public.datamart_calendario c ON stk.fecha = c.fecha


        LEFT JOIN public.automatizacion_pla_sucursal_filtro tp


            ON trim(replace(stk.sucursal,'- CONSIGNACION','')) = tp.sucursal


           AND tp.fecha = stk.fecha


        LEFT JOIN public.dm_comercial_sucursales su


            ON su.sucursal = stk.sucursal


			AND su.idempresa = 1


       -- WHERE EXTRACT(YEAR FROM stk.fecha) = v_anio_act


 		WHERE stk.fecha BETWEEN v_fecha_ini AND v_fecha_fin


          AND stk.categoria NOT IN (


                'MERCHANDISING','SUMINISTROS','ACTIVOS',


                'AVIOS E INSUMOS','SERVICIOS GENERALES','TELAS'


              )


        GROUP BY


            stk.empresa, stk.grupo_canal, stk.fecha, stk.dia_semana, stk.semana,


            stk.categoria, stk.subcategoria, stk.linea, stk.sublinea,


            stk.codigo_padre, stk.descripcion_padre, stk.coleccion,


            stk.marca, stk.proveedor, stk.tipo, stk.origen, stk.temporada,


            stk.sucursal, tp.filtro_sss, su.centro_comercial,


            stk.estancia, stk.estancia_tda, stk.edad_dias, stk.edad_tda;





    END IF;


IF p_forzar_todo OR NOT EXISTS (


        SELECT 1 FROM public.automatizacion_pla_reporte_ventas


        WHERE tipo_fila = 'stock_hst' LIMIT 1


    ) THEN


        DELETE FROM public.automatizacion_pla_reporte_ventas WHERE tipo_fila = 'stock_hst';





        INSERT INTO public.automatizacion_pla_reporte_ventas (


            empresa, grupo_canal, fecha_documento, dia_semana, semana,


            dia_equivalente, mes, categoria, subcategoria, linea, linea_2, linea_2_2,


            sublinea, codigo_padre, descripcion_padre, coleccion, marca, marca_2,


            marca_temporada, proveedor, tipo, origen, localidad, temporada, temporada_2,


            temporada_3, sucursal, sucursal_2, sucursal_3, tipo_doc, serie, documento,


            serie_documento, unidades_hst_1, importe_subtotal_hst_1, costo_venta_neta_hst_1,


            unidades_hst, importe_subtotal_hst, costo_venta_neta_hst, unidades,


            importe_subtotal, costo_venta_neta, meta_venta, meta_contribucion,


            meta_venta_faro, meta_contribucion_faro, filtro_sss, filtro_sss2, mall,


            condicion, nro_tickets_hst_1, nro_tickets_hst, nro_tickets, dias_estancia,


            dias_estancia_tda, meses_estancia, meses_estancia_tda, inv_unds_act,


            inv_costo_act, inv_unds_hst, inv_costo_hst, pvp, vta_retail_act,


            vta_retail_hst, vta_retail_hst_1, cubicaje, area, conteo, conteo_hst,


            temporada_4, supervisor, sucursal_2_1, sucursal_3_1, _mes_coleccion,


            largo, material, tdas_liquidadoras, flag_tickets_act, flag_tickets_hst, tipo_fila


        )


        SELECT


            stk.empresa,


            stk.grupo_canal,


            stk.fecha                AS fecha_documento,


            stk.dia_semana,


            stk.semana,


            CAST(to_char(stk.fecha, 'dd') AS NUMERIC),  -- dia_equivalente


        	MAX(c.mes_nombre) AS mes,


            stk.categoria,


            stk.subcategoria,


            stk.linea,


            CASE WHEN stk.linea IN ('BERMUDA','SHORT') THEN 'SHORT'


                WHEN stk.linea IN ('BUFANDA','CHALINA','PASHMINA','PASHIMINA') THEN 'PASHMINA'


                WHEN stk.linea IN ('BVD','TOP','POLO') THEN 'POLO'


                WHEN stk.linea IN ('JUMPER','ENTERIZO') THEN 'ENTERIZO'


                WHEN stk.linea IN ('CARDIGAN','CHOMPA') THEN 'CHOMPA'


                ELSE stk.linea END,


            CASE WHEN stk.linea IN ('BERMUDA','SHORT','FALDA') THEN 'SHORT+FALDA'


                WHEN stk.linea IN ('BUFANDA','CHALINA','PASHMINA','PASHIMINA') THEN 'PASHMINA'


                WHEN stk.linea IN ('BVD','TOP','POLO','BLUSA') THEN 'BLUSA+POLO'


                WHEN stk.linea IN ('JUMPER','ENTERIZO','VESTIDO') THEN 'VESTIDO+ENTERIZO'


                WHEN stk.linea IN ('CARDIGAN','CHOMPA','CASACA','CHALECO','ABRIGO') THEN 'CASACA+CHOMPA+ABRIGO'


                WHEN stk.linea IN ('PANTALON','JEAN') THEN 'PANTALON+JEAN'


                ELSE 'OTROS' END,


            stk.sublinea,


            stk.codigo_padre,


            stk.descripcion_padre,


            trim(stk.coleccion),


            stk.marca,


            CASE WHEN stk.marca IN ('FINA','WILLKA') THEN 'FINA'


                WHEN stk.marca IN ('MENTHA TWENTIES','MENTHA & CHOCOLATE') THEN 'MENTHA & CHOCOLATE'


                ELSE stk.marca END,


            'NO CORRIENTE',


            stk.proveedor,


            stk.tipo,


            stk.origen,


            CASE WHEN stk.sucursal LIKE '%TRUJILLO' OR stk.sucursal LIKE '%PIURA%'


                      OR stk.sucursal LIKE '%CHICLAYO%' OR stk.sucursal LIKE '%IQUITOS%' THEN 'Norte'


                 WHEN stk.sucursal LIKE '%CUSCO' OR stk.sucursal LIKE '%AREQUIPA%' THEN 'Sur'


                 ELSE 'Lima' END,


            stk.temporada,


            CASE WHEN stk.temporada LIKE 'PRIMAVERA%' OR stk.temporada LIKE 'VERANO%'


                    THEN 'PV ' || right(stk.temporada,4)


                WHEN stk.temporada LIKE 'OTONO%' OR stk.temporada LIKE 'INVIERNO%'


                    THEN 'OI ' || right(stk.temporada,4)


                ELSE stk.temporada END,


            CASE WHEN stk.temporada LIKE 'PRIMAVERA%' OR stk.temporada LIKE 'VERANO%' THEN 'PV'


                WHEN stk.temporada LIKE 'OTONO%' OR stk.temporada LIKE 'INVIERNO%' THEN 'OI'


                WHEN stk.temporada LIKE 'BASICO TODAS TEMPORADA%' THEN 'BTT'


                ELSE stk.temporada END,


            CASE WHEN stk.sucursal LIKE '%ON WEB'


                    THEN replace(stk.sucursal,'ON WEB','ONLINE')


                WHEN stk.sucursal LIKE '%- CONSIGNACION'


                    THEN trim(replace(stk.sucursal,'- CONSIGNACION',''))


                ELSE stk.sucursal END,


            CASE WHEN stk.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


                WHEN stk.sucursal LIKE '%ON WEB%' OR stk.sucursal LIKE '%ONLINE%' THEN 'WEB'


                WHEN stk.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


                WHEN stk.sucursal LIKE '%MCH%' THEN 'BOUTIQUES MCH'


                WHEN stk.sucursal LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


                WHEN stk.sucursal LIKE '%MILK%' THEN 'BOUTIQUES MILK'


                WHEN stk.sucursal LIKE '%FINA%' THEN 'BOUTIQUES FINA'


                WHEN stk.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


                ELSE stk.sucursal END,


            CASE WHEN stk.sucursal LIKE '%ON WEB%' OR stk.sucursal LIKE '%ONLINE%' THEN 'WEB'


                WHEN stk.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


                WHEN stk.sucursal LIKE '%MCH%' OR stk.sucursal LIKE '%EXIT%'


                  OR stk.sucursal LIKE '%MILK%' OR stk.sucursal LIKE '%FINA%'


                  OR stk.sucursal LIKE '%KORDA%' OR stk.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES'


                ELSE stk.sucursal END,


            NULL::varchar, NULL::integer, NULL::integer, NULL::text,


            0, 0, 0,    -- unidades_hst_1, importe_subtotal_hst_1, costo_venta_neta_hst_1


            0, 0, 0,    -- unidades_hst, importe_subtotal_hst, costo_venta_neta_hst


            0, 0, 0,    -- unidades, importe_subtotal, costo_venta_neta


            0, 0, 0, 0, -- meta_venta, meta_contribucion, meta_venta_faro, meta_contribucion_faro


            tp.filtro_sss,


            CASE WHEN tp.filtro_sss LIKE '%SSS%' THEN 'SSS'


                WHEN tp.filtro_sss LIKE '%NUEVO%' THEN 'NUEVO'


                WHEN tp.filtro_sss LIKE '%CIERRE%' THEN 'CIERRE'


                ELSE tp.filtro_sss END,


            su.centro_comercial,  -- mall


            NULL::text,           -- condicion


            0, 0, 0,              -- nro_tickets_hst_1, nro_tickets_hst, nro_tickets


            stk.estancia,         -- dias_estancia


            stk.estancia_tda,     -- dias_estancia_tda


            stk.edad_dias,        -- meses_estancia


            stk.edad_tda,         -- meses_estancia_tda


            0,                    -- inv_unds_act


            0,                    -- inv_costo_act


            SUM(stk.stock),       -- inv_unds_hst  ← stock histórico


            SUM(stk.costo_mn),    -- inv_costo_hst


            0,                    -- pvp


            0, 0, 0,              -- vta_retail_act, vta_retail_hst, vta_retail_hst_1


            0, 0, 0, 0,           -- cubicaje, area, conteo, conteo_hst


            NULL::text,           -- temporada_4


            NULL::varchar,        -- supervisor


            CASE WHEN stk.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


                WHEN stk.sucursal LIKE '%ON WEB%' OR stk.sucursal LIKE '%ONLINE%' THEN 'WEB'


                WHEN stk.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


                WHEN stk.sucursal LIKE '%MCH%' THEN 'BOUTIQUES MCH'


                WHEN stk.sucursal LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


                WHEN stk.sucursal LIKE '%MILK%' THEN 'BOUTIQUES MILK'


                WHEN stk.sucursal LIKE '%FINA%' THEN 'BOUTIQUES FINA'


                WHEN stk.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


                ELSE stk.sucursal END,    -- sucursal_2_1


            CASE WHEN stk.sucursal LIKE '%ON WEB%' OR stk.sucursal LIKE '%ONLINE%' THEN 'WEB'


                WHEN stk.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


                WHEN stk.sucursal LIKE '%MCH%' OR stk.sucursal LIKE '%EXIT%'


                  OR stk.sucursal LIKE '%MILK%' OR stk.sucursal LIKE '%FINA%'


                  OR stk.sucursal LIKE '%KORDA%' OR stk.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES'


                ELSE stk.sucursal END,    -- sucursal_3_1


            NULL::text,  -- _mes_coleccion


            NULL::text,  -- largo


            NULL::text,  -- material


            NULL::text,  -- tdas_liquidadoras


            0,           -- flag_tickets_act


            0,           -- flag_tickets_hst


            'stock_hst'


        FROM public.automatizacion_stock_semanal stk


		LEFT JOIN public.datamart_calendario c ON stk.fecha = c.fecha


        LEFT JOIN public.automatizacion_pla_sucursal_filtro tp


            ON trim(replace(stk.sucursal,'- CONSIGNACION','')) = tp.sucursal


           AND tp.fecha = stk.fecha


        LEFT JOIN public.dm_comercial_sucursales su


            ON su.sucursal = stk.sucursal


			AND su.idempresa = 1


        --WHERE stk.fecha BETWEEN v_fecha_ini AND v_fecha_fin


		WHERE stk.fecha BETWEEN v_fecha_ini_hst AND v_fecha_fin_hst


          AND stk.categoria NOT IN (


                'MERCHANDISING','SUMINISTROS','ACTIVOS',


                'AVIOS E INSUMOS','SERVICIOS GENERALES','TELAS'


              )


        GROUP BY


            stk.empresa, stk.grupo_canal, stk.fecha, stk.dia_semana, stk.semana,


            stk.categoria, stk.subcategoria, stk.linea, stk.sublinea,


            stk.codigo_padre, stk.descripcion_padre, stk.coleccion,


            stk.marca, stk.proveedor, stk.tipo, stk.origen, stk.temporada,


            stk.sucursal, tp.filtro_sss, su.centro_comercial,


            stk.estancia, stk.estancia_tda, stk.edad_dias, stk.edad_tda;





    END IF;





    IF p_forzar_todo OR NOT EXISTS (SELECT 1 FROM public.automatizacion_pla_reporte_ventas WHERE tipo_fila = 'metas_faro' LIMIT 1) THEN


        DELETE FROM public.automatizacion_pla_reporte_ventas WHERE tipo_fila = 'metas_faro';


        INSERT INTO public.automatizacion_pla_reporte_ventas (


        empresa,


        grupo_canal,


        fecha_documento,


        dia_semana,


        semana,


        dia_equivalente,


        mes,


        categoria,


        subcategoria,


        linea,


        linea_2,


        linea_2_2,


        sublinea,


        codigo_padre,


        descripcion_padre,


        coleccion,


        marca,


        marca_2,


        marca_temporada,


        proveedor,


        tipo,


        origen,


        localidad,


        temporada,


        temporada_2,


        temporada_3,


        sucursal,


        sucursal_2,


        sucursal_3,


        tipo_doc,


        serie,


        documento,


        serie_documento,


        unidades_hst_1,


        importe_subtotal_hst_1,


        costo_venta_neta_hst_1,


        unidades_hst,


        importe_subtotal_hst,


        costo_venta_neta_hst,


        unidades,


        importe_subtotal,


        costo_venta_neta,


        meta_venta,


        meta_contribucion,


        meta_venta_faro,


        meta_contribucion_faro,


        filtro_sss,


        filtro_sss2,


        mall,


        condicion,


        nro_tickets_hst_1,


        nro_tickets_hst,


        nro_tickets,


        dias_estancia,


        dias_estancia_tda,


        meses_estancia,


        meses_estancia_tda,


        inv_unds_act,


        inv_costo_act,


        inv_unds_hst,


        inv_costo_hst,


        pvp,


        vta_retail_act,


        vta_retail_hst,


        vta_retail_hst_1,


        cubicaje,


        area,


        conteo,


        conteo_hst,


        temporada_4,


        supervisor,


        sucursal_2_1,


        sucursal_3_1,


        _mes_coleccion,


        largo,


        material,


        tdas_liquidadoras,


        flag_tickets_act,


        flag_tickets_hst,


        tipo_fila


    )


    SELECT


        'SMART BRANDS S.A.C.',


        0,


        m.fecha::date,


        c.dia_semana,


        c.semana,


        CAST(to_char(c.fecha,'dd') AS NUMERIC),


        c.mes_nombre,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        COALESCE(s.marca, mn.marca),


        COALESCE(s.marca, mn.marca),


        COALESCE(t.temporada_marca,'NO CORRIENTE'),


        0,


        0,


        0,


        CASE WHEN m.sucursal LIKE '%TRUJILLO' OR m.sucursal LIKE '%PIURA%'


                   OR m.sucursal LIKE '%CHICLAYO%' OR m.sucursal LIKE '%IQUITOS%' THEN 'Norte'


                 WHEN m.sucursal LIKE '%CUSCO' OR m.sucursal LIKE '%AREQUIPA%' THEN 'Sur'


                 ELSE 'Lima' END,


        0,


        0,


        0,


        m.sucursal,


        CASE WHEN m.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN m.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN m.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


            WHEN m.sucursal LIKE '%MCH%' THEN 'BOUTIQUES MCH'


            WHEN m.sucursal LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


            WHEN m.sucursal LIKE '%MILK%' THEN 'BOUTIQUES MILK'


            WHEN m.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


            WHEN m.sucursal LIKE '%FINA%' THEN 'BOUTIQUES FINA'


            ELSE m.sucursal END,


        CASE WHEN m.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN m.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN m.sucursal LIKE '%MCH%' OR m.sucursal LIKE '%EXIT%'


              OR m.sucursal LIKE '%MILK%' OR m.sucursal LIKE '%FINA%'


              OR m.sucursal LIKE '%BLUES%' OR m.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES'


            ELSE m.sucursal END,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        CASE WHEN m.sucursal IN ('MCH ROBLE','MCH LR SAN BORJA','MCH OP ANGAMOS',


                'MCH PLAZA NORTE','MCH PLAZA SAN MIGUEL','MCH RP SALAVERRY','MCH CRONOS','MCH RP TRUJILLO')


                THEN s.porcentaje * m.importe


            WHEN m.fecha::date >= '2022-10-12' THEN COALESCE(t.porcentaje,1) * m.importe


            ELSE m.importe END,


        CASE WHEN m.sucursal IN ('MCH ROBLE','MCH LR SAN BORJA','MCH OP ANGAMOS',


                'MCH PLAZA NORTE','MCH PLAZA SAN MIGUEL','MCH RP SALAVERRY','MCH CRONOS','MCH RP TRUJILLO')


                THEN s.porcentaje * m.importe * m.contribucion


            WHEN m.fecha::date >= '2022-10-12' THEN COALESCE(t.porcentaje,1) * m.importe * m.contribucion


            ELSE m.importe * m.contribucion END,


        tp.filtro_sss,


        CASE WHEN tp.filtro_sss LIKE '%SSS%' THEN 'SSS' WHEN tp.filtro_sss LIKE '%NUEVO%' THEN 'NUEVO' WHEN tp.filtro_sss LIKE '%CIERRE%' THEN 'CIERRE' ELSE tp.filtro_sss END,


        su.centro_comercial,


        NULL::text,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        CASE WHEN m.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN m.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN m.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


            WHEN m.sucursal LIKE '%MCH%' THEN 'BOUTIQUES MCH'


            WHEN m.sucursal LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


            WHEN m.sucursal LIKE '%MILK%' THEN 'BOUTIQUES MILK'


            WHEN m.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


            WHEN m.sucursal LIKE '%FINA%' THEN 'BOUTIQUES FINA'


            ELSE m.sucursal END,


        CASE WHEN m.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN m.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN m.sucursal LIKE '%MCH%' OR m.sucursal LIKE '%EXIT%'


              OR m.sucursal LIKE '%MILK%' OR m.sucursal LIKE '%FINA%'


              OR m.sucursal LIKE '%BLUES%' OR m.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES'


            ELSE m.sucursal END,


        0,


        0,


        0,


        0,


        0,


        0,


        'metas_faro'


    FROM public.datamart_ventas_cuota_postgre_faro m


    LEFT JOIN public.pla_meta_sucursal_marca s ON s.sucursal = m.sucursal


    LEFT JOIN public.pla_meta_sucursal_marca_nuevo_2026_separado mn ON mn.sucursal = m.sucursal


    LEFT JOIN public.pla_meta_sucursal_marca_korda_milk_fecha t


        ON t.sucursal = m.sucursal AND m.fecha::date >= '2022-10-12'


    LEFT JOIN public.pla_fechas_equivalentes f ON m.fecha::date = f.fecha


    LEFT JOIN public.datamart_calendario c ON COALESCE(f.fecha_equivalente, m.fecha::date) = c.fecha


    LEFT JOIN public.dm_comercial_sucursales su ON m.idsucursal = su.idsucursal


    LEFT JOIN public.automatizacion_pla_sucursal_filtro tp


        ON m.idsucursal = tp.idsucursal AND c.semana = tp.semana AND c.dia_semana = tp.dia_semana;


    END IF;





    -- Recarga metas_std si: forzado, vacío, o llegó un mes nuevo de cuotas al origen (fix 2026-11-18)
    IF p_forzar_todo
       OR NOT EXISTS (SELECT 1 FROM public.automatizacion_pla_reporte_ventas WHERE tipo_fila = 'metas_std' LIMIT 1)
       OR (SELECT MAX(fecha::date) FROM public.datamart_ventas_cuota_postgre)
          > (SELECT MAX(fecha_documento) FROM public.automatizacion_pla_reporte_ventas WHERE tipo_fila = 'metas_std') THEN


        DELETE FROM public.automatizacion_pla_reporte_ventas WHERE tipo_fila = 'metas_std';


        INSERT INTO public.automatizacion_pla_reporte_ventas (


        empresa,


        grupo_canal,


        fecha_documento,


        dia_semana,


        semana,


        dia_equivalente,


        mes,


        categoria,


        subcategoria,


        linea,


        linea_2,


        linea_2_2,


        sublinea,


        codigo_padre,


        descripcion_padre,


        coleccion,


        marca,


        marca_2,


        marca_temporada,


        proveedor,


        tipo,


        origen,


        localidad,


        temporada,


        temporada_2,


        temporada_3,


        sucursal,


        sucursal_2,


        sucursal_3,


        tipo_doc,


        serie,


        documento,


        serie_documento,


        unidades_hst_1,


        importe_subtotal_hst_1,


        costo_venta_neta_hst_1,


        unidades_hst,


        importe_subtotal_hst,


        costo_venta_neta_hst,


        unidades,


        importe_subtotal,


        costo_venta_neta,


        meta_venta,


        meta_contribucion,


        meta_venta_faro,


        meta_contribucion_faro,


        filtro_sss,


        filtro_sss2,


        mall,


        condicion,


        nro_tickets_hst_1,


        nro_tickets_hst,


        nro_tickets,


        dias_estancia,


        dias_estancia_tda,


        meses_estancia,


        meses_estancia_tda,


        inv_unds_act,


        inv_costo_act,


        inv_unds_hst,


        inv_costo_hst,


        pvp,


        vta_retail_act,


        vta_retail_hst,


        vta_retail_hst_1,


        cubicaje,


        area,


        conteo,


        conteo_hst,


        temporada_4,


        supervisor,


        sucursal_2_1,


        sucursal_3_1,


        _mes_coleccion,


        largo,


        material,


        tdas_liquidadoras,


        flag_tickets_act,


        flag_tickets_hst,


        tipo_fila


    )


    SELECT


        'SMART BRANDS S.A.C.',


        0,


        m.fecha::date,


        c.dia_semana,


        c.semana,


        CAST(to_char(c.fecha,'dd') AS NUMERIC),


        c.mes_nombre,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        mn.marca,


        mn.marca,


        mn.marca,


        0,


        0,


        0,


        CASE WHEN m.sucursal LIKE '%TRUJILLO' OR m.sucursal LIKE '%PIURA%'


                   OR m.sucursal LIKE '%CHICLAYO%' OR m.sucursal LIKE '%IQUITOS%' THEN 'Norte'


                 WHEN m.sucursal LIKE '%CUSCO' OR m.sucursal LIKE '%AREQUIPA%' THEN 'Sur'


                 ELSE 'Lima' END,


        0,


        0,


        0,


        m.sucursal,


        CASE WHEN m.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN m.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN m.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


            WHEN m.sucursal LIKE '%MCH%' THEN 'BOUTIQUES MCH'


            WHEN m.sucursal LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


            WHEN m.sucursal LIKE '%MILK%' THEN 'BOUTIQUES MILK'


            WHEN m.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


            WHEN m.sucursal LIKE '%FINA%' THEN 'BOUTIQUES FINA'


            ELSE m.sucursal END,


        CASE WHEN m.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN m.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN m.sucursal LIKE '%MCH%' OR m.sucursal LIKE '%EXIT%'


              OR m.sucursal LIKE '%MILK%' OR m.sucursal LIKE '%FINA%'


              OR m.sucursal LIKE '%BLUES%' OR m.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES'


            ELSE m.sucursal END,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        mn.porcentaje_venta * m.importe,


        m.importe * m.contribucion * mn.porcentaje_contro,


        0,


        0,


        tp.filtro_sss,


        CASE WHEN tp.filtro_sss LIKE '%SSS%' THEN 'SSS' WHEN tp.filtro_sss LIKE '%NUEVO%' THEN 'NUEVO' WHEN tp.filtro_sss LIKE '%CIERRE%' THEN 'CIERRE' ELSE tp.filtro_sss END,


        su.centro_comercial,


        NULL::text,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        CASE WHEN m.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN m.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN m.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


            WHEN m.sucursal LIKE '%MCH%' THEN 'BOUTIQUES MCH'


            WHEN m.sucursal LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


            WHEN m.sucursal LIKE '%MILK%' THEN 'BOUTIQUES MILK'


            WHEN m.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


            WHEN m.sucursal LIKE '%FINA%' THEN 'BOUTIQUES FINA'


            ELSE m.sucursal END,


        CASE WHEN m.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN m.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN m.sucursal LIKE '%MCH%' OR m.sucursal LIKE '%EXIT%'


              OR m.sucursal LIKE '%MILK%' OR m.sucursal LIKE '%FINA%'


              OR m.sucursal LIKE '%BLUES%' OR m.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES'


            ELSE m.sucursal END,


        0,


        0,


        0,


        0,


        0,


        0,


        'metas_std'


    FROM public.datamart_ventas_cuota_postgre m


    LEFT JOIN public.pla_meta_sucursal_marca_nuevo_2026_separado mn ON mn.sucursal = m.sucursal


    LEFT JOIN public.pla_fechas_equivalentes f ON m.fecha::date = f.fecha


    LEFT JOIN public.datamart_calendario c ON COALESCE(f.fecha_equivalente, m.fecha::date) = c.fecha


    LEFT JOIN public.dm_comercial_sucursales su ON m.idsucursal = su.idsucursal


    LEFT JOIN public.automatizacion_pla_sucursal_filtro tp


        ON m.idsucursal = tp.idsucursal AND c.semana = tp.semana AND c.dia_semana = tp.dia_semana;


    END IF;





    IF p_forzar_todo OR NOT EXISTS (SELECT 1 FROM public.automatizacion_pla_reporte_ventas WHERE tipo_fila = 'area' LIMIT 1) THEN


        DELETE FROM public.automatizacion_pla_reporte_ventas WHERE tipo_fila = 'area';


        INSERT INTO public.automatizacion_pla_reporte_ventas (


        empresa,


        grupo_canal,


        fecha_documento,


        dia_semana,


        semana,


        dia_equivalente,


        mes,


        categoria,


        subcategoria,


        linea,


        linea_2,


        linea_2_2,


        sublinea,


        codigo_padre,


        descripcion_padre,


        coleccion,


        marca,


        marca_2,


        marca_temporada,


        proveedor,


        tipo,


        origen,


        localidad,


        temporada,


        temporada_2,


        temporada_3,


        sucursal,


        sucursal_2,


        sucursal_3,


        tipo_doc,


        serie,


        documento,


        serie_documento,


        unidades_hst_1,


        importe_subtotal_hst_1,


        costo_venta_neta_hst_1,


        unidades_hst,


        importe_subtotal_hst,


        costo_venta_neta_hst,


        unidades,


        importe_subtotal,


        costo_venta_neta,


        meta_venta,


        meta_contribucion,


        meta_venta_faro,


        meta_contribucion_faro,


        filtro_sss,


        filtro_sss2,


        mall,


        condicion,


        nro_tickets_hst_1,


        nro_tickets_hst,


        nro_tickets,


        dias_estancia,


        dias_estancia_tda,


        meses_estancia,


        meses_estancia_tda,


        inv_unds_act,


        inv_costo_act,


        inv_unds_hst,


        inv_costo_hst,


        pvp,


        vta_retail_act,


        vta_retail_hst,


        vta_retail_hst_1,


        cubicaje,


        area,


        conteo,


        conteo_hst,


        temporada_4,


        supervisor,


        sucursal_2_1,


        sucursal_3_1,


        _mes_coleccion,


        largo,


        material,


        tdas_liquidadoras,


        flag_tickets_act,


        flag_tickets_hst,


        tipo_fila


    )


    SELECT


        'SMART BRANDS S.A.C.',


        0,


        NULL::date,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        CASE WHEN su.sucursal LIKE '%TRUJILLO' OR su.sucursal LIKE '%PIURA%'


                   OR su.sucursal LIKE '%CHICLAYO%' OR su.sucursal LIKE '%IQUITOS%' THEN 'Norte'


                 WHEN su.sucursal LIKE '%CUSCO' OR su.sucursal LIKE '%AREQUIPA%' THEN 'Sur'


                 ELSE 'Lima' END,


        0,


        0,


        0,


        su.sucursal,


        CASE WHEN su.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN su.sucursal LIKE '%ON WEB' THEN 'WEB'


            WHEN su.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN su.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


            WHEN su.sucursal LIKE '%MCH%' THEN 'BOUTIQUES MCH'


            WHEN su.sucursal LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


            WHEN su.sucursal LIKE '%MILK%' THEN 'BOUTIQUES MILK'


            WHEN su.sucursal LIKE '%FINA%' THEN 'BOUTIQUES FINA'


            WHEN su.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


            ELSE su.sucursal END,


        CASE WHEN su.sucursal LIKE '%ON WEB' THEN 'WEB'


            WHEN su.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN su.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN su.sucursal LIKE '%MCH%' OR su.sucursal LIKE '%EXIT%'


              OR su.sucursal LIKE '%MILK%' OR su.sucursal LIKE '%FINA%'


              OR su.sucursal LIKE '%KORDA%' OR su.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES'


            ELSE su.sucursal END,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        tp.filtro_sss,


        CASE WHEN tp.filtro_sss LIKE '%SSS%' THEN 'SSS' WHEN tp.filtro_sss LIKE '%NUEVO%' THEN 'NUEVO' WHEN tp.filtro_sss LIKE '%CIERRE%' THEN 'CIERRE' ELSE tp.filtro_sss END,


        su.centro_comercial,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        su.metros_cuadrados,


        0,


        0,


        0,


        0,


        CASE WHEN su.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN su.sucursal LIKE '%ON WEB' THEN 'WEB'


            WHEN su.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN su.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


            WHEN su.sucursal LIKE '%MCH%' THEN 'BOUTIQUES MCH'


            WHEN su.sucursal LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


            WHEN su.sucursal LIKE '%MILK%' THEN 'BOUTIQUES MILK'


            WHEN su.sucursal LIKE '%FINA%' THEN 'BOUTIQUES FINA'


            WHEN su.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


            ELSE su.sucursal END,


        CASE WHEN su.sucursal LIKE '%ON WEB' THEN 'WEB'


            WHEN su.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN su.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN su.sucursal LIKE '%MCH%' OR su.sucursal LIKE '%EXIT%'


              OR su.sucursal LIKE '%MILK%' OR su.sucursal LIKE '%FINA%'


              OR su.sucursal LIKE '%KORDA%' OR su.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES'


            ELSE su.sucursal END,


        0,


        0,


        0,


        0,


        0,


        0,


        'area'


    FROM public.dm_comercial_sucursales su


    LEFT JOIN public.automatizacion_pla_sucursal_filtro tp


        ON su.idsucursal = tp.idsucursal AND (current_date - 1) = tp.fecha


    WHERE su.idempresa = 1;


    END IF;





    IF p_forzar_todo OR NOT EXISTS (SELECT 1 FROM public.automatizacion_pla_reporte_ventas WHERE tipo_fila = 'cubicajes' LIMIT 1) THEN


        DELETE FROM public.automatizacion_pla_reporte_ventas WHERE tipo_fila = 'cubicajes';


        INSERT INTO public.automatizacion_pla_reporte_ventas (


        empresa,


        grupo_canal,


        fecha_documento,


        dia_semana,


        semana,


        dia_equivalente,


        mes,


        categoria,


        subcategoria,


        linea,


        linea_2,


        linea_2_2,


        sublinea,


        codigo_padre,


        descripcion_padre,


        coleccion,


        marca,


        marca_2,


        marca_temporada,


        proveedor,


        tipo,


        origen,


        localidad,


        temporada,


        temporada_2,


        temporada_3,


        sucursal,


        sucursal_2,


        sucursal_3,


        tipo_doc,


        serie,


        documento,


        serie_documento,


        unidades_hst_1,


        importe_subtotal_hst_1,


        costo_venta_neta_hst_1,


        unidades_hst,


        importe_subtotal_hst,


        costo_venta_neta_hst,


        unidades,


        importe_subtotal,


        costo_venta_neta,


        meta_venta,


        meta_contribucion,


        meta_venta_faro,


        meta_contribucion_faro,


        filtro_sss,


        filtro_sss2,


        mall,


        condicion,


        nro_tickets_hst_1,


        nro_tickets_hst,


        nro_tickets,


        dias_estancia,


        dias_estancia_tda,


        meses_estancia,


        meses_estancia_tda,


        inv_unds_act,


        inv_costo_act,


        inv_unds_hst,


        inv_costo_hst,


        pvp,


        vta_retail_act,


        vta_retail_hst,


        vta_retail_hst_1,


        cubicaje,


        area,


        conteo,


        conteo_hst,


        temporada_4,


        supervisor,


        sucursal_2_1,


        sucursal_3_1,


        _mes_coleccion,


        largo,


        material,


        tdas_liquidadoras,


        flag_tickets_act,


        flag_tickets_hst,


        tipo_fila


    )


    SELECT


        'SMART BRANDS S.A.C.',


        0,


        NULL::date,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        cu.sucursal,


        cu.sucursal_2,


        cu.sucursal_3,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        'SSS',


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        cu.cubicaje,


        0,


        0,


        0,


        0,


        0,


        cu.sucursal_2,


        cu.sucursal_3,


        0,


        0,


        0,


        0,


        0,


        0,


        'cubicajes'


    FROM public.pla_cubicajes cu;


    END IF;





    DELETE FROM public.automatizacion_pla_reporte_ventas


    WHERE tipo_fila = 'poken_act' AND fecha_documento BETWEEN v_fecha_ini AND v_fecha_fin;


    INSERT INTO public.automatizacion_pla_reporte_ventas (


        empresa,


        grupo_canal,


        fecha_documento,


        dia_semana,


        semana,


        dia_equivalente,


        mes,


        categoria,


        subcategoria,


        linea,


        linea_2,


        linea_2_2,


        sublinea,


        codigo_padre,


        descripcion_padre,


        coleccion,


        marca,


        marca_2,


        marca_temporada,


        proveedor,


        tipo,


        origen,


        localidad,


        temporada,


        temporada_2,


        temporada_3,


        sucursal,


        sucursal_2,


        sucursal_3,


        tipo_doc,


        serie,


        documento,


        serie_documento,


        unidades_hst_1,


        importe_subtotal_hst_1,


        costo_venta_neta_hst_1,


        unidades_hst,


        importe_subtotal_hst,


        costo_venta_neta_hst,


        unidades,


        importe_subtotal,


        costo_venta_neta,


        meta_venta,


        meta_contribucion,


        meta_venta_faro,


        meta_contribucion_faro,


        filtro_sss,


        filtro_sss2,


        mall,


        condicion,


        nro_tickets_hst_1,


        nro_tickets_hst,


        nro_tickets,


        dias_estancia,


        dias_estancia_tda,


        meses_estancia,


        meses_estancia_tda,


        inv_unds_act,


        inv_costo_act,


        inv_unds_hst,


        inv_costo_hst,


        pvp,


        vta_retail_act,


        vta_retail_hst,


        vta_retail_hst_1,


        cubicaje,


        area,


        conteo,


        conteo_hst,


        temporada_4,


        supervisor,


        sucursal_2_1,


        sucursal_3_1,


        _mes_coleccion,


        largo,


        material,


        tdas_liquidadoras,


        flag_tickets_act,


        flag_tickets_hst,


        tipo_fila


    )


    SELECT


        'SMART BRANDS S.A.C.',


        0,


        a.fecha,


        c.dia_semana,


        c.semana,


        CAST(to_char(c.fecha,'dd') AS NUMERIC),


        c.mes_nombre,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        CASE WHEN su.sucursal LIKE '%TRUJILLO' OR su.sucursal LIKE '%PIURA%'


                   OR su.sucursal LIKE '%CHICLAYO%' OR su.sucursal LIKE '%IQUITOS%' THEN 'Norte'


                 WHEN su.sucursal LIKE '%CUSCO' OR su.sucursal LIKE '%AREQUIPA%' THEN 'Sur'


                 ELSE 'Lima' END,


        0,


        0,


        0,


        CASE WHEN su.sucursal LIKE '%ON WEB'


                THEN replace(su.sucursal,'ON WEB','ONLINE')


            ELSE su.sucursal END,


        CASE WHEN su.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN su.sucursal LIKE '%ON WEB' THEN 'WEB'


            WHEN su.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN su.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


            WHEN su.sucursal LIKE '%MCH%' THEN 'BOUTIQUES MCH'


            WHEN su.sucursal LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


            WHEN su.sucursal LIKE '%MILK%' THEN 'BOUTIQUES MILK'


            WHEN su.sucursal LIKE '%FINA%' THEN 'BOUTIQUES FINA'


            WHEN su.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


            ELSE su.sucursal END,


        CASE WHEN su.sucursal LIKE '%ON WEB' THEN 'WEB'


            WHEN su.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN su.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN su.sucursal LIKE '%MCH%' OR su.sucursal LIKE '%EXIT%'


              OR su.sucursal LIKE '%MILK%' OR su.sucursal LIKE '%FINA%'


              OR su.sucursal LIKE '%KORDA%' OR su.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES'


            ELSE su.sucursal END,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        'SSS',


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        a.conteo,


        0,


        0,


        0,


        CASE WHEN su.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN su.sucursal LIKE '%ON WEB' THEN 'WEB'


            WHEN su.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN su.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


            WHEN su.sucursal LIKE '%MCH%' THEN 'BOUTIQUES MCH'


            WHEN su.sucursal LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


            WHEN su.sucursal LIKE '%MILK%' THEN 'BOUTIQUES MILK'


            WHEN su.sucursal LIKE '%FINA%' THEN 'BOUTIQUES FINA'


            WHEN su.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


            ELSE su.sucursal END,


        CASE WHEN su.sucursal LIKE '%ON WEB' THEN 'WEB'


            WHEN su.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN su.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN su.sucursal LIKE '%MCH%' OR su.sucursal LIKE '%EXIT%'


              OR su.sucursal LIKE '%MILK%' OR su.sucursal LIKE '%FINA%'


              OR su.sucursal LIKE '%KORDA%' OR su.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES'


            ELSE su.sucursal END,


        0,


        0,


        0,


        0,


        0,


        0,


        'poken_act'


    FROM public.tbretail_conteos a


    LEFT JOIN public.pla_fechas_equivalentes f ON a.fecha = f.fecha


    LEFT JOIN public.datamart_calendario c ON COALESCE(f.fecha_equivalente, a.fecha) = c.fecha


    INNER JOIN public.dm_comercial_sucursales su ON a.entidad_id = su.idsucursal


    WHERE a.tipo = 'tienda'


      AND EXTRACT(YEAR FROM a.fecha) = v_anio_act


      AND a.fecha BETWEEN v_fecha_ini AND v_fecha_fin;





    DELETE FROM public.automatizacion_pla_reporte_ventas


    WHERE tipo_fila = 'poken_hst' AND fecha_documento BETWEEN v_fecha_ini_hst AND v_fecha_fin_hst;


    INSERT INTO public.automatizacion_pla_reporte_ventas (


        empresa,


        grupo_canal,


        fecha_documento,


        dia_semana,


        semana,


        dia_equivalente,


        mes,


        categoria,


        subcategoria,


        linea,


        linea_2,


        linea_2_2,


        sublinea,


        codigo_padre,


        descripcion_padre,


        coleccion,


        marca,


        marca_2,


        marca_temporada,


        proveedor,


        tipo,


        origen,


        localidad,


        temporada,


        temporada_2,


        temporada_3,


        sucursal,


        sucursal_2,


        sucursal_3,


        tipo_doc,


        serie,


        documento,


        serie_documento,


        unidades_hst_1,


        importe_subtotal_hst_1,


        costo_venta_neta_hst_1,


        unidades_hst,


        importe_subtotal_hst,


        costo_venta_neta_hst,


        unidades,


        importe_subtotal,


        costo_venta_neta,


        meta_venta,


        meta_contribucion,


        meta_venta_faro,


        meta_contribucion_faro,


        filtro_sss,


        filtro_sss2,


        mall,


        condicion,


        nro_tickets_hst_1,


        nro_tickets_hst,


        nro_tickets,


        dias_estancia,


        dias_estancia_tda,


        meses_estancia,


        meses_estancia_tda,


        inv_unds_act,


        inv_costo_act,


        inv_unds_hst,


        inv_costo_hst,


        pvp,


        vta_retail_act,


        vta_retail_hst,


        vta_retail_hst_1,


        cubicaje,


        area,


        conteo,


        conteo_hst,


        temporada_4,


        supervisor,


        sucursal_2_1,


        sucursal_3_1,


        _mes_coleccion,


        largo,


        material,


        tdas_liquidadoras,


        flag_tickets_act,


        flag_tickets_hst,


        tipo_fila


    )


    SELECT


        'SMART BRANDS S.A.C.',


        0,


        a.fecha,


        c.dia_semana,


        c.semana,


        CAST(to_char(c.fecha,'dd') AS NUMERIC),


        c.mes_nombre,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        CASE WHEN su.sucursal LIKE '%TRUJILLO' OR su.sucursal LIKE '%PIURA%'


                   OR su.sucursal LIKE '%CHICLAYO%' OR su.sucursal LIKE '%IQUITOS%' THEN 'Norte'


                 WHEN su.sucursal LIKE '%CUSCO' OR su.sucursal LIKE '%AREQUIPA%' THEN 'Sur'


                 ELSE 'Lima' END,


        0,


        0,


        0,


        CASE WHEN su.sucursal LIKE '%ON WEB'


                THEN replace(su.sucursal,'ON WEB','ONLINE')


            ELSE su.sucursal END,


        CASE WHEN su.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN su.sucursal LIKE '%ON WEB' THEN 'WEB'


            WHEN su.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN su.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


            WHEN su.sucursal LIKE '%MCH%' THEN 'BOUTIQUES MCH'


            WHEN su.sucursal LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


            WHEN su.sucursal LIKE '%MILK%' THEN 'BOUTIQUES MILK'


            WHEN su.sucursal LIKE '%FINA%' THEN 'BOUTIQUES FINA'


            WHEN su.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


            ELSE su.sucursal END,


        CASE WHEN su.sucursal LIKE '%ON WEB' THEN 'WEB'


            WHEN su.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN su.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN su.sucursal LIKE '%MCH%' OR su.sucursal LIKE '%EXIT%'


              OR su.sucursal LIKE '%MILK%' OR su.sucursal LIKE '%FINA%'


              OR su.sucursal LIKE '%KORDA%' OR su.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES'


            ELSE su.sucursal END,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        'SSS',


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        0,


        a.conteo,


        0,


        0,


        CASE WHEN su.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN su.sucursal LIKE '%ON WEB' THEN 'WEB'


            WHEN su.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN su.sucursal LIKE '%KORDA%' THEN 'BOUTIQUES KORDA'


            WHEN su.sucursal LIKE '%MCH%' THEN 'BOUTIQUES MCH'


            WHEN su.sucursal LIKE '%EXIT%' THEN 'BOUTIQUES EXIT'


            WHEN su.sucursal LIKE '%MILK%' THEN 'BOUTIQUES MILK'


            WHEN su.sucursal LIKE '%FINA%' THEN 'BOUTIQUES FINA'


            WHEN su.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES BLUES'


            ELSE su.sucursal END,


        CASE WHEN su.sucursal LIKE '%ON WEB' THEN 'WEB'


            WHEN su.sucursal LIKE '%ONLINE%' THEN 'WEB'


            WHEN su.sucursal LIKE '%OUTLET%' THEN 'OUTLETS'


            WHEN su.sucursal LIKE '%MCH%' OR su.sucursal LIKE '%EXIT%'


              OR su.sucursal LIKE '%MILK%' OR su.sucursal LIKE '%FINA%'


              OR su.sucursal LIKE '%KORDA%' OR su.sucursal LIKE '%BLUES%' THEN 'BOUTIQUES'


            ELSE su.sucursal END,


        0,


        0,


        0,


        0,


        0,


        0,


        'poken_hst'


    FROM public.tbretail_conteos a


    LEFT JOIN public.pla_fechas_equivalentes f ON a.fecha = f.fecha


    LEFT JOIN public.datamart_calendario c ON COALESCE(f.fecha_equivalente, a.fecha) = c.fecha


    INNER JOIN public.dm_comercial_sucursales su ON a.entidad_id = su.idsucursal


    WHERE a.tipo = 'tienda'


      AND EXTRACT(YEAR FROM a.fecha) = v_anio_ant


      AND a.fecha BETWEEN v_fecha_ini_hst AND v_fecha_fin_hst;





    INSERT INTO public.automatizacion_control_ejecucion (


        tipo_ejecucion, p_fecha_ini, p_fecha_fin,


        fecha_stock_act_usada, fecha_stock_hst_usada,


        duracion_segundos, estado, tipo_fila


    ) VALUES (


        CASE WHEN p_fecha_ini IS NULL AND NOT p_forzar_todo THEN 'incremental'


             WHEN p_forzar_todo THEN 'forzado' ELSE 'manual' END,


        v_fecha_ini, v_fecha_fin,


        NULL, NULL,


        EXTRACT(EPOCH FROM (clock_timestamp() - v_ts_inicio)),


        'OK', 'reporte_ventas'


    );





EXCEPTION WHEN OTHERS THEN


    INSERT INTO public.automatizacion_control_ejecucion (


        tipo_ejecucion, p_fecha_ini, p_fecha_fin,


        duracion_segundos, estado, mensaje_error, tipo_fila


    ) VALUES (


        'error', v_fecha_ini, v_fecha_fin,


        EXTRACT(EPOCH FROM (clock_timestamp() - v_ts_inicio)),


        'ERROR', SQLERRM, 'reporte_ventas'


    );


    RAISE;


END;


$function$;


-- 2) Ejecutar una corrida normal: ahora recarga metas_std por sí sola
SELECT automatizacion_sp_reporte_ventas(NULL, NULL, FALSE);

-- 3) Verificar que noviembre ya tiene metas (esperado: filas > 0 para 2026-11)
SELECT date_trunc('month', fecha_documento)::date AS mes, COUNT(*) AS filas,
       ROUND(SUM(meta_venta)::numeric,2) AS meta
FROM automatizacion_pla_reporte_ventas
WHERE tipo_fila = 'metas_std' AND fecha_documento >= '2026-10-01'
GROUP BY 1 ORDER BY 1;
