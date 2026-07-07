# Arquitectura de Tabla Dinámica (Pivot tipo Excel) — Smart Brands Gerencial

> Documento de decisión y arquitectura para la vista `/dashboard/reporte`.
> Objetivo: experiencia tipo tabla dinámica de Excel usando solo tecnología gratuita/open source.
> Última actualización: 2026-07-02

---

## Decisión

**AG Grid Community (MIT) + motor de pivot propio en JS + SortableJS para el panel de configuración.**

Es la única combinación gratuita que da simultáneamente:
- Pivot real (dimensión → columnas)
- Múltiples métricas por grupo de columna (lo exige el Excel de ventas)
- Virtualización de filas y columnas (cientos de miles de filas sin trabarse)
- Personalización visual total

Alternativa evaluada y descartada como principal: **WebDataRocks** (pivot Excel-like de fábrica, gratis, multi-medida) — descartada por techo de rendimiento y baja personalización. Sirve como plan B si se prioriza velocidad de implementación sobre control.

---

## Regla de oro de rendimiento

**Nunca enviar las filas crudas (1.67M) al navegador.**

Postgres agrega con `GROUP BY` al grano más fino necesario (ej. día × sucursal × marca × categoría) y el navegador recibe unos **2.000–8.000 registros** ya agregados. El motor JS re-pivota/re-agrega sobre ese conjunto pequeño para dar interactividad instantánea. Este patrón ya está implementado en `DashboardReporteController::pivot()`.

---

## AG Grid: Community vs Enterprise

| Feature | Community (gratis) | Enterprise (pago) |
|---|---|---|
| Sort, filtros texto/número/fecha | ✅ | |
| Resize / reorder / pin / column groups | ✅ | |
| Virtualización filas + columnas | ✅ | |
| Pinned rows (top/bottom) | ✅ | |
| Custom cell renderers / filters | ✅ | |
| CSV export | ✅ | |
| Row Grouping automático | ❌ | ✅ |
| Aggregation (`aggFunc`) | ❌ | ✅ |
| Pivoting automático | ❌ | ✅ |
| Row Group Panel / Pivot Panel | ❌ | ✅ |
| Set Filter (checkboxes Excel) | ❌ | ✅ |
| Tool Panels laterales | ❌ | ✅ |
| Excel export con estilos | ❌ | ✅ |
| Server-Side Row Model | ❌ | ✅ |

Todo lo Enterprise que importa se reconstruye en Community porque son transformaciones de datos que se hacen en JS antes de pasar las filas a la grilla. Community aporta lo caro de verdad: la virtualización.

## Replicar Enterprise manualmente

| Enterprise | Implementación en Community |
|---|---|
| Aggregation | Motor JS: reduce por grupo con SUM/COUNT/AVG/MIN/MAX |
| Row Grouping | Árbol aplanado con nivel + estado expandido; expand/collapse = `setGridOption('rowData', visibles)` |
| Subtotales | Filas sintéticas `_esSubtotal` + `getRowStyle` |
| Total general | `pinnedBottomRowData` (soportado en Community) |
| Pivot | Escanear valores distintos de la dimensión → `columnDefs` con column groups dinámicos |
| Multi-métrica | `children` del column group, uno por métrica |
| Panel drag | SortableJS con zonas Campos/Filas/Columnas/Valores |
| Set Filter | Custom Filter Component (checkboxes) |
| Selector agregación | Dropdown propio por campo de valor |
| Persistencia | Config JSON en localStorage o tabla `automatizacion_*` |

Cobertura estimada: ~90-95% de la experiencia Enterprise. Lo único no replicable (Server-Side Row Model) no se necesita porque se agrega en Postgres.

---

## Comparación de librerías gratuitas

| Librería | Licencia | Pivot real | Multi-métrica | Volumen | Excel-like | Personalización |
|---|---|---|---|---|---|---|
| AG Grid Community + motor | MIT | ✅ custom | ✅ | ✅✅ virtual | Alta | ✅✅ total |
| WebDataRocks | Free propietaria | ✅✅ nativo | ✅ | ⚠️ techo | ✅✅ | ⚠️ limitada |
| PivotTable.js | MIT | ✅ | ❌ 1 medida | ❌ sin virtual | Media | Media |
| TanStack Table | MIT | ❌ (grouping sí) | parcial | ✅✅ +Virtual | Media | ✅✅ headless |
| Tabulator | MIT | ❌ (grouping filas) | ✅ calcs | ✅ | Media-alta | ✅ |
| RevoGrid | MIT | ❌ | — | ✅✅✅ millones | Baja | ✅ |
| DataTables | MIT | ❌ | ❌ | ⚠️ jQuery | Baja | Media |
| Handsontable CE | ⚠️ NO libre comercial | ✅ addon | — | ✅ | Alta | ✅ |
| Flexmonster | 💰 Pago | ✅✅ | ✅ | ✅✅ | ✅✅ | ✅ |

Avisos:
- **Handsontable**: licencia no gratuita para uso comercial. Descartar (riesgo legal en empresa).
- **WebDataRocks**: soporta múltiples medidas y es lo más Excel-like de fábrica, pero rinde con datos pre-agregados, no con cientos de miles crudos; estilo/comportamiento limitados.
- **PivotTable.js**: excelente drag-drop pero una sola medida → no replica el Excel.
- **RevoGrid**: máximo rendimiento pero sin pivot/agregación nativa.

---

## Arquitectura

```
POSTGRES: GROUP BY al grano fino  →  ~miles de filas agregadas (JSON)
   │
NAVEGADOR:
   Panel config (SortableJS)  →  Motor Pivot JS  →  AG Grid Community
   Campos/Filas/Cols/Valores     group/agg/pivot     render + virtual + sort/filtros
                                  subtotales/total
   Persistencia: config JSON → localStorage / backend
```

Responsabilidades:
1. Postgres = agregación pesada.
2. Motor pivot JS = módulo reutilizable sin dependencias (group/aggregate/pivot/subtotales). Testeable aislado.
3. AG Grid Community = solo render + virtualización + interacción.
4. SortableJS = drag-drop de campos con zona Valores + selector de agregación por valor.
5. Persistencia = guardar/cargar vistas.

Por qué SortableJS y no PivotTable.js: PivotTable.js depende de jQuery UI, su modelo es de una sola medida y no tiene zona "Valores" con agregación por campo. SortableJS (MIT, ~12kb, sin dependencias) da drag-drop puro y control total del modelo de configuración.

---

## Plan de implementación por fases

### Fase 1 — Motor de pivot como módulo aislado
- Extraer a `resources/js/pivot-engine.js`: `computePivot(data, {rows, cols, values})` con `values = [{field, aggFn}]`.
- Soportar `aggFn`: `sum|count|avg|min|max`.
- Soportar N dimensiones en columnas → column groups anidados.

### Fase 2 — Panel de configuración con SortableJS
- Zonas: Campos disponibles, Filas, Columnas, Valores.
- Cada chip de Valores con `<select>` de función de agregación.
- `onEnd` → releer zonas → computePivot → repintar AG Grid.

### Fase 3 — Árbol de grupos expand/collapse
- Filas-grupo con `_nivel` y `_expandido`; chevron en renderer de `_label`.
- Click → alternar visibilidad de descendientes → `setGridOption('rowData', visibles)`.

### Fase 4 — Totales y filtros
- Total general con `pinnedBottomRowData`.
- Set Filter custom (checkboxes) como Custom Filter Component.

### Fase 5 — Persistencia
- "Guardar vista" → localStorage o tabla `automatizacion_vistas_reporte`.

### Fase 6 — Pulido visual
- Formato condicional (semáforos %cumplimiento), sticky first column, export xlsx con SheetJS.

---

## Estado actual del proyecto (2026-07-02)

Implementado y validado:
- **Fase 1** ✅ Motor pivot aislado en `public/assets/js/pivot-engine.js` (`PivotEngine.compute`).
  Agrega campos base con SUM/AVG/MIN/MAX/COUNT, medidas `raw` y `calc` (ratios),
  N dimensiones en filas y columnas (grupos anidados), subtotales por nivel y total general.
  Sin dependencias y libre de DOM (devuelve columnDefs con tag `__fmt`).
- **Fase 2** ✅ Panel SortableJS en `reporte.blade.php`: pools Dimensiones/Métricas +
  zonas Filas/Columnas/Valores. La zona Valores lleva selector de agregación por medida.
- **Fase 3** ✅ Árbol de grupos con expand/collapse (chevron + click en la columna label).
- **Fase 4** ✅ Total general con `pinnedBottomRowData`; sorting Community; semáforos en %.
- **Fase 5** ✅ Persistencia de la configuración (rows/cols/values + colapsados) en
  `localStorage` (`pivot_reporte_cfg_v2`) + botón "Restablecer vista".
- Agregación en Postgres (`pivot()`, `dia()`, `detalle()`).

Validación: `node` sobre el engine con dataset sintético → subtotales, total general,
ratios y orden canónico correctos. Blade compila; JS inline y engine pasan `node -c`.

Pendientes (mejoras futuras, no bloqueantes):
- Set Filter custom (checkboxes estilo Excel) por dimensión.
- Vistas nombradas múltiples (hoy se autoguarda solo la última).
- Exportación xlsx con estilos (hoy exporta valores planos con SheetJS).
