-- ============================================================
-- Refactor: SAGA FALABELLA ventas usa fecha directa del archivo
-- Elimina columnas lunes-domingo del staging, elimina p_fecha_lunes
-- Ejecutar en psql o pgAdmin conectado a smartanalytic
-- ============================================================

BEGIN;

-- ============================================================
-- 1) Vaciar staging antes de alterar esquema
-- ============================================================
TRUNCATE automatizacion_temp_saga_txd;
TRUNCATE automatizacion_pla_temp_saga_txd;

-- ============================================================
-- 2) Cambiar esquema de ambas tablas staging
--    Reemplaza lunes..domingo + nro_local por fecha
-- ============================================================
ALTER TABLE automatizacion_temp_saga_txd
    ADD COLUMN fecha date,
    ALTER COLUMN skip    DROP NOT NULL,
    ALTER COLUMN nro_local DROP NOT NULL,
    DROP COLUMN lunes,
    DROP COLUMN martes,
    DROP COLUMN miercoles,
    DROP COLUMN jueves,
    DROP COLUMN viernes,
    DROP COLUMN sabado,
    DROP COLUMN domingo;

ALTER TABLE automatizacion_pla_temp_saga_txd
    ADD COLUMN fecha date,
    ALTER COLUMN skip    DROP NOT NULL,
    ALTER COLUMN nro_local DROP NOT NULL,
    DROP COLUMN lunes,
    DROP COLUMN martes,
    DROP COLUMN miercoles,
    DROP COLUMN jueves,
    DROP COLUMN viernes,
    DROP COLUMN sabado,
    DROP COLUMN domingo;

-- ============================================================
-- 3) Reemplazar automatizacion_sp_ventas_txd
--    - Elimina p_fecha_lunes
--    - Auto-detecta p_fecha_ini / p_fecha_fin del staging
--    - Reemplaza pivot por inserción directa con fecha real
-- ============================================================
DROP FUNCTION IF EXISTS automatizacion_sp_ventas_txd(date, date, date, date);

CREATE OR REPLACE FUNCTION automatizacion_sp_ventas_txd(
    p_fecha_ini   date DEFAULT NULL,
    p_fecha_fin   date DEFAULT NULL,
    p_fecha_stock date DEFAULT NULL
) RETURNS integer
LANGUAGE plpgsql AS $$
DECLARE
    v_id_carga          integer;
    v_inicio            timestamp := clock_timestamp();
    v_filas_insertadas  integer := 0;
    v_filas_pivotadas   integer := 0;
    v_skus_faltantes    integer := 0;
BEGIN
    -- -----------------------------------------------------------------
    -- 1) Auto-detectar rango desde el contenido del staging.
    --    Oechsle y Ripley ya tienen fecha en el archivo.
    --    SAGA ahora también (created_at → fecha real).
    -- -----------------------------------------------------------------
    SELECT MIN(min_f), MAX(max_f)
    INTO p_fecha_ini, p_fecha_fin
    FROM (
        SELECT MIN(fecha) AS min_f, MAX(fecha) AS max_f
            FROM automatizacion_temp_oechsle_txd WHERE fecha IS NOT NULL
        UNION ALL
        SELECT MIN(fecha), MAX(fecha)
            FROM automatizacion_temp_ripley_txd WHERE fecha IS NOT NULL
        UNION ALL
        SELECT MIN(fecha), MAX(fecha)
            FROM automatizacion_temp_saga_txd WHERE fecha IS NOT NULL
    ) t;

    -- Fallback si los tres staging están vacíos
    IF p_fecha_ini IS NULL THEN
        p_fecha_fin := current_date - 1;
        p_fecha_ini := p_fecha_fin - 6;
    END IF;

    p_fecha_stock := COALESCE(p_fecha_stock, p_fecha_fin);

    -- -----------------------------------------------------------------
    -- 2) Registrar la carga
    -- -----------------------------------------------------------------
    INSERT INTO automatizacion_cargas_txd
        (canal, fecha_carga, usuario, fecha_ini, fecha_fin, fecha_lunes, nombre_archivo, estado)
    VALUES
        ('TXD_CONSOLIDADO', now(), current_user,
         p_fecha_ini, p_fecha_fin, p_fecha_ini, NULL, 'EN PROCESO')
    RETURNING id INTO v_id_carga;

    -- -----------------------------------------------------------------
    -- 3) Copiar staging → historizado (con id_carga)
    -- -----------------------------------------------------------------
    INSERT INTO automatizacion_pla_temp_oechsle_txd
        (id_carga, fecha, sku_txd, desc_sku, marca, cod_local, desc_local,
         vta_act, vta_unds, stk_soles, stk_unds)
    SELECT v_id_carga, fecha,
           CASE WHEN sku_txd LIKE '%.0' THEN TRIM(TRAILING '.0' FROM sku_txd) ELSE sku_txd END,
           desc_sku, marca, cod_local, desc_local, vta_act, vta_unds, stk_soles, stk_unds
    FROM automatizacion_temp_oechsle_txd;

    INSERT INTO automatizacion_pla_temp_ripley_txd
        (id_carga, fecha, sku_txd, desc_sku, codigo_modelo, nombre_modelo, marca,
         temporada, sucursal, rebate_act, vta_soles, vta_unds, contr, costo_vta,
         stock_soles, stock_unds)
    SELECT v_id_carga, fecha,
           CASE WHEN sku_txd LIKE '%.0' THEN TRIM(TRAILING '.0' FROM sku_txd) ELSE sku_txd END,
           desc_sku, codigo_modelo, nombre_modelo, marca, temporada, sucursal,
           rebate_act, vta_soles, vta_unds, contr, costo_vta, stock_soles, stock_unds
    FROM automatizacion_temp_ripley_txd;

    -- SAGA: ahora tiene columna fecha en lugar de lunes..domingo
    INSERT INTO automatizacion_pla_temp_saga_txd
        (id_carga, sku, desc_sku, sucursal, fecha, vta_unds, vta_soles, marca)
    SELECT v_id_carga, sku, desc_sku, sucursal, fecha, vta_unds, vta_soles, marca
    FROM automatizacion_temp_saga_txd;

    INSERT INTO automatizacion_pla_temp_stock_txd
        (id_carga, sku_txd, desc_hijo_txd, sucursal, temporada, inv_unds_act, marca)
    SELECT v_id_carga, sku_txd, desc_hijo_txd, sucursal, temporada, inv_unds_act, marca
    FROM automatizacion_stock_txd;

    -- -----------------------------------------------------------------
    -- 4) SAGA: insertar en tabla de trabajo con costo_unit
    --    Antes era un pivot lunes..domingo → fechas. Ahora la fecha ya
    --    viene directamente del archivo (campo created_at del Seller Center).
    -- -----------------------------------------------------------------
    INSERT INTO automatizacion_pla_temp_saga_txd_2
        (id_carga, fecha, sku, desc_sku, sucursal, vta_unds, costo_unit, marca)
    SELECT
        v_id_carga,
        s.fecha,
        TRIM(s.sku),
        TRIM(s.desc_sku),
        s.sucursal,
        s.vta_unds,
        CASE WHEN s.vta_unds = 0 THEN 0 ELSE s.vta_soles / s.vta_unds END,
        COALESCE(NULLIF(s.marca, ''), p.marca)
    FROM automatizacion_pla_temp_saga_txd s
    LEFT JOIN (
        SELECT DISTINCT ON (sku_txd) sku_txd, sku_sb
        FROM pla_sku_txd ORDER BY sku_txd, sku_sb
    ) k ON TRIM(s.sku) = k.sku_txd
    LEFT JOIN datamart_logistica_productos p ON k.sku_sb = p.codigo_producto
    WHERE s.id_carga = v_id_carga
      AND s.vta_unds > 0;

    GET DIAGNOSTICS v_filas_pivotadas = ROW_COUNT;

    -- -----------------------------------------------------------------
    -- 5) Limpiar rango antes de re-insertar (permite reprocesos)
    -- -----------------------------------------------------------------
    DELETE FROM automatizacion_pla_ventas_txd
    WHERE fecha BETWEEN p_fecha_ini AND p_fecha_fin
      AND txd IN ('SAGA FALABELLA', 'OECHSLE', 'RIPLEY');

    -- -----------------------------------------------------------------
    -- 6) Insertar combinado en tabla final
    -- -----------------------------------------------------------------
    INSERT INTO automatizacion_pla_ventas_txd
        (txd, fecha, sku_txd, desc_hijo_txd, sku_padre, desc_padre_txd, marca,
         temporada_txd, desc_local, vta_soles, vta_unds, inv_soles, inv_unds,
         vta_costo, rebate)

    -- SAGA ventas (con fecha real del archivo)
    SELECT
        'SAGA FALABELLA', s.fecha, TRIM(s.sku), TRIM(s.desc_sku), NULL, NULL,
        s.marca, p.temporada, TRIM(s.sucursal),
        s.costo_unit * s.vta_unds,
        s.vta_unds,
        0, 0,
        (s.costo_unit * s.vta_unds) * 0.81,
        0
    FROM automatizacion_pla_temp_saga_txd_2 s
    LEFT JOIN (
        SELECT DISTINCT ON (sku_txd) sku_txd, sku_sb
        FROM pla_sku_txd ORDER BY sku_txd, sku_sb
    ) k ON TRIM(s.sku) = k.sku_txd
    LEFT JOIN datamart_logistica_productos p ON k.sku_sb = p.codigo_producto
    WHERE s.id_carga = v_id_carga

    UNION ALL

    -- OECHSLE
    SELECT
        'OECHSLE', a.fecha, a.sku_txd, NULL, b.sku_padre, b.estilo,
        a.marca, b.temporada, a.desc_local,
        sum(a.vta_act), sum(a.vta_unds),
        sum(a.stk_soles), sum(a.stk_unds),
        sum(a.vta_act) * 0.68,
        0
    FROM automatizacion_pla_temp_oechsle_txd a
    LEFT JOIN pla_ficha_productos_txd b ON a.sku_txd = b.cod_txd
    WHERE a.id_carga = v_id_carga
    GROUP BY a.fecha, a.sku_txd, b.sku_padre, b.estilo, a.marca, b.temporada, a.desc_local

    UNION ALL

    -- RIPLEY
    SELECT
        'RIPLEY', fecha, sku_txd, desc_sku, codigo_modelo, nombre_modelo,
        marca, temporada, sucursal,
        sum(vta_soles), sum(vta_unds),
        sum(stock_soles), sum(stock_unds),
        sum(costo_vta),
        sum(rebate_act)
    FROM automatizacion_pla_temp_ripley_txd
    WHERE id_carga = v_id_carga
    GROUP BY fecha, sku_txd, desc_sku, codigo_modelo, nombre_modelo,
             marca, temporada, sucursal

    UNION ALL

    -- STOCK SAGA (snapshot puntual; fecha = p_fecha_stock)
    SELECT
        'SAGA FALABELLA', p_fecha_stock, s.sku_txd, s.desc_hijo_txd, NULL, NULL,
        p.marca, s.temporada, s.sucursal,
        0, 0,
        0, sum(s.inv_unds_act),
        0, 0
    FROM automatizacion_pla_temp_stock_txd s
    LEFT JOIN (
        SELECT DISTINCT ON (sku_txd) sku_txd, sku_sb
        FROM pla_sku_txd ORDER BY sku_txd, sku_sb
    ) k ON TRIM(s.sku_txd) = k.sku_txd
    LEFT JOIN datamart_logistica_productos p ON k.sku_sb = p.codigo_producto
    WHERE s.id_carga = v_id_carga
    GROUP BY s.sku_txd, s.desc_hijo_txd, p.marca, s.temporada, s.sucursal;

    GET DIAGNOSTICS v_filas_insertadas = ROW_COUNT;

    -- -----------------------------------------------------------------
    -- 7) SKUs faltantes
    -- -----------------------------------------------------------------
    SELECT count(*) INTO v_skus_faltantes
    FROM (
        SELECT DISTINCT TRIM(o.sku_txd)
        FROM automatizacion_pla_temp_oechsle_txd o
        LEFT JOIN pla_sku_txd r ON TRIM(o.sku_txd) = r.sku_txd
        WHERE o.id_carga = v_id_carga AND r.sku_sb IS NULL
        UNION
        SELECT DISTINCT TRIM(q.sku_txd)
        FROM automatizacion_pla_temp_ripley_txd q
        LEFT JOIN pla_sku_txd r ON TRIM(q.sku_txd) = r.sku_txd
        WHERE q.id_carga = v_id_carga AND r.sku_sb IS NULL
        UNION
        SELECT DISTINCT TRIM(y.sku)
        FROM automatizacion_pla_temp_saga_txd_2 y
        LEFT JOIN pla_sku_txd r ON TRIM(y.sku) = r.sku_txd
        WHERE y.id_carga = v_id_carga AND r.sku_sb IS NULL
        UNION
        SELECT DISTINCT TRIM(st.sku_txd)
        FROM automatizacion_pla_temp_stock_txd st
        LEFT JOIN pla_sku_txd r ON TRIM(st.sku_txd) = r.sku_txd
        WHERE st.id_carga = v_id_carga AND r.sku_sb IS NULL
    ) faltantes;

    -- -----------------------------------------------------------------
    -- 8) Cerrar carga + log
    -- -----------------------------------------------------------------
    UPDATE automatizacion_cargas_txd
    SET estado = 'OK', skus_faltantes = v_skus_faltantes
    WHERE id = v_id_carga;

    INSERT INTO automatizacion_control_ejecucion_txd
        (tipo_ejecucion, p_fecha_ini, p_fecha_fin, p_fecha_stock, p_fecha_lunes,
         id_carga, filas_insertadas, filas_pivotadas, skus_faltantes,
         duracion_segundos, estado)
    VALUES
        ('VENTA_TXD', p_fecha_ini, p_fecha_fin, p_fecha_stock, p_fecha_ini,
         v_id_carga, v_filas_insertadas, v_filas_pivotadas, v_skus_faltantes,
         EXTRACT(EPOCH FROM (clock_timestamp() - v_inicio)), 'OK');

    RETURN v_id_carga;

EXCEPTION WHEN OTHERS THEN
    UPDATE automatizacion_cargas_txd SET estado = 'ERROR', notas = SQLERRM
    WHERE id = v_id_carga;

    INSERT INTO automatizacion_control_ejecucion_txd
        (tipo_ejecucion, p_fecha_ini, p_fecha_fin, p_fecha_stock, p_fecha_lunes,
         id_carga, filas_insertadas, filas_pivotadas, skus_faltantes,
         duracion_segundos, estado, mensaje_error)
    VALUES
        ('VENTA_TXD', p_fecha_ini, p_fecha_fin, p_fecha_stock, p_fecha_ini,
         v_id_carga, v_filas_insertadas, v_filas_pivotadas, v_skus_faltantes,
         EXTRACT(EPOCH FROM (clock_timestamp() - v_inicio)), 'ERROR', SQLERRM);

    RAISE;
END;
$$;

-- ============================================================
-- 4) Reemplazar automatizacion_ejecutar_txd_completo
--    - Elimina p_fecha_lunes
--    - Lee fechas detectadas desde automatizacion_cargas_txd
--    - Las pasa a automatizacion_sp_reporte_txd
-- ============================================================
DROP FUNCTION IF EXISTS automatizacion_ejecutar_txd_completo(date, date, date, date, boolean);

CREATE OR REPLACE FUNCTION automatizacion_ejecutar_txd_completo(
    p_fecha_ini   date    DEFAULT NULL,
    p_fecha_fin   date    DEFAULT NULL,
    p_fecha_stock date    DEFAULT NULL,
    p_forzar_todo boolean DEFAULT FALSE
) RETURNS boolean
LANGUAGE plpgsql AS $$
DECLARE
    v_inicio        timestamp := clock_timestamp();
    v_intento       integer;
    v_exito         boolean;
    v_ultimo_error  text;
    v_resultado     integer;
    v_fecha_ini     date;
    v_fecha_fin     date;
BEGIN
    -- -----------------------------------------------------------------
    -- PASO 1: ventas TXD (auto-detecta fechas del staging)
    -- -----------------------------------------------------------------
    v_exito := FALSE;
    FOR v_intento IN 1..2 LOOP
        BEGIN
            SELECT automatizacion_sp_ventas_txd(p_fecha_ini, p_fecha_fin, p_fecha_stock)
            INTO v_resultado;
            v_exito := TRUE;
            EXIT;
        EXCEPTION WHEN OTHERS THEN
            v_ultimo_error := SQLERRM;
        END;
    END LOOP;

    IF NOT v_exito THEN
        INSERT INTO automatizacion_alertas
            (fecha_alerta, paso_fallido, mensaje_error, intentos, atendida)
        VALUES (now(), 'VENTAS_TXD', v_ultimo_error, 2, FALSE);

        INSERT INTO automatizacion_control_ejecucion_txd
            (tipo_ejecucion, p_fecha_ini, p_fecha_fin, p_fecha_stock, p_fecha_lunes,
             duracion_segundos, estado, mensaje_error)
        VALUES
            ('ORQUESTADOR', p_fecha_ini, p_fecha_fin, p_fecha_stock, p_fecha_ini,
             EXTRACT(EPOCH FROM (clock_timestamp() - v_inicio)), 'ERROR',
             'Falló VENTAS_TXD tras 2 intentos: ' || v_ultimo_error);

        RETURN FALSE;
    END IF;

    -- Leer las fechas detectadas por el SP de ventas (guardadas en cargas_txd)
    SELECT fecha_ini, fecha_fin INTO v_fecha_ini, v_fecha_fin
    FROM automatizacion_cargas_txd WHERE id = v_resultado;

    -- -----------------------------------------------------------------
    -- PASO 2: reporte TXD con las fechas detectadas
    -- -----------------------------------------------------------------
    v_exito := FALSE;
    FOR v_intento IN 1..2 LOOP
        BEGIN
            SELECT automatizacion_sp_reporte_txd(v_fecha_ini, v_fecha_fin, p_forzar_todo)
            INTO v_resultado;
            v_exito := TRUE;
            EXIT;
        EXCEPTION WHEN OTHERS THEN
            v_ultimo_error := SQLERRM;
        END;
    END LOOP;

    IF NOT v_exito THEN
        INSERT INTO automatizacion_alertas
            (fecha_alerta, paso_fallido, mensaje_error, intentos, atendida)
        VALUES (now(), 'REPORTE_TXD', v_ultimo_error, 2, FALSE);

        INSERT INTO automatizacion_control_ejecucion_txd
            (tipo_ejecucion, p_fecha_ini, p_fecha_fin, p_fecha_stock, p_fecha_lunes,
             duracion_segundos, estado, mensaje_error)
        VALUES
            ('ORQUESTADOR', v_fecha_ini, v_fecha_fin, p_fecha_stock, v_fecha_ini,
             EXTRACT(EPOCH FROM (clock_timestamp() - v_inicio)), 'ERROR',
             'VENTAS_TXD ok, pero falló REPORTE_TXD tras 2 intentos: ' || v_ultimo_error);

        RETURN FALSE;
    END IF;

    -- -----------------------------------------------------------------
    -- PASO 3: consolidado
    -- -----------------------------------------------------------------
    v_exito := FALSE;
    FOR v_intento IN 1..2 LOOP
        BEGIN
            SELECT automatizacion_sp_reporte_consolidado() INTO v_resultado;
            v_exito := TRUE;
            EXIT;
        EXCEPTION WHEN OTHERS THEN
            v_ultimo_error := SQLERRM;
        END;
    END LOOP;

    IF NOT v_exito THEN
        INSERT INTO automatizacion_alertas
            (fecha_alerta, paso_fallido, mensaje_error, intentos, atendida)
        VALUES (now(), 'CONSOLIDADO', v_ultimo_error, 2, FALSE);

        INSERT INTO automatizacion_control_ejecucion_txd
            (tipo_ejecucion, p_fecha_ini, p_fecha_fin, p_fecha_stock, p_fecha_lunes,
             duracion_segundos, estado, mensaje_error)
        VALUES
            ('ORQUESTADOR', v_fecha_ini, v_fecha_fin, p_fecha_stock, v_fecha_ini,
             EXTRACT(EPOCH FROM (clock_timestamp() - v_inicio)), 'ERROR',
             'VENTAS_TXD y REPORTE_TXD ok, pero falló CONSOLIDADO tras 2 intentos: ' || v_ultimo_error);

        RETURN FALSE;
    END IF;

    INSERT INTO automatizacion_control_ejecucion_txd
        (tipo_ejecucion, p_fecha_ini, p_fecha_fin, p_fecha_stock, p_fecha_lunes,
         duracion_segundos, estado)
    VALUES
        ('ORQUESTADOR', v_fecha_ini, v_fecha_fin, p_fecha_stock, v_fecha_ini,
         EXTRACT(EPOCH FROM (clock_timestamp() - v_inicio)), 'OK');

    RETURN TRUE;
END;
$$;

COMMIT;

-- ============================================================
-- Verificación post-migración
-- ============================================================
SELECT
    table_name,
    string_agg(column_name, ', ' ORDER BY ordinal_position) AS columnas
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name IN ('automatizacion_temp_saga_txd', 'automatizacion_pla_temp_saga_txd')
GROUP BY table_name;
