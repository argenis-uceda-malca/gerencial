# FLUJO ETL COMPLETO — Smart Brands S.A.C.
> Resumen detallado de todo el flujo: orígenes → Pentaho → ETL PL/pgSQL → web.
> Creado a partir del código fuente real, `docs/ARQUITECTURA_COMPLETA.md` y CLAUDE.md.

---

## PARTE 1 — ORÍGENES DE DATOS

Hay **3 fuentes** que alimentan todo:

| Fuente | Servidor | Qué aporta |
|---|---|---|
| **SQL Server (ERP)** | Servidor transaccional | Ventas históricas completas + movimientos de inventario |
| **TB Retail API** | `https://api.tbretail.com` (GraphQL) | Tráfico/visitas (`ENTERS`) de ~50-58 locations físicas |
| **Soluflex** | `172.16.1.3:5432` base `soluflex_faro_reporting` | Metas FARO + tablas auxiliares (no consultada directamente por el código actual) |

Además hay **2 APIs externas** que la web consume en vivo:
- **API Smart Brands** (`apirest.sbperu.com/v2/smartapp/...`) — login de usuarios, reportes RFM, listado de usuarios Soluflex.
- **API TB Retail** — el tráfico.

---

## PARTE 2 — CAPA DE VOLCADO (PENTAHO)

Pentaho corre **en el servidor** (no es gestionado por Laravel). Frecuencia **cada 10 min**, método `TRUNCATE + INSERT` completo (no incremental).

Tablas destino en **PostgreSQL `smartanalytic` (172.16.1.23:5432)**:

| Tabla | Contenido | Filas aprox. |
|---|---|---|
| `datamart_ventas_actual` | Ventas 2026 | ~424K |
| `datamart_ventas_2025 / _2024 / _2023 / _2022 / _2021` | Históricos anuales | Histórico |
| `datamart_logistica_movimientos_actual` | Movimientos inventario 2026 | — |
| `datamart_logistica_movimientos` | Movimientos 2019-2025 | 14 GB / 1.3M |

> ⚠️ **Riesgo conocido**: el `TRUNCATE+INSERT` cada 10 min puede generar lectura de datos incompletos si el ETL nuevo lee durante la escritura (condición de carrera). Hay un diseño de "swap atómico" documentado pero **pendiente de implementar**.

**Tablas auxiliares del volcado** (poblarían también el histórico): `dm_almacenes_saldo_inicial_anio_actual` (2026) y `dm_almacenes_saldo_inicial_anio` (años previos) — usadas para calcular stock.

---

## PARTE 3 — CAPA ETL PL/pgSQL (el corazón del flujo)

Todo bajo prefijo `automatizacion_` (regla fundamental del proyecto). El flujo tiene **3 pasos principales + 2 funciones auxiliares**.

### PASO 1 — `automatizacion_sp_pla_ventas_diarias(p_fecha_ini, p_fecha_fin)`

- **Lee:** `datamart_ventas_actual` (o `datamart_ventas_YYYY` para histórico).
- **Escribe:** `automatizacion_pla_ventas_diarias` (136K filas, 74 MB, 2 meses).
- **Default:** últimos 7 días si se pasa `NULL, NULL`.
- **Lógica (DELETE + INSERT por rango):**
  1. Filtra **solo sucursales activas** (`automatizacion_dm_sucursales_activas`).
  2. Aplica **equivalencias de `codigo_padre`** (`automatizacion_dm_codigos_equivalencia`) — maneja cambios de código de producto.
  3. **JOIN con `mes_coleccion_1`** (catálogo de productos: categoría, línea, temporada, sublínea).
  4. Aplica **renombres históricos de sucursal** (`automatizacion_dm_sucursal_cambios`) — genera `sucursal_2` y `sucursal_3` correctos según `fecha_desde/fecha_hasta`.
  5. **Limpia** filas sin datos útiles (sin importe, sin unidades, sin codigo_padre).

**Variante histórica:** `automatizacion_sp_pla_ventas_diarias_historico(año, ini, fin)` — carga años completos (2021-2025) leyendo `datamart_ventas_YYYY` + `datamart_logistica_movimientos`.

### PASO 2 — `automatizacion_sp_filtro_sss(p_fecha_ini, p_fecha_fin)`

- **Lee:** `automatizacion_pla_ventas_diarias` + `automatizacion_dm_sucursal_clasificacion`.
- **Escribe:** `automatizacion_pla_sucursal_filtro`.
- **Lógica:** clasifica cada sucursal/día en **SSS / NUEVO / CIERRE** según umbrales de venta y antigüedad de apertura.
- **Default:** mes anterior al mes siguiente.
- ⚠️ **No es idempotente**: correrlo 2 veces sobre el mismo rango duplica filas y distorsiona montos.

### PASO 3 — `automatizacion_sp_reporte_ventas(p_fecha_ini, p_fecha_fin, p_forzar_todo)`

- **Lee:** ventas diarias + filtro SSS + todas las `dm_*` + `automatizacion_stock_semanal` + `mes_coleccion_1` + `pla_fechas_equivalentes` + metas.
- **Escribe:** **`automatizacion_pla_reporte_ventas` — LA TABLA FINAL DE CONSUMO** (1.67M filas, 816 MB).
- **Lógica:** construye **10 bloques** en una sola tabla, identificados por `tipo_fila`, con columnas paralelas `act` vs `hst`:

| tipo_fila | Filas aprox. | Columnas que llena |
|---|---|---|
| `ventas_act` | 1.1M | `importe_subtotal`, `unidades`, `costo_venta_neta`, `nro_tickets`, `flag_tickets_act` (marca 1er producto del ticket), `pvp` |
| `ventas_hst` | 27K | mismas columnas con sufijo `_1` (año anterior, misma semana vía `pla_fechas_equivalentes`) |
| `stock_act` | 289K | `inv_unds_act`, `inv_costo_act` |
| `stock_hst` | **0** | pendiente cargar 52 semanas de stock 2025 |
| `metas_std` | 209K | `meta_venta`, `meta_contribucion` |
| `metas_faro` | 48K | `meta_venta_faro` |
| `poken_act` | 6K | `conteo` |
| `poken_hst` | 6.5K | `conteo_hst` |
| `area` | ~80 | valores fijos por sucursal |
| `cubicajes` | ~80 | valores fijos por sucursal |

- **Refresco inteligente:** `p_forzar_todo=FALSE` solo recarga ventas + poken (stock/metas solo si cambiaron); `TRUE` recarga todo.

### FUNCIÓN AUXILIAR — `automatizacion_sp_insertar_stock_semana(fecha_cierre)`

- **Lee:** `datamart_logistica_movimientos_actual` + `datamart_logistica_movimientos` + saldos iniciales de año.
- **Escribe:** `automatizacion_stock_semanal`.
- **Lógica:** acumula movimientos de inventario hasta la fecha de cierre + saldo inicial del año → stock final por producto × almacén × semana. **Idempotente** (DELETE fecha + INSERT). Es la entrada del bloque `stock_act`.

### ORQUESTADOR (diseñado, pendiente)

- **`automatizacion_ejecutar_etl_completo()`** — script `sql/fase_pgcron_job_automatizacion.sql`. Encadena los 3 SPs, reintenta 1 vez ante fallo y registra alerta si ambos intentos fallan. **No creado aún** (pg_cron no instalado en el VPS).

### TABLAS DE CONTROL

- `automatizacion_control_ejecucion` — log de cada corrida (tipo, fechas, duración, estado, error). Última ejecución verificable con `SELECT ... ORDER BY id DESC LIMIT 5`.
- `automatizacion_alertas` — se llena cuando el orquestador falla 2 veces seguidas (`atendida=FALSE` pendientes de revisar).

---

## PARTE 4 — CÓMO SE ACTUALIZA CADA DIMENSIÓN DE NEGOCIO

### 4.1 Metas (dos vías, ambas llegan a `tipo_fila='metas_*'`)

- **`metas_std`** (meta_venta, meta_contribucion): se cargan **manualmente desde archivos Excel** directamente a `automatizacion_pla_reporte_ventas`. **No hay SP de carga ni automatización.** (En el sistema legacy las metas viven en `datamart_ventas_cuota_postgre` + `pla_meta_sucursal_marca_nuevo`.)
- **`metas_faro`** (meta_venta_faro): provienen del mundo Soluflex (`soluflex_faro_reporting`), cargadas también a la tabla final. Actualmente **no hay lectura directa desde Laravel** hacia Soluflex.

### 4.2 Sucursales (~80 tiendas)

Gobernadas por **3 tablas dimensión** que el ETL consulta en cada corrida:
- `automatizacion_dm_sucursales_activas` — decide qué tiendas entran al reporte. Incluye ECOMMERCE OFF (idsucursal=278, fix 2026-06-16).
- `automatizacion_dm_sucursal_cambios` — historiza renombres con `fecha_desde/fecha_hasta` (ej. KORDA MA IQUITOS → MILK MA IQUITOS desde 2025-05-10). Permite que el histórico conserve el nombre vigente en cada fecha.
- `automatizacion_dm_sucursal_clasificacion` — `localidad_override`, `tdas_liquidadoras` (define qué tiendas son liquidadoras).

### 4.3 Stock

- Cálculo semanal vía `automatizacion_sp_insertar_stock_semana` → `automatizacion_stock_semanal` → bloque `stock_act` del reporte final.
- **Pendiente:** `stock_hst` vacío (faltan 52 semanas de stock 2025 para comparar inventario vs año anterior).

### 4.4 Tráfico (Poken / FF) — TB Retail

Flujo **Laravel → API → Postgres → ETL**:
1. `TbRetailService::guardarConteosTbRetail(tipo='marca'|'tienda', fecha)`:
   - Obtiene `access_token` vía OAuth2 (`grant_type=refresh_token`, **refresh_token y client_secret hardcodeados en el código** — pendiente mover a `.env`).
   - Query GraphQL `getData(metrics:[{name:ENTERS,operation:SUM}], group:{dimension:LOCATION})` para una lista fija de ~50-58 locations.
   - Mapea location → idmarca/idtienda usando `location_id_tienda`.
   - **UPSERT** en `tbretail_conteos` (PK: fecha+tipo+entidad_id).
2. El comando `tbretail:guardar-conteos {fecha?}` ejecuta marca + tienda (default: ayer).
3. `sp_reporte_ventas` vuelca esos conteos a los bloques `poken_act`/`poken_hst`.

### 4.5 Catálogo de productos

- `mes_coleccion_1` — **única tabla "no automatizacion_" que se puede modificar** (excepción acordada). Limpiada de duplicados el 2026-06-16 (6,437 códigos duplicados eliminados; respaldo `mes_coleccion_1_respaldo_20260616`).

---

## PARTE 5 — AUTOMATIZACIÓN / ORQUESTACIÓN ACTUAL

> ⚠️ **Discrepancia**: CLAUDE.md dice "scheduler comentado", pero `app/Console/Kernel.php` lo tiene **ACTIVO**.

| Comando Artisan | Qué ejecuta | Schedule actual |
|---|---|---|
| `etl:refrescar-ultimos-dias {dias=3}` | Paso 1 (`sp_pla_ventas_diarias`) + Paso 3 (`sp_reporte_ventas`) | **Cada 10 min** (sin solapamiento 5 min) |
| `etl:refrescar-filtro-sss` | Paso 2 (`sp_filtro_sss`) | **Diario 04:00** (sin solapamiento 10 min) |
| `tbretail:guardar-conteos {fecha?}` | API TB Retail marca+tienda | (no está en el scheduler) |

- El scheduler necesita `php artisan schedule:run` invocado por tarea programada (Windows Task Scheduler en Laragon).
- `etl:refrescar-ultimos-dias` **no ejecuta filtro_sss** (los datos SSS quedan fijos del 04:00).
- pg_cron en el VPS: **pendiente** de instalar (`sql/fase_instalacion_pg_cron.md`) y de activar el orquestador.
- El comando `etl:refrescar-ultimos-dias` tampoco tiene try/catch (falla silenciosa sin alerta).

---

## PARTE 6 — CAPA WEB (Laravel, `http://gerencial.test`)

### 6.1 Conexiones de BD configuradas

| Conexión | Host:Puerto | Base | Uso real |
|---|---|---|---|
| `pgsql` (default) | 172.16.1.23:5432 | `smartanalytic` | **Todo el flujo nuevo + legacy** |
| `pgsql2` | 172.16.1.30:54322 | `db_bi` | Solo `InicioController::cargarColas()` (código experimental, Job inexistente) |
| `sqlsrv` | 172.16.1.3:5432 | `soluflex_faro_reporting` | Definida; stub sin uso real |
| `sqlsrvp` | — | — | Definida; sin uso |

### 6.2 Autenticación y permisos

- Login `POST /login` (`LoginController::login2`): **primero intenta contra la API Smart Brands** (`login_user`), descifra la clave con un algoritmo propio (`des_encrypt_sb`), y si falla usa **fallback local** contra `auth_user` de `smartanalytic` (columna `migrated_password`).
- Al loguear se cargan los permisos en sesión (`session('permisos')`) vía `Auth_user_permissions::validadar_usuario_permiso()`.
- Los dashboards nuevos se protegen con middleware que valida el permiso en sesión:
  - `acceso_reporte_ventas` → `/dashboard/reporte`
  - `acceso_dashboard_ventas` → `/dashboard/ventas`
  - `acceso_ff_to` → `/dashboard/ff-to`
  - `acceso_gerencial` → `/dashboard/gerencial`
- Admin: `/useradmin` (`AdministradorController`) gestiona usuarios y permisos, y `/sync-users` sincroniza usuarios desde la API Soluflex (`listarusuariossoluflex`).
- El menú lateral (Sneat Bootstrap) muestra solo las opciones cuyo permiso tiene el usuario.

### 6.3 Dashboards NUEVOS (consumen `automatizacion_pla_reporte_ventas`)

**A. `/dashboard/reporte` — Tabla Dinámica (pivot)** (`DashboardReporteController`)
- **Regla de oro de rendimiento:** Postgres agrega con `GROUP BY` al grano fino (día × sucursal × marca × categoría) y el navegador recibe solo **2,000-8,000 registros** agregados; el pivot se re-computa en JS.
- Endpoints:
  - `pivot()` → JSON `{act, hst, metas, stock}` desde 4 queries (tipos `ventas_act`, `ventas_hst` vía `pla_fechas_equivalentes`, `metas_std`, `stock_act` por última fecha de cada semana).
  - `dia()` → pivot por día de semana (canal × día), con var%, GM%, cumplimiento vs meta.
  - `detalle()` → tabla por canal con subtotales (vta26/vta25, part%, meta, cumpl_meta, GM, contribución, unidades, P.Prom, %dscto).
- **Frontend:** PivotEngine (JS propio, `public/assets/js/pivot-engine.js`) + AG Grid Community 31.3.2 + SortableJS 1.15.2. Config en localStorage (`pivot_reporte_cfg_v4`, filtros en `pivot_reporte_filters_v1`, auto-refresh 10s/30s/1min/5min). Export "Excel" = Blob HTML `.xls` (SheetJS cargado pero **no usado**).
- Medidas: vta26, vta25, gm26, gm25, unds26, unds25, tickets26, meta_vta, inv_unds_act, inv_costo_act + calculadas (var_pct, gm%, pprom, ticket_prom, cumpl_pct).
- 14 dimensiones pivoteables: Mes, Semana, Día #, Día, Canal, Subcanal, Tienda, Marca, Marca Temporada, Categoría, SSS, Localidad, Líneas, Temporada.

**B. `/dashboard/ventas`** (`DashboardVentasController`)
- Datos inline `@json` + 3 endpoints AJAX: `tiendas()` (ranking tiendas con venta/meta/pct/GM), `rows()` (filas planas día×marca×canal, incluye metas huérfanas como venta=0), `topProductos()` (Top 10 por SKU `codigo_padre`).
- Normaliza `ECOMMERCE OFF` → canal `WEB`.
- Gráficos con ApexCharts 3.28.3 (tendencia, radial cumplimiento, treemap marca, bar venta vs meta).
- FF = suma de `poken_act`/`poken_hst` (conteo).

**C. `/dashboard/gerencial`** (`DashboardGerencialController`)
- Tabla server-rendered (sin JS): `fecha_documento, sucursal_2, sucursal_3, marca, categoria, SUM(importe_subtotal), SUM(meta_venta), %cumplimiento`. Filtros por fechas y marca.

**D. `/dashboard/ff-to`** (`DashboardFfToController`)
- **FF (tráfico)** desde `tbretail_conteos` + `location_id_tienda` + `automatizacion_dm_sucursales_activas`; **TO (ventas)** desde `ventas_act`/`ventas_hst`.
- KPIs: CR (tickets/FF), ATV (venta/tickets), ratio (venta/FF), gap vs 2025.
- **Backfill en vivo:** si faltan días en `tbretail_conteos`, llama a la API TB Retail día por día (hasta **15 días/request**) y persiste — así el dashboard nunca muestra huecos.

### 6.4 Sistema LEGACY (lee `datamart_*` directo — consumidores del volcado crudo)

| Controlador | Rutas | Qué hace |
|---|---|---|
| `ReportEnterController` (1,890 líneas) | `/reporte`, `/reportesb`, `/tabla`, `/reporte-ventas`, `/vermas`, `/getapi`, `/getapi_conteo/{tipo}/{ini}/{fin}`, `/config_sucursales` | Reporte marca/tienda con cálculos de **costo venta complejos** (mega-CASE: obsolescencia `IDMOVIMIENTO=57`, TIPO_COMPRA MUESTRA/CONSIGNACIÓN, motivos REBATE TXD/DESCUENTO, costos desde `dm_productos_costos` y `dm_consignacion_parametro_persona`), comparativo año anterior vía `pla_fechas_equivalentes`, conteos desde `report_enter`+`location_id_tienda`, metas desde `datamart_ventas_cuota_postgre`+`pla_meta_sucursal_marca_nuevo`. Escribe en `config_sucursales`. |
| `InicioController` | `/inicio` | KPIs del mes (SUM importe/cantidad/costo, COUNT DISTINCT idtransaccion) sobre `datamart_ventas_actual` + serie anual de `datamart_ventas_2025`. |
| `ReportRfmController` | `/rfm`, `/submit_rfm` | Delega el RFM a la API Smart Brands (`reporte_rfm`); lista sucursales con actividad 7 días. |
| `ReproteTxdController` | `/reportetxd`, `/cargar_documentos`, `/subir_documentos` | Carga archivos Ripley/Oechsle (Excel/CSV) a `automatizacion_temp_ripley_txd` y `automatizacion_temp_oechsle_txd`; `ejecutarPython()` llama `run_python.sh` (script Python externo en servidor Linux). |

**Dato clave:** el sistema legacy **NO toca** `pla_reporte_ventas_2`, `pla_ventas_diarias_2`, `pla_reporte_ventas` ni `automatizacion_pla_reporte_ventas` — lee directo `datamart_ventas_actual`. El rol de reporte final lo toman solo los dashboards nuevos.

---

## PARTE 7 — VALIDACIÓN Y ESTADO

**Validado:** `automatizacion_pla_reporte_ventas` (tipo_fila='ventas_act') coincide **exacto** con `pla_reporte_ventas_2`: 8/8 días de junio 2026 y 31/31 días de mayo 2026 (73,784 filas / S/5,089,759.73). Riesgo de ventana de 7 días medido y aceptado (99.7% de NCs se cargan el mismo día).

**Pendientes críticos:**
1. Índices de `automatizacion_pla_reporte_ventas` (categoria, marca, codigo_padre, categoria+fecha) — script `sql/fase_indices_automatizacion.sql` sin ejecutar.
2. `stock_hst` vacío — cargar 52 semanas de stock 2025.
3. 2025 mal ubicado como `ventas_act` en vez de `ventas_hst` (comparativas año vs año distorsionadas).
4. Swap atómico Pentaho (condición de carrera).
5. `sp_filtro_sss` no idempotente.
6. pg_cron sin instalar; orquestador `automatizacion_ejecutar_etl_completo()` sin crear.
7. Manejo de errores/alertas ausente en los comandos Artisan.
8. Credenciales TB Retail hardcodeadas (seguridad).
9. `testPythonVersion` → ruta existe pero método no existe (rompería).

**Discrepancias detectadas en la documentación:**
- CLAUDE.md dice scheduler "TODO comentado", pero `Kernel.php` lo tiene **activo** (10 min + 04:00).
- CLAUDE.md dice host Postgres `172.16.1.10`; el `.env`/`config` real usa **172.16.1.23**.
- Los scripts de `sql/` documentados **no existen en el repo local** (probablemente en el VPS).
