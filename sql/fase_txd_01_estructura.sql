-- =============================================================================
-- FASE TXD - SCRIPT 1: ESTRUCTURA
-- =============================================================================
-- Proyecto : Smart Brands Gerencial (C:\laragon\www\gerencial)
-- BD       : smartanalytic @ 172.16.1.23:5432 (user postgres)
-- Objetivo : Estructuras nuevas del flujo TXD automatizado. NO toca el reporte
--            de ventas automatizado ni sus SPs.
--
-- Contenido:
--   1) tabla de log dedicada  : automatizacion_control_ejecucion_txd
--   2) indices FALTANTES en   : automatizacion_pla_ventas_txd
--                               automatizacion_pla_reporte_txd
--   3) tabla consolidada      : automatizacion_pla_reporte_consolidado
--                               (superset columnas VENTAS + TXD + 'origen')
--   4) indices de la consolidada
--
-- NOTA de mapeo SAGA (validar con archivo real antes de fijar parametros):
--   El manual asigna la semana tematizada 03-09/08/2026 con orden INVERTIDO
--   (lunes->'2026-08-09', domingo->'2026-08-03'). Hoy es jueves 13/08/2026.
--   El pivot queda PARAMETRIZADO; confirmar p_fecha_lunes contra el archivo.
--
-- Ejecucion recomendada: psql -U postgres -d smartanalytic -f fase_txd_01_estructura.sql
-- =============================================================================

BEGIN;

-- ---------------------------------------------------------------------------
-- 1) LOG DEDICADO TXD (equivalente a automatizacion_control_ejecucion de ventas)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS automatizacion_control_ejecucion_txd (
    id                 serial PRIMARY KEY,
    tipo_ejecucion     text        NOT NULL,             -- VENTA_TXD / REPORTE_TXD / CONSOLIDADO / ORQUESTADOR
    p_fecha_ini        date,
    p_fecha_fin        date,
    p_fecha_stock      date,                              -- solo VENTA_TXD
    p_fecha_lunes      date,                              -- solo VENTA_TXD (mapping SAGA)
    id_carga           integer,                           -- ref automatizacion_cargas_txd.id
    filas_insertadas   bigint,
    filas_pivotadas    bigint,                            -- solo VENTA_TXD
    skus_faltantes     integer,
    duracion_segundos  numeric(10,2),
    estado             text        NOT NULL DEFAULT 'OK', -- OK / ERROR
    mensaje_error      text,
    fecha_ejecucion    timestamptz NOT NULL DEFAULT now()
);

COMMENT ON TABLE automatizacion_control_ejecucion_txd IS
  'Log de cada corrida del flujo TXD automatizado (ventas/reporte/consolidado/orquestador).';

-- ---------------------------------------------------------------------------
-- 2) INDICES FALTANTES (CREATE INDEX IF NOT EXISTS = idempotente)
--    Los que ya existen NO se recrean (verificados en pg_indexes).
-- ---------------------------------------------------------------------------
-- ventas_txd: ya existen (fecha), (sku_txd), (txd, fecha).
-- Se agrega (txd, sku_txd, fecha) para el post-proceso de equivalencias.
CREATE INDEX IF NOT EXISTS idx_aut_ventas_txd_canal_sku_fecha
    ON automatizacion_pla_ventas_txd (txd, sku_txd, fecha);

-- reporte_txd: ya existen (fecha), (txd), (marca), (corner), (txd,fecha), (tipo_fila).
-- Se agregan combinaciones para consultas de gerentes por marca+fecha y tipo_fila+fecha.
CREATE INDEX IF NOT EXISTS idx_aut_rep_txd_marca_fecha
    ON automatizacion_pla_reporte_txd (marca, fecha);

CREATE INDEX IF NOT EXISTS idx_aut_rep_txd_tipo_fila_fecha
    ON automatizacion_pla_reporte_txd (tipo_fila, fecha);

CREATE INDEX IF NOT EXISTS idx_aut_rep_txd_canal_marca
    ON automatizacion_pla_reporte_txd (txd, marca);

-- ---------------------------------------------------------------------------
-- 3) TABLA CONSOLIDADA (reporte unico: ventas + TXD)
--    Superset del detalle de ambas tablas. 'origen' identifica procedencia:
--       origen = 'VENTAS' -> fila proveniente de automatizacion_pla_reporte_ventas
--       origen = 'TXD'    -> fila proveniente de automatizacion_pla_reporte_txd
--    Regenerada en cada corrida por el SP automatizacion_sp_reporte_consolidado().
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS automatizacion_pla_reporte_consolidado (
    -- ---------- clave / control ----------
    origen            text            NOT NULL,           -- 'VENTAS' | 'TXD'
    tipo_fila         varchar(30),
    fecha             date,

    -- ---------- comunes unificadas (reporte / agregacion web) ----------
    dia_semana        varchar(20),
    semana            integer,
    dia_equivalente   numeric,
    mes               varchar(25),
    marca             varchar(50),
    canal             varchar(100),                       -- grupo_canal (VENTAS) | txd (TXD)
    corner            varchar(200),                       -- sucursal_3 (VENTAS) | corner (TXD)
    categoria         varchar(100),
    subcategoria      varchar(100),
    linea             varchar(100),
    linea_2           varchar(100),
    linea_2_2         text,
    sublinea          varchar(100),
    descripcion_padre varchar(300),
    coleccion         text,
    temporada         varchar(100),
    supervisor        varchar(100),
    filtro_sss2       text,
    pvp               numeric,
    meta              numeric,                            -- meta_venta (V) | meta (T)
    meta_contribucion numeric,
    gm_meta           numeric,
    cubicaje          numeric,
    area              numeric,
    vta_retail_act    numeric,
    vta_retail_hst    numeric,

    -- ---------- KPIs de venta (unificados) ----------
    vta_act           numeric,                            -- importe_subtotal (V) | vta_act (T)
    vta_hst           numeric,                            -- importe_subtotal_hst (V) | vta_hst (T)
    vta_unds_act      numeric,                            -- unidades (V) | vta_unds_act (T)
    vta_unds_hst      numeric,                            -- unidades_hst (V) | vta_unds_hst (T)
    costo_act         numeric,                            -- costo_venta_neta (V) | vta_costo_act (T)
    costo_hst         numeric,                            -- costo_venta_neta_hst (V) | vta_costo_hst (T)
    rebate_act        numeric,
    rebate_hst        numeric,
    inv_unds_act      numeric,
    inv_unds_hst      numeric,
    inv_soles_act     numeric,
    inv_soles_hst     numeric,
    inv_costo_act     numeric,
    inv_costo_hst     numeric,
    nro_tickets       bigint,

    -- ---------- detalle propio VENTAS (prefijo v_) ----------
    v_empresa                varchar(100),
    v_grupo_canal            varchar,
    v_fecha_documento        date,
    v_codigo_padre           varchar(20),
    v_marca_2                varchar(50),
    v_marca_temporada        text,
    v_proveedor              varchar(100),
    v_tipo                   varchar(100),
    v_origen                 varchar(100),
    v_localidad              text,
    v_temporada_2            varchar(100),
    v_temporada_3            varchar(100),
    v_sucursal               varchar(200),
    v_sucursal_2             varchar(200),
    v_sucursal_3             varchar(200),
    v_tipo_doc               varchar(5),
    v_serie                  integer,
    v_documento              integer,
    v_serie_documento        text,
    v_unidades_hst_1         numeric,
    v_importe_subtotal_hst_1 numeric,
    v_costo_venta_neta_hst_1 numeric,
    v_unidades_hst           integer,
    v_importe_subtotal_hst   integer,
    v_costo_venta_neta_hst   integer,
    v_unidades               numeric,
    v_importe_subtotal       numeric,
    v_costo_venta_neta       numeric,
    v_meta_venta             numeric,
    v_meta_venta_faro        numeric,
    v_meta_contribucion_faro numeric,
    v_filtro_sss             text,
    v_mall                   varchar(100),
    v_condicion              text,
    v_nro_tickets_hst_1      bigint,
    v_nro_tickets_hst        integer,
    v_dias_estancia          integer,
    v_dias_estancia_tda      integer,
    v_meses_estancia         integer,
    v_meses_estancia_tda     integer,
    v_vta_retail_hst_1       numeric,
    v_conteo                 integer,
    v_conteo_hst             integer,
    v_temporada_4            text,
    v_sucursal_2_1           varchar(200),
    v_sucursal_3_1           varchar(200),
    v_mes_coleccion          text,
    v_largo                  text,
    v_material               text,
    v_tdas_liquidadoras      text,
    v_flag_tickets_act       integer,
    v_flag_tickets_hst       integer,
    v_fecha_carga            timestamptz,

    -- ---------- detalle propio TXD (prefijo t_) ----------
    t_txd                    varchar,
    t_fecha_vta              date,
    t_anio                   integer,
    t_mes_int                integer,
    t_dia                    numeric,
    t_sku_txd                varchar,
    t_sku_padre_txd          varchar,
    t_desc_padre_txd         varchar,
    t_sku_sb                 varchar,
    t_sku_padre_sb           varchar,
    t_color                  varchar,
    t_talla                  varchar,
    t_tipo_producto          varchar,
    t_temporada_txd          varchar,
    t_temporada_txd2         varchar,
    t_temporada_sb           varchar,
    t_temporada_sb2          varchar,
    t_costo_si_act           numeric,
    t_vta_act                numeric,
    t_vta_soles_si_act       numeric,
    t_vta_unds_act           numeric,
    t_vta_costo_act          numeric,
    t_inv_unds_act           numeric,
    t_inv_soles_act          numeric,
    t_rebate_act             numeric,
    t_costo_si_hst           numeric,
    t_vta_hst                numeric,
    t_vta_soles_si_hst       numeric,
    t_vta_unds_hst           numeric,
    t_vta_costo_hst          numeric,
    t_inv_unds_hst           numeric,
    t_inv_soles_hst          numeric,
    t_rebate_hst             numeric,
    t_meta                   numeric,
    t_meta_contribucion      numeric,
    t_gm_meta                numeric,
    t_cubicaje               numeric,
    t_vta_retail_act         numeric,
    t_vta_retail_hst         numeric,
    t_area                   numeric,
    t_canal                  varchar,
    t_temporada_             varchar,
    t_mes_coleccion_         varchar,
    t_supervisor             varchar,
    t_filtro_sss2            varchar,
    t_zona                   varchar,
    t_coleccion_2            varchar,
    t_coleccion_3            varchar,
    t_tipo_fila              varchar
);

COMMENT ON TABLE automatizacion_pla_reporte_consolidado IS
  'Reporte unico consolidado = union de automatizacion_pla_reporte_ventas (origen=VENTAS)
   y automatizacion_pla_reporte_txd (origen=TXD). Regenerado por el SP de consolidado.';

-- ---------------------------------------------------------------------------
-- 4) INDICES CONSOLIDADA (patron de consulta de gerentes: origen+tipo_fila+fecha)
-- ---------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_aut_consol_origen_tipofila_fecha
    ON automatizacion_pla_reporte_consolidado (origen, tipo_fila, fecha);

CREATE INDEX IF NOT EXISTS idx_aut_consol_fecha
    ON automatizacion_pla_reporte_consolidado (fecha);

CREATE INDEX IF NOT EXISTS idx_aut_consol_marca_fecha
    ON automatizacion_pla_reporte_consolidado (marca, fecha);

CREATE INDEX IF NOT EXISTS idx_aut_consol_canal_fecha
    ON automatizacion_pla_reporte_consolidado (canal, fecha);

COMMIT;

-- =============================================================================
-- Verificacion rapida (opcional):
--   SELECT count(*) FROM automatizacion_pla_reporte_consolidado;  -- 0 recien creada
--   SELECT indexname FROM pg_indexes WHERE tablename='automatizacion_pla_reporte_consolidado';
-- =============================================================================