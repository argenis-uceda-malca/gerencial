-- ============================================================================
-- fase_stock_diario_resumen.sql
-- Stock diario resumido por almacén × marca × categoría × línea × temporada.
--
-- Origen : automatizacion_stock_semanal (foto diaria a nivel SKU, ya existente)
-- Destino: automatizacion_stock_diario_resumen (~9 mil filas por día)
--
-- NO modifica automatizacion_sp_reporte_ventas ni ninguna tabla del flujo
-- original. Todo lo que crea lleva el prefijo automatizacion_.
--
-- Regla de tiempos: cada llamada debe terminar en menos de 1 minuto.
-- Medido: agregar 1 día tarda ~4 s. El SP rechaza rangos de más de 10 días;
-- para rangos largos usar `php artisan etl:stock-diario`, que llama día a día.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1. Catálogo: qué tipo de almacén es cada uno
--    Permite separar "stock en tienda" del stock total de la empresa.
--    Se llena solo con una regla por nombre; las filas con editado_manual=TRUE
--    no se vuelven a tocar, así que se puede corregir a mano.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.automatizacion_dm_almacen_tipo (
    idalmacen       INTEGER PRIMARY KEY,
    almacen         VARCHAR(200),
    tipo_almacen    VARCHAR(30) NOT NULL,
    editado_manual  BOOLEAN NOT NULL DEFAULT FALSE,
    actualizado     TIMESTAMP NOT NULL DEFAULT now()
);

COMMENT ON TABLE public.automatizacion_dm_almacen_tipo IS
'Tipo de almacén para el stock: TIENDA, ECOMMERCE, CONSIGNACION EN TIENDA, TXD, CD, TRANSITO, MAYORISTA, MERMA, OTROS';


-- ----------------------------------------------------------------------------
-- 2. Tabla resumen diaria
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.automatizacion_stock_diario_resumen (
    fecha         DATE NOT NULL,
    semana        INTEGER,
    idalmacen     INTEGER,
    almacen       VARCHAR(200),   -- nombre tal cual viene del almacén
    sucursal      VARCHAR(200),   -- nombre normalizado igual que el bloque stock_act del reporte
    sucursal_2_1  VARCHAR(200),   -- subcanal
    sucursal_3_1  VARCHAR(200),   -- canal
    localidad     VARCHAR(20),
    filtro_sss    VARCHAR(100),
    marca         VARCHAR(200),
    categoria     VARCHAR(200),
    linea         VARCHAR(200),
    temporada     TEXT,
    inv_unds      NUMERIC NOT NULL DEFAULT 0,
    inv_costo     NUMERIC NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_auto_stkdia_fecha
    ON public.automatizacion_stock_diario_resumen (fecha);
CREATE INDEX IF NOT EXISTS idx_auto_stkdia_almacen_fecha
    ON public.automatizacion_stock_diario_resumen (idalmacen, fecha);

-- Fechas ya cargadas (1 fila por día). La vista la usa para saber cuál es el
-- último día disponible de cada semana y de cada mes sin recorrer el resumen.
CREATE TABLE IF NOT EXISTS public.automatizacion_stock_diario_fechas (
    fecha       DATE PRIMARY KEY,
    filas       INTEGER NOT NULL,
    inv_unds    NUMERIC NOT NULL,
    inv_costo   NUMERIC NOT NULL,
    cargado     TIMESTAMP NOT NULL DEFAULT now()
);


-- ----------------------------------------------------------------------------
-- 3. SP de carga (DELETE + INSERT por rango, idempotente)
--    Default: ayer. Máximo 10 días por llamada.
-- ----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION public.automatizacion_sp_stock_diario_resumen(
    p_fecha_ini DATE DEFAULT NULL,
    p_fecha_fin DATE DEFAULT NULL
)
RETURNS INTEGER
LANGUAGE plpgsql
SET work_mem TO '128MB'
AS $function$
DECLARE
    v_fecha_ini  DATE := COALESCE(p_fecha_ini, current_date - 1);
    v_fecha_fin  DATE := COALESCE(p_fecha_fin, COALESCE(p_fecha_ini, current_date - 1));
    v_ts_inicio  TIMESTAMP := clock_timestamp();
    v_filas      INTEGER := 0;
BEGIN
    IF v_fecha_fin < v_fecha_ini THEN
        RAISE EXCEPTION 'Rango inválido: % > %', v_fecha_ini, v_fecha_fin;
    END IF;
    IF v_fecha_fin - v_fecha_ini > 9 THEN
        RAISE EXCEPTION 'Máximo 10 días por llamada (pedido: % a %). Usar etl:stock-diario para rangos largos.',
            v_fecha_ini, v_fecha_fin;
    END IF;

    -- (1) Almacenes nuevos → catálogo de tipos (no pisa los editados a mano)
    INSERT INTO public.automatizacion_dm_almacen_tipo (idalmacen, almacen, tipo_almacen)
    SELECT x.idalmacen, x.almacen,
           CASE WHEN x.almacen ILIKE '%MERMA%' THEN 'MERMA'
                WHEN x.almacen ILIKE '%RIPLEY%' OR x.almacen ILIKE '%OECHSLE%'
                  OR x.almacen ILIKE '%FALABELLA%' THEN 'TXD'
                WHEN x.almacen ILIKE '%CONSIGNACI%' THEN 'CONSIGNACION EN TIENDA'
                WHEN x.almacen ILIKE '%ON WEB%' OR x.almacen ILIKE '%ONLINE%'
                  OR x.almacen ILIKE 'ECOMMERCE%' THEN 'ECOMMERCE'
                WHEN a.flag_transito = 'S' THEN 'TRANSITO'
                WHEN x.almacen ILIKE '%MAYORISTA%' THEN 'MAYORISTA'
                WHEN a.flag_cd = 'S' THEN 'CD'
                WHEN x.almacen ~ '^(EXIT|MILK|MCH|FINA|KORDA|BLUES|OUTLET)' THEN 'TIENDA'
                ELSE 'OTROS' END
    FROM (
        SELECT idsucursal AS idalmacen, MAX(sucursal) AS almacen
        FROM public.automatizacion_stock_semanal
        WHERE fecha BETWEEN v_fecha_ini AND v_fecha_fin
          AND idsucursal IS NOT NULL
        GROUP BY idsucursal
    ) x
    LEFT JOIN public.dm_almacenes a ON a.idalmacen = x.idalmacen
    ON CONFLICT (idalmacen) DO NOTHING;

    -- (2) Resumen
    DELETE FROM public.automatizacion_stock_diario_resumen
    WHERE fecha BETWEEN v_fecha_ini AND v_fecha_fin;

    INSERT INTO public.automatizacion_stock_diario_resumen (
        fecha, semana, idalmacen, almacen, sucursal, sucursal_2_1, sucursal_3_1,
        localidad, filtro_sss, marca, categoria, linea, temporada, inv_unds, inv_costo
    )
    SELECT
        stk.fecha,
        MAX(stk.semana),
        stk.idsucursal,
        stk.sucursal,
        -- Mismas reglas que el bloque stock_act de automatizacion_sp_reporte_ventas,
        -- salvo la consignación: los almacenes se llaman '- CONSIGNACIÓN' (con
        -- tilde) y el reporte busca sin tilde, así que allí nunca se unen a su
        -- tienda. Aquí sí se unen, igual que en el proceso manual del Excel.
        CASE WHEN stk.sucursal LIKE '%ON WEB'
                THEN replace(stk.sucursal,'ON WEB','ONLINE')
             ELSE trim(regexp_replace(stk.sucursal, '\s*-\s*CONSIGNACI[OÓ]N$', '')) END,
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
        CASE WHEN stk.sucursal LIKE '%TRUJILLO' OR stk.sucursal LIKE '%PIURA%'
               OR stk.sucursal LIKE '%CHICLAYO%' OR stk.sucursal LIKE '%IQUITOS%' THEN 'Norte'
             WHEN stk.sucursal LIKE '%CUSCO' OR stk.sucursal LIKE '%AREQUIPA%' THEN 'Sur'
             ELSE 'Lima' END,
        tp.filtro_sss,
        stk.marca,
        stk.categoria,
        stk.linea,
        stk.temporada,
        SUM(stk.stock),
        COALESCE(SUM(stk.costo_mn), 0)
    FROM public.automatizacion_stock_semanal stk
    LEFT JOIN public.automatizacion_pla_sucursal_filtro tp
           ON trim(regexp_replace(stk.sucursal, '\s*-\s*CONSIGNACI[OÓ]N$', '')) = tp.sucursal
          AND tp.fecha = stk.fecha
    WHERE stk.fecha BETWEEN v_fecha_ini AND v_fecha_fin
      AND stk.categoria NOT IN (
            'MERCHANDISING','SUMINISTROS','ACTIVOS',
            'AVIOS E INSUMOS','SERVICIOS GENERALES','TELAS'
          )
    GROUP BY stk.fecha, stk.idsucursal, stk.sucursal, tp.filtro_sss,
             stk.marca, stk.categoria, stk.linea, stk.temporada
    HAVING SUM(stk.stock) <> 0 OR COALESCE(SUM(stk.costo_mn), 0) <> 0;

    GET DIAGNOSTICS v_filas = ROW_COUNT;

    -- (3) Control de fechas cargadas
    DELETE FROM public.automatizacion_stock_diario_fechas
    WHERE fecha BETWEEN v_fecha_ini AND v_fecha_fin;

    INSERT INTO public.automatizacion_stock_diario_fechas (fecha, filas, inv_unds, inv_costo)
    SELECT fecha, COUNT(*), SUM(inv_unds), SUM(inv_costo)
    FROM public.automatizacion_stock_diario_resumen
    WHERE fecha BETWEEN v_fecha_ini AND v_fecha_fin
    GROUP BY fecha;

    INSERT INTO public.automatizacion_control_ejecucion
        (tipo_ejecucion, p_fecha_ini, p_fecha_fin, filas_stock_act,
         duracion_segundos, estado, tipo_fila)
    VALUES ('stock_diario_resumen', v_fecha_ini, v_fecha_fin, v_filas,
            EXTRACT(EPOCH FROM (clock_timestamp() - v_ts_inicio)), 'OK', 'stock_resumen');

    RETURN v_filas;
END;
$function$;


-- ----------------------------------------------------------------------------
-- 4. Vista de consumo (web y Excel)
--    es_cierre_semana / es_cierre_mes marcan el último día CARGADO de cada
--    semana (lunes a domingo) y de cada mes. Para la semana o mes en curso es
--    el último día disponible. El stock no se suma entre días: para ver una
--    semana o un mes se filtra por el flag correspondiente.
-- ----------------------------------------------------------------------------
CREATE OR REPLACE VIEW public.automatizacion_vw_stock_diario AS
WITH cierres AS (
    SELECT fecha,
           fecha = MAX(fecha) OVER (PARTITION BY date_trunc('week',  fecha)) AS es_cierre_semana,
           fecha = MAX(fecha) OVER (PARTITION BY date_trunc('month', fecha)) AS es_cierre_mes,
           fecha = MAX(fecha) OVER ()                                        AS es_ultima_foto
    FROM public.automatizacion_stock_diario_fechas
)
SELECT r.fecha,
       r.semana,
       c.mes_nombre                       AS mes,
       EXTRACT(YEAR FROM r.fecha)::INT    AS anio,
       r.idalmacen,
       r.almacen,
       COALESCE(t.tipo_almacen, 'OTROS')  AS tipo_almacen,
       r.sucursal,
       r.sucursal_2_1,
       r.sucursal_3_1,
       r.localidad,
       r.filtro_sss,
       r.marca,
       r.categoria,
       r.linea,
       r.temporada,
       r.inv_unds,
       r.inv_costo,
       k.es_cierre_semana,
       k.es_cierre_mes,
       k.es_ultima_foto
FROM public.automatizacion_stock_diario_resumen r
JOIN cierres k ON k.fecha = r.fecha
LEFT JOIN public.automatizacion_dm_almacen_tipo t ON t.idalmacen = r.idalmacen
LEFT JOIN public.datamart_calendario c ON c.fecha = r.fecha;


-- ============================================================================
-- VALIDACIÓN (ejecutar después de cargar; todo es solo lectura)
-- ============================================================================

-- V1. El resumen debe cuadrar exacto con el bloque stock_act del reporte
--     para las fechas que existen en ambos (hoy: 2026-09-20 y 2026-09-22).
--     Esperado: 0 filas, SOLO si el stock por SKU de esas fechas no se ha
--     recalculado después de generar el bloque del reporte (los movimientos
--     que llegan con fecha atrasada cambian la foto) y sin contar las tiendas
--     con almacén de consignación, que aquí se unen a su tienda.
/*
WITH a AS (
    SELECT fecha_documento AS fecha, sucursal, marca, categoria,
           SUM(inv_unds_act) AS unds, SUM(inv_costo_act) AS costo
    FROM automatizacion_pla_reporte_ventas
    WHERE tipo_fila = 'stock_act'
    GROUP BY 1,2,3,4
), b AS (
    SELECT fecha, sucursal, marca, categoria,
           SUM(inv_unds) AS unds, SUM(inv_costo) AS costo
    FROM automatizacion_stock_diario_resumen
    WHERE fecha IN (SELECT DISTINCT fecha FROM a)
    GROUP BY 1,2,3,4
)
SELECT COALESCE(a.fecha, b.fecha) AS fecha, COALESCE(a.sucursal, b.sucursal) AS sucursal,
       COALESCE(a.marca, b.marca) AS marca, COALESCE(a.categoria, b.categoria) AS categoria,
       a.unds AS unds_reporte, b.unds AS unds_resumen,
       a.costo AS costo_reporte, b.costo AS costo_resumen
FROM a FULL JOIN b
  ON a.fecha = b.fecha AND a.sucursal IS NOT DISTINCT FROM b.sucursal
 AND a.marca IS NOT DISTINCT FROM b.marca AND a.categoria IS NOT DISTINCT FROM b.categoria
WHERE COALESCE(a.unds,0) <> COALESCE(b.unds,0)
   OR ROUND(COALESCE(a.costo,0),2) <> ROUND(COALESCE(b.costo,0),2);
*/

-- V2. Stock por tipo de almacén en la última foto (stock en tienda vs total).
/*
SELECT tipo_almacen, COUNT(DISTINCT idalmacen) AS almacenes,
       SUM(inv_unds) AS unds, ROUND(SUM(inv_costo)) AS costo
FROM automatizacion_vw_stock_diario
WHERE es_ultima_foto
GROUP BY 1 ORDER BY unds DESC;
*/

-- V3. Días cargados y saltos bruscos de un día a otro (señal de carga mala).
/*
SELECT fecha, filas, inv_unds, ROUND(inv_costo) AS costo,
       inv_unds - LAG(inv_unds) OVER (ORDER BY fecha) AS variacion_unds
FROM automatizacion_stock_diario_fechas
ORDER BY fecha DESC LIMIT 40;
*/

-- V4. Revisar y corregir a mano el tipo de un almacén (ejemplo):
/*
SELECT * FROM automatizacion_dm_almacen_tipo ORDER BY tipo_almacen, almacen;
UPDATE automatizacion_dm_almacen_tipo
   SET tipo_almacen = 'OTROS', editado_manual = TRUE, actualizado = now()
 WHERE idalmacen = 0;
*/
