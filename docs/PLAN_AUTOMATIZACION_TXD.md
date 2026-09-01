# PLAN DE AUTOMATIZACIÓN DEL REPORTE TXD

> **Proyecto:** Smart Brands S.A.C. — Smart Brands Gerencial
> **Fecha del plan:** 2026-08-13
> **Base de datos:** `smartanalytic` @ `172.16.1.23:5432` (user `postgres`)
> **Repositorio:** `C:\laragon\www\gerencial`
> **Estado:** Plan aprobado — Script 1 generado (`sql/fase_txd_01_estructura.sql`), pendiente de ejecutar en BD.

---

## 1. Objetivo general

Automatizar el **reporte TXD** (canales de tiendas por departamento: SAGA, OECHSLE, RIPLEY) replicando en PostgreSQL el flujo manual que hoy se ejecuta a mano, **sin tocar** el flujo de ventas automatizado (`automatizacion_pla_reporte_ventas` y sus SPs). Como resultado se materializa un **reporte único consolidado** (ventas + TXD) que conserva el detalle completo de ambos reportes.

### Reglas inquebrantables (heredadas del proyecto)

- Todo objeto nuevo lleva el prefijo `automatizacion_`.
- NO se modifica ni se crean índices en `pla_ventas_diarias_2` (115 GB sin índice), `pla_reporte_ventas_2`, `pla_reporte_ventas`, `sp_pla_ventas_diarias`.
- NO se modifican las tablas ni SPs del flujo de ventas automatizado.
- Migraciones de Laravel: solo tablas de la app web, nunca del ETL.

---

## 2. Arquitectura objetivo

```
                            ARCHIVOS SEMANALES (FTP 172.16.1.20:2323)
                                          │
                  script Python reporte_txd.py  (ya apunta a automatizacion_temp_*)
                                          ▼
         ┌─────────────────┬──────────────────────────┬──────────────────────┐
         ▼                 ▼                          ▼                      ▼
automatizacion_temp_   automatizacion_temp_     automatizacion_temp_   automatizacion_stock_
oechsle_txd           ripley_txd               saga_txd               txd
(carga diaria)        (carga diaria)           (ventas semanal        (stock SAGA)
                                               lunes..domingo)

                    ┌───────────────────────────────────────────────────────┐
                    │  automatizacion_sp_ventas_txd(ini, fin, fecha_stock)  │
                    │  1) registra id_carga en automatizacion_cargas_txd    │
                    │  2) copia temp_* → pla_temp_* con id_carga            │
                    │  3) limpia SKU (quita ".0"), trim                      │
                    │  4) pivot SAGA (lunes..domingo → fechas)              │
                    │  5) UNION SAGA + OECHSLE + RIPLEY (+ stock SAGA)      │
                    │     costos: SAGA ×0.81, OECHSLE ×0.68, RIPLEY ×0.65   │
                    │  6) calcula skus_faltantes (equivalencias pla_sku_txd)│
                    │  7) DELETE+INSERT en automatizacion_pla_ventas_txd    │
                    │  8) log en automatizacion_control_ejecucion_txd       │
                    └───────────────────────────────────────────────────────┘
                                          ▼
                    ┌───────────────────────────────────────────────────────┐
                    │  automatizacion_sp_reporte_txd(ini, fin, forzar_todo) │
                    │  bloques tipo_fila: VENTA act/hst, EXIT FALABELLA,    │
                    │  CUBICAJE, META, AREA + post-procesos inline          │
                    │  → automatizacion_pla_reporte_txd (ya existe, 4.1M)   │
                    └───────────────────────────────────────────────────────┘
                                          ▼
                    ┌───────────────────────────────────────────────────────┐
                    │  automatizacion_sp_reporte_consolidado()              │
                    │  DELETE + INSERT SELECT (ambas tablas → tabla única)  │
                    │  → automatizacion_pla_reporte_consolidado (NUEVA)     │
                    └───────────────────────────────────────────────────────┘
                                          ▼
                    ┌───────────────────────────────────────────────────────┐
                    │  automatizacion_ejecutar_txd_completo() [ORQUESTADOR] │
                    │  encadena los 3 SPs, reintenta 1 vez, alerta en       │
                    │  automatizacion_alertas si falla 2 veces              │
                    └───────────────────────────────────────────────────────┘
                                          ▼
                        Tabla de consumo: automatizacion_pla_reporte_consolidado
                              (origen = 'VENTAS' | 'TXD')
```

---

## 3. Estado actual verificado (levantamiento de información)

### 3.1 Conexión y entorno

| Ítem | Valor |
|---|---|
| Host/BD | `172.16.1.23:5432` / `smartanalytic` |
| Versión | PostgreSQL 16.0 (Debian) |
| Cliente psql | NO disponible en Windows → se usa PDO/psql remoto |
| Túnel | No requerido (conexión directa OK) |

### 3.2 Objetos TXD que ya existen (NO se recrean)

**Tablas de datos:**
- `automatizacion_pla_ventas_txd` — 15 cols: `txd, fecha, sku_txd, desc_hijo_txd, sku_padre, desc_padre_txd, marca, temporada_txd, desc_local, vta_soles, vta_unds, inv_soles, inv_unds, vta_costo, rebate`.
- `automatizacion_pla_reporte_txd` — 63 cols + `tipo_fila`, ~4.1M filas. Bloques: `VENTA`, `EXIT FALABELLA`, `CUBICAJE`, `META`, `AREA` (act/hst según corresponda). Índices existentes: `fecha`, `txd`, `marca`, `corner`, `(txd,fecha)`, `tipo_fila`.

**Tablas de staging (escribe el script Python / controller):**
- `automatizacion_temp_oechsle_txd`, `automatizacion_temp_ripley_txd`, `automatizacion_temp_saga_txd`, `automatizacion_stock_txd` — contienen `id_carga`.

**Control:**
- `automatizacion_cargas_txd` — vacía, con secuencia `automatizacion_cargas_txd_id_seq`; columnas: `id, canal, fecha_carga, usuario, fecha_ini, fecha_fin, fecha_lunes, nombre_archivo, estado, skus_faltantes, notas`.

**Tablas auxiliares usadas por el manual (referencia):**
`pla_sku_txd` (66,406 filas), `pla_ficha_productos_txd`, `pla_fechas_equivalentes` (2019–2025), `dm_productos_costos` (352,988), `datamart_logistica_productos`, `lista_precios_detalle_item` (idlista=27), `pla_metas_txd` (1.15M, hasta 2026-08-31), `pla_area_txd` (12), `pla_cubicaje_txd_2` (62), `pla_txd_supervisores`, `zona_txd`, `coleccion_txd`, `txd_acondicionado`, `productos_faltantes_2`, `datamart_calendario`.

### 3.3 Lo que NO existe (corazón del trabajo)

- **Funciones `automatizacion_sp_*` para TXD**: NO hay ninguna. Solo existen las de ventas.
- **Tabla consolidada** `automatizacion_pla_reporte_consolidado`: NO existe.
- **Log TXD** `automatizacion_control_ejecucion_txd`: NO existe.
- **Orquestador TXD** `automatizacion_ejecutar_txd_completo()`: NO existe.
- `submit_txd()` en `ReproteTxdController` está **vacío** (web no conectada).

### 3.4 Rendimiento medido

| Consulta | Tiempo |
|---|---|
| Agregado por `txd` sobre reporte_txd (4.1M filas) | ~0.6 s |
| Agregado por `fecha` | ~0.15 s |

→ Conclusión: la estrategia de **materializar en tabla y agregar en Postgres con GROUP BY** (patrón ya validado en ventas) es correcta. Sin resúmenes pre-agregados por ahora.

### 3.5 Datos actuales

- `pla_reporte_txd`: jun–ago 2026, con SAGA/OECHSLE/RIPLEY.
- `pla_temp_saga_txd_2`: fechas 2026-08-07 y 2026-08-09, solo 5 filas (datos de prueba).

---

## 4. Hallazgo crítico: mapeo de días SAGA (requiere validación)

El manual asigna la semana tematizada **03–09 de agosto 2026** con un orden **INVERTIDO** respecto al calendario real:

| Día columna | Calendario real | Asignación del manual |
|---|---|---|
| `lunes` | 2026-08-03 | **2026-08-09** |
| `domingo` | 2026-08-09 | **2026-08-03** |

Hoy es **jueves 13/08/2026** y la semana cerrada es del 03 al 09/08.

**Decisión:** el pivot SAGA queda **parametrizado** (`p_fecha_lunes` en los SPs) para poder corregirlo sin cambiar código. **Pendiente:** confirmar contra el archivo real de SAGA cuál es el mapeo correcto antes de fijar el default. El Script 2 se entregará con el mapeo documentado y validable con una consulta de contraste.

---

## 5. Entregables: 5 scripts SQL (carpeta `sql/`)

| # | Archivo | Contenido | Estado |
|---|---|---|---|
| 1 | `fase_txd_01_estructura.sql` | Log `automatizacion_control_ejecucion_txd`, índices faltantes en `ventas_txd`/`reporte_txd`, tabla `automatizacion_pla_reporte_consolidado` + índices | ✅ Generado, pendiente ejecutar |
| 2 | `fase_txd_02_ventas_txd.sql` | `automatizacion_sp_ventas_txd(p_fecha_ini, p_fecha_fin, p_fecha_stock)` | ⏳ Pendiente |
| 3 | `fase_txd_03_reporte_txd.sql` | `automatizacion_sp_reporte_txd(p_fecha_ini, p_fecha_fin, p_forzar_todo)` | ⏳ Pendiente |
| 4 | `fase_txd_04_consolidado.sql` | `automatizacion_sp_reporte_consolidado()` | ⏳ Pendiente |
| 5 | `fase_txd_05_orquestador.sql` | `automatizacion_ejecutar_txd_completo()` | ⏳ Pendiente |

> **Regla de ejecución:** los scripts se entregan al usuario para que los ejecute en BD; NO se aplican directo desde el agente.

---

### 5.1 SCRIPT 1 — Estructura (`fase_txd_01_estructura.sql`) ✅

**Objeto 1: `automatizacion_control_ejecucion_txd`** (tabla de log, analógica a la de ventas)
- `id serial PK`, `tipo_ejecucion` (VENTA_TXD/REPORTE_TXD/CONSOLIDADO/ORQUESTADOR), `p_fecha_ini`, `p_fecha_fin`, `p_fecha_stock`, `p_fecha_lunes`, `id_carga`, `filas_insertadas`, `filas_pivotadas`, `skus_faltantes`, `duracion_segundos`, `estado` (OK/ERROR), `mensaje_error`, `fecha_ejecucion`.
- *Motivo:* la tabla de log de ventas (`automatizacion_control_ejecucion`) tiene columnas específicas de ventas; no conviene mezclar.

**Objeto 2: índices faltantes** (usando `CREATE INDEX IF NOT EXISTS`, idempotente)

| Tabla | Índice nuevo | Para qué |
|---|---|---|
| `automatizacion_pla_ventas_txd` | `(txd, sku_txd, fecha)` | post-proceso equivalencias |
| `automatizacion_pla_reporte_txd` | `(marca, fecha)` | consultas gerentes por marca |
| `automatizacion_pla_reporte_txd` | `(tipo_fila, fecha)` | filtros por bloque+fecha |
| `automatizacion_pla_reporte_txd` | `(txd, marca)` | consultas por canal+marca |

(Índices ya existentes —`fecha`, `txd`, `marca`, `corner`, `txd+fecha`, `tipo_fila` en reporte_txd; `fecha`, `sku`, `txd+fecha` en ventas_txd— NO se recrean.)

**Objeto 3: `automatizacion_pla_reporte_consolidado`** (tabla NUEVA — el reporte único)
- Columnas **comunes unificadas** (consultables por la web): `origen` ('VENTAS'|'TXD'), `tipo_fila`, `fecha`, `dia_semana`, `semana`, `dia_equivalente`, `mes`, `marca`, `canal`, `corner`, `categoria`, `subcategoria`, `linea`, `linea_2`, `linea_2_2`, `sublinea`, `descripcion_padre`, `coleccion`, `temporada`, `supervisor`, `filtro_sss2`, `pvp`, `meta`, `meta_contribucion`, `gm_meta`, `cubicaje`, `area`, `vta_retail_act`, `vta_retail_hst`.
- **KPIs unificados**: `vta_act`, `vta_hst`, `vta_unds_act`, `vta_unds_hst`, `costo_act`, `costo_hst`, `rebate_act`, `rebate_hst`, `inv_unds_act`, `inv_unds_hst`, `inv_soles_act`, `inv_soles_hst`, `inv_costo_act`, `inv_costo_hst`, `nro_tickets`.
- **Detalle propio VENTAS** (prefijo `v_`): las 60+ columnas nativas restantes (sucursales, tickets, estancias, flags, fechas de carga, etc.).
- **Detalle propio TXD** (prefijo `t_`): las 45+ columnas nativas restantes (SKUs, talla, color, zona, colecciones, rebates, etc.).
- **Índices**: `(origen, tipo_fila, fecha)`, `fecha`, `(marca, fecha)`, `(canal, fecha)`.
- El DDL se generó **desde el esquema real** (81 cols ventas + 63 cols TXD, 24 colisiones detectadas y resueltas con prefijos), no tipeado a mano.

---

### 5.2 SCRIPT 2 — Ventas TXD (`fase_txd_02_ventas_txd.sql`) ⏳

**Función:** `automatizacion_sp_ventas_txd(p_fecha_ini date DEFAULT NULL, p_fecha_fin date DEFAULT NULL, p_fecha_stock date DEFAULT NULL)`

**Lógica (replica fiel del manual, parametrizada):**

1. **Defaults**: si `NULL,NULL` → últimos 7 días (como ventas).
2. **Registrar carga**: `INSERT` en `automatizacion_cargas_txd` → obtiene `id_carga` (canal, rango, `fecha_lunes`, nombre archivo, estado='EN PROCESO').
3. **Copiar staging** `automatizacion_temp_*` → `automatizacion_pla_temp_*` con `id_carga` (para historizar las cargas sin pisar la última descargada).
   - Oechsle → `automatizacion_pla_temp_oechsle_txd`
   - Ripley → `automatizacion_pla_temp_ripley_txd`
   - SAGA ventas → `automatizacion_pla_temp_saga_txd` → pivote → `automatizacion_pla_temp_saga_txd_2`
   - SAGA stock → `automatizacion_pla_temp_stock_txd`
4. **Limpieza de SKU**: quitar `.0` final y TRIM a `sku_txd` (mismo bug que el manual resuelve).
5. **Pivot SAGA** (semanal `lunes..domingo` → fechas):
   ```sql
   SELECT sku, descripcion, 'SAGA' AS txd, fecha, SUM(unidades) AS vta_unds, ...
   FROM ... UNPIVOT(lunes..domingo)
   GROUP BY ... HAVING SUM(dia) <> 0
   ```
   - Mapeo día→fecha **parametrizado** (`p_fecha_lunes` + offset) — ver sección 4.
6. **INSERT combinado** en `automatizacion_pla_ventas_txd` (DELETE del rango + INSERT), con **factores de costo** del manual:
   - SAGA: `costo_unit = costo * vta_unds * 0.81`
   - OECHSLE: `* 0.68`
   - RIPLEY: `* 0.65`
7. **`skus_faltantes`**: productos del archivo sin equivalencia en `pla_sku_txd` → se guardan en `automatizacion_cargas_txd.skus_faltantes` y en el log. **Solo alertar, NO resolver automático** (la equivalencia es dimensión manual).
8. **Log**: `INSERT` en `automatizacion_control_ejecucion_txd` con duración, filas, estado.

**Parámetros** (estilo ventas): `p_fecha_ini`, `p_fecha_fin`, `p_fecha_stock` (fecha del stock SAGA; default = fecha_fin o hoy).

---

### 5.3 SCRIPT 3 — Reporte TXD (`fase_txd_03_reporte_txd.sql`) ⏳

**Función:** `automatizacion_sp_reporte_txd(p_fecha_ini date DEFAULT NULL, p_fecha_fin date DEFAULT NULL, p_forzar_todo boolean DEFAULT FALSE)`

**Lógica (replica los bloques del manual en `automatizacion_pla_reporte_txd`, ya existente):**

| Bloque `tipo_fila` | Fuente | Qué llena |
|---|---|---|
| `VENTA` act | `automatizacion_pla_ventas_txd` (rango) | vta_act, vta_unds_act, inv_unds_act, inv_soles_act, vta_costo_act, rebate_act, costo_si_act |
| `VENTA` hst | `automatizacion_pla_ventas_txd` año anterior vía `pla_fechas_equivalentes` / `datamart_calendario` | vta_hst, vta_unds_hst, inv_hst... |
| `EXIT FALABELLA` | `pla_ventas_diarias_2` (solo lectura, bloque específico) | ventas EXIT en FALABELLA |
| `CUBICAJE` | `pla_cubicaje_txd_2` | cubicaje |
| `META` | `pla_metas_txd` | meta, meta_contribucion, gm_meta |
| `AREA` | `pla_area_txd` | area |

**Post-procesos inline (iguales al manual):**
- Corner fixes (MCH/BLUES, FALABELLA).
- Temporadas (`temporada_txd`, `temporada_sb`), `temporada_2/3`.
- `mes_coleccion_` desde `pla_ficha_productos_txd` / `coleccion_txd`.
- `filtro_sss2`, `zona` (`zona_txd`), `coleccion_2/3` (`coleccion_txd`).
- `UPPER()`/`TRIM()` en texto.
- Equivalencias SKU padre vía `pla_sku_txd`.

**Refresco**: `DELETE` del rango + `INSERT`; `p_forzar_todo=TRUE` fuerza todo el periodo.

**Log**: `automatizacion_control_ejecucion_txd`.

---

### 5.4 SCRIPT 4 — Consolidado (`fase_txd_04_consolidado.sql`) ⏳

**Función:** `automatizacion_sp_reporte_consolidado()`

- `TRUNCATE`/`DELETE` de `automatizacion_pla_reporte_consolidado`.
- `INSERT SELECT` desde **`automatizacion_pla_reporte_ventas`** con `origen='VENTAS'` (mapeando las 81 columnas al esquema consolidado + rellena comunes y prefijo `v_`).
- `INSERT SELECT` desde **`automatizacion_pla_reporte_txd`** con `origen='TXD'` (63 columnas → comunes + prefijo `t_`).
- Se regenera **completo en cada corrida** (DELETE+INSERT), por lo que siempre es consistente con el estado actual de ambas tablas.
- **Log**: `automatizacion_control_ejecucion_txd`.

---

### 5.5 SCRIPT 5 — Orquestador (`fase_txd_05_orquestador.sql`) ⏳

**Función:** `automatizacion_ejecutar_txd_completo()`

1. Llama en orden: `sp_ventas_txd` → `sp_reporte_txd` → `sp_reporte_consolidado`.
2. **Reintento**: si alguno falla, reintenta 1 vez.
3. **Alerta**: si ambos intentos fallan, `INSERT` en `automatizacion_alertas` (`atendida=FALSE`).
4. **Log** consolidado en `automatizacion_control_ejecucion_txd` (tipo_ejecucion='ORQUESTADOR').
5. **Sin pg_cron por ahora** (pendiente en el VPS). La ejecución será manual o vía llamada web.

---

## 6. Web (NO incluida en este alcance)

La vista `reportetxd` y el endpoint `submit_txd()` quedan **igual** (back-end primero). En una fase futura:
- Conectar `submit_txd()` para disparar el orquestador y mostrar el resultado del log.
- La vista ag-grid consumiría `automatizacion_pla_reporte_consolidado` (filtros `origen`/`tipo_fila`/`fecha`/`marca`/`canal`).

---

## 7. Plan de ejecución paso a paso

1. **Ejecutar Script 1** en BD → confirmar DDL (tabla consolidada + log + índices). ✅ *Script listo.*
2. **Validar mapeo SAGA** contra el archivo real (contraste de totales) → fijar `p_fecha_lunes`.
3. **Script 2** → ejecutar → validar `automatizacion_pla_ventas_txd` del rango vs esperado.
4. **Script 3** → ejecutar → validar `automatizacion_pla_reporte_txd` vs `pla_reporte_txd` (jun–ago 2026) por `txd`/`marca`.
5. **Script 4** → ejecutar → validar consolidado: conteo = ventas + TXD; totales por marca/canal/fecha coinciden con cada tabla origen.
6. **Script 5** → ejecutar → probar el flujo completo end-to-end y la corrida incremental (2ª corrida no duplica).
7. **Registrar** en el CLAUDE.md del proyecto el nuevo flujo y los scripts.

### Validaciones específicas a ejecutar

```sql
-- (a) conteo consolidado = suma de ambas tablas
SELECT origen, count(*) FROM automatizacion_pla_reporte_consolidado GROUP BY origen;

-- (b) totales por marca coinciden con tabla TXD origen
SELECT marca, sum(vta_act) FROM automatizacion_pla_reporte_consolidado
WHERE origen='TXD' GROUP BY marca;

-- (c) totales por marca coinciden con tabla VENTAS origen
SELECT marca, sum(importe_subtotal) FROM automatizacion_pla_reporte_ventas
WHERE tipo_fila='ventas_act' GROUP BY marca;

-- (d) corrida incremental no duplica
SELECT count(*) FROM automatizacion_pla_ventas_txd WHERE fecha BETWEEN '2026-08-03' AND '2026-08-09';
```

---

## 8. Riesgos y decisiones

| Riesgo | Mitigación |
|---|---|
| Mapeo SAGA invertido | Parametrizado (`p_fecha_lunes`); validación previa con archivo real |
| `pla_ventas_diarias_2` (115 GB, sin índice) para EXIT FALABELLA hst | Solo lectura, fiel al manual; ventana de rango acotada |
| `sp_filtro_sss` de ventas NO es idempotente | TXD usa su propio filtro (`filtro_sss2`) como post-proceso, sin reutilizar el de ventas |
| Consolidado pesado (2× filas) | Regeneración completa controlada; agregados por GROUP BY en Postgres (~0.15–0.6 s medidos) |
| `automatizacion_cargas_txd` vacía / secuencia | Se reutiliza con su secuencia existente; no se crea nueva |
| Staging `automatizacion_temp_*` se sobrescribe en cada descarga | Se copian a `pla_temp_*` con `id_carga` antes del pivot → historización por carga |
| Costos SAGA/OECHSLE/RIPLEY por factores | Factores del manual: 0.81 / 0.68 / 0.65; parametrizables |

---

## 9. Inventario de objetos a crear (resumen)

| Objeto | Tipo | Script |
|---|---|---|
| `automatizacion_control_ejecucion_txd` | tabla | 1 |
| índices en `automatizacion_pla_ventas_txd` (1) | índice | 1 |
| índices en `automatizacion_pla_reporte_txd` (3) | índice | 1 |
| `automatizacion_pla_reporte_consolidado` (+4 índices) | tabla | 1 |
| `automatizacion_sp_ventas_txd(ini,fin,stock)` | función | 2 |
| `automatizacion_sp_reporte_txd(ini,fin,forzar)` | función | 3 |
| `automatizacion_sp_reporte_consolidado()` | función | 4 |
| `automatizacion_ejecutar_txd_completo()` | función | 5 |

**Total: 3 tablas, 8 índices, 4 funciones.**

---

## 10. Estado actual del avance

| Tarea | Estado |
|---|---|
| Levantamiento de esquemas, índices y rendimiento | ✅ |
| Análisis del manual y mapeo del flujo | ✅ |
| Hallazgo mapeo SAGA invertido | ✅ documentado |
| Script 1 (estructura) | ✅ generado |
| Script 1 ejecutado en BD | ⏳ pendiente |
| Validación mapeo SAGA con archivo real | ⏳ pendiente |
| Scripts 2–5 | ⏳ pendientes |
| Validaciones de datos (sección 7) | ⏳ pendientes |
| Registro en CLAUDE.md | ⏳ pendiente |
