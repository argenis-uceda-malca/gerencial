@extends('layouts.base')
@section('title', 'Reporte de Ventas')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.3.2/styles/ag-grid.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.3.2/styles/ag-theme-quartz.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ══════════════════════════════════════════════════════════
   PALETA CORPORATIVA PASTEL
   Primario: lavanda suave · Acento: teal · Acento cálido: coral
   Superficies: blanco cálido con sombras sutiles
   ══════════════════════════════════════════════════════════ */
:root {
  --sb-primary:       #6C63FF;
  --sb-primary-dark:  #5A52D5;
  --sb-primary-light: #EEECFF;
  --sb-primary-soft:  #F5F3FF;
  --sb-accent:        #48C9B0;
  --sb-accent-dark:   #3AB09A;
  --sb-accent-light:  #E0F7F2;
  --sb-warm:          #FF8A80;
  --sb-warm-light:    #FFF0EE;
  --sb-amber:         #F5A623;
  --sb-amber-light:   #FFF8E7;
  --sb-ink:           #1E2A3A;
  --sb-muted:         #8E9BB4;
  --sb-muted-light:   #B8C3D9;
  --sb-border:        #E8ECF5;
  --sb-surface:       #FFFFFF;
  --sb-surface-alt:   #F8F9FE;
  --sb-surface-warm:  #FCF7F5;
  --sb-green:         #34C759;
  --sb-green-light:   #E8F8ED;
  --sb-red:           #FF6B6B;
  --sb-red-light:     #FFE8E8;
  --sb-radius:        16px;
  --sb-radius-sm:     10px;
  --sb-radius-xs:     6px;
  --sb-shadow:        0 2px 16px rgba(30,42,58,.06);
  --sb-shadow-md:     0 4px 24px rgba(30,42,58,.08);
  --sb-shadow-lg:     0 8px 40px rgba(30,42,58,.12);
  --sb-ease:          cubic-bezier(.25,.1,.25,1);
  /* Row backgrounds (consumidos por rowStyleFn vía var()) */
  --sb-row-total-bg:    linear-gradient(135deg, #EEECFF, #F5F3FF);
  --sb-row-total-border:#C8C4F0;
  --sb-row-group-0-bg:  #F8F9FE;
  --sb-row-group-bg:    #FAFAFE;
}

* { box-sizing:border-box; }

/* ── Animaciones ── */
@keyframes fadeUp   { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
@keyframes fadeIn   { from{opacity:0} to{opacity:1} }
@keyframes scaleIn  { from{opacity:0;transform:scale(.96)} to{opacity:1;transform:scale(1)} }
@keyframes slideDown{ from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
@keyframes pulse    { 0%,100%{box-shadow:0 0 0 0 rgba(108,99,255,.24)} 50%{box-shadow:0 0 0 8px rgba(108,99,255,.04)} }
@keyframes shimmer  { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
@keyframes spin     { to{transform:rotate(360deg)} }

.container-p-y > * {
  animation: fadeUp .4s var(--sb-ease) both;
}
.container-p-y > *:nth-child(2) { animation-delay:.05s; }
.container-p-y > *:nth-child(3) { animation-delay:.10s; }

/* ── Tipografía ── */
body { font-family:'Inter','Public Sans',-apple-system,BlinkMacSystemFont,sans-serif; }

/* ── Card principal ── */
.filter-card {
  background:var(--sb-surface);
  border:1px solid var(--sb-border) !important;
  border-radius:var(--sb-radius) !important;
  box-shadow:var(--sb-shadow);
  overflow:hidden;
  transition:box-shadow .25s var(--sb-ease);
}
.filter-card:hover { box-shadow:var(--sb-shadow-md); }
.filter-card .card-header {
  background:linear-gradient(135deg,var(--sb-primary-soft) 0%,var(--sb-surface) 100%);
  border-bottom:1px solid var(--sb-border);
  padding:14px 20px;
}
.filter-card .card-header .header-top {
  display:flex;
  align-items:center;
  gap:10px;
  margin-bottom:10px;
}
.filter-card .card-header .header-icon {
  width:36px;height:36px;
  background:var(--sb-primary-light);
  border-radius:var(--sb-radius-sm);
  display:flex;align-items:center;justify-content:center;
  color:var(--sb-primary);
  font-size:1.15rem;flex-shrink:0;
}
.filter-card .card-header h5 {
  font-weight:700;font-size:.92rem;color:var(--sb-ink);margin:0;
  letter-spacing:-.01em;
}
.filter-card .card-body { padding:16px 20px; }

/* ── Period pills (segmented control style) ── */
.periodo-group {
  display:flex;
  flex-wrap:wrap;
  gap:4px;
  background:var(--sb-surface-alt);
  padding:3px;
  border-radius:var(--sb-radius-sm);
}
.periodo-pill {
  font-size:.74rem;font-weight:600;padding:6px 16px;
  border-radius:8px;color:var(--sb-muted) !important;
  border:none !important;background:transparent !important;
  transition:all .2s var(--sb-ease);cursor:pointer;
  white-space:nowrap;user-select:none;
}
.periodo-pill:hover  { color:var(--sb-primary) !important; background:var(--sb-primary-light) !important; }
.periodo-pill.active { background:var(--sb-surface) !important;
  color:var(--sb-primary-dark) !important;font-weight:700;
  box-shadow:0 1px 6px rgba(108,99,255,.18);
}
.periodo-pill.active::after { display:none; }

/* ── Header controls (right side) ── */
.header-controls {
  display:flex;
  align-items:center;
  gap:12px;
  margin-left:auto;
  flex-shrink:0;
}

.live-toggle {
  display:flex;align-items:center;gap:6px;
  font-size:.72rem;color:var(--sb-muted);
  background:var(--sb-surface-alt);
  padding:4px 10px;border-radius:8px;
}
.live-dot {
  width:7px;height:7px;border-radius:50%;
  background:#D1D5E0;
  transition:background .3s;
}
.live-dot.on {
  background:var(--sb-green);
  box-shadow:0 0 0 3px rgba(52,199,89,.2);
  animation:pulse 1.8s ease-in-out infinite;
}
#sel-live {
  font-size:.7rem;border:1px solid var(--sb-border);
  border-radius:6px;padding:2px 6px;
  color:var(--sb-muted);background:var(--sb-surface);
  cursor:pointer;outline:none;
}

/* ── Badge info ── */
.badge-info {
  background:var(--sb-primary-light);
  color:var(--sb-primary-dark);
  font-size:.7rem;font-weight:700;
  padding:4px 12px;border-radius:20px;
  white-space:nowrap;
}

/* ── Botón Excel ── */
#btn-excel {
  background:linear-gradient(135deg,#34C759,#28A745) !important;
  color:#fff !important;border:none !important;
  border-radius:8px !important;
  font-size:.76rem;font-weight:600;padding:6px 16px;
  transition:all .2s var(--sb-ease);
  display:inline-flex;align-items:center;gap:6px;
}
#btn-excel:hover {
  transform:translateY(-1px);
  box-shadow:0 4px 16px rgba(52,199,89,.35);
}

/* ══ FILTROS DE DIMENSIÓN ══ */
.filter-bar {
  margin-top:14px;
  padding-top:14px;
  border-top:1px solid var(--sb-border);
}
.filter-bar-label {
  font-size:.74rem;font-weight:600;color:var(--sb-muted);
  display:flex;align-items:center;gap:5px;
  white-space:nowrap;
}
.dimfilter { position:relative;display:inline-block; }
.dimfilter-btn {
  font-size:.72rem;font-weight:600;padding:5px 14px 5px 14px;
  border-radius:20px;color:var(--sb-muted);
  border:1.5px solid var(--sb-border);background:var(--sb-surface);
  cursor:pointer;display:inline-flex;align-items:center;gap:6px;
  transition:all .18s var(--sb-ease);
  white-space:nowrap;
}
.dimfilter-btn:hover {
  border-color:var(--sb-primary);
  color:var(--sb-primary);
  background:var(--sb-primary-soft);
}
.dimfilter-btn.active {
  background:var(--sb-primary-light);
  border-color:var(--sb-primary);
  color:var(--sb-primary-dark);
  font-weight:700;
  box-shadow:0 2px 8px rgba(108,99,255,.12);
}
.dimfilter-btn .dimfilter-count {
  background:var(--sb-primary);color:#fff;border-radius:10px;
  font-size:.62rem;font-weight:700;padding:0 7px;
  line-height:16px;min-width:16px;text-align:center;
}

/* Panel flotante de filtros */
.dimfilter-panel {
  display:none;position:fixed;z-index:999999;
  width:250px;max-height:320px;
  background:var(--sb-surface);
  border:1px solid var(--sb-border);
  border-radius:var(--sb-radius-sm);
  box-shadow:var(--sb-shadow-lg);
  overflow:hidden;flex-direction:column;
}
.dimfilter-panel.open { display:flex;animation:scaleIn .18s var(--sb-ease) both;transform-origin:top left; }
.dimfilter-search {
  border:none;border-bottom:1px solid var(--sb-border);
  padding:10px 12px;font-size:.75rem;
  outline:none;width:100%;
  background:var(--sb-surface-alt);
  font-family:inherit;
}
.dimfilter-search::placeholder { color:var(--sb-muted-light); }
.dimfilter-actions {
  display:flex;gap:6px;padding:7px 12px;
  border-bottom:1px solid var(--sb-border);
}
.dimfilter-actions button {
  font-size:.67rem;border:none;
  background:var(--sb-primary-light);
  color:var(--sb-primary);border-radius:6px;
  padding:4px 10px;font-weight:600;cursor:pointer;
  transition:all .14s var(--sb-ease);
}
.dimfilter-actions button:hover { background:var(--sb-primary);color:#fff; }
.dimfilter-list { overflow-y:auto;padding:4px 8px;flex:1; }
.dimfilter-item {
  display:flex;align-items:center;gap:8px;
  font-size:.74rem;color:var(--sb-ink);
  padding:6px 8px;border-radius:8px;cursor:pointer;
  transition:background .12s;
}
.dimfilter-item:hover { background:var(--sb-surface-alt); }
.dimfilter-item input { accent-color:var(--sb-primary);cursor:pointer; }
.dimfilter-empty { font-size:.7rem;color:var(--sb-muted-light);padding:12px;text-align:center; }

#btn-clear-filters {
  font-size:.7rem;color:var(--sb-warm);
  background:var(--sb-warm-light);border:1px solid #FFD6D6;
  cursor:pointer;font-weight:600;display:none;
  align-items:center;gap:4px;padding:4px 12px;
  border-radius:16px;transition:all .15s;
  white-space:nowrap;
}
#btn-clear-filters.show { display:inline-flex;animation:fadeIn .2s; }
#btn-clear-filters:hover {
  background:var(--sb-red-light);
  border-color:var(--sb-red);
  color:var(--sb-red);
}

/* ══ TABS ── diseño moderno con indicador ══ */
.nav-tabs {
  border-bottom:1px solid var(--sb-border);
  gap:0;padding:0 0 0 0;
  background:var(--sb-surface-alt);
  border-radius:var(--sb-radius) var(--sb-radius) 0 0;
  overflow:hidden;
}
.nav-tabs .nav-item { margin:0; }
.nav-tabs .nav-link {
  color:var(--sb-muted);font-size:.82rem;font-weight:600;
  padding:12px 22px;border:none;border-radius:0;
  transition:all .2s var(--sb-ease);
  position:relative;background:transparent;
  margin:0;display:flex;align-items:center;gap:7px;
}
.nav-tabs .nav-link i { font-size:1.05rem; }
.nav-tabs .nav-link::after {
  content:'';position:absolute;bottom:0;left:50%;right:50%;
  height:3px;background:var(--sb-primary);
  border-radius:3px 3px 0 0;
  transition:all .25s var(--sb-ease);
}
.nav-tabs .nav-link:hover { color:var(--sb-primary);background:var(--sb-primary-soft); }
.nav-tabs .nav-link.active { color:var(--sb-primary-dark);font-weight:700;background:var(--sb-surface); }
.nav-tabs .nav-link.active::after { left:20%;right:20%; }

/* ══ PANEL DE CONFIGURACIÓN ── glassmorphism ══ */
.pv-panel {
  border:1px solid var(--sb-border);
  border-radius:var(--sb-radius);
  overflow:hidden;
  background:var(--sb-surface);
  transition:box-shadow .25s var(--sb-ease);
}
.pv-panel:hover { box-shadow:var(--sb-shadow); }
.pv-panel-toggle {
  width:100%;display:flex;align-items:center;gap:10px;
  padding:12px 16px;
  background:linear-gradient(135deg,var(--sb-primary-soft),var(--sb-surface-alt));
  border:none;cursor:pointer;
  font-size:.78rem;font-weight:600;color:var(--sb-ink);text-align:left;
  transition:background .2s var(--sb-ease);
}
.pv-panel-toggle:hover { background:var(--sb-primary-soft); }
.pv-panel-toggle i:first-child { color:var(--sb-primary);font-size:1rem; }
.pv-panel-summary {
  flex:1;font-weight:400;color:var(--sb-muted);font-size:.72rem;
  overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
}
.pv-panel-chevron { transition:transform .25s var(--sb-ease);color:var(--sb-muted);font-size:1.1rem; }
.pv-panel.collapsed .pv-panel-chevron { transform:rotate(-90deg); }
.pv-panel .pivot-config {
  margin:0 !important;border:none;border-top:1px solid var(--sb-border);border-radius:0;
  max-height:400px;opacity:1;
  transition:max-height .3s var(--sb-ease), opacity .25s var(--sb-ease), padding .3s var(--sb-ease);
}
.pv-panel.collapsed .pivot-config {
  max-height:0 !important;opacity:0;padding-top:0 !important;padding-bottom:0 !important;
  border-top:none;overflow:hidden;
}

/* Config body */
.pivot-config {
  display:flex;align-items:stretch;gap:10px;flex-wrap:nowrap;overflow-x:auto;
  background:var(--sb-surface-warm);
  padding:14px;
}
.pool-col  { display:flex;flex-direction:row;gap:10px;flex:0 0 auto; }
.zones-col { display:flex;flex-direction:row;gap:10px;flex:1 1 auto;flex-wrap:nowrap;min-width:0; }
.zones-col .pv-box { flex:1 1 0;min-width:0; }

.pv-box {
  background:var(--sb-surface);
  border:1px solid var(--sb-border);
  border-radius:var(--sb-radius-sm);
  display:flex;flex-direction:column;overflow:hidden;
  transition:box-shadow .2s var(--sb-ease);
}
.pv-box:hover { box-shadow:0 2px 12px rgba(30,42,58,.05); }
.pool-dims  { width:270px; }
.pool-measures { width:270px; }
.pv-box .pv-head {
  font-size:.61rem;font-weight:700;color:var(--sb-muted-light);
  text-transform:uppercase;letter-spacing:.06em;
  padding:6px 10px;
  border-bottom:1px solid var(--sb-border);
  display:flex;align-items:center;gap:5px;white-space:nowrap;
}
.pv-box .pv-head i { font-size:.85rem; }
.pv-box .zone-body {
  flex:1;min-height:38px;padding:7px 8px;display:flex;flex-wrap:wrap;
  align-content:flex-start;gap:5px;overflow-y:auto;
  transition:background .2s var(--sb-ease);
}

.zone-rows   .pv-head { background:#FFF5E6; color:#D4942B; }
.zone-cols   .pv-head { background:#E6F7F5; color:#3AB09A; }
.zone-values .pv-head { background:#E8F8ED; color:#28A745; }
.zone-rows   .zone-body.pv-drop { background:#FFFBF0; }
.zone-cols   .zone-body.pv-drop { background:#F0FCFA; }
.zone-values .zone-body.pv-drop { background:#F0FCF5; }

/* Chips */
.pivot-chip {
  display:inline-flex;align-items:center;gap:4px;
  font-size:.69rem;font-weight:600;padding:3px 10px;
  border-radius:20px;cursor:grab;user-select:none;
  border:1.5px solid #DCDAF5;
  background:var(--sb-surface);color:var(--sb-ink);
  transition:all .15s var(--sb-ease);white-space:nowrap;
}
.pivot-chip:hover {
  border-color:var(--sb-primary);
  box-shadow:0 2px 8px rgba(108,99,255,.15);
  transform:translateY(-1px);
}
.pivot-chip:active { cursor:grabbing; }
.pivot-chip.chip-measure { border-color:#B8E6C8; }
.zone-rows   .pivot-chip { background:#FFF8EC;border-color:#F5C26B;color:#B87A0A; }
.zone-cols   .pivot-chip { background:#E6F7F5;border-color:#7DD4C0;color:#2A8A78; }
.zone-values .pivot-chip { background:#E8F8ED;border-color:#7FD99A;color:#1F8B3A; }
.pivot-chip .chip-grip { opacity:.35;font-size:.85em; }

.agg-select {
  display:none;border:none;background:rgba(255,255,255,.7);
  color:#1F8B3A;font-size:.63rem;font-weight:700;
  border-radius:6px;padding:1px 3px;outline:none;cursor:pointer;
}
#zone-values .agg-select { display:inline-block; }

.sortable-ghost { opacity:.3;background:var(--sb-primary-light) !important; }
.sortable-drag  { opacity:.95;transform:rotate(2deg); }

.pv-hint { font-size:.68rem;color:var(--sb-muted-light);font-style:italic;padding:4px 2px; }
.pv-chevron { color:var(--sb-primary);font-weight:700;cursor:pointer;display:inline-block;transition:transform .15s; }

#btn-reset {
  font-size:.7rem;border:1.5px solid var(--sb-border);
  color:var(--sb-muted);border-radius:var(--sb-radius-sm);
  white-space:nowrap;padding:6px 14px;background:var(--sb-surface);
  transition:all .18s var(--sb-ease);
  display:inline-flex;align-items:center;gap:5px;
}
#btn-reset:hover {
  border-color:var(--sb-primary) !important;
  color:var(--sb-primary) !important;
  background:var(--sb-primary-light);
  box-shadow:0 2px 8px rgba(108,99,255,.1);
}

/* ══ AG GRID ── tema quartz refinado ══ */
.ag-theme-quartz {
  --ag-font-family: 'Inter','Public Sans',sans-serif;
  --ag-font-size: 12.5px;
  --ag-background-color: var(--sb-surface);
  --ag-header-background-color: var(--sb-surface-alt);
  --ag-header-foreground-color: var(--sb-ink);
  --ag-odd-row-background-color: #FAFAFE;
  --ag-row-hover-color: var(--sb-primary-soft);
  --ag-border-color: var(--sb-border);
  --ag-header-column-separator-color: var(--sb-border);
  --ag-row-border-color: #F0F2F8;
  --ag-cell-horizontal-border: var(--sb-border);
  --ag-selected-row-background-color: var(--sb-primary-light);
  --ag-range-selection-border-color: var(--sb-primary);
  --ag-accent-color: var(--sb-primary);
  --ag-borders: solid 1px;
  --ag-border-radius: var(--sb-radius-sm);
  --ag-wrapper-border-radius: 0 0 var(--sb-radius-sm) var(--sb-radius-sm);
  border:1px solid var(--sb-border) !important;
}
.ag-theme-quartz .ag-header-cell-label { font-weight:600; }
.ag-theme-quartz .ag-header-group-cell {
  background: linear-gradient(135deg, #EEECFF, #F5F3FF) !important;
  color: var(--sb-primary-dark) !important;
  border-bottom:2px solid var(--sb-primary-light) !important;
}
.ag-theme-quartz .ag-header-group-cell-label {
  justify-content:center;font-weight:700 !important;
  font-size:.78rem;letter-spacing:-.01em;
}
.ag-theme-quartz .ag-cell {
  display:flex;align-items:center;transition:background-color .12s;
  padding:0 8px;
}
.ag-theme-quartz .ag-row-hover .ag-cell { background-color:var(--sb-primary-soft) !important; }
.ag-theme-quartz .ag-row { border-bottom:1px solid #F0F2F8 !important; }

/* Data pills for AG Grid */
.sb-pill {
  display:inline-flex;align-items:center;padding:2px 10px;
  border-radius:20px;
  font-size:.73rem;font-weight:600;line-height:1.5;
}
.sb-pill-money  { background:var(--sb-primary-light);color:var(--sb-primary-dark); }
.sb-pill-pos    { background:var(--sb-green-light);color:#1A7D3A; }
.sb-pill-neg    { background:var(--sb-red-light);color:#CC4444; }
.sb-pill-neutral{ background:var(--sb-surface-alt);color:var(--sb-muted); }

/* Grid containers */
#grid-pivot   { width:100%; }
#grid-detalle { height:calc(100vh - 310px);min-height:400px; }

/* Tab content cards */
.tab-card {
  border-radius:0 0 var(--sb-radius) var(--sb-radius);
  border-top:none;
  border:1px solid var(--sb-border);
  background:var(--sb-surface);
  position:relative;
  overflow:hidden;
}

/* Loader */
.tab-loader {
  display:none;position:absolute;inset:0;
  background:rgba(255,255,255,.92);
  z-index:10;align-items:center;justify-content:center;
  animation:fadeIn .2s;backdrop-filter:blur(2px);
}
.tab-loader.show { display:flex; }
.tab-loader .loader-icon {
  width:48px;height:48px;
  border:3px solid var(--sb-border);
  border-top-color:var(--sb-primary);
  border-radius:50%;
  animation:spin .7s linear infinite;
  margin-bottom:10px;
}
.tab-loader .loader-text {
  color:var(--sb-muted);font-size:.85rem;font-weight:500;
}

/* Total rows styling */
.ag-row-total {
  font-weight:800 !important;
  background: linear-gradient(135deg, #EEECFF, #F5F3FF) !important;
  border-top:2px solid var(--sb-primary-light) !important;
}
.ag-row-group-0 {
  font-weight:700 !important;
  background:#F8F9FE !important;
}
.ag-row-group {
  font-weight:600 !important;
  background:#FAFAFE !important;
}

/* Barra de cumplimiento en detalle */
.cumpl-bar {
  display:flex;align-items:center;gap:6px;width:100%;
}
.cumpl-track {
  flex:1;height:6px;border-radius:3px;
  background:#ECEEF5;overflow:hidden;
}
.cumpl-fill {
  height:100%;border-radius:3px;
  transition:width .4s var(--sb-ease);
}
.cumpl-label {
  font-size:.73rem;font-weight:600;white-space:nowrap;min-width:38px;
}

/* ══════════════════════════════════════════════════════════
   RESPONSIVE
   ══════════════════════════════════════════════════════════ */
@media (max-width: 992px) {
  .pivot-config { flex-direction:column; }
  .pool-col { flex-direction:row;width:100%; }
  .pool-dims,.pool-measures { flex:1 1 0;width:auto; }
  .zones-col { min-width:0; }
  .pivot-config-footer { justify-content:center; }
  .header-controls { margin-left:0;width:100%;justify-content:flex-end; }
}

@media (max-width: 768px) {
  .container-p-y { padding-left:.6rem !important;padding-right:.6rem !important; }
  .filter-card .card-body { padding:12px !important; }
  .filter-card .card-header { padding:12px 14px;flex-direction:column;align-items:stretch; }
  .periodo-pill { font-size:.7rem;padding:5px 11px; }
  .filter-bar { overflow-x:auto;flex-wrap:nowrap !important;-webkit-overflow-scrolling:touch;padding-bottom:8px; }
  .filter-bar::-webkit-scrollbar { height:4px; }
  #dimfilters-container { flex-wrap:nowrap; }
  .dimfilter-btn { flex-shrink:0; }
  .dimfilter-panel { width:220px;left:auto;right:0; }
  .header-controls { justify-content:flex-start;flex-wrap:wrap; }

  .nav-tabs { flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch; }
  .nav-tabs .nav-link { white-space:nowrap;padding:10px 16px;font-size:.78rem; }

  .pivot-config { padding:10px;gap:8px; }
  .pool-col { flex-direction:column; }
  .pool-dims,.pool-measures { width:100%; }
  .zones-col { flex-direction:column; }
  .zones-col .pv-box { flex:1 1 auto;min-width:0; }
  .pivot-config-footer { justify-content:center !important; }

  #grid-detalle { height:55vh;min-height:300px; }
}

@media (max-width: 480px) {
  #lbl-rango { display:none; }
  .periodo-pill { font-size:.66rem;padding:4px 9px; }
  .badge-info { display:none; }
}

/* ══════════════════════════════════════════════════════════
   DARK MODE – Overrides para reporte
   ══════════════════════════════════════════════════════════ */
[data-theme="dark"] {
  --sb-primary:       #8B83FF;
  --sb-primary-dark:  #9E97FF;
  --sb-primary-light: #2D2A5C;
  --sb-primary-soft:  #25224F;
  --sb-accent:        #5DD4BE;
  --sb-accent-dark:   #48C9B0;
  --sb-accent-light:  #1E3D38;
  --sb-warm:          #FF8A80;
  --sb-warm-light:    #3D2428;
  --sb-amber:         #F5A623;
  --sb-amber-light:   #3D2E14;
  --sb-ink:           #E2E6F0;
  --sb-muted:         #8B90A8;
  --sb-muted-light:   #6B7088;
  --sb-border:        #2E3450;
  --sb-surface:       #1F2438;
  --sb-surface-alt:   #282E44;
  --sb-surface-warm:  #1F2438;
  --sb-green:         #4ADE80;
  --sb-green-light:   #1A3D28;
  --sb-red:           #F87171;
  --sb-red-light:     #3D2028;
  --sb-shadow:        0 2px 16px rgba(0,0,0,.3);
  --sb-shadow-md:     0 4px 24px rgba(0,0,0,.35);
  --sb-shadow-lg:     0 8px 40px rgba(0,0,0,.4);
  --sb-row-total-bg:    linear-gradient(135deg, #2D2A5C, #25224F);
  --sb-row-total-border:#4A47A0;
  --sb-row-group-0-bg:  #282E44;
  --sb-row-group-bg:    #24283E;
}

[data-theme="dark"] .filter-card,
[data-theme="dark"] .tab-card,
[data-theme="dark"] .pv-panel {
  background: var(--sb-surface);
  border-color: var(--sb-border);
  box-shadow: var(--sb-shadow);
}
[data-theme="dark"] .filter-card .card-header {
  background: linear-gradient(135deg, #25224F, var(--sb-surface));
  border-color: var(--sb-border);
}
[data-theme="dark"] .header-icon {
  background: var(--sb-primary-light);
  color: var(--sb-primary);
}
[data-theme="dark"] .periodo-group {
  background: var(--sb-surface-alt);
}
[data-theme="dark"] .periodo-pill {
  color: var(--sb-muted) !important;
}
[data-theme="dark"] .periodo-pill:hover {
  color: var(--sb-primary) !important;
  background: var(--sb-primary-light) !important;
}
[data-theme="dark"] .periodo-pill.active {
  background: var(--sb-surface) !important;
  color: var(--sb-primary) !important;
  box-shadow: 0 1px 6px rgba(139,131,255,.2);
}
[data-theme="dark"] .badge-info {
  background: var(--sb-primary-light);
  color: var(--sb-primary);
}
[data-theme="dark"] .live-toggle {
  background: var(--sb-surface-alt);
  color: var(--sb-muted);
}
[data-theme="dark"] #sel-live {
  background: var(--sb-surface);
  border-color: var(--sb-border);
  color: var(--sb-muted);
}
[data-theme="dark"] .filter-bar {
  border-color: var(--sb-border);
}
[data-theme="dark"] .filter-bar-label {
  color: var(--sb-muted);
}
[data-theme="dark"] .dimfilter-btn {
  border-color: var(--sb-border);
  background: var(--sb-surface);
  color: var(--sb-muted);
}
[data-theme="dark"] .dimfilter-btn:hover {
  border-color: var(--sb-primary);
  color: var(--sb-primary);
  background: var(--sb-primary-soft);
}
[data-theme="dark"] .dimfilter-btn.active {
  background: var(--sb-primary-light);
  border-color: var(--sb-primary);
  color: var(--sb-primary);
  box-shadow: 0 2px 8px rgba(139,131,255,.12);
}
[data-theme="dark"] .dimfilter-panel {
  background: var(--sb-surface);
  border-color: var(--sb-border);
  box-shadow: var(--sb-shadow-lg);
}
[data-theme="dark"] .dimfilter-search {
  background: var(--sb-surface-alt);
  border-color: var(--sb-border);
  color: var(--sb-ink);
}
[data-theme="dark"] .dimfilter-actions button {
  background: var(--sb-primary-light);
  color: var(--sb-primary);
}
[data-theme="dark"] .dimfilter-actions button:hover {
  background: var(--sb-primary);
  color: #fff;
}
[data-theme="dark"] .dimfilter-item {
  color: var(--sb-ink);
}
[data-theme="dark"] .dimfilter-item:hover {
  background: var(--sb-surface-alt);
}
[data-theme="dark"] #btn-clear-filters {
  background: var(--sb-warm-light);
  border-color: #3D2428;
  color: var(--sb-warm);
}
[data-theme="dark"] #btn-clear-filters:hover {
  background: var(--sb-red-light);
  border-color: var(--sb-red);
  color: var(--sb-red);
}
[data-theme="dark"] .nav-tabs {
  background: var(--sb-surface-alt);
  border-color: var(--sb-border);
}
[data-theme="dark"] .nav-tabs .nav-link {
  color: var(--sb-muted);
}
[data-theme="dark"] .nav-tabs .nav-link:hover {
  color: var(--sb-primary);
  background: var(--sb-primary-soft);
}
[data-theme="dark"] .nav-tabs .nav-link.active {
  color: var(--sb-primary);
  background: var(--sb-surface);
}
[data-theme="dark"] .pv-panel-toggle {
  background: linear-gradient(135deg, var(--sb-primary-soft), var(--sb-surface-alt));
  color: var(--sb-ink);
}
[data-theme="dark"] .pv-panel-toggle:hover {
  background: var(--sb-primary-soft);
}
[data-theme="dark"] .pivot-config {
  background: var(--sb-surface-alt);
  border-color: var(--sb-border);
}
[data-theme="dark"] .pivot-config-footer {
  background: var(--sb-surface-alt);
  border-color: var(--sb-border);
}
[data-theme="dark"] .pv-box {
  background: var(--sb-surface);
  border-color: var(--sb-border);
}
[data-theme="dark"] .pv-box .pv-head {
  border-color: var(--sb-border);
}
[data-theme="dark"] .zone-rows .pv-head { background: #3D2E14; color: var(--sb-amber); }
[data-theme="dark"] .zone-cols .pv-head { background: #1E3D38; color: var(--sb-accent); }
[data-theme="dark"] .zone-values .pv-head { background: #1A3D28; color: var(--sb-green); }
[data-theme="dark"] .zone-rows .zone-body.pv-drop { background: #2A2010; }
[data-theme="dark"] .zone-cols .zone-body.pv-drop { background: #102826; }
[data-theme="dark"] .zone-values .zone-body.pv-drop { background: #102818; }
[data-theme="dark"] .pivot-chip {
  border-color: #3A3860;
  background: var(--sb-surface);
  color: var(--sb-ink);
}
[data-theme="dark"] .zone-rows .pivot-chip { background: #3D2E14; border-color: #6B5220; color: #F5C26B; }
[data-theme="dark"] .zone-cols .pivot-chip { background: #1E3D38; border-color: #2A6B5E; color: #7DD4C0; }
[data-theme="dark"] .zone-values .pivot-chip { background: #1A3D28; border-color: #286B3A; color: #7FD99A; }
[data-theme="dark"] .pivot-chip:hover {
  box-shadow: 0 2px 8px rgba(139,131,255,.2);
}
[data-theme="dark"] #btn-reset {
  border-color: var(--sb-border);
  color: var(--sb-muted);
  background: var(--sb-surface);
}
[data-theme="dark"] #btn-reset:hover {
  border-color: var(--sb-primary) !important;
  color: var(--sb-primary) !important;
  background: var(--sb-primary-light);
}

/* Dark mode AG Grid overrides */
[data-theme="dark"] .ag-theme-quartz {
  --ag-background-color: var(--sb-surface);
  --ag-header-background-color: var(--sb-surface-alt);
  --ag-odd-row-background-color: #1C2135;
  --ag-row-hover-color: var(--sb-primary-soft);
  --ag-border-color: var(--sb-border);
  --ag-row-border-color: #282E44;
  --ag-header-foreground-color: var(--sb-ink);
  --ag-header-column-separator-color: var(--sb-border);
  --ag-cell-horizontal-border: var(--sb-border);
  --ag-foreground-color: var(--sb-ink);
  --ag-data-color: var(--sb-ink);
  --ag-secondary-foreground-color: var(--sb-muted);
}
[data-theme="dark"] .ag-theme-quartz .ag-header-group-cell {
  background: linear-gradient(135deg, #25224F, #2D2A5C) !important;
  color: var(--sb-primary) !important;
  border-bottom: 2px solid var(--sb-primary-light) !important;
}
[data-theme="dark"] .ag-theme-quartz .ag-row {
  border-bottom: 1px solid #282E44 !important;
  color: var(--sb-ink);
}
[data-theme="dark"] .ag-theme-quartz .ag-row:not(.ag-row-first) .ag-cell {
  color: var(--sb-ink);
}
[data-theme="dark"] .ag-theme-quartz .ag-cell {
  color: var(--sb-ink);
}
[data-theme="dark"] .ag-theme-quartz .ag-header-cell-label {
  color: var(--sb-ink);
}
[data-theme="dark"] .ag-theme-quartz .ag-pinned-left-header .ag-header-cell-label {
  color: var(--sb-ink);
}
[data-theme="dark"] .ag-theme-quartz .ag-row-hover .ag-cell {
  background-color: var(--sb-primary-soft) !important;
}
[data-theme="dark"] .sb-pill-money {
  background: var(--sb-primary-light);
  color: var(--sb-primary);
}
[data-theme="dark"] .sb-pill-pos {
  background: var(--sb-green-light);
  color: #4ADE80;
}
[data-theme="dark"] .sb-pill-neg {
  background: var(--sb-red-light);
  color: #F87171;
}
[data-theme="dark"] .sb-pill-neutral {
  background: var(--sb-surface-alt);
  color: var(--sb-muted);
}
[data-theme="dark"] .cumpl-track {
  background: #2E3450;
}
[data-theme="dark"] .tab-loader {
  background: rgba(31,36,56,.9);
}
[data-theme="dark"] .tab-loader .loader-icon {
  border-color: var(--sb-border);
  border-top-color: var(--sb-primary);
}
[data-theme="dark"] .tab-loader .loader-text {
  color: var(--sb-muted);
}
</style>
@endpush

@section('contenido')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- ══ CARD PRINCIPAL ── FILTROS ══ --}}
  <div class="card filter-card mb-4">
    <div class="card-header">
      <div class="header-top">
        <div class="header-icon"><i class="bx bx-line-chart"></i></div>
        <div>
          <h5>Reporte de Ventas</h5>
        </div>
        <div class="header-controls">
          <span id="lbl-filas" class="badge-info"></span>
          <span id="lbl-rango" class="text-muted" style="font-size:.73rem;font-weight:500;white-space:nowrap"></span>
          <div class="live-toggle" title="Actualización automática">
            <span class="live-dot" id="live-dot"></span>
            <select id="sel-live">
              <option value="0">Manual</option>
              <option value="30000">30s</option>
              <option value="60000">1 min</option>
              <option value="300000">5 min</option>
            </select>
          </div>
          <button id="btn-excel"><i class="bx bx-file"></i>Excel</button>
        </div>
      </div>

      <div class="periodo-group">
        @foreach($meses as $m)
          @php
            $ini  = \Carbon\Carbon::create(2026,$m['n'],1)->startOfMonth()->toDateString();
            $finM = \Carbon\Carbon::create(2026,$m['n'],1)->endOfMonth();
            $fin  = $finM->gt(\Carbon\Carbon::today()) ? $today : $finM->toDateString();
          @endphp
          <button class="periodo-pill {{ $loop->last ? 'active' : '' }}"
                  data-ini="{{ $ini }}" data-fin="{{ $fin }}">{{ $m['nom'] }}</button>
        @endforeach
        <button class="periodo-pill"
                data-ini="{{ \Carbon\Carbon::today()->startOfYear()->toDateString() }}"
                data-fin="{{ $today }}">Acumulado</button>
      </div>
    </div>

    <div class="card-body">
      {{-- FILTROS DE DIMENSIÓN --}}
      <div class="filter-bar d-flex flex-wrap align-items-center gap-2">
        <span class="filter-bar-label"><i class="bx bx-filter-alt"></i>Filtros:</span>
        <div id="dimfilters-container" class="d-flex flex-wrap gap-2"></div>
        <button id="btn-clear-filters"><i class="bx bx-x"></i> Limpiar</button>
      </div>
    </div>
  </div>

  {{-- ══ TABS ══ --}}
  <ul class="nav nav-tabs" id="mainTabs">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-pivot" id="btn-tab-pivot">
        <i class="bx bx-grid-alt"></i>Tabla Dinámica
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-detalle" id="btn-tab-detalle">
        <i class="bx bx-table"></i>Tabla Detalle
      </button>
    </li>
  </ul>

  <div class="tab-content" style="padding:0;">

    {{-- ══ TAB PIVOT ══ --}}
    <div class="tab-pane fade show active" id="tab-pivot">
      <div class="tab-card">
        <div class="tab-loader show" id="pivot-loader">
          <div class="text-center">
            <div class="loader-icon"></div>
            <p class="loader-text">Cargando datos…</p>
          </div>
        </div>
        <div class="card-body p-3">

          {{-- Panel de configuración --}}
          <div class="pv-panel mb-3">
            <button type="button" class="pv-panel-toggle" id="pv-panel-toggle">
              <i class="bx bx-slider-alt"></i>
              <span>Configurar tabla</span>
              <span id="pv-panel-summary" class="pv-panel-summary"></span>
              <i class="bx bx-chevron-down pv-panel-chevron"></i>
            </button>
            <div class="pivot-config" id="pivot-config">
              <div class="pool-col">
                <div class="pv-box pool-dims">
                  <div class="pv-head"><i class="bx bx-purchase-tag-alt"></i> Campos</div>
                  <div class="zone-body" id="pool-dims"></div>
                </div>
                <div class="pv-box pool-measures">
                  <div class="pv-head"><i class="bx bx-calculator"></i> Métricas</div>
                  <div class="zone-body" id="pool-measures"></div>
                </div>
              </div>
              <div class="zones-col">
                <div class="pv-box zone-rows">
                  <div class="pv-head">▼ Filas</div>
                  <div class="zone-body" id="zone-rows"></div>
                </div>
                <div class="pv-box zone-cols">
                  <div class="pv-head">▶ Columnas</div>
                  <div class="zone-body" id="zone-cols"></div>
                </div>
                <div class="pv-box zone-values">
                  <div class="pv-head">Σ Valores</div>
                  <div class="zone-body" id="zone-values"></div>
                </div>
              </div>
            </div>
            <div class="pivot-config-footer" style="display:flex;justify-content:flex-end;padding:8px 14px 14px;background:var(--sb-surface-warm);border-top:1px solid var(--sb-border)">
              <button id="btn-reset"><i class="bx bx-reset"></i> Restablecer</button>
            </div>
          </div>

          {{-- AG Grid --}}
          <div id="grid-pivot" class="ag-theme-quartz"></div>

        </div>
      </div>
    </div>

    {{-- ══ TAB DETALLE ══ --}}
    <div class="tab-pane fade" id="tab-detalle">
      <div class="tab-card">
        <div class="card-body p-0">
          <div id="grid-detalle" class="ag-theme-quartz"></div>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

@section('footer')
<script src="../assets/js/pivot-engine.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.3.2/dist/ag-grid-community.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script>
'use strict';

/* ══════════════════════════════════════════════════════════
   ESTADO
   ══════════════════════════════════════════════════════════ */
var ALL_DATA    = [];
var HST_DATA    = [];
var METAS_DATA  = [];
var MERGED_DATA = [];
var activeIni   = '{{ $mesIni }}';
var activeFin   = '{{ $today }}';
var gridPivot   = null;
var gridDetalle = null;
var detLoaded   = false;
var collapsed   = new Set();
var sortables   = [];
var LS_KEY      = 'pivot_reporte_cfg_v4';
var MARCA_TEMPORADA = ['FINA','EXIT','KORDA','MILK','MCH','BBM'];

var DIMENSIONS  = ['Mes','Semana','Día #','Día','Canal','Subcanal','Tienda','Marca','Marca Temporada','Categoría','SSS','Localidad','Lineas','Temporada'];
var MEASURE_KEYS = Object.keys(PivotEngine.MEASURES);

var ORDER_MAP = {
  'Día'  : ['lunes','martes','miércoles','jueves','viernes','sábado','domingo'],
  'Mes'  : ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
  'Canal': ['BOUTIQUES','OUTLETS','WEB'],
  'Marca': ['MCH','EXIT','MILK','BBM','FINA','KORDA'],
  'Marca Temporada': ['MCH','EXIT','MILK','BBM','FINA','KORDA'],
};

var DEFAULT_CFG = {
  rows:   ['Canal','Subcanal'],
  cols:   ['Día'],
  values: [
    { key:'vta26',       aggFn:'sum' },
    { key:'var_pct',     aggFn:'sum' },
    { key:'cumpl_pct',   aggFn:'sum' },
    { key:'gm26_pct',    aggFn:'sum' },
    { key:'ticket_prom', aggFn:'sum' },
  ],
};

/* ══════════════════════════════════════════════════════════
   FILTROS DE DIMENSIÓN
   ══════════════════════════════════════════════════════════ */
var FILTER_DIMS = [
  { key:'Canal',     param:'canales',     label:'Canal' },
  { key:'Marca',             param:'marcas',             label:'Marca' },
  { key:'Marca Temporada',   param:'marca_temporada',    label:'Marca Temporada' },
  { key:'Semana',            param:'semanas',             label:'Semana' },
  { key:'Día',       param:'dias',        label:'Día' },
  { key:'Tienda',    param:'tiendas',     label:'Tienda' },
  { key:'Categoría', param:'categorias',  label:'Categoría' },
  { key:'SSS',       param:'sss',         label:'SSS' },
  { key:'Localidad', param:'localidades', label:'Localidad' },
  { key:'Lineas',    param:'lineas',      label:'Líneas' },
  { key:'Temporada', param:'temporadas',  label:'Temporada' },
];
var FILTERS = {};
FILTER_DIMS.forEach(function(f){ FILTERS[f.key] = new Set(); });
var FILTER_OPTIONS = {};
var LS_FILTERS_KEY = 'pivot_reporte_filters_v1';

function buildFilterOptions(){
  FILTER_DIMS.forEach(function(f){
    var set = new Set();
    ALL_DATA.forEach(function(r){ var v=r[f.key]; if(v!=null && v!=='') set.add(String(v)); });
    var order = ORDER_MAP[f.key];
    var arr = Array.from(set);
    arr.sort(function(a,b){
      if(order){ var ia=order.indexOf(a), ib=order.indexOf(b); if(ia<0)ia=999; if(ib<0)ib=999; if(ia!==ib) return ia-ib; }
      return a.localeCompare(b,'es');
    });
    if(f.key === 'Marca Temporada') arr = MARCA_TEMPORADA.slice();
    FILTER_OPTIONS[f.key] = arr;
  });
}

function getFilteredData(){
  var activeDims = FILTER_DIMS.filter(function(f){ return FILTERS[f.key].size > 0; });
  if(!activeDims.length) return ALL_DATA;
  return ALL_DATA.filter(function(r){
    return activeDims.every(function(f){ return FILTERS[f.key].has(String(r[f.key])); });
  });
}

function updateClearFiltersBtn(){
  var any = FILTER_DIMS.some(function(f){ return FILTERS[f.key].size>0; });
  document.getElementById('btn-clear-filters').classList.toggle('show', any);
}

function saveFilters(){
  var plain = {};
  FILTER_DIMS.forEach(function(f){ plain[f.key] = Array.from(FILTERS[f.key]); });
  try { localStorage.setItem(LS_FILTERS_KEY, JSON.stringify(plain)); } catch(e){}
}
function loadFilters(){
  try {
    var raw = localStorage.getItem(LS_FILTERS_KEY);
    if(!raw) return;
    var o = JSON.parse(raw);
    FILTER_DIMS.forEach(function(f){ if(o[f.key]) FILTERS[f.key] = new Set(o[f.key]); });
  } catch(e){}
}

function renderFilterButton(f){
  var wrap = document.createElement('div');
  wrap.className = 'dimfilter';
  wrap.dataset.dim = f.key;

  var btn = document.createElement('button');
  btn.className = 'dimfilter-btn';
  btn.type = 'button';
  btn.innerHTML = '<span class="dimfilter-label">'+escHtml(f.label)+'</span>';

  var panel = document.createElement('div');
  panel.className = 'dimfilter-panel';

  var search = document.createElement('input');
  search.className = 'dimfilter-search';
  search.type = 'text';
  search.placeholder = 'Buscar ' + f.label.toLowerCase() + '…';

  var actions = document.createElement('div');
  actions.className = 'dimfilter-actions';
  var btnAll = document.createElement('button'); btnAll.type='button'; btnAll.textContent='Todos';
  var btnNone = document.createElement('button'); btnNone.type='button'; btnNone.textContent='Ninguno';
  actions.appendChild(btnAll); actions.appendChild(btnNone);

  var list = document.createElement('div');
  list.className = 'dimfilter-list';

  function refreshBtnState(){
    var n = FILTERS[f.key].size;
    var countEl = btn.querySelector('.dimfilter-count');
    if(n>0){
      btn.classList.add('active');
      if(!countEl){ countEl=document.createElement('span'); countEl.className='dimfilter-count'; btn.appendChild(countEl); }
      countEl.textContent = n;
    } else {
      btn.classList.remove('active');
      if(countEl) countEl.remove();
    }
  }

  function renderList(filterText){
    list.innerHTML = '';
    var opts = FILTER_OPTIONS[f.key] || [];
    var ft = (filterText||'').toLowerCase();
    var shown = opts.filter(function(v){ return !ft || v.toLowerCase().indexOf(ft)>=0; });
    if(!shown.length){ list.innerHTML = '<div class="dimfilter-empty">Sin resultados</div>'; return; }
    shown.forEach(function(v){
      var item = document.createElement('label');
      item.className = 'dimfilter-item';
      var cb = document.createElement('input');
      cb.type = 'checkbox';
      cb.checked = FILTERS[f.key].has(v);
      cb.addEventListener('change', function(){
        if(cb.checked) FILTERS[f.key].add(v); else FILTERS[f.key].delete(v);
        refreshBtnState();
        onFiltersChanged();
      });
      var txt = document.createElement('span');
      txt.textContent = v;
      item.appendChild(cb); item.appendChild(txt);
      list.appendChild(item);
    });
  }

  function positionPanel(){
    var r = btn.getBoundingClientRect();
    var pw = 250;
    var left = r.left;
    if(left + pw > window.innerWidth - 8) left = window.innerWidth - pw - 8;
    panel.style.top  = (r.bottom + 8) + 'px';
    panel.style.left = Math.max(8, left) + 'px';
  }

  btn.addEventListener('click', function(e){
    e.stopPropagation();
    var wasOpen = panel.classList.contains('open');
    document.querySelectorAll('.dimfilter-panel.open').forEach(function(p){ p.classList.remove('open'); });
    if(!wasOpen){
      renderList(''); search.value='';
      positionPanel();
      panel.classList.add('open');
      search.focus();
    }
  });
  search.addEventListener('input', function(){ renderList(search.value); });
  search.addEventListener('click', function(e){ e.stopPropagation(); });
  btnAll.addEventListener('click', function(e){
    e.stopPropagation();
    (FILTER_OPTIONS[f.key]||[]).forEach(function(v){ FILTERS[f.key].add(v); });
    refreshBtnState(); renderList(search.value); onFiltersChanged();
  });
  btnNone.addEventListener('click', function(e){
    e.stopPropagation();
    FILTERS[f.key].clear();
    refreshBtnState(); renderList(search.value); onFiltersChanged();
  });

  panel.appendChild(search); panel.appendChild(actions); panel.appendChild(list);
  wrap.appendChild(btn);
  document.body.appendChild(panel);
  refreshBtnState();
  return wrap;
}

function buildFilterBar(){
  var container = document.getElementById('dimfilters-container');
  container.innerHTML = '';
  document.querySelectorAll('body > .dimfilter-panel').forEach(function(p){ p.remove(); });
  FILTER_DIMS.forEach(function(f){ container.appendChild(renderFilterButton(f)); });
  updateClearFiltersBtn();
}

document.addEventListener('click', function(e){
  if(e.target.closest('.dimfilter-panel') || e.target.closest('.dimfilter-btn')) return;
  document.querySelectorAll('.dimfilter-panel.open').forEach(function(p){ p.classList.remove('open'); });
});
window.addEventListener('scroll', function(e){
  if(e.target && e.target.closest && e.target.closest('.dimfilter-panel')) return;
  document.querySelectorAll('.dimfilter-panel.open').forEach(function(p){ p.classList.remove('open'); });
}, true);

function onFiltersChanged(){
  updateClearFiltersBtn();
  saveFilters();
  applyPivot();
  if(detLoaded) loadDetalle();
}

/* ══════════════════════════════════════════════════════════
   FORMATTERS AG GRID
   ══════════════════════════════════════════════════════════ */
function fmtMoney(p){ var v=p.value; if(v==null||v===0) return '-'; return 'S/ '+Math.round(v).toLocaleString('es-PE'); }
function fmtInt(p)  { var v=p.value; if(v==null||v===0) return '-'; return Math.round(v).toLocaleString('es-PE'); }
function fmtPct(p)  { var v=p.value; if(v==null) return '-'; return v.toFixed(1)+'%'; }
function fmtVar(p)  {
  var v=p.value; if(v==null) return '-';
  var cls = v>=0 ? 'sb-pill-pos' : 'sb-pill-neg';
  return '<span class="sb-pill '+cls+'">'+(v>=0?'+':'')+v.toFixed(1)+'%</span>';
}
function escHtml(s){ return (''+s).replace(/[&<>]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;'}[c];}); }

function labelRenderer(p){
  var d=p.data; if(!d) return '';
  var pad=(d._level||0)*18;
  var ch = d._isGroup ? '<span class="pv-chevron">'+(d._expanded?'▾':'▸')+'</span> ' : '';
  return '<span style="padding-left:'+pad+'px">'+ch+escHtml(d._label)+'</span>';
}

var rowStyleFn = function(p){
  if(!p.data) return null;
  if(p.data._isTotal) return {fontWeight:'800',background:'var(--sb-row-total-bg)',borderTop:'2px solid var(--sb-row-total-border)'};
  if(p.data._isGroup && (p.data._level||0)===0) return {fontWeight:'700',background:'var(--sb-row-group-0-bg)'};
  if(p.data._isGroup) return {fontWeight:'600',background:'var(--sb-row-group-bg)'};
  return null;
};

function attachFormatters(defs){
  (function walk(list){
    list.forEach(function(c){
      if(c.children){ walk(c.children); return; }
      if(c.__label){
        c.cellRenderer = labelRenderer;
        c.cellStyle = function(p){ return (p.data&&p.data._isGroup)?{cursor:'pointer'}:null; };
        return;
      }
      if(c.__fmt==='money')     c.valueFormatter = fmtMoney;
      else if(c.__fmt==='int')  c.valueFormatter = fmtInt;
      else if(c.__fmt==='pct')  c.valueFormatter = fmtPct;
      else if(c.__fmt==='var')  c.cellRenderer   = fmtVar;
    });
  })(defs);
}

/* ══════════════════════════════════════════════════════════
   PANEL DE CONFIGURACIÓN (SortableJS)
   ══════════════════════════════════════════════════════════ */
function chipDim(key){
  var el = document.createElement('div');
  el.className = 'pivot-chip chip-dim';
  el.dataset.key = key;
  el.innerHTML = '<span class="chip-grip">⠿</span>'+escHtml(key);
  return el;
}

function chipMeasure(key, aggFn){
  var def = PivotEngine.MEASURES[key] || {label:key};
  var el = document.createElement('div');
  el.className = 'pivot-chip chip-measure';
  el.dataset.key = key;
  var html = '<span class="chip-grip">⠿</span>'+escHtml(def.label);
  if(def.type==='raw' && def.allowAgg){
    var sel = ['sum','avg','min','max','count'].map(function(a){
      var lbl = PivotEngine.AGG_LABEL[a]||a;
      return '<option value="'+a+'"'+((aggFn||'sum')===a?' selected':'')+'>'+lbl+'</option>';
    }).join('');
    html += '<select class="agg-select">'+sel+'</select>';
  }
  el.innerHTML = html;
  var s = el.querySelector('.agg-select');
  if(s) s.addEventListener('change', onConfigChanged);
  return el;
}

function elById(id){ return document.getElementById(id); }

function buildPanel(cfg){
  ['pool-dims','zone-rows','zone-cols','pool-measures','zone-values'].forEach(function(id){ elById(id).innerHTML=''; });

  var usedDims = {};
  (cfg.rows||[]).forEach(function(d){ usedDims[d]=1; });
  (cfg.cols||[]).forEach(function(d){ usedDims[d]=1; });
  DIMENSIONS.forEach(function(d){ if(!usedDims[d]) elById('pool-dims').appendChild(chipDim(d)); });
  (cfg.rows||[]).forEach(function(d){ elById('zone-rows').appendChild(chipDim(d)); });
  (cfg.cols||[]).forEach(function(d){ elById('zone-cols').appendChild(chipDim(d)); });

  var usedMeas = {};
  (cfg.values||[]).forEach(function(v){ usedMeas[v.key]=1; });
  MEASURE_KEYS.forEach(function(k){ if(!usedMeas[k]) elById('pool-measures').appendChild(chipMeasure(k)); });
  (cfg.values||[]).forEach(function(v){ elById('zone-values').appendChild(chipMeasure(v.key, v.aggFn)); });

  initSortables();
}

function initSortables(){
  sortables.forEach(function(s){ s.destroy(); });
  sortables = [];
  var opt = {
    animation:130, ghostClass:'sortable-ghost', dragClass:'sortable-drag',
    onEnd: onConfigChanged,
    onStart: function(){ document.querySelectorAll('.zone-body').forEach(function(z){z.classList.add('pv-drop');}); },
  };
  function stop(){ document.querySelectorAll('.zone-body').forEach(function(z){z.classList.remove('pv-drop');}); }

  ['pool-dims','zone-rows','zone-cols'].forEach(function(id){
    sortables.push(Sortable.create(elById(id), Object.assign({}, opt, {
      group:{ name:'dims', pull:true, put:true },
      onEnd:function(e){ stop(); onConfigChanged(); }
    })));
  });
  ['pool-measures','zone-values'].forEach(function(id){
    sortables.push(Sortable.create(elById(id), Object.assign({}, opt, {
      group:{ name:'measures', pull:true, put:true },
      onEnd:function(e){ stop(); onConfigChanged(); }
    })));
  });
}

function readConfig(){
  function keysOf(id){
    return Array.prototype.map.call(elById(id).querySelectorAll('.pivot-chip'), function(c){ return c.dataset.key; });
  }
  var values = Array.prototype.map.call(elById('zone-values').querySelectorAll('.pivot-chip'), function(c){
    var s = c.querySelector('.agg-select');
    return { key:c.dataset.key, aggFn: s ? s.value : 'sum' };
  });
  return { rows:keysOf('zone-rows'), cols:keysOf('zone-cols'), values:values };
}

function saveConfig(){
  try { localStorage.setItem(LS_KEY, JSON.stringify({ cfg:readConfig(), collapsed:Array.from(collapsed) })); } catch(e){}
}
function loadSaved(){
  try {
    var raw = localStorage.getItem(LS_KEY);
    if(!raw) return null;
    var o = JSON.parse(raw);
    if(o && o.collapsed) collapsed = new Set(o.collapsed);
    return o && o.cfg ? o.cfg : null;
  } catch(e){ return null; }
}

/* ══════════════════════════════════════════════════════════
   RENDER
   ══════════════════════════════════════════════════════════ */
function getMergedFiltered(){
  var activeDims = FILTER_DIMS.filter(function(f){ return FILTERS[f.key].size > 0; });
  if(!activeDims.length) return MERGED_DATA;
  var nonSssDims = activeDims.filter(function(f){ return f.key !== 'SSS'; });
  return MERGED_DATA.filter(function(r){
    var dims = r._isHst ? nonSssDims : activeDims;
    return dims.every(function(f){ return FILTERS[f.key].has(String(r[f.key])); });
  });
}

function applyPivot(){
  if(!ALL_DATA.length) return;
  var filtered    = getFilteredData();
  var mergedFilt  = getMergedFiltered();
  document.getElementById('lbl-filas').textContent = filtered.length.toLocaleString('es-PE')+' filas'
    + (filtered.length!==ALL_DATA.length ? ' (de '+ALL_DATA.length.toLocaleString('es-PE')+')' : '');
  var cfg = readConfig();
  if(!cfg.values.length || !mergedFilt.length){
    gridPivot.setGridOption('columnDefs',[{field:'_label',headerName:'',pinned:'left',width:240}]);
    gridPivot.setGridOption('rowData',[]);
    gridPivot.setGridOption('pinnedBottomRowData',[]);
    document.getElementById('pivot-loader').classList.remove('show');
    return;
  }
  var res = PivotEngine.compute(mergedFilt, {
    rows:cfg.rows, cols:cfg.cols, values:cfg.values,
    collapsed:collapsed, orderMap:ORDER_MAP
  });
  attachFormatters(res.columnDefs);
  gridPivot.setGridOption('columnDefs', res.columnDefs);
  gridPivot.setGridOption('rowData', res.rowData);
  gridPivot.setGridOption('pinnedBottomRowData', res.pinnedBottom);
  document.getElementById('pivot-loader').classList.remove('show');
  updatePanelSummary(cfg);
}

function onConfigChanged(){ applyPivot(); saveConfig(); }

function updatePanelSummary(cfg){
  var el = document.getElementById('pv-panel-summary');
  if(!el) return;
  var vlabels = cfg.values.map(function(v){
    var d = PivotEngine.MEASURES[v.key]; return d ? d.label : v.key;
  });
  var parts = [];
  if(cfg.rows.length) parts.push('Filas: '+cfg.rows.join(', '));
  if(cfg.cols.length) parts.push('Columnas: '+cfg.cols.join(', '));
  if(vlabels.length)  parts.push('Valores: '+vlabels.join(', '));
  el.textContent = parts.join('  ·  ');
}

/* ══════════════════════════════════════════════════════════
   TABLA DETALLE
   ══════════════════════════════════════════════════════════ */
var varRend   = function(p){ if(p.value==null) return '-'; var cls=p.value>=0?'sb-pill-pos':'sb-pill-neg'; return '<span class="sb-pill '+cls+'">'+(p.value>=0?'+':'')+p.value.toFixed(1)+'%</span>'; };
var cumplRend = function(p){
  if(p.value==null) return '-';
  var c=p.value>=100?'var(--sb-green)':p.value>=80?'var(--sb-amber)':'var(--sb-red)';
  var bg=p.value>=100?'var(--sb-green-light)':p.value>=80?'var(--sb-amber-light)':'var(--sb-red-light)';
  return '<div class="cumpl-bar"><div class="cumpl-track"><div class="cumpl-fill" style="width:'+Math.min(p.value,100)+'%;background:'+c+'"></div></div><span class="cumpl-label" style="color:'+c+'">'+p.value.toFixed(1)+'%</span></div>';
};
var mFmt = function(p){ return (p.value!=null&&p.value!==0) ? 'S/ '+Math.round(p.value).toLocaleString('es-PE') : '-'; };
var pFmt = function(p){ return p.value!=null ? p.value.toFixed(1)+'%' : '-'; };
var iFmt = function(p){ return (p.value!=null&&p.value!==0) ? Math.round(p.value).toLocaleString('es-PE') : '-'; };

var DET_COLS = [
  { field:'label', headerName:'Sucursal', pinned:'left', width:230,
    cellStyle:function(p){return p.data&&(p.data._esSubtotal||p.data._esTotal)?{fontWeight:'700'}:null;} },
  { field:'vta26',       headerName:'Vta Neta 26',  width:115, valueFormatter:mFmt },
  { field:'vta25',       headerName:'Vta Neta 25',  width:115, valueFormatter:mFmt },
  { field:'var_vta',     headerName:'%Var',          width:85,  cellRenderer:varRend },
  { field:'part26',      headerName:'%Part 26',      width:80,  valueFormatter:pFmt },
  { field:'part25',      headerName:'%Part 25',      width:80,  valueFormatter:pFmt },
  { field:'meta_vta',    headerName:'Meta Vta',      width:110, valueFormatter:mFmt },
  { field:'cumpl_meta',  headerName:'%Cumpl',        width:110, cellRenderer:cumplRend },
  { field:'gm26',        headerName:'%GM 26',        width:80,  valueFormatter:pFmt },
  { field:'gm25',        headerName:'%GM 25',        width:80,  valueFormatter:pFmt },
  { field:'gm_meta',     headerName:'%GM Meta',      width:85,  valueFormatter:pFmt },
  { field:'contrib26',   headerName:'Contrib 26',    width:115, valueFormatter:mFmt },
  { field:'contrib25',   headerName:'Contrib 25',    width:115, valueFormatter:mFmt },
  { field:'var_contrib', headerName:'%Var Contrib',  width:90,  cellRenderer:varRend },
  { field:'meta_contri', headerName:'Meta Contri',   width:115, valueFormatter:mFmt },
  { field:'cumpl_contri',headerName:'%Cumpl Contri', width:115, cellRenderer:cumplRend },
  { field:'unds26',      headerName:'Unid 26',       width:85,  valueFormatter:iFmt },
  { field:'unds25',      headerName:'Unid 25',       width:85,  valueFormatter:iFmt },
  { field:'var_unds',    headerName:'%Var Unid',     width:85,  cellRenderer:varRend },
  { field:'pprom26',     headerName:'P.Prom 26',     width:90,  valueFormatter:mFmt },
  { field:'pprom25',     headerName:'P.Prom 25',     width:90,  valueFormatter:mFmt },
  { field:'var_pprom',   headerName:'%Var Pprom',    width:90,  cellRenderer:varRend },
  { field:'dscto26',     headerName:'%Dscto 26',     width:85,  valueFormatter:pFmt },
];

function filterParams(){
  var p = new URLSearchParams({ini:activeIni,fin:activeFin});
  FILTER_DIMS.forEach(function(f){
    if(f.key === 'Marca Temporada') return;
    FILTERS[f.key].forEach(function(v){ p.append(f.param+'[]', v); });
  });
  return p;
}

async function loadDetalle(){
  try {
    var r    = await fetch('/dashboard/reporte/detalle?'+filterParams());
    var data = await r.json();
    if(FILTERS['Marca Temporada'].size > 0){
      data = data.filter(function(row){ return FILTERS['Marca Temporada'].has(row['Marca']); });
    }
    if(!gridDetalle){
      gridDetalle = agGrid.createGrid(document.getElementById('grid-detalle'), {
        columnDefs:DET_COLS, rowData:data,
        defaultColDef:{resizable:true,sortable:true,cellStyle:{fontSize:'12px'}},
        getRowStyle:rowStyleFn, suppressCellFocus:true, animateRows:false,
      });
    } else {
      gridDetalle.setGridOption('rowData', data);
    }
  } catch(e){ console.error(e); }
}

/* ══════════════════════════════════════════════════════════
   CARGA DE DATOS
   ══════════════════════════════════════════════════════════ */
async function loadPivotData(){
  document.getElementById('pivot-loader').classList.add('show');
  document.getElementById('lbl-rango').textContent = activeIni + ' → ' + activeFin;
  detLoaded = false;
  try {
    var r    = await fetch('/dashboard/reporte/pivot?'+new URLSearchParams({ini:activeIni,fin:activeFin}));
    var payload = await r.json();
    ALL_DATA   = payload.act;
    HST_DATA   = payload.hst;
    METAS_DATA = payload.metas || [];

    ALL_DATA.forEach(function(r){ r['Marca Temporada'] = MARCA_TEMPORADA.includes(r.Marca) ? r.Marca : ''; });

    var semMes = {};
    ALL_DATA.forEach(function(row){ if(row['Semana'] && row['Mes']) semMes[row['Semana']] = row['Mes']; });

    var actMerged = ALL_DATA.map(function(row){
      return Object.assign({}, row, {vta25:0, gm25:0, unds25:0, meta_vta:0});
    });
    var hstMerged = HST_DATA.map(function(row){
      return {
        'Mes':      row['Mes'] || semMes[row['Semana']] || '',
        'Semana':   row['Semana'],
        'Día #':    row['Día #'] ?? '',
        'Día':      row['Día'],
        'Canal':    row['Canal'],
        'Subcanal': row['Subcanal'],
        'Tienda':   row['Tienda'],
        'Marca':    row['Marca'],
        'Marca Temporada': MARCA_TEMPORADA.includes(row['Marca']) ? row['Marca'] : '',
        'Categoría':row['Categoría'],
        'SSS':      '',
        'Localidad':row['Localidad'],
        'Lineas':   row['Lineas'] ?? '',
        'Temporada':row['Temporada'] ?? '',
        'vta26': 0, 'gm26': 0, 'unds26': 0, 'tickets26': 0,
        'vta25': row['vta25'], 'gm25': row['gm25'], 'unds25': row['unds25'],
        'meta_vta': 0,
        '_isHst': true
      };
    });
    var metasMerged = METAS_DATA.map(function(row){
      return {
        'Mes':      row['Mes'],
        'Semana':   row['Semana'],
        'Día #':    row['Día #'] ?? '',
        'Día':      row['Día'],
        'Canal':    row['Canal'],
        'Subcanal': row['Subcanal'],
        'Tienda':   row['Tienda'],
        'Marca':    row['Marca'],
        'Marca Temporada': MARCA_TEMPORADA.includes(row['Marca']) ? row['Marca'] : '',
        'Categoría':row['Categoría'],
        'SSS':      row['SSS'],
        'Localidad':row['Localidad'],
        'Lineas':   row['Lineas'] ?? '',
        'Temporada':row['Temporada'] ?? '',
        'vta26': 0, 'gm26': 0, 'unds26': 0, 'tickets26': 0,
        'vta25': 0, 'gm25': 0, 'unds25': 0,
        'meta_vta': row['meta_vta']
      };
    });
    MERGED_DATA = actMerged.concat(hstMerged).concat(metasMerged);

    buildFilterOptions();
    buildFilterBar();
    applyPivot();
  } catch(e){
    console.error(e);
    document.getElementById('pivot-loader').classList.remove('show');
  }
}

/* ══════════════════════════════════════════════════════════
   EXCEL EXPORT
   ══════════════════════════════════════════════════════════ */
document.getElementById('btn-excel').addEventListener('click', function(){
  var active = document.querySelector('#mainTabs .nav-link.active').dataset.bsTarget;
  var grid   = active==='#tab-pivot' ? gridPivot : gridDetalle;
  if(!grid){ alert('No hay tabla que exportar.'); return; }
  var rows=[]; grid.forEachNodeAfterFilterAndSort(function(n){if(n.data)rows.push(n.data);});
  grid.forEachNode(function(){});
  var pinned = (grid.getGridOption && grid.getGridOption('pinnedBottomRowData')) || [];
  rows = rows.concat(pinned);
  var cols=grid.getColumnDefs(); var hdrs=[],flds=[];
  (function walk(list){ list.forEach(function(c){ if(c.children) walk(c.children); else { hdrs.push(c.headerName||c.field); flds.push(c.field); } }); })(cols);
  var ws = XLSX.utils.aoa_to_sheet([hdrs].concat(rows.map(function(r){ return flds.map(function(f){ return r[f]??''; }); })));
  var wb = XLSX.utils.book_new(); XLSX.utils.book_append_sheet(wb,ws,'Reporte');
  XLSX.writeFile(wb,'reporte_'+activeIni+'_'+activeFin+'.xlsx');
});

/* ══════════════════════════════════════════════════════════
   EVENTOS
   ══════════════════════════════════════════════════════════ */
function recalcPeriod(){
  var active = Array.from(document.querySelectorAll('.periodo-pill.active'));
  if(!active.length) return;
  var inis = active.map(function(b){ return b.dataset.ini; }).sort();
  var fins = active.map(function(b){ return b.dataset.fin; }).sort();
  activeIni = inis[0];
  activeFin = fins[fins.length-1];
}

document.querySelectorAll('.periodo-pill').forEach(function(btn){
  btn.addEventListener('click', function(){
    var isAcum = this.textContent.trim() === 'Acumulado';
    if(isAcum){
      document.querySelectorAll('.periodo-pill').forEach(function(b){ b.classList.remove('active'); });
      this.classList.add('active');
    } else {
      document.querySelectorAll('.periodo-pill').forEach(function(b){
        if(b.textContent.trim()==='Acumulado') b.classList.remove('active');
      });
      this.classList.toggle('active');
      if(!document.querySelector('.periodo-pill.active')) this.classList.add('active');
    }
    recalcPeriod();
    loadPivotData();
    if(detLoaded) loadDetalle();
  });
});

document.getElementById('btn-tab-detalle').addEventListener('click', function(){
  if(!detLoaded){ loadDetalle(); detLoaded=true; }
});

document.getElementById('btn-reset').addEventListener('click', function(){
  collapsed = new Set();
  try { localStorage.removeItem(LS_KEY); } catch(e){}
  buildPanel(JSON.parse(JSON.stringify(DEFAULT_CFG)));
  applyPivot();
});

document.getElementById('btn-clear-filters').addEventListener('click', function(){
  FILTER_DIMS.forEach(function(f){ FILTERS[f.key].clear(); });
  buildFilterBar();
  onFiltersChanged();
});

/* ══════════════════════════════════════════════════════════
   PANEL COLAPSABLE
   ══════════════════════════════════════════════════════════ */
var LS_PANEL_KEY = 'pivot_reporte_panel_collapsed';
document.getElementById('pv-panel-toggle').addEventListener('click', function(){
  var panel = document.querySelector('.pv-panel');
  var collapsedNow = panel.classList.toggle('collapsed');
  try { localStorage.setItem(LS_PANEL_KEY, collapsedNow ? '1' : '0'); } catch(e){}
});

/* ══════════════════════════════════════════════════════════
   AUTO-ACTUALIZACIÓN
   ══════════════════════════════════════════════════════════ */
var LS_LIVE_KEY = 'pivot_reporte_live_ms';
var liveTimer = null;

function setLiveDot(on){ document.getElementById('live-dot').classList.toggle('on', on); }

function stopLiveRefresh(){
  if(liveTimer){ clearInterval(liveTimer); liveTimer = null; }
  setLiveDot(false);
}

function startLiveRefresh(ms){
  stopLiveRefresh();
  if(!ms) return;
  setLiveDot(true);
  liveTimer = setInterval(function(){
    loadPivotData();
    if(detLoaded) loadDetalle();
  }, ms);
}

document.getElementById('sel-live').addEventListener('change', function(){
  var ms = parseInt(this.value, 10) || 0;
  try { localStorage.setItem(LS_LIVE_KEY, String(ms)); } catch(e){}
  startLiveRefresh(ms);
});

/* ══════════════════════════════════════════════════════════
   INIT
   ══════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function(){
  var a = document.querySelector('.periodo-pill.active');
  if(a){ activeIni=a.dataset.ini; activeFin=a.dataset.fin; }

  gridPivot = agGrid.createGrid(document.getElementById('grid-pivot'), {
    columnDefs: [{field:'_label',headerName:'',pinned:'left',width:240}],
    rowData: [],
    defaultColDef:{ resizable:true, sortable:false, cellStyle:{fontSize:'12px'} },
    domLayout: 'autoHeight',
    getRowStyle: rowStyleFn,
    suppressCellFocus: true,
    animateRows: false,
    onCellClicked: function(e){
      if(e.colDef && e.colDef.field==='_label' && e.data && e.data._isGroup){
        var k=e.data._rowKey;
        if(collapsed.has(k)) collapsed.delete(k); else collapsed.add(k);
        applyPivot(); saveConfig();
      }
    },
  });

  var saved = loadSaved();
  buildPanel(saved || JSON.parse(JSON.stringify(DEFAULT_CFG)));

  loadFilters();

  try {
    if(localStorage.getItem(LS_PANEL_KEY) === '1'){
      document.querySelector('.pv-panel').classList.add('collapsed');
    }
  } catch(e){}

  try {
    var savedMs = parseInt(localStorage.getItem(LS_LIVE_KEY), 10) || 0;
    if(savedMs){
      document.getElementById('sel-live').value = String(savedMs);
      startLiveRefresh(savedMs);
    }
  } catch(e){}

  loadPivotData();
});
</script>
@endsection
