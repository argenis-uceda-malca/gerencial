# ARQUITECTURA COMPLETA — Smart Brands S.A.C.
> Documento generado el 2026-07-14 tras revisión integral del código fuente
> Proyecto: Gerencial (Laravel) + ETL (PL/pgSQL) + Pentaho

---

## 1. Resumen Arquitectónico

```
┌───────────────────────────────────────────────────────────────────────┐
│                         ORÍGENES DE DATOS                             │
├────────────────────┬──────────────────┬───────────────────────────────┤
│  SQL Server (ERP)  │  TB Retail API   │  soluflex_faro_reporting      │
│  (histórico        │  (tráfico/       │  (metas FARO, tablas          │
│   completo)        │   visitas)       │   auxiliares)                 │
└─────────┬──────────┴────────┬─────────┴───────────────┬───────────────┘
          │                   │                         │
          ▼                   ▼                         ▼
┌───────────────────────────────────────────────────────────────────────┐
│                    CAPA DE VOLCADO (Pentaho)                          │
│                                                                       │
│  Frecuencia: cada 10 min (TRUNCATE + INSERT completo)                 │
│  Ejecuta: ETL Pentaho en servidor (no gestionado desde Laravel)       │
│                                                                       │
│  Tablas destino en `smartanalytic` (172.16.1.23:5432):                │
│  ┌──────────────────────────────┬──────────────┬──────────────────┐   │
│  │ Tabla                       │ Cobertura    │ Filas aprox.     │   │
│  ├──────────────────────────────┼──────────────┼──────────────────┤   │
│  │ datamart_ventas_actual      │ 2026         │ ~424K            │   │
│  │ datamart_ventas_2025        │ 2025         │ Histórico        │   │
│  │ datamart_ventas_2024        │ 2024         │ Histórico        │   │
│  │ datamart_ventas_2023        │ 2023         │ Histórico        │   │
│  │ datamart_ventas_2022        │ 2022         │ Histórico        │   │
│  │ datamart_ventas_2021        │ 2021         │ Histórico        │   │
│  │ datamart_logistica_movimientos_actual │ 2026 │ Mov. inventario │   │
│  │ datamart_logistica_movimientos       │ 2019-2025 │ 14 GB (1.3M)│   │
│  └──────────────────────────────┴──────────────┴──────────────────┘   │
└───────────────────────────────────────────────────────────────────────┘
          │
          ▼
┌───────────────────────────────────────────────────────────────────────┐
│               CAPA ETL (PL/pgSQL en Postgres)                         │
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │ PASO 1                                                          │  │
│  │ automatizacion_sp_pla_ventas_diarias(fecha_ini, fecha_fin)     │  │
│  │                                                            │    │  │
│  │  Lee: datamart_ventas_actual (o _YYYY para histórico)          │  │
│  │  Escribe: automatizacion_pla_ventas_diarias                     │  │
│  │  Lógica: DELETE + INSERT por rango de fechas                   │  │
│  │   - Filtra sucursales activas (dm_sucursales_activas)          │  │
│  │   - Aplica equivalencias de codigo_padre (dm_codigos_equivalencia)│  │
│  │   - JOIN con mes_coleccion_1 (catálogo)                        │  │
│  │   - Maneja renombres históricos (dm_sucursal_cambios)          │  │
│  │   - Limpia registros sin datos útiles                          │  │
│  └─────────────────────────────────────────────────────────────────┘  │
│                              ▼                                        │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │ PASO 2                                                          │  │
│  │ automatizacion_sp_filtro_sss(fecha_ini, fecha_fin)             │  │
│  │                                                            │    │  │
│  │  Lee: automatizacion_pla_ventas_diarias                         │  │
│  │       + dm_sucursal_clasificacion                               │  │
│  │  Escribe: automatizacion_pla_sucursal_filtro                    │  │
│  │  Lógica: clasifica cada sucursal/día en SSS/NUEVO/CIERRE       │  │
│  │           según umbrales de venta y antigüedad de apertura      │  │
│  └─────────────────────────────────────────────────────────────────┘  │
│                              ▼                                        │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │ PASO 3                                                          │  │
│  │ automatizacion_sp_reporte_ventas(fecha_ini, fecha_fin, forzar) │  │
│  │                                                            │    │  │
│  │  Lee: ventas_diarias + sucursal_filtro + dm_*                  │  │
│  │       + stock_semanal + mes_coleccion_1                        │  │
│  │       + pla_fechas_equivalentes + metas                        │  │
│  │  Escribe: automatizacion_pla_reporte_ventas (TABLA FINAL)      │  │
│  │  Lógica: construye 10 bloques (tipo_fila) en una sola tabla    │  │
│  │           con columnas paralelas (act vs hst)                   │  │
│  │  - p_forzar_todo=TRUE → recarga todo                           │  │
│  │  - p_forzar_todo=FALSE → solo ventas y poken                   │  │
│  └─────────────────────────────────────────────────────────────────┘  │
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │ FUNCIÓN AUXILIAR — Stock                                        │  │
│  │ automatizacion_sp_insertar_stock_semana(fecha_cierre)           │  │
│  │                                                                  │  │
│  │  Lee: datamart_logistica_movimientos* + saldos_iniciales        │  │
│  │  Escribe: automatizacion_stock_semanal                           │  │
│  │  Es idempotente: DELETE fecha + INSERT                           │  │
│  └─────────────────────────────────────────────────────────────────┘  │
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │ FUNCIÓN HISTÓRICA — Ventas años anteriores                      │  │
│  │ automatizacion_sp_pla_ventas_diarias_historico(año, ini, fin)   │  │
│  │                                                                  │  │
│  │  Lee: datamart_ventas_YYYY + datamart_logistica_movimientos     │  │
│  │  Escribe: automatizacion_pla_ventas_diarias                      │  │
│  │  Para cargar años completos (2021-2025)                          │  │
│  └─────────────────────────────────────────────────────────────────┘  │
└───────────────────────────────────────────────────────────────────────┘
          │
          ▼
┌───────────────────────────────────────────────────────────────────────┐
│              CAPA DE CONSUMO — automatizacion_pla_reporte_ventas      │
│                                                                       │
│  ~1.67M filas, 816 MB, columna `tipo_fila` identifica el bloque      │
│                                                                       │
│  ┌────────────────┬──────────┬────────────────────────────────────┐   │
│  │ tipo_fila      │ Filas    │ Columnas que llena                 │   │
│  ├────────────────┼──────────┼────────────────────────────────────┤   │
│  │ ventas_act     │ 1.1M     │ importe_subtotal, unidades,        │   │
│  │                │          │ costo_venta_neta, nro_tickets,     │   │
│  │                │          │ flag_tickets_act, pvp              │   │
│  ├────────────────┼──────────┼────────────────────────────────────┤   │
│  │ ventas_hst     │ 27K      │ importe_subtotal_hst_1,           │   │
│  │                │          │ unidades_hst_1, costo_venta_neta_  │   │
│  │                │          │ hst_1, nro_tickets_hst_1          │   │
│  ├────────────────┼──────────┼────────────────────────────────────┤   │
│  │ stock_act      │ 289K     │ inv_unds_act, inv_costo_act       │   │
│  ├────────────────┼──────────┼────────────────────────────────────┤   │
│  │ stock_hst      │ 0        │ (pendiente de cargar)             │   │
│  ├────────────────┼──────────┼────────────────────────────────────┤   │
│  │ metas_std      │ 209K     │ meta_venta, meta_contribucion     │   │
│  ├────────────────┼──────────┼────────────────────────────────────┤   │
│  │ metas_faro     │ 48K      │ meta_venta_faro                   │   │
│  ├────────────────┼──────────┼────────────────────────────────────┤   │
│  │ poken_act      │ 6K       │ conteo                            │   │
│  ├────────────────┼──────────┼────────────────────────────────────┤   │
│  │ poken_hst      │ 6.5K     │ conteo_hst                        │   │
│  ├────────────────┼──────────┼────────────────────────────────────┤   │
│  │ area           │ ~80      │ (valores fijos por sucursal)      │   │
│  ├────────────────┼──────────┼────────────────────────────────────┤   │
│  │ cubicajes      │ ~80      │ (valores fijos por sucursal)      │   │
│  └────────────────┴──────────┴────────────────────────────────────┘   │
└───────────────────────────────────────────────────────────────────────┘
          │
          ▼
┌───────────────────────────────────────────────────────────────────────┐
│                CAPA WEB (Laravel — http://gerencial.test)             │
│                                                                       │
│  ┌───────────────────────────────────────────────────────────────┐   │
│  │ SISTEMA NUEVO (automatizacion_)                               │   │
│  │                                                               │   │
│  │  DashboardReporteController  → /dashboard/reporte             │   │
│  │   ├─ pivot()  → JSON agregado para AG Grid + PivotEngine     │   │
│  │   │             3 queries: ventas_act, ventas_hst, metas_std  │   │
│  │   ├─ dia()    → Pivot por día de semana (canal × día)        │   │
│  │   └─ detalle()→ Tabla detalle con subtotales por canal       │   │
│  │                                                               │   │
│  │  DashboardVentasController  → /dashboard/ventas               │   │
│  │   ├─ tiendas()      → Ranking de tiendas por venta           │   │
│  │   ├─ rows()         → Filas planas día × marca × canal       │   │
│  │   └─ topProductos() → Top 10 productos por SKU               │   │
│  │                                                               │   │
│  │  DashboardGerencialController → /dashboard/gerencial          │   │
│  │   └─ index() → Resumen simple con tabla de cumplimiento     │   │
│  │                                                               │   │
│  │  DashboardFfToController → /dashboard/ff-to                   │   │
│  │   ├─ data()  → FF (tráfico) vs TO (ventas) por sucursal     │   │
│  │   └─ index() → Vista con filtros                             │   │
│  │                                                               │   │
│  │  TbRetailService → integración API TB Retail                  │   │
│  │   └─ guardarConteosTbRetail() → tbretail_conteos             │   │
│  │                                                               │   │
│  │  PivotEngine (JS) / AG Grid Community / SortableJS           │   │
│  │   → Tabla dinámica tipo Excel en el navegador                │   │
│  ├───────────────────────────────────────────────────────────────┤   │
│  │ SISTEMA LEGADO (datamart_* directo)                          │   │
│  │                                                               │   │
│  │  ReportEnterController  → /reporte, /reportesb, /tabla      │   │
│  │  InicioController       → /inicio (gráficos)                 │   │
│  │  ReportRfmController    → /rfm                               │   │
│  │  ReproteTxdController   → /reportetxd (Python)              │   │
│  └───────────────────────────────────────────────────────────────┘   │
└───────────────────────────────────────────────────────────────────────┘
```

---

## 2. Conexiones a Bases de Datos

| Alias .env | Host | Puerto | Base | Usuario | Propósito |
|---|---|---|---|---|---|
| `pgsql` (default) | 172.16.1.23 | 5432 | `smartanalytic` | postgres | ETL + dashboards nuevos |
| `DB_HOST2` | 172.16.1.30 | 54322 | `db_bi` | postgres | (sin uso en código) |
| `DB_HOST_SRV` | 172.16.1.3 | 5432 | `soluflex_faro_reporting` | sa | Metas FARO (no referenciada directamente) |

> **Nota:** `DB_HOST2` (`db_bi`) está en `.env` pero **no se referencia** en ningún controlador o comando. Posiblemente heredada.

---

## 3. Tablas de Referencia (Dimensión)

| Esquema | Tabla | Propósito |
|---|---|---|
| `public` | `automatizacion_dm_sucursales_activas` | Sucursales habilitadas en reporte |
| `public` | `automatizacion_dm_sucursal_cambios` | Renombres históricos (KORDA→MILK IQUITOS, etc.) |
| `public` | `automatizacion_dm_sucursal_clasificacion` | localidad_override, tdas_liquidadoras |
| `public` | `automatizacion_dm_codigos_equivalencia` | Mapeo de codigo_padre cuando cambia |
| `public` | `automatizacion_costo_stk` | Stock + costo por almacén/fecha |
| `public` | `automatizacion_temp_estancia` | Estancia por producto/fecha |
| `public` | `mes_coleccion_1` | Catálogo de productos (única excepción a regla "no modificar") |
| `public` | `pla_fechas_equivalentes` | Equivalencia fecha actual ↔ año anterior |
| `public` | `tbretail_conteos` | Tráfico de visitantes (TB Retail) |
| `public` | `location_id_tienda` | Mapeo location API ↔ tienda Smart Brands |

---

## 4. Tablas de Control

| Tabla | Propósito |
|---|---|
| `automatizacion_control_ejecucion` | Log de cada corrida: tipo, fechas, duración, estado, error |
| `automatizacion_alertas` | Alertas cuando el orquestador falla 2 veces seguidas |

---

## 5. Diagrama de Flujo Detallado (por tipo de dato)

### 5.1 Ventas

```
Pentaho (TRUNC+INSERT c/10 min desde SQL Server)
    │
    ▼
datamart_ventas_actual (año 2026)
datamart_ventas_2025 / _2024 / _2023 / _2022 / _2021 (históricos)
    │
    ▼
[automatizacion_sp_pla_ventas_diarias]
    │  • DELETE + INSERT por rango
    │  • Filtra: solo sucursales activas
    │  • JOIN: mes_coleccion_1 (categoría, línea, temporada)
    │  • Aplica: dm_codigos_equivalencia (codigo_padre)
    │  • Renombra: dm_sucursal_cambios (sucursal_2, sucursal_3)
    │  • Limpia: filas sin importe, sin unidades, sin codigo_padre
    ▼
automatizacion_pla_ventas_diarias (136K filas, 74 MB)
    │
    ▼
[automatizacion_sp_reporte_ventas]
    │  • JOIN con sucursal_filtro (clasificación SSS)
    │  • JOIN con pla_fechas_equivalentes (para bloque _hst)
    │  • Calcula flag_tickets_act (1 = primer producto del ticket)
    │  • Agrupa por fecha × sucursal × producto
    │  • Genera tipo_fila = 'ventas_act' (año actual)
    │  • Genera tipo_fila = 'ventas_hst' (año anterior, misma semana)
    ▼
automatizacion_pla_reporte_ventas
```

### 5.2 Stock (multisemana)

```
datamart_logistica_movimientos_actual (2026)
datamart_logistica_movimientos (2019-2025, 14 GB)
dm_almacenes_saldo_inicial_anio_actual (2026)
dm_almacenes_saldo_inicial_anio (años anteriores)
    │
    ▼
[automatizacion_sp_insertar_stock_semana(fecha_cierre)]
    │  • Acumula movimientos de inventario hasta fecha_cierre
    │  • Aplica saldo inicial del año
    │  • Calcula stock final por producto × almacén
    │  • INSERT en automatizacion_stock_semanal (idempotente)
    ▼
automatizacion_stock_semanal
    │
    ▼ (leído por sp_reporte_ventas, bloque stock)
automatizacion_pla_reporte_ventas (tipo_fila = 'stock_act' / 'stock_hst')
```

### 5.3 Metas

```
[Origen externo: archivos Excel → carga manual]
    │
    ▼
automatizacion_pla_reporte_ventas
    tipo_fila = 'metas_std'  → meta_venta, meta_contribucion
    tipo_fila = 'metas_faro' → meta_venta_faro
```

### 5.4 Tráfico (Poken — TB Retail)

```
TB Retail API (GraphQL — https://api.tbretail.com)
    │
    ▼
[TbRetailService::guardarConteosTbRetail(tipo, fecha)]
    │  1. Obtiene access_token (refresh_token + client_id)
    │  2. Consulta métrica ENTERS por location (lista fija de ~50 locations)
    │  3. Mapea location → marca/tienda (location_id_tienda)
    │  4. UPSERT en tbretail_conteos
    ▼
tbretail_conteos
    │
    ▼ (leído por sp_reporte_ventas, bloque poken)
automatizacion_pla_reporte_ventas (tipo_fila = 'poken_act' / 'poken_hst')
```

### 5.5 FF vs TO (Follow-up — Dashboard)

```
┌──────────────────────┐    ┌──────────────────────────┐
│  tbretail_conteos    │    │  automatizacion_pla_     │
│  (tráfico = FF)      │    │  reporte_ventas (ventas) │
│                      │    │  tipo_fila='ventas_act'  │
│                      │    │  tipo_fila='ventas_hst'  │
└──────────┬───────────┘    └──────────┬────────────────┘
           │                           │
           └──────────┬────────────────┘
                      ▼
         [DashboardFfToController::data()]
           • CR = tickets / FF (conversion rate)
           • ATV = venta / tickets (avg ticket value)
           • Ratio = venta / FF
           • Gap = ratio_act - ratio_hst
                      │
                      ▼
         JSON → AG Grid (frontend)
```

---

## 6. Comandos Artisan (ETL)

| Comando | Firma | Pasos ETL | Estado |
|---|---|---|---|
| `etl:refrescar-ultimos-dias` | `{dias=3}` | Paso 1 + Paso 3 (salta filtro_sss) | ✅ |
| `etl:refrescar-filtro-sss` | — | Paso 2 solo | ✅ |
| `tbretail:guardar-conteos` | `{fecha?}` | API TB Retail | ✅ |
| `temp:debug` | — | Debug (comparación datos) | ⚠️ Debug |

**Scheduler (`Kernel.php`) — TODO COMENTADO:**
```php
// $schedule->command('etl:refrescar-ultimos-dias')
//      ->everyTenMinutes()->withoutOverlapping(5);
// $schedule->command('etl:refrescar-filtro-sss')
//      ->dailyAt('04:00')->withoutOverlapping(10);
// $schedule->command('tbretail:guardar-conteos')
//      ->dailyAt('02:00');
```

---

## 7. Frontend — Tabla Dinámica (Pivot)

| Componente | Tecnología | Versión | Rol |
|---|---|---|---|
| Grid principal | AG Grid Community | 31.3.2 | Render + virtualización + sort/filtros |
| Motor pivot | PivotEngine (propio) | v2 | Group/aggregate/pivot en JS puro |
| Drag & drop | SortableJS | 1.15.2 | Panel de configuración de ejes |
| Export | SheetJS (xlsx) | 0.18.5 | Exportación a Excel |
| Persistencia | localStorage | — | Guarda configuración (`pivot_reporte_cfg_v4`) |

### Medidas disponibles en PivotEngine

| Clave | Label | Tipo | Formato |
|---|---|---|---|
| `vta26` | Vta Neta 26 | raw (sum) | S/ |
| `vta25` | Vta Neta 25 | raw (sum) | S/ |
| `gm26` | GM 26 (S/) | raw (sum) | S/ |
| `gm25` | GM 25 (S/) | raw (sum) | S/ |
| `unds26` | Unid 26 | raw (sum) | entero |
| `unds25` | Unid 25 | raw (sum) | entero |
| `tickets26` | Tickets | raw (sum) | entero |
| `meta_vta` | Meta Vta | raw (sum) | S/ |
| `var_pct` | %Var vs 25 | calc | % con signo |
| `gm26_pct` | %GM 26 | calc | % |
| `gm25_pct` | %GM 25 | calc | % |
| `var_unds` | %Var Unid | calc | % con signo |
| `pprom26` | P.Prom 26 | calc | S/ |
| `ticket_prom` | Ticket Prom | calc | S/ |
| `cumpl_pct` | %Cumpl Meta | calc | % |

### Dimensiones disponibles

`Mes`, `Semana`, `Día #`, `Día`, `Canal`, `Subcanal`, `Tienda`, `Marca`, `Marca Temporada`, `Categoría`, `SSS`, `Localidad`, `Lineas`, `Temporada`

---

## 8. Problemas Detectados

### 🔴 Críticos

| # | Problema | Impacto | Solución Propuesta |
|---|---|---|---|
| 1 | **Condición de carrera Pentaho** — TRUNCATE+INSERT c/10 min; si el ETL nuevo lee durante la escritura, obtiene datos incompletos | Reportes con sucursales faltantes, montos incorrectos | Implementar staging + swap atómico (diseño existe en `solucion_pentaho_swap_atomico.md`) |
| 2 | **Sin automatización** — pg_cron no instalado; scheduler Laravel todo comentado | Proceso manual, riesgo de olvido, datos desactualizados | Activar scheduler Laravel con tarea programada en Windows/Laragon (cada 10 min) |
| 3 | **stock_hst = 0 filas** — No hay stock del año anterior | Dashboards no pueden calcular variación de inventario vs 2025 | Cargar 52 semanas de stock 2025 con `automatizacion_sp_insertar_stock_semana()` |
| 4 | **2025 en ventas_act** — Datos de 2025 están como tipo_fila='ventas_act' en vez de 'ventas_hst' | Comparativas año vs año incorrectas; 2026 no puede ocupar ventas_act limpio | Reorganizar: pasar 2025 a ventas_hst, cargar 2026 en ventas_act |

### 🟡 Medios

| # | Problema | Impacto | Solución Propuesta |
|---|---|---|---|
| 5 | **Índices faltantes** — `fase_indices_automatizacion.sql` no ejecutado | Consultas lentas al crecer la tabla (1.67M → 10M+ con histórico) | Ejecutar script: índices en categoria, marca, codigo_padre, (categoria+fecha) |
| 6 | **`sp_filtro_sss` no es idempotente** — Si corre 2 veces para el mismo rango, duplica filas en `sucursal_filtro` | Montos del reporte se duplican (x2, x3...) | Hacer DELETE+INSERT en la función, no solo INSERT |
| 7 | **Scheduler incompleto** — `etl:refrescar-ultimos-dias` salta `sp_filtro_sss` | Clasificación SSS no se actualiza durante todo el día | Agregar llamado a filtro_sss o crear orquestador único |
| 8 | **Sin manejo de errores** — Los comandos ETL no capturan excepciones | Fallos silenciosos, reporte desactualizado sin aviso | Agregar try/catch con `Log::error` y `automatizacion_alertas` |
| 9 | **`db_bi` sin uso** — Definida en `.env` (172.16.1.30) pero no referenciada | Confusión, posible config huérfana | Documentar o remover |

### 🟢 Bajos / Mejora Continua

| # | Mejora | Beneficio |
|---|---|---|
| 10 | Mover credenciales hardcodeadas (TB Retail token, client_secret) a `.env` | Seguridad |
| 11 | Generar `pla_fechas_equivalentes` desde `datamart_calendario` automáticamente | Menos mantenimiento manual |
| 12 | Agregar tests PHPUnit para controllers nuevos | Detectar regresiones |
| 13 | Agregar métricas de duración por paso a `automatizacion_control_ejecucion` | Monitoreo de rendimiento |
| 14 | Agregar timeout en queries del frontend (pivot/detalle) | Evitar requests colgadas |

---

## 9. Mantenimiento Recomendado

### 9.1 Diario

```sql
-- Verificar estado del sistema
SELECT
    (SELECT MAX(fecha_documento) FROM automatizacion_pla_reporte_ventas
     WHERE tipo_fila='ventas_act' AND EXTRACT(YEAR FROM fecha_documento)=2026) AS ultima_venta,
    (SELECT current_date - MAX(fecha_documento) FROM automatizacion_pla_reporte_ventas
     WHERE tipo_fila='ventas_act' AND EXTRACT(YEAR FROM fecha_documento)=2026) AS retraso_dias,
    (SELECT MAX(fecha) FROM automatizacion_stock_semanal) AS ultimo_stock,
    (SELECT estado FROM automatizacion_control_ejecucion ORDER BY id DESC LIMIT 1) AS ultimo_estado;

-- Verificar alertas pendientes
SELECT * FROM automatizacion_alertas WHERE atendida = FALSE ORDER BY fecha_alerta DESC;
```

### 9.2 Semanal

```sql
-- Verificar duplicados en sucursal_filtro
SELECT idsucursal, semana, COUNT(*) AS duplicados
FROM automatizacion_pla_sucursal_filtro
GROUP BY idsucursal, semana HAVING COUNT(*) > 1;

-- Verificar tamaño de tablas automatizacion_
SELECT relname, n_live_tup AS filas,
       pg_size_pretty(pg_total_relation_size(relid)) AS total
FROM pg_stat_user_tables
WHERE relname LIKE 'automatizacion_%'
ORDER BY n_live_tup DESC;

-- Verificar mes_coleccion_1 sin duplicados
SELECT COUNT(*) AS duplicados FROM (
    SELECT codigo_padrex FROM mes_coleccion_1
    GROUP BY codigo_padrex HAVING COUNT(*) > 1
) x;
```

### 9.3 Mensual

```sql
-- Monitoreo de Notas de Crédito con latencia alta
-- Ver sql/fase_monitoreo_nc_latencia.sql

-- Resumen de ejecuciones del último mes
SELECT tipo_ejecucion,
       COUNT(*) AS corridas,
       ROUND(AVG(duracion_segundos)::numeric, 1) AS seg_prom,
       COUNT(*) FILTER (WHERE estado != 'OK') AS errores
FROM automatizacion_control_ejecucion
WHERE created_at > now() - interval '30 days'
GROUP BY tipo_ejecucion ORDER BY tipo_ejecucion;
```

### 9.4 Checklist Mensual de Salud

- [ ] `dias_retraso` en ventas_act = 0 o 1
- [ ] Sin registros en `automatizacion_alertas WHERE atendida = FALSE`
- [ ] Sin duplicados en `automatizacion_pla_sucursal_filtro`
- [ ] Stock semanal actualizado (última semana disponible)
- [ ] `mes_coleccion_1` sin duplicados
- [ ] Índices funcionando (`EXPLAIN ANALYZE` en queries lentas)
- [ ] Logs de Laravel sin errores ETL (`storage/logs/laravel.log`)

---

## 10. Flujo Recomendado de Automatización

Cuando se active el scheduler, este debería ser el orquestador ideal:

```
[Timer] cada 10 min (Laravel schedule:run vía Windows Task Scheduler)
    │
    ├─ 1. Verificar sin overlapping previo
    │
    ├─ 2. [Siempre] automatizacion_sp_pla_ventas_diarias(NULL, NULL)
    │      → últimos 7 días (~2-15 seg)
    │
    ├─ 3. [Solo primeras 3 del día] automatizacion_sp_filtro_sss(NULL, NULL)
    │      → evita ejecutar cada 10 min (~1-5 seg)
    │
    ├─ 4. [Siempre] automatizacion_sp_reporte_ventas(NULL, NULL, FALSE)
    │      → solo ventas y poken (~2-15 seg)
    │
    └─ 5. Registrar en log + alertar si falla

[Diario 04:00] (adicional)
    ├─ Refrescar filtro SSS (si no se ejecutó antes)
    ├─ [Lunes] Insertar stock de la semana que cerró
    └─ [1° del mes] Verificar alertas + limpiar logs viejos
```

Comando `artisan` único sugerido (no implementado):

```bash
# Orquestador completo (reemplazaría a los 2 comandos actuales)
php artisan etl:ejecutar-completo
```

---

## 11. Archivos del Proyecto (estructura clave)

```
C:\laragon\www\gerencial\
│
├── app/
│   ├── Console/
│   │   ├── Kernel.php                    # Scheduler (comentado)
│   │   └── Commands/
│   │       ├── RefrescarUltimosDiasEtl.php   # Paso 1 + 3
│   │       ├── RefrescarFiltroSss.php        # Paso 2
│   │       ├── GuardarConteosTbRetail.php    # TB Retail
│   │       └── TempDebugCommand.php          # Debug
│   │
│   ├── Http/Controllers/
│   │   ├── DashboardReporteController.php    # Pivot + día + detalle
│   │   ├── DashboardVentasController.php     # Ventas, tiendas, top
│   │   ├── DashboardGerencialController.php  # Resumen gerencial
│   │   ├── DashboardFfToController.php       # FF vs TO
│   │   ├── ReportEnterController.php         # LEGACY (1800+ líneas)
│   │   ├── ReporteVentasController.php       # LEGACY (stub)
│   │   ├── ReportRfmController.php           # LEGACY
│   │   ├── ReproteTxdController.php          # LEGACY (Python)
│   │   └── InicioController.php              # LEGACY
│   │
│   └── Services/
│       └── TbRetailService.php               # API TB Retail
│
├── resources/views/
│   ├── dashboard/
│   │   ├── reporte.blade.php                 # Pivot + AG Grid + SortableJS
│   │   ├── ventas.blade.php                  # KPI cards + gráficos
│   │   ├── gerencial.blade.php               # Tabla resumen
│   │   └── ff_to.blade.php                   # FF vs TO compacto
│   └── ... (vistas legacy)
│
├── public/assets/js/
│   └── pivot-engine.js                       # Motor pivot JS sin dependencias
│
├── routes/
│   ├── web.php                               # Rutas legacy + nuevas
│   └── api.php                               # Sanctum (1 ruta)
│
├── .env                                      # Conexiones DB
├── CLAUDE.md                                 # Contexto permanente
│
└── docs/
    ├── ARQUITECTURA_COMPLETA.md               # ← Este archivo
    └── pivot-arquitectura.md                  # Decisión técnica pivot
```

---

## 12. Scripts SQL Referenciados (NO encontrados en el repo local)

Los siguientes scripts existen según la documentación pero **no están en `C:\laragon\www\gerencial\sql\`** (probablemente en el VPS o por crearse):

| Script | Descripción | Estado |
|---|---|---|
| `fase_indices_automatizacion.sql` | Índices para tablas automatizacion_ | **Pendiente** |
| `fase_instalacion_pg_cron.md` | Guía instalación pg_cron en VPS | **Pendiente** |
| `fase_pgcron_job_automatizacion.sql` | Orquestador + job pg_cron cada 30 min | **Pendiente** |
| `solucion_pentaho_swap_atomico.md` | Diseño swap atómico Pentaho | **Pendiente** |
| `carga_historica_ventas.sql` | Función carga ventas históricas | Disponible (ejecutado) |
| `automatizacion_stock_multisemana.sql` | Función stock por semana | Ejecutado |
| `cambios_quirurgicos_sp_dbeaver.sql` | Cambios stock_act/stock_hst en SP | Ejecutado |
| `fase_monitoreo_nc_latencia.sql` | Monitoreo mensual NCs con latencia | Usar mensualmente |
| `fase_limpieza_mes_coleccion_1_en_sitio.sql` | Limpieza de mes_coleccion_1 | Ejecutado |
| `fase_fix_ecommerce_off_sucursales_activas.sql` | INSERT ECOMMERCE OFF | Ejecutado |

---

## 13. Pendientes Prioritarios

| # | Pendiente | Esfuerzo | Depende de |
|---|---|---|---|
| 1 | Ejecutar `fase_indices_automatizacion.sql` | 5 min | Acceso a Postgres |
| 2 | Activar scheduler Laravel (descomentar Kernel.php + configurar tarea) | 15 min | — |
| 3 | Hacer `automatizacion_sp_filtro_sss` idempotente (DELETE+INSERT) | 30 min | — |
| 4 | Cargar stock histórico 2025 (52 semanas) | 2-3 h | Función stock funcionando |
| 5 | Implementar swap atómico Pentaho | 4-8 h | Diseño existente |
| 6 | Reorganizar ventas_act (2025 → ventas_hst) | 2 h | Carga histórica 2026 completa |
| 7 | Agregar manejo de errores en comandos ETL | 1 h | — |
| 8 | Mover credenciales TB Retail a `.env` | 30 min | — |
| 9 | Cargar ventas históricas 2021-2024 | 1-2 h/año | Funciones históricas |
| 10 | Instalar pg_cron en VPS | 1 h (con reinicio) | Acceso ssh al VPS |

---

## 14. Reglas Fundamentales

1. **Todo lo nuevo lleva prefijo `automatizacion_`.** Tablas, funciones, índices.
2. **No modificar tablas del flujo original:** `pla_ventas_diarias_2`, `pla_reporte_ventas_2`, `pla_reporte_ventas`, `sp_pla_ventas_diarias`.
3. **Excepción:** `mes_coleccion_1` es catálogo y se puede modificar.
4. **Migraciones Laravel:** solo para tablas de la app web, NUNCA para tablas ETL.
5. **`.env` no va al repositorio** (en `.gitignore`).
6. **Nunca correr `sp_filtro_sss` dos veces para el mismo rango** sin limpiar antes.
7. **Nunca correr `sp_reporte_ventas` con rangos solapados** — hacer DELETE previo del rango si se repite.

---

*Documento generado tras revisión de: 4 controladores nuevos, 1 servicio, 3 comandos Artisan, routes, frontend pivot engine, config, .env, y documentación existente.*
