# CLAUDE.md — Smart Brands Gerencial
> Archivo de contexto permanente para Claude Code.
> Leer completo antes de ejecutar cualquier acción.
> Última actualización: 2026-06-16

---

## 1. Quién soy y qué hace este proyecto

Soy el responsable de datos de **Smart Brands S.A.C.**, empresa de retail
de moda con múltiples marcas en Perú. Este proyecto tiene dos componentes:

**ETL (ya construido y validado):** rediseño del proceso de ventas que
migramos de scripts manuales a funciones automáticas en Postgres bajo el
prefijo `automatizacion_`. El flujo original sigue vivo en paralelo como
referencia mientras se termina la capa web.

**Web (en construcción):** aplicación Laravel que consume los datos del
ETL nuevo para dashboards de gerentes y análisis comercial.

**Marcas:** EXIT, MILK, MCH (Mentha & Chocolate), KORDA, FINA, BLUES BY MILK
**Canales:** Boutiques propias, Outlets, E-commerce, Tiendas por departamento
**Cobertura:** Lima, Norte (Trujillo, Piura, Chiclayo, Iquitos), Sur (Cusco, Arequipa)
**Sucursales activas:** ~80 entre boutiques, outlets y online

---

## 2. Stack tecnológico

| Componente | Tecnología |
|---|---|
| Base de datos origen | SQL Server (histórico completo) |
| Base de datos analítica | PostgreSQL 16 en VPS (172.16.1.10:5432, base: smartanalytic) |
| Herramienta ETL | Pentaho Data Integration (Fase 3 original pendiente) |
| ETL nuevo (construido) | Funciones PL/pgSQL con prefijo `automatizacion_` |
| Web local | Laravel en C:\laragon\www\gerencial (Laragon, PHP 8.x) |
| Web producción | Laravel en VPS, mismo Postgres |
| Consumo comercial (objetivo) | Apache Superset (pendiente de instalar) |
| Consumo gerentes (objetivo) | Laravel con dashboards nativos (en construcción) |

**Conexión local:** Laragon corriendo → `http://gerencial.test`
**Repositorio:** clonar en `C:\laragon\www\gerencial\`
**Arranque Claude Code:** `cd C:\laragon\www\gerencial && claude`
*(Si Postgres no acepta conexión directa, levantar túnel SSH primero:
`ssh -L 5432:localhost:5432 Webs` en terminal aparte, luego
`DB_HOST=127.0.0.1` en `.env`)*

---

## 3. Regla fundamental del proyecto (NO violar)

**Todo lo nuevo lleva el prefijo `automatizacion_`.**
Las tablas, funciones e índices del flujo original NO se modifican:
- `pla_ventas_diarias_2` (115 GB, sin índices — NO crear índices aquí)
- `pla_reporte_ventas_2` (327 MB — NO modificar)
- `pla_reporte_ventas` (tabla final original — NO modificar)
- `sp_pla_ventas_diarias` (función original — NO modificar)

**Excepción acordada:** `mes_coleccion_1` es tabla de referencia/catálogo
y se puede modificar en sitio. Ya fue limpiada (2026-06-16).

**Migraciones de Laravel:** solo para tablas de la aplicación web, NUNCA
para las tablas del ETL (esas se gestionan directamente en Postgres).

---

## 4. Estado actual del ETL (lo que ya está construido y validado)

### 4.1 Arquitectura implementada (distinta al plan Pentaho original)

En vez de Pentaho Insert/Update + vista materializada (plan original del
CLAUDE.md), se construyó un flujo paralelo dentro de Postgres:

```
datamart_ventas_actual (volcado desde SQL Server por Pentaho, sin cambios)
    ↓ automatizacion_sp_pla_ventas_diarias(NULL, NULL)
automatizacion_pla_ventas_diarias  — ventana últimos 7 días
    ↓ automatizacion_sp_filtro_sss(NULL, NULL)
automatizacion_pla_sucursal_filtro — filtro SSS/NUEVO/CIERRE actualizado
    ↓ automatizacion_sp_reporte_ventas(NULL, NULL, FALSE)
automatizacion_pla_reporte_ventas  — tabla final de consumo (~1.67M filas)
         ↓                              ↓
Laravel (gerentes)          Apache Superset (comercial)
[en construcción]           [pendiente de instalar]
```

### 4.2 Objetos nuevos en Postgres (todos en base `smartanalytic`)

**Tablas de datos:**
- `automatizacion_pla_ventas_diarias` — 74 MB, 136,357 filas (2 meses)
- `automatizacion_pla_reporte_ventas` — 816 MB, 1,676,731 filas (2 meses). Columna `tipo_fila` identifica el bloque: `ventas_act`, `ventas_hst`, `stock_act`, `stock_hst`, `metas_faro`, `metas_std`, `area`, `cubicajes`, `poken_act`, `poken_hst`

**Tablas de referencia/dimensión:**
- `automatizacion_dm_sucursales_activas` — sucursales que deben incluirse en el reporte. ECOMMERCE OFF (idsucursal=278) ya incluida (fix 2026-06-16)
- `automatizacion_dm_sucursal_cambios` — historiza renombres de sucursal con fecha_desde/fecha_hasta (ej. KORDA MA IQUITOS → MILK MA IQUITOS desde 2025-05-10)
- `automatizacion_dm_sucursal_clasificacion` — localidad_override, tdas_liquidadoras
- `automatizacion_dm_codigos_equivalencia` — equivalencia de codigo_padre
- `automatizacion_pla_sucursal_filtro` — filtro SSS/NUEVO/CIERRE
- `automatizacion_pla_filtro_corner_2`
- `automatizacion_alertas` — se llena cuando el orquestador falla 2 veces; revisar con `SELECT * FROM automatizacion_alertas WHERE atendida=FALSE`

**Control:**
- `automatizacion_control_ejecucion` — log de cada corrida: tipo, rango de fechas, duración, estado, error

**Funciones:**
- `automatizacion_sp_pla_ventas_diarias(p_fecha_ini, p_fecha_fin)` — DELETE+INSERT por rango desde `datamart_ventas_actual`. Default: últimos 7 días
- `automatizacion_sp_filtro_sss(p_fecha_ini, p_fecha_fin)` — actualiza filtro SSS. Default: mes anterior al mes siguiente
- `automatizacion_sp_reporte_ventas(p_fecha_ini, p_fecha_fin, p_forzar_todo)` — reconstruye `automatizacion_pla_reporte_ventas` por bloques con lógica de refresco inteligente. `p_forzar_todo=TRUE` para recargar todo (ej. primer uso o cambios en datos de referencia)
- `automatizacion_ejecutar_etl_completo()` — **orquestador diseñado, pendiente de crear** (script: `sql/fase_pgcron_job_automatizacion.sql`). Encadena los 3 SPs, reintenta 1 vez ante fallo, registra alerta si ambos intentos fallan

### 4.3 Ejecución manual (mientras no está automatizado)

```sql
-- Paso 1: ventas diarias (últimos 7 días)
SELECT automatizacion_sp_pla_ventas_diarias(NULL, NULL);

-- Paso 2: filtro SSS
SELECT automatizacion_sp_filtro_sss(NULL, NULL);

-- Paso 3: reporte (solo ventas y poken; stock y metas solo si cambiaron)
SELECT automatizacion_sp_reporte_ventas(NULL, NULL, FALSE);

-- Para forzar recarga completa de todo (ej. tras cambio en tablas de referencia):
SELECT automatizacion_sp_reporte_ventas(NULL, NULL, TRUE);

-- Para un rango específico (ej. cargar un mes histórico):
SELECT automatizacion_sp_pla_ventas_diarias('2026-05-01', '2026-05-31');
SELECT automatizacion_sp_filtro_sss('2026-05-01', '2026-05-31');
SELECT automatizacion_sp_reporte_ventas('2026-05-01', '2026-05-31', FALSE);

-- Verificar última ejecución:
SELECT tipo_ejecucion, p_fecha_ini, p_fecha_fin,
       ROUND(duracion_segundos::numeric,1) AS seg, estado, mensaje_error
FROM automatizacion_control_ejecucion
ORDER BY id DESC LIMIT 5;
```

### 4.4 Índices existentes en automatizacion_pla_reporte_ventas

Ya existen: `fecha_documento`, `sucursal`, `marca+fecha_documento`, `tipo_fila`.
**Pendientes de crear** (script: `sql/fase_indices_automatizacion.sql`):
`categoria`, `marca` sola, `codigo_padre`, `categoria+fecha_documento`.
Crearlos antes de cargar el histórico de 3 años.

---

## 5. Validación completada (ETL confiable para estos rangos)

Comparado `automatizacion_pla_reporte_ventas` (tipo_fila='ventas_act')
vs `pla_reporte_ventas_2` (original). Resultado: coincidencia exacta.

| Rango | Resultado |
|---|---|
| 2026-06-01 al 2026-06-08 | ✅ 8/8 días exactos en filas y monto |
| 2026-05-01 al 2026-05-31 | ✅ 31/31 días exactos. Total: 73,784 filas / S/5,089,759.73 |

**Bugs resueltos durante la validación:**
- ECOMMERCE OFF (idsucursal=278) faltaba en `automatizacion_dm_sucursales_activas` → INSERT aplicado
- `mes_coleccion_1` tenía 6,437 códigos duplicados inflando montos → limpieza en sitio con regla acordada (priorizar fila con mes_coleccionx + sublinea2x llenos; si no, último registro por ctid). Respaldo en `mes_coleccion_1_respaldo_20260616`

**Riesgo de la ventana de 7 días:** medido y aceptado. El 99.7% de NCs
en flujo diario normal se cargan el mismo día de su fecha_documento.
Solo 4 casos aislados (de 3,249) con más de 14 días de latencia real
en el histórico. Script de monitoreo periódico: `sql/fase_monitoreo_nc_latencia.sql`

---

## 6. Automatización (pg_cron) — pendiente de implementar

**Decisión:** pg_cron cada 30 minutos. Postgres 16 en Debian PGDG.
pg_cron NO está instalado aún (`shared_preload_libraries` vacío).

**Pasos:**
1. Instalar: ver `sql/fase_instalacion_pg_cron.md`
   (requiere `sudo apt install postgresql-16-cron` + editar
   `postgresql.conf` + 1 reinicio del servicio)
2. Crear orquestador y job: ver `sql/fase_pgcron_job_automatizacion.sql`
   (crear la función `automatizacion_ejecutar_etl_completo()`,
   la tabla `automatizacion_alertas`, y el job `etl_smart_brands_30min`)
3. Probar manualmente los 4 pasos del script antes de confiar en el job
   (el orquestador se diseñó con conector solo lectura, no pudo testearse)

---

## 7. Fase 0 Pentaho — hallazgo documentado (no bloqueante)

Si en algún momento se retoma la Fase 3 del plan original (Pentaho
Insert/Update), usar estas claves — NO las propuestas en el CLAUDE.md
original (idtransaccion e IDKARDEX no son únicas):

- **Paso 7 ventas** (`datamart_ventas_actual`): clave = `secuencia` sola
  (es correlativo global, 423,866 filas = 423,866 valores únicos)
- **Paso 8 logística** (`datamart_logistica_movimientos_actual`): clave = `id` sola
  (1,368,633 filas = 1,368,633 ids únicos). NO usar `secuencia` aquí
  (es correlativo local, se repite entre distintos idkardex)

---

## 8. Públicos objetivo de la web y tablas a usar

### Gerentes (Laravel, dashboards fijos)
Consultar vista resumen a crear sobre `automatizacion_pla_reporte_ventas`:
```sql
-- Patrón de consulta para gerentes:
SELECT fecha_documento, sucursal_2, sucursal_3, marca, categoria,
       SUM(importe_subtotal) AS venta_neta,
       SUM(meta_venta) AS meta,
       ROUND(SUM(importe_subtotal)/NULLIF(SUM(meta_venta),0)*100,1) AS pct_cumplimiento
FROM automatizacion_pla_reporte_ventas
WHERE tipo_fila = 'ventas_act'
  AND fecha_documento BETWEEN :fecha_ini AND :fecha_fin
GROUP BY fecha_documento, sucursal_2, sucursal_3, marca, categoria;
```

### Comercial (Apache Superset)
Dataset completo: `automatizacion_pla_reporte_ventas` con filtros dinámicos.
Row Level Security a evaluar por usuario si corresponde.

---

## 9. Scripts generados (carpeta sql/ del proyecto)

| Script | Qué hace | Estado |
|---|---|---|
| `fase_diagnostico_mes_coleccion_1_duplicados.sql` | Diagnóstico de duplicados en mes_coleccion_1 | Ejecutado |
| `fase_fix_ecommerce_off_sucursales_activas.sql` | INSERT ECOMMERCE OFF | Ejecutado |
| `fase_reporte_mes_coleccion_1_variantes_608.sql` | Detalle de 608 códigos con variantes | Referencia |
| `fase_limpieza_mes_coleccion_1_en_sitio.sql` | Limpieza de mes_coleccion_1 | Ejecutado |
| `fase_monitoreo_nc_latencia.sql` | Monitoreo periódico de NCs con latencia alta | Usar mensualmente |
| `fase_indices_automatizacion.sql` | Índices en tablas automatizacion_* | **PENDIENTE de ejecutar** |
| `fase_instalacion_pg_cron.md` | Guía instalación pg_cron en el VPS | **PENDIENTE** |
| `fase_pgcron_job_automatizacion.sql` | Orquestador + job pg_cron cada 30min | **PENDIENTE de probar y activar** |
| `fase_validacion_54_codigos_mes_coleccion.md` | Validación de la limpieza de mes_coleccion_1 | Completado |

---

## 10. Próximos pasos en orden de prioridad

1. ✅ **Prioridad 1 — Validación ampliada:** cerrada. Jun 2026 y mayo 2026 validados exacto
2. ✅ **Prioridad 2 — mes_coleccion_1:** cerrada. Limpieza aplicada y validada
3. ✅ **Prioridad 3 — Fase 0 Pentaho:** cerrada. Claves documentadas
4. ⏳ **Prioridad 4 — Índices:** ejecutar `sql/fase_indices_automatizacion.sql` antes de cargar histórico de 3 años
5. ⏸️ **Prioridad 5 — Plan de corte:** pausado hasta que la web esté integrada
6. ⏳ **Prioridad 6 — pg_cron:** instalar extensión y activar orquestador (scripts listos)
7. 🚧 **Prioridad 7 — Web Laravel + Superset:** en construcción (este repositorio)

---

## 11. Cómo trabajar con Claude Code en este proyecto

Al iniciar cada sesión indicar:
1. Qué se va a trabajar en esta sesión (ETL, web, ambos)
2. Si hay algún error o comportamiento inesperado desde la sesión anterior
3. Si se ejecutó algún script SQL fuera de Claude Code

Ejemplo de mensaje de inicio:
```
Proyecto: Smart Brands Gerencial
Ruta: C:\laragon\www\gerencial
DB: smartanalytic en 172.16.1.10:5432 (MCP conectado)
URL local: http://gerencial.test

Sesión anterior: [qué se hizo]
Esta sesión: [qué se quiere hacer hoy]
```

**Reglas importantes para Claude Code:**
- Nunca modificar tablas sin prefijo `automatizacion_` (excepto mes_coleccion_1)
- Las migraciones de Laravel son solo para tablas de la app web
- Antes de cualquier cambio destructivo en Postgres, verificar con una SELECT primero
- El .env nunca va al repositorio (está en .gitignore)
- Al terminar la sesión: `git add . && git commit -m "descripcion"` si hay cambios listos
