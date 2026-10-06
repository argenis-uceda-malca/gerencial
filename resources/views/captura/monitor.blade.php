@extends('layouts.base')

@section('title', 'Facturación Electrónica')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.1.1/styles/ag-grid.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.1.1/styles/ag-theme-quartz.css">
<style>
/* ── AG Grid custom ─────────────────────────────────────────── */
#ag-tabla-reg.ag-theme-quartz {
  --ag-font-size: .8rem;
}
[data-theme="dark"] #ag-tabla-reg.ag-theme-quartz {
  --ag-background-color: var(--dm-surface, #1F2438);
  --ag-header-background-color: var(--dm-surface-alt, #282E44);
  --ag-odd-row-background-color: #1C2135;
  --ag-row-hover-color: rgba(139,131,255,.08);
  --ag-selected-row-background-color: rgba(139,131,255,.15);
  --ag-border-color: var(--dm-border, #2E3450);
  --ag-foreground-color: var(--dm-ink, #E2E6F0);
  --ag-data-color: var(--dm-ink, #E2E6F0);
  --ag-secondary-foreground-color: var(--dm-muted, #8B90A8);
  --ag-header-foreground-color: var(--dm-ink, #E2E6F0);
}
[data-theme="dark"] #ag-tabla-reg.ag-theme-quartz .ag-cell {
  color: var(--dm-ink, #E2E6F0);
}

@keyframes spin { to { transform: rotate(360deg); } }
@keyframes banner-flow {
  0%   { background-position: 0% 50%; }
  50%  { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
@keyframes banner-shimmer {
  0%   { transform: translateX(-100%); }
  100% { transform: translateX(400%); }
}

/* ── Banner de operación en curso ─────────────────────────── */
#op-running-banner {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 9050;
  background: linear-gradient(270deg, #4a4dcc, #696cff, #8b8eff, #696cff, #4a4dcc);
  background-size: 300% 100%;
  animation: banner-flow 2.5s ease infinite;
  color: #fff;
  padding: 9px 22px;
  display: flex;
  align-items: center;
  gap: 10px;
  overflow: hidden;
  transform: translateY(-110%);
  transition: transform .28s cubic-bezier(.4,0,.2,1);
  box-shadow: 0 4px 24px rgba(105,108,255,.5);
  font-size: .82rem;
  font-weight: 600;
  letter-spacing: .02em;
}
#op-running-banner::after {
  content: '';
  position: absolute;
  top: 0; bottom: 0;
  left: 0; width: 25%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.18), transparent);
  animation: banner-shimmer 1.8s ease-in-out infinite;
}
#op-running-banner.visible { transform: translateY(0); }
#op-running-banner .op-spin {
  width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin .65s linear infinite;
  flex-shrink: 0;
}

/* ── Botones toolbar filtros ─────────────────────────────────── */
.btn-tb {
  background: transparent;
  border: 1px solid #d1d5db;
  color: #6b7280;
  font-size: .8rem;
  transition: background .12s, border-color .12s, color .12s;
}
.btn-tb:hover { background: #f3f4f6; border-color: #9ca3af; color: #374151; }
.btn-tb:focus { box-shadow: none; outline: none; }

/* ── Botones de acción en la tabla ──────────────────────────── */
.ac-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 28px; height: 28px;
  border-radius: 6px;
  border: none; background: transparent;
  color: #9ca3af;
  cursor: pointer; padding: 0;
  font-size: 15px; line-height: 1;
  transition: background .12s, color .12s;
  vertical-align: middle;
}
.ac-btn + .ac-btn { margin-left: 1px; }
.ac-btn:hover:not(:disabled) { background: rgba(0,0,0,.06); color: #374151; }
.ac-btn:disabled { opacity: .28; cursor: not-allowed; }
.ac-btn-force:hover:not(:disabled) { background: #fff1f2; color: #e11d48; }
#ag-tabla-reg .ag-row.r-ok   .ag-cell:first-child { border-left: 3px solid #4ADE80 !important; }
#ag-tabla-reg .ag-row.r-err  .ag-cell:first-child { border-left: 3px solid #ff3e1d !important; }
#ag-tabla-reg .ag-row.r-warn .ag-cell:first-child { border-left: 3px solid #ffab00 !important; }
#ag-tabla-reg .ag-row.r-pend .ag-cell:first-child { border-left: 3px solid #8592a3 !important; }
#ag-tabla-reg .ag-cell { display: flex; align-items: center; overflow: hidden; }
/* La columna acciones necesita overflow visible para los botones */
#ag-tabla-reg .ag-cell[col-id="acciones"] { overflow: visible; }
#ag-tabla-reg .ag-header-cell-text { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; }
#ag-tabla-reg .ag-paging-panel { font-size: .78rem; }
/* ── Animations ─────────────────────────────────────────── */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes pulse-dot {
  0%,100% { opacity:1; transform:scale(1); }
  50%      { opacity:.5; transform:scale(1.35); }
}
@keyframes shimmer {
  0%   { background-position:-200% 0; }
  100% { background-position:200% 0; }
}
.ani { animation: fadeInUp .35s ease both; }
.ani:nth-child(1){animation-delay:.04s}.ani:nth-child(2){animation-delay:.08s}
.ani:nth-child(3){animation-delay:.12s}.ani:nth-child(4){animation-delay:.16s}
.ani:nth-child(5){animation-delay:.20s}.ani:nth-child(6){animation-delay:.24s}

/* ── Health dot ─────────────────────────────────────────── */
.h-dot {
  width:9px;height:9px;border-radius:50%;display:inline-block;
  animation:pulse-dot 2s ease-in-out infinite;
}
.h-dot.ok    { background:#4ADE80; }
.h-dot.warn  { background:#ffab00; }
.h-dot.error { background:#ff3e1d; }

/* ── Pipeline bar ───────────────────────────────────────── */
.pipeline {
  display:flex; align-items:center; gap:0; overflow-x:auto;
  padding:.5rem 0; scrollbar-width:none;
}
.pipeline::-webkit-scrollbar { display:none; }
.pipe-step {
  display:flex;flex-direction:column;align-items:center;
  min-width:90px; text-align:center;
}
.pipe-icon {
  width:40px;height:40px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  font-size:1.15rem; transition:transform .2s;
}
.pipe-step:hover .pipe-icon { transform:scale(1.08); }
.pipe-label { font-size:.68rem;font-weight:600;text-transform:uppercase;
  letter-spacing:.05em; margin-top:.3rem; }
.pipe-sub   { font-size:.64rem;color:#a1acb8;margin-top:1px; }
.pipe-arrow {
  font-size:1rem;color:#d1d5db;padding:0 .25rem;
  flex-shrink:0;margin-bottom:1.2rem;
}

/* ── KPI cards ──────────────────────────────────────────── */
.kpi {
  border-radius:12px;border:none;transition:all .2s ease;
  position:relative;overflow:hidden;
}
.kpi:hover { transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.07); }
[data-theme="dark"] .kpi:hover { box-shadow:0 6px 20px rgba(0,0,0,.25); }
.kpi .kpi-ico {
  width:44px;height:44px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  font-size:1.25rem;flex-shrink:0;
}
.kpi .kpi-val { font-size:1.75rem;font-weight:700;line-height:1;font-variant-numeric:tabular-nums; }
.kpi .kpi-lbl { font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;color:#a1acb8;font-weight:500; }
.kpi .kpi-bar { position:absolute;bottom:0;left:0;right:0;height:3px;border-radius:0 0 12px 12px;opacity:.7; }
.kpi .kpi-hint { font-size:.65rem;margin-top:2px;font-weight:500; }

/* ── Tienda cards ───────────────────────────────────────── */
.tc {
  border-radius:10px;border:1px solid rgba(0,0,0,.06);
  padding:.6rem .85rem;font-size:.8rem;
  transition:all .2s;background:var(--bs-card-bg);cursor:default;
}
.tc:hover { border-color:rgba(105,108,255,.22);transform:translateY(-1px);box-shadow:0 3px 10px rgba(0,0,0,.05); }
[data-theme="dark"] .tc:hover { box-shadow:0 3px 10px rgba(0,0,0,.2); }
.tc.activa   { border-left:3px solid #4ADE80; }
.tc.inactiva { border-left:3px solid #8592a3;opacity:.5; }
.tc-code  { font-weight:700;font-size:.88rem;line-height:1.2; }
.tc-name  { font-size:.68rem;color:#a1acb8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:120px; }
.tc-cola  { font-size:.62rem;font-weight:600; }
.tc-time  { font-size:.62rem;color:#a1acb8;margin-top:2px; }

/* ── Tienda search ──────────────────────────────────────── */
.tc-search {
  border-radius:7px;border:1px solid rgba(0,0,0,.08);
  padding:.35rem .7rem;font-size:.8rem;max-width:230px;width:100%;
  transition:border-color .2s;
}
.tc-search:focus { border-color:#696cff;outline:none;box-shadow:0 0 0 3px rgba(105,108,255,.1); }

/* ── Estados badge helper ───────────────────────────────── */
.s-dot { width:6px;height:6px;border-radius:50%;display:inline-block;margin-right:4px;vertical-align:middle; }
.s-dot.ok   { background:#4ADE80; }
.s-dot.pend { background:#8592a3; }
.s-dot.err  { background:#ff3e1d; }
.s-dot.warn { background:#ffab00; }

/* ── Tabla ──────────────────────────────────────────────── */
#tabla-reg { width:100%; }
#tabla-reg td,#tabla-reg th { font-size:.8rem;vertical-align:middle; }
#tabla-reg thead th {
  position:sticky;top:0;z-index:2;
  background:var(--bs-table-bg,#f8f9fa);
  border-bottom:2px solid rgba(0,0,0,.06);
}
#tabla-reg tr.r-ok   td:first-child { border-left:3px solid #4ADE80; }
#tabla-reg tr.r-err  td:first-child { border-left:3px solid #ff3e1d; }
#tabla-reg tr.r-warn td:first-child { border-left:3px solid #ffab00; }
#tabla-reg tr.r-pend td:first-child { border-left:3px solid #8592a3; }

/* Skeleton */
.skel { background:linear-gradient(90deg,rgba(0,0,0,.04) 25%,rgba(0,0,0,.08) 50%,rgba(0,0,0,.04) 75%);
  background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:4px;height:13px;display:inline-block; }
[data-theme="dark"] .skel {
  background:linear-gradient(90deg,rgba(255,255,255,.04) 25%,rgba(255,255,255,.08) 50%,rgba(255,255,255,.04) 75%);
  background-size:200% 100%; }

/* ── Refresh badge ──────────────────────────────────────── */
.rf-badge {
  font-size:.7rem;display:inline-flex;align-items:center;gap:4px;
  padding:2px 9px;border-radius:20px;
  background:rgba(105,108,255,.1);color:#696cff;font-weight:500;transition:all .2s;
}
.rf-badge.paused  { background:rgba(133,146,163,.1);color:#8592a3; }
.rf-badge.running { background:rgba(255,171,0,.15);color:#e6a817; }

/* ── Log ────────────────────────────────────────────────── */
.log-box {
  background:#1a1a2e;color:#c9d1d9;border-radius:10px;
  font-family:'SF Mono','Cascadia Code','Consolas',monospace;
  font-size:.73rem;padding:.8rem 1rem;max-height:260px;
  overflow-y:auto;line-height:1.7;
}
[data-theme="light"] .log-box { background:#f6f8fa;color:#24292f;border:1px solid rgba(0,0,0,.06); }
.log-E { color:#ff7b72;font-weight:600; }
.log-W { color:#ffab00; }
.log-I { color:#79c0ff; }
.log-line { display:block;padding:1px 0; }
.log-line:hover { background:rgba(255,255,255,.03); }
[data-theme="light"] .log-line:hover { background:rgba(0,0,0,.03); }
.lf-btn {
  font-size:.68rem;padding:2px 7px;border-radius:4px;
  border:1px solid rgba(255,255,255,.1);background:transparent;color:#8592a3;cursor:pointer;transition:all .15s;
}
.lf-btn:hover,.lf-btn.active { background:rgba(255,255,255,.08);color:#fff; }
[data-theme="light"] .lf-btn { border-color:rgba(0,0,0,.1);color:#777; }
[data-theme="light"] .lf-btn:hover,[data-theme="light"] .lf-btn.active { background:rgba(0,0,0,.06);color:#333; }

/* ── CPE modal ──────────────────────────────────────────── */
.cpe-field { font-size:.78rem; }
.cpe-field .label { font-weight:600;color:#a1acb8;font-size:.68rem;text-transform:uppercase;letter-spacing:.04em; }
.cpe-field .val   { margin-top:1px; }
#tabla-cpe-det td,#tabla-cpe-det th { font-size:.76rem;vertical-align:middle; }

/* ── Misc ───────────────────────────────────────────────── */
code.mn { font-size:.76rem;background:rgba(105,108,255,.07);padding:1px 5px;border-radius:4px; }
.badge { font-weight:500; }
.filter-bar .form-select,.filter-bar .input-group { font-size:.8rem; }
.tooltip-inner { font-size:.76rem;padding:4px 9px;border-radius:6px; }
[data-theme="dark"] #tabla-reg { color:var(--dm-ink,#cdd5e0); }

/* ══════════════════════════════════════════════════════
   DARK MODE – Componentes específicos de /captura/monitor
   ══════════════════════════════════════════════════════ */

/* ── KPI cards ── */
[data-theme="dark"] .kpi {
  background: var(--dm-surface);
  border: 1px solid var(--dm-border);
}
[data-theme="dark"] .kpi:hover {
  box-shadow: 0 6px 20px rgba(0,0,0,.4);
}
[data-theme="dark"] .kpi .kpi-val { color: var(--dm-ink); }
[data-theme="dark"] .kpi .kpi-lbl { color: var(--dm-muted); }
[data-theme="dark"] .kpi .kpi-hint { color: var(--dm-muted); }
[data-theme="dark"] .kpi .kpi-bar { opacity: .5; }
[data-theme="dark"] .kpi .kpi-ico {
  background: rgba(105,108,255,.12) !important;
}

/* ── Tienda cards ── */
[data-theme="dark"] .tc {
  background: var(--dm-surface);
  border-color: var(--dm-border);
}
[data-theme="dark"] .tc:hover {
  border-color: rgba(105,108,255,.3);
  box-shadow: 0 3px 10px rgba(0,0,0,.3);
}
[data-theme="dark"] .tc-name,
[data-theme="dark"] .tc-time { color: var(--dm-muted); }
[data-theme="dark"] .tc-code { color: var(--dm-ink); }
[data-theme="dark"] .tc.activa { border-left-color: #4ADE80; }
[data-theme="dark"] .tc.inactiva { border-left-color: #8592a3; opacity: .6; }
[data-theme="dark"] .tc-cola { color: var(--dm-muted); }

/* ── Tabla ── */
[data-theme="dark"] #tabla-reg thead th {
  background: var(--dm-surface-alt) !important;
  border-bottom-color: var(--dm-border);
  color: var(--dm-ink);
}
[data-theme="dark"] #tabla-reg tbody td {
  background: var(--dm-surface);
  border-bottom-color: var(--dm-border);
  color: var(--dm-ink);
}
[data-theme="dark"] #tabla-reg tbody tr:hover td {
  background: var(--dm-surface-alt);
}
[data-theme="dark"] #tabla-reg tbody tr.r-ok td:first-child { border-left-color: #4ADE80; }
[data-theme="dark"] #tabla-reg tbody tr.r-err td:first-child { border-left-color: #ff3e1d; }
[data-theme="dark"] #tabla-reg tbody tr.r-warn td:first-child { border-left-color: #ffab00; }
[data-theme="dark"] #tabla-reg tbody tr.r-pend td:first-child { border-left-color: #8592a3; }

/* ── Pipeline bar ── */
[data-theme="dark"] .pipe-icon { background: rgba(105,108,255,.1) !important; }
[data-theme="dark"] .pipe-sub,
[data-theme="dark"] .pipe-label { color: var(--dm-muted); }
[data-theme="dark"] .pipe-arrow { color: var(--dm-border); }

/* ── Form controls ── */
[data-theme="dark"] .tc-search {
  background: var(--dm-input-bg);
  border-color: var(--dm-border);
  color: var(--dm-ink);
}
[data-theme="dark"] .tc-search:focus {
  border-color: #696cff;
  box-shadow: 0 0 0 3px rgba(105,108,255,.2);
}
[data-theme="dark"] .filter-bar .form-select,
[data-theme="dark"] .filter-bar .input-group {
  background: var(--dm-input-bg);
  border-color: var(--dm-border);
  color: var(--dm-ink);
}
[data-theme="dark"] .filter-bar .input-group-text {
  background: var(--dm-input-bg);
  border-color: var(--dm-border);
  color: var(--dm-muted);
}

/* ── CPE modal ── */
[data-theme="dark"] .cpe-field .label { color: var(--dm-muted); }
[data-theme="dark"] .cpe-field .val { color: var(--dm-ink); }
[data-theme="dark"] #tabla-cpe-det thead th {
  background: var(--dm-surface-alt);
  border-bottom-color: var(--dm-border);
  color: var(--dm-ink);
}
[data-theme="dark"] #tabla-cpe-det tbody td {
  background: var(--dm-surface);
  border-bottom-color: var(--dm-border);
  color: var(--dm-ink);
}
[data-theme="dark"] code.mn {
  background: rgba(105,108,255,.15);
  color: #b8bfff;
}

/* ── Modal ── */
[data-theme="dark"] .modal-content {
  background: var(--dm-surface);
  border-color: var(--dm-border);
}
[data-theme="dark"] .modal-header {
  border-bottom-color: var(--dm-border);
}
[data-theme="dark"] .modal-title { color: var(--dm-ink); }
[data-theme="dark"] .modal-body { color: var(--dm-ink); }

/* ── Badges en modo oscuro ── */
[data-theme="dark"] .badge.bg-label-success,
[data-theme="dark"] .badge.bg-label-secondary,
[data-theme="dark"] .badge.bg-label-primary,
[data-theme="dark"] .badge.bg-label-info,
[data-theme="dark"] .badge.bg-label-danger,
[data-theme="dark"] .badge.bg-label-warning {
  opacity: .85;
}

/* ── Refresh badge ── */
[data-theme="dark"] .rf-badge {
  background: rgba(105,108,255,.15);
  color: #b8bfff;
}

/* ── Pipeline status text ── */
[data-theme="dark"] #pipeline-status .text-success { color: #4ADE80 !important; }
[data-theme="dark"] #pipeline-status .text-danger { color: #ff3e1d !important; }
[data-theme="dark"] #pipeline-status .text-warning { color: #ffab00 !important; }

/* ── Health dot ── */
[data-theme="dark"] .h-dot.ok  { background: #4ADE80; }
[data-theme="dark"] .h-dot.warn { background: #ffab00; }
[data-theme="dark"] .h-dot.error{ background: #ff3e1d; }

/* ── Tabla CPE totals ── */
[data-theme="dark"] #cpe-totales td.text-muted { color: var(--dm-muted) !important; }
[data-theme="dark"] #cpe-totales tr.border-top { border-color: var(--dm-border) !important; }

/* ── Skeleton en dark mode ── */
[data-theme="dark"] .skel {
  background: linear-gradient(90deg,rgba(255,255,255,.04) 25%,rgba(255,255,255,.08) 50%,rgba(255,255,255,.04) 75%);
  background-size: 200% 100%;
}

/* ── Tooltip oscuro ── */
[data-theme="dark"] .tooltip-inner {
  background: var(--dm-surface-alt);
  color: var(--dm-ink);
  border: 1px solid var(--dm-border);
}
[data-theme="dark"] .tooltip .tooltip-arrow::before {
  background: var(--dm-surface-alt);
}

/* ── Toastr oscuro ── */
[data-theme="dark"] .toast {
  background: var(--dm-surface-alt);
  color: var(--dm-ink);
  border: 1px solid var(--dm-border);
}

/* ── SweetAlert oscuro ── */
[data-theme="dark"] .swal2-popup {
  background: var(--dm-surface) !important;
  color: var(--dm-ink) !important;
}
[data-theme="dark"] .swal2-title { color: var(--dm-ink) !important; }
[data-theme="dark"] .swal2-header { border-bottom-color: var(--dm-border) !important; }

/* DataTables eliminado — se usa AG Grid */

/* ── Select2 oscuro ── */
[data-theme="dark"] .select2-container--default .select2-selection--single {
  background: var(--dm-input-bg);
  border-color: var(--dm-border);
  color: var(--dm-ink);
}
[data-theme="dark"] .select2-container--default .select2-selection--multiple {
  background: var(--dm-input-bg);
  border-color: var(--dm-border);
}
[data-theme="dark"] .select2-container--default .select2-selection--multiple .select2-selection__choice {
  background: rgba(105,108,255,.2);
  border-color: rgba(105,108,255,.3);
  color: #b8bfff;
}
[data-theme="dark"] .select2-container--default .select2-selection__rendered {
  color: var(--dm-ink);
}
[data-theme="dark"] .select2-container--default .select2-selection__placeholder {
  color: var(--dm-muted);
}
[data-theme="dark"] .select2-container--default .select2-dropdown {
  background: var(--dm-surface);
  border-color: var(--dm-border);
}
[data-theme="dark"] .select2-container--default .select2-results__option {
  color: var(--dm-ink);
}
[data-theme="dark"] .select2-container--default .select2-results__option--highlighted[aria-selected] {
  background: rgba(105,108,255,.2);
}
[data-theme="dark"] .select2-container--open .select2-selection--single {
  border-color: #696cff;
}

/* ── Progress bar oscuro ── */
[data-theme="dark"] .progress-bar {
  background-image: none !important;
}

/* ── Border helper oscuro ── */
[data-theme="dark"] .border { border-color: var(--dm-border) !important; }
[data-theme="dark"] .border-top { border-top-color: var(--dm-border) !important; }
[data-theme="dark"] .border-bottom { border-bottom-color: var(--dm-border) !important; }

/* ── Text helpers oscuros ── */
[data-theme="dark"] .text-muted { color: var(--dm-muted) !important; }
[data-theme="dark"] .small { color: var(--dm-muted) !important; }
[data-theme="dark"] .fw-semibold { color: var(--dm-ink); }

/* ── Divider oscuro ── */
[data-theme="dark"] .dropdown-divider {
  border-color: var(--dm-border);
}

/* ── Modal Bizlinks detalle ── */
[data-theme="dark"] #biz-pre {
  background: var(--dm-surface-alt);
  color: var(--dm-ink);
}

/* ── Modal forzar captura ── */
[data-theme="dark"] #forzar-res {
  background: var(--dm-surface-alt);
  color: var(--dm-ink);
}

/* ── Card header oscuro ── */
[data-theme="dark"] .card-header {
  border-bottom-color: var(--dm-border);
  background: var(--dm-surface-alt);
  color: var(--dm-ink);
}

/* ══════════════════════════════════════════════════════
   FIN DARK MODE – Componentes de /captura/monitor
   ══════════════════════════════════════════════════════ */

/* ══════════════════════════════════════════════════════
   RESPONSIVE – Dispositivos móviles
   ══════════════════════════════════════════════════════ */

/* ── Tablet y móviles pequeños (≤ 768px) ── */
@media (max-width: 768px) {
  .container-xxl { padding-left: .75rem; padding-right: .75rem; }

  /* Header: apilar */
  .d-flex.align-items-start.justify-content-between {
    flex-direction: column;
    align-items: stretch;
  }
  .d-flex.align-items-start.justify-content-between > div:first-child {
    width: 100%;
  }
  .d-flex.align-items-start.justify-content-between > div:last-child {
    width: 100%;
    justify-content: flex-end;
  }

  /* Pipeline: compactar */
  .pipeline { gap: 0; }
  .pipe-step { min-width: 55px; }
  .pipe-icon { width: 32px; height: 32px; font-size: .95rem; }
  .pipe-label { font-size: .58rem; }
  .pipe-sub   { font-size: .52rem; }
  .pipe-arrow { font-size: .75rem; margin-bottom: 0; }

  /* KPI cards: texto más pequeño */
  .kpi .kpi-val { font-size: 1.35rem; }
  .kpi .kpi-lbl { font-size: .62rem; }
  .kpi .kpi-ico { width: 38px; height: 38px; font-size: 1.05rem; }

  /* Tienda cards: 1 por fila en móvil muy pequeño */
  .tc { padding: .5rem .65rem; font-size: .74rem; }
  .tc-name { max-width: 90px; }
  .tc-code { font-size: .82rem; }

  /* Tabla: scroll horizontal forzado */
  .table-responsive {
    -webkit-overflow-scrolling: touch;
    overflow-x: auto;
    display: block;
  }
  #tabla-reg { min-width: 680px; }
  #tabla-reg td, #tabla-reg th { font-size: .72rem; padding: .4rem .35rem; }

  /* Filter bar: apilar selects en móvil */
  .filter-bar { flex-wrap: wrap; gap: .4rem !important; }
  .filter-bar .form-select,
  .filter-bar .input-group { width: 100% !important; max-width: 100% !important; }
  .filter-bar .input-group { width: 100% !important; }
  .filter-bar .ms-auto { margin-left: 0 !important; width: 100% !important; }
  .filter-bar .btn { width: 100% !important; }
  .filter-bar .form-select[style],
  .filter-bar .input-group[style],
  .filter-bar .ms-auto[style] { width: 100% !important; max-width: 100% !important; }

  /* Log */
  .log-box { font-size: .68rem; padding: .6rem .75rem; max-height: 180px; }

  /* Badges y estado */
  .badge { font-size: .65rem; }
  .rf-badge { font-size: .62rem; }
}

/* ── Móviles muy pequeños (≤ 576px) ── */
@media (max-width: 575.98px) {
  /* KPI: 1 por fila */
  .col-6.col-xl-3 { flex: 0 0 100% !important; max-width: 100% !important; }

  /* Tienda: 1 por fila */
  .col-6.col-sm-4.col-md-3.col-xl-2 { flex: 0 0 100% !important; max-width: 100% !important; }

  /* Pipeline: scroll horizontal */
  .pipeline { flex-wrap: nowrap; }
  .pipe-arrow { display: none; }
  .pipe-step { min-width: 70px; }

  /* Header */
  h4 { font-size: 1.1rem; }

  /* Table: más compacta */
  #tabla-reg td, #tabla-reg th { font-size: .68rem; padding: .3rem .25rem; }

  /* Botones: más grandes para tacto */
  .btn { min-height: 36px; padding: .5rem .75rem; }
  .btn-sm { min-height: 34px; font-size: .8rem; }

  /* Modal: ancho completo */
  .modal-dialog { margin: .5rem; max-width: calc(100% - 1rem); }

  /* Cards */
  .card { margin-bottom: .75rem; }
}

/* ── Touch targets más grandes ── */
@media (pointer: coarse) {
  .tc { padding: .75rem; min-height: 60px; }
  .kpi { min-height: 80px; }
  .btn-forzar { padding: 8px; }
  .table-responsive td, .table-responsive th { padding: .5rem !important; }
}

/* ── Evitar overflow horizontal en el layout ── */
.layout-page, .content-wrapper { overflow-x: hidden; }

/* ══════════════════════════════════════════════════════
   FIN RESPONSIVE – Dispositivos móviles
   ══════════════════════════════════════════════════════ */
</style>
    @endpush

@section('contenido')
{{-- Banner fijo: aparece durante cualquier operación larga, visible desde cualquier scroll --}}
<div id="op-running-banner" role="status" aria-live="polite">
  <div class="op-spin"></div>
  <span id="op-banner-text">Procesando…</span>
</div>

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- ── Header ────────────────────────────────────────────── --}}
  <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2 ani">
    <div>
      <div class="d-flex align-items-center gap-2">
        <h4 class="mb-0 fw-bold">
          <i class="bx bxs-receipt me-2" style="color:#696cff"></i>Facturación Electrónica
        </h4>
        <span class="h-dot ok" id="health-dot"
              data-bs-toggle="tooltip" data-bs-placement="right" title="Sistema operativo"></span>
      </div>
      <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
        <small class="text-muted">Motor de captura Soluflex → Bizlinks</small>
        <span class="text-muted" style="font-size:.68rem">•</span>
        <small class="text-muted" style="font-size:.7rem">Captura: <span id="last-capture-time">—</span></small>
        <span class="text-muted" style="font-size:.68rem">•</span>
        <small class="text-muted" style="font-size:.7rem">Sync: <span id="sync-time">—</span></small>
        <span class="rf-badge" id="lbl-rf"><i class="bx bx-refresh" id="rf-icon"></i><span id="txt-cd">60s</span></span>
        <span id="ciclo-resultado" style="font-size:.68rem;display:none"></span>
      </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <button class="btn btn-sm btn-outline-secondary" id="btn-toggle-rf"
              data-bs-toggle="tooltip" title="Pausar auto-refresh"><i class="bx bx-pause"></i></button>
      <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()"
              data-bs-toggle="tooltip" title="Recargar página">
        <i class="bx bx-refresh me-1"></i>Recargar
      </button>
      <button class="btn btn-sm btn-primary fw-semibold" id="btn-ejecutar">
        <i class="bx bx-play-circle me-1"></i>Ejecutar ahora
      </button>
      <span id="motor-status" class="small text-muted ms-1" style="display:none">
        <span class="spinner-border spinner-border-sm text-primary me-1" style="width:12px;height:12px;border-width:2px"></span>
        Motor corriendo…
      </span>
    </div>
  </div>

  {{-- ── Pipeline visual ────────────────────────────────────── --}}
  <div class="card mb-4 ani">
    <div class="card-body py-3 px-4">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="pipeline flex-grow-1">

          <div class="pipe-step">
            <div class="pipe-icon" style="background:rgba(105,108,255,.1)">
              <i class="bx bx-data" style="color:#696cff"></i>
            </div>
            <div class="pipe-label" style="color:#696cff">Soluflex</div>
            <div class="pipe-sub">Origen ventas</div>
          </div>

          <div class="pipe-arrow"><i class="bx bx-right-arrow-alt"></i></div>

          <div class="pipe-step">
            <div class="pipe-icon" style="background:rgba(113,221,55,.1)">
              <i class="bx bx-cog" style="color:#4ADE80"></i>
            </div>
            <div class="pipe-label" style="color:#4ADE80">Motor</div>
            <div class="pipe-sub">cada 1 min</div>
          </div>

          <div class="pipe-arrow"><i class="bx bx-right-arrow-alt"></i></div>

          <div class="pipe-step">
            <div class="pipe-icon" style="background:rgba(3,195,236,.1)">
              <i class="bx bx-cloud-upload" style="color:#03c9ec"></i>
            </div>
            <div class="pipe-label" style="color:#03c9ec">Bizlinks</div>
            <div class="pipe-sub">BD intermedia</div>
          </div>

          <div class="pipe-arrow"><i class="bx bx-right-arrow-alt"></i></div>

          <div class="pipe-step">
            <div class="pipe-icon" style="background:rgba(255,171,0,.1)">
              <i class="bx bx-check-shield" style="color:#ffab00"></i>
            </div>
            <div class="pipe-label" style="color:#ffab00">SUNAT</div>
            <div class="pipe-sub">Envío FE</div>
          </div>

        </div>

        {{-- Estado resumen --}}
        <div class="text-end flex-shrink-0" style="min-width:160px">
          <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:#a1acb8;font-weight:600">
            Estado del sistema
          </div>
          <div id="pipeline-status" class="fw-semibold mt-1" style="font-size:.88rem">
            @if($stats['errores'] > 0)
              <span class="text-danger"><i class="bx bxs-error-circle me-1"></i>{{ $stats['errores'] }} errores activos</span>
            @elseif($stats['cuarentena'] > 0)
              <span class="text-warning"><i class="bx bxs-error me-1"></i>{{ $stats['cuarentena'] }} en cuarentena</span>
            @else
              <span class="text-success"><i class="bx bxs-check-circle me-1"></i>Operativo</span>
            @endif
          </div>
          <div class="text-muted mt-1" style="font-size:.7rem">
            {{ $tiendas->where('estado','ACTIVA')->count() }} tienda{{ $tiendas->where('estado','ACTIVA')->count() !== 1 ? 's' : '' }} activa{{ $tiendas->where('estado','ACTIVA')->count() !== 1 ? 's' : '' }}
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── KPI cards ───────────────────────────────────────────── --}}
  <div class="row g-3 mb-4">

    <div class="col-6 col-xl-3 ani">
      <div class="card kpi h-100"
           data-bs-toggle="tooltip" title="Comprobantes capturados exitosamente hoy">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="kpi-ico" style="background:rgba(113,221,55,.12)">
            <i class="bx bx-check-shield" style="color:#4ADE80"></i>
          </div>
          <div class="flex-grow-1">
            <div class="kpi-lbl">Capturados hoy</div>
            <div class="kpi-val" style="color:#4ADE80">{{ $stats['capturados_hoy'] }}</div>
            <div class="kpi-hint text-muted">Enviados a Bizlinks</div>
          </div>
        </div>
        <div class="kpi-bar" style="background:linear-gradient(90deg,#4ADE80,#22c55e)"></div>
      </div>
    </div>

    <div class="col-6 col-xl-3 ani">
      <div class="card kpi h-100"
           data-bs-toggle="tooltip" title="En cola, esperan la próxima ejecución del motor (cada minuto)">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="kpi-ico" style="background:rgba(133,146,163,.12)">
            <i class="bx bx-time-five" style="color:#8592a3"></i>
          </div>
          <div class="flex-grow-1">
            <div class="kpi-lbl">En cola</div>
            <div class="kpi-val" style="color:#8592a3">{{ $stats['pendientes'] }}</div>
            <div class="kpi-hint text-muted">Próx. ciclo automático</div>
          </div>
        </div>
        <div class="kpi-bar" style="background:linear-gradient(90deg,#8592a3,#a5b0be)"></div>
      </div>
    </div>

    <div class="col-6 col-xl-3 ani">
      <div class="card kpi h-100"
           data-bs-toggle="tooltip" title="Fallaron más de 3 veces — requieren revisión o reset manual">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="kpi-ico" style="background:rgba(255,171,0,.12)">
            <i class="bx bx-error" style="color:#ffab00"></i>
          </div>
          <div class="flex-grow-1">
            <div class="kpi-lbl">Cuarentena</div>
            <div class="kpi-val" style="color:#ffab00">{{ $stats['cuarentena'] }}</div>
            <div class="kpi-hint {{ $stats['cuarentena'] > 0 ? 'text-warning' : 'text-muted' }}">
              {{ $stats['cuarentena'] > 0 ? 'Revisión manual' : 'Sin novedades' }}
            </div>
          </div>
        </div>
        <div class="kpi-bar" style="background:linear-gradient(90deg,#ffab00,#ffc933)"></div>
      </div>
    </div>

    <div class="col-6 col-xl-3 ani">
      <div class="card kpi h-100"
           data-bs-toggle="tooltip" title="Intentos fallidos recientes — ver detalles en la tabla">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="kpi-ico" style="background:rgba(255,62,29,.1)">
            <i class="bx bx-x-circle" style="color:#ff3e1d"></i>
          </div>
          <div class="flex-grow-1">
            <div class="kpi-lbl">Con error</div>
            <div class="kpi-val" style="color:#ff3e1d">{{ $stats['errores'] }}</div>
            <div class="kpi-hint {{ $stats['errores'] > 0 ? 'text-danger' : 'text-muted' }}">
              {{ $stats['errores'] > 0 ? 'Ver tabla abajo' : 'Sin errores' }}
            </div>
          </div>
        </div>
        <div class="kpi-bar" style="background:linear-gradient(90deg,#ff3e1d,#ff6b4a)"></div>
      </div>
    </div>

  </div>

  {{-- ── Tiendas ─────────────────────────────────────────────── --}}
  @php
    $tiActivas  = $tiendas->where('estado','ACTIVA');
    $tiInactivas = $tiendas->where('estado','!=','ACTIVA');
    $pctAct = $tiendas->count() > 0
      ? round(($tiActivas->count() / $tiendas->count()) * 100) : 0;
  @endphp
  <div class="card mb-4 ani">
    <div class="card-header d-flex align-items-center justify-content-between py-2 flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <i class="bx bx-store me-1 text-primary"></i>
        <span class="fw-semibold small">Tiendas</span>
        <span class="badge bg-label-success" style="font-size:.65rem">{{ $tiActivas->count() }} activas</span>
        @if($tiInactivas->count() > 0)
          <span class="badge bg-label-secondary" style="font-size:.65rem">{{ $tiInactivas->count() }} inactivas</span>
        @endif
        <div class="progress ms-1" style="width:60px;height:5px;border-radius:3px"
             data-bs-toggle="tooltip" title="{{ $pctAct }}% activas">
          <div class="progress-bar" style="width:{{ $pctAct }}%;background:#4ADE80"></div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <input type="text" class="tc-search" id="tc-search" placeholder="Buscar tienda...">
      </div>
    </div>
    <div class="card-body py-3">
      @if($tiendas->isEmpty())
        <div class="text-center py-4 text-muted">
          <i class="bx bx-store-alt" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3"></i>
          <small>Sin tiendas configuradas</small>
        </div>
      @else
      {{-- Controles del carrusel --}}
      <div class="d-flex align-items-center justify-content-between mb-2" id="tc-nav">
        <button id="tc-prev" class="btn btn-sm btn-icon btn-outline-secondary" style="padding:2px 8px" disabled>
          <i class="bx bx-chevron-left"></i>
        </button>
        <span id="tc-page-info" class="small text-muted" style="font-size:.72rem"></span>
        <button id="tc-next" class="btn btn-sm btn-icon btn-outline-secondary" style="padding:2px 8px">
          <i class="bx bx-chevron-right"></i>
        </button>
      </div>
      <div class="row g-2" id="tc-grid">
        @foreach($tiendas as $tienda)
        <div class="col-6 col-sm-4 col-md-3 col-xl-2 tc-item" style="display:none">
          <div class="tc {{ $tienda->estado === 'ACTIVA' ? 'activa' : 'inactiva' }}"
               data-tienda="{{ $tienda->codigo_tienda }}"
               data-nombre="{{ strtolower($tienda->nombre_tienda ?? '') }}"
               data-codigo="{{ strtolower($tienda->codigo_tienda ?? '') }}">

            {{-- Fila superior: código + badges --}}
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="tc-code">{{ $tienda->codigo_tienda }}</div>
                <div class="tc-name" title="{{ $tienda->nombre_tienda }}">{{ $tienda->nombre_tienda }}</div>
              </div>
              <div class="d-flex flex-column align-items-end gap-1">
                @if($tienda->estado === 'ACTIVA')
                  <span class="badge" style="font-size:.55rem;background:#4ADE80;color:#166534">ACTIVA</span>
                  <span class="tc-cola" data-tienda-cola="{{ $tienda->codigo_tienda }}">
                    <span class="badge" style="background:rgba(105,108,255,.1);color:#696cff;font-size:.55rem">
                      <i class="bx bx-loader-alt bx-spin" style="font-size:.6rem"></i>
                    </span>
                  </span>
                @else
                  <span class="badge bg-secondary" style="font-size:.55rem">{{ $tienda->estado }}</span>
                @endif
              </div>
            </div>

            {{-- Fila inferior: cursor + tiempo + acción --}}
            <div class="d-flex justify-content-between align-items-center mt-2">
              <div class="tc-time">
                @if($tienda->fecha_ultima_captura)
                  <span data-bs-toggle="tooltip" title="{{ $tienda->fecha_ultima_captura->format('d/m/Y H:i') }}">
                    {{ $tienda->fecha_ultima_captura->diffForHumans() }}
                  </span>
                @else
                  <span>Nunca</span>
                @endif
              </div>
              @if($tienda->estado === 'ACTIVA')
              <button class="btn-forzar"
                      style="background:none;border:none;padding:0;cursor:pointer;line-height:1"
                      data-tienda="{{ $tienda->codigo_tienda }}"
                      data-nombre="{{ $tienda->nombre_tienda }}"
                      title="Forzar captura de un ID específico">
                <i class="bx bx-upload" style="font-size:.85rem;color:#696cff;opacity:.65"></i>
              </button>
              @endif
            </div>

          </div>
        </div>
        @endforeach
      </div>
      <div id="tc-empty" class="text-center text-muted py-3" style="display:none">
        <i class="bx bx-search" style="font-size:1.1rem"></i>
        <br><small>Sin resultados</small>
      </div>
      @endif
    </div>
  </div>

  {{-- ── Tabla comprobantes ──────────────────────────────────── --}}
  <div class="card mb-4 ani">
    <div class="card-header py-2">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="fw-semibold small">
          <i class="bx bx-list-check me-1 text-primary"></i>Comprobantes
        </span>
        {{-- Leyenda de estados --}}
        <div class="d-flex gap-3 flex-wrap" style="font-size:.68rem">
          <span><span class="s-dot ok"></span>Capturado</span>
          <span><span class="s-dot pend"></span>Pendiente</span>
          <span><span class="s-dot warn"></span>Cuarentena</span>
          <span><span class="s-dot err"></span>Error</span>
        </div>
      </div>
    </div>

    <div class="card-body px-3 pt-3 pb-2">
      {{-- Toolbar de filtros --}}
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3 filter-bar">
        <select id="f-estado" class="form-select form-select-sm" style="width:150px">
          <option value="">Todos los estados</option>
          <option value="CAPTURADO">✓ Capturado</option>
          <option value="PENDIENTE">◷ Pendiente</option>
          <option value="ERROR_CAPTURA">✕ Error captura</option>
          <option value="CUARENTENA">⚠ Cuarentena</option>
        </select>

        <select id="f-tienda" class="form-select form-select-sm" style="width:110px">
          <option value="">Todas</option>
          @foreach($tiendas as $t)
          <option value="{{ $t->codigo_tienda }}">{{ $t->codigo_tienda }}</option>
          @endforeach
        </select>

        <div class="input-group input-group-sm" style="width:210px">
          <span class="input-group-text"><i class="bx bx-search"></i></span>
          <input type="text" id="f-buscar" class="form-control" placeholder="RUC / serie / cliente">
        </div>

        <input type="date" id="f-fecha-ini" class="form-control form-control-sm"
               style="width:140px" title="Fecha desde">
        <input type="date" id="f-fecha-fin" class="form-control form-control-sm"
               style="width:140px" title="Fecha hasta">

        <button class="btn btn-sm btn-tb ms-auto" id="btn-sync-bizlinks"
                data-bs-toggle="tooltip" title="Actualizar estado de respuesta Bizlinks/SUNAT">
          <i class="bx bx-cloud-download me-1"></i>Sync Bizlinks
        </button>
        <button class="btn btn-sm btn-tb" id="btn-reset-masivo"
                data-bs-toggle="tooltip" title="Resetear todos los errores/cuarentena a PENDIENTE">
          <i class="bx bx-refresh me-1"></i>Reintentar errores
        </button>
        <button class="btn btn-sm btn-tb" id="btn-reset-rechazados"
                data-bs-toggle="tooltip" title="Reintentar documentos rechazados por SUNAT (error 2119, etc.)">
          <i class="bx bx-revision me-1"></i>Reintentar rechazados SUNAT
        </button>
        <button class="btn btn-sm btn-tb" id="btn-csv"
                data-bs-toggle="tooltip" title="Exportar a CSV">
          <i class="bx bx-download me-1"></i>CSV
        </button>
      </div>

      <div id="ag-tabla-reg" style="height:560px;width:100%"></div>
    </div>
  </div>

  {{-- ── Log del sistema ─────────────────────────────────────── --}}
  <div class="card ani">
    <div class="card-header py-2" style="cursor:pointer"
         data-bs-toggle="collapse" data-bs-target="#log-body">
      <div class="d-flex align-items-center justify-content-between">
        <span class="fw-semibold small">
          <i class="bx bx-terminal me-1 text-primary"></i>Log del sistema
          <span class="text-muted fw-normal">(últimas 50 entradas)</span>
        </span>
        <div class="d-flex align-items-center gap-2" onclick="event.stopPropagation()">
          <div class="d-flex gap-1">
            <button class="lf-btn active" data-level="ALL">ALL</button>
            <button class="lf-btn" data-level="INFO" style="color:#79c0ff">INFO</button>
            <button class="lf-btn" data-level="WARN" style="color:#ffab00">WARN</button>
            <button class="lf-btn" data-level="ERROR" style="color:#ff7b72">ERROR</button>
          </div>
          <button class="btn btn-sm btn-outline-secondary" id="btn-copy-log"
                  data-bs-toggle="tooltip" title="Copiar log"
                  style="padding:2px 7px;font-size:.7rem">
            <i class="bx bx-copy"></i>
          </button>
          <i class="bx bx-chevron-down text-muted"></i>
        </div>
      </div>
    </div>
    <div class="collapse" id="log-body">
      <div class="card-body p-2">
        <div class="log-box" id="log-content">
          @forelse($logsRecientes as $log)
          <span class="log-line" data-level="{{ substr($log->nivel,0,1) }}">
            <span class="text-muted">{{ $log->fecha->format('m-d H:i:s') }}</span>
            <span class="log-{{ substr($log->nivel,0,1) }}"> {{ str_pad($log->nivel,5) }}</span>
            {{ $log->mensaje }}
          </span>
          @empty
          <span class="text-muted" style="font-size:.75rem">Sin entradas de log recientes.</span>
          @endforelse
        </div>
      </div>
    </div>
  </div>

</div>

{{-- ── Modal: Detalle respuesta Bizlinks ───────────────────── --}}
<div class="modal fade" id="modal-biz-detalle" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius:12px;border:none">
      <div class="modal-header py-2" style="border-bottom:1px solid rgba(0,0,0,.06)">
        <div>
          <h6 class="modal-title fw-semibold">
            <i class="bx bx-cloud me-2 text-primary"></i>Respuesta Bizlinks
            — <code class="mn" id="biz-serie">—</code>
          </h6>
          <small class="text-muted" id="biz-estado-label"></small>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <pre id="biz-pre"
             style="margin:0;padding:1.1rem 1.25rem;font-size:.78rem;
                    font-family:'SF Mono','Cascadia Code','Consolas',monospace;
                    background:transparent;border:none;white-space:pre-wrap;
                    word-break:break-word;max-height:480px;overflow-y:auto;
                    line-height:1.7"></pre>
      </div>
    </div>
  </div>
</div>

{{-- ── Modal: Ver CPE (documento Bizlinks) ────────────────── --}}
<div class="modal fade" id="modal-cpe" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:12px;border:none">
      <div class="modal-header py-2" style="border-bottom:1px solid rgba(0,0,0,.06)">
        <div>
          <h6 class="modal-title fw-semibold mb-0">
            <i class="bx bx-file me-2 text-primary"></i>
            Comprobante electrónico — <span id="cpe-serie" class="text-primary"></span>
          </h6>
          <small class="text-muted" id="cpe-subtitulo"></small>
        </div>
        <div class="d-flex gap-2 ms-auto align-items-center flex-wrap">
          <a id="btn-cpe-pdf" href="#" target="_blank"
             class="btn btn-sm btn-success d-none"
             data-bs-toggle="tooltip" title="Descargar PDF oficial de Bizlinks">
            <i class="bx bx-file-pdf me-1"></i>PDF Bizlinks
          </a>
          <a id="btn-cpe-xml" href="#" target="_blank"
             class="btn btn-sm btn-outline-secondary d-none"
             data-bs-toggle="tooltip" title="Descargar XML UBL">
            <i class="bx bx-code-alt me-1"></i>XML
          </a>
          <a id="btn-cpe-cdr" href="#" target="_blank"
             class="btn btn-sm btn-outline-secondary d-none"
             data-bs-toggle="tooltip" title="Descargar CDR SUNAT">
            <i class="bx bx-download me-1"></i>CDR
          </a>
          <button class="btn btn-sm btn-outline-primary" id="btn-cpe-imprimir"
                  data-bs-toggle="tooltip" title="Imprimir vista previa">
            <i class="bx bx-printer me-1"></i>Imprimir
          </button>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
      </div>
      <div class="modal-body">
        <div id="cpe-loading" class="text-center py-4">
          <div class="spinner-border spinner-border-sm text-primary mb-2"></div>
          <div class="text-muted small">Consultando Bizlinks…</div>
        </div>
        <div id="cpe-content" style="display:none">
          {{-- Cabecera del comprobante --}}
          <div id="cpe-printable">
            <div class="row g-3 mb-3">
              <div class="col-md-8">
                <div class="card border-0" style="background:rgba(105,108,255,.04);border-radius:10px">
                  <div class="card-body py-3">
                    <div class="row g-2">
                      <div class="col-sm-4 cpe-field">
                        <div class="label">Emisor (RUC)</div>
                        <div class="val fw-semibold" id="cpe-ruc-emisor">—</div>
                      </div>
                      <div class="col-sm-8 cpe-field">
                        <div class="label">Razón social emisor</div>
                        <div class="val" id="cpe-rs-emisor">—</div>
                      </div>
                      <div class="col-sm-4 cpe-field">
                        <div class="label">Tipo documento</div>
                        <div class="val" id="cpe-tipo">—</div>
                      </div>
                      <div class="col-sm-4 cpe-field">
                        <div class="label">Serie / Número</div>
                        <div class="val"><code class="mn" id="cpe-serie2">—</code></div>
                      </div>
                      <div class="col-sm-4 cpe-field">
                        <div class="label">Fecha emisión</div>
                        <div class="val" id="cpe-fecha">—</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card border-0" style="background:rgba(113,221,55,.05);border-radius:10px">
                  <div class="card-body py-3">
                    <div class="mb-2 cpe-field">
                      <div class="label">Adquiriente</div>
                      <div class="val fw-semibold" id="cpe-rs-cli">—</div>
                    </div>
                    <div class="mb-2 cpe-field">
                      <div class="label">RUC / DNI</div>
                      <div class="val" id="cpe-doc-cli">—</div>
                    </div>
                    <div class="cpe-field">
                      <div class="label">Importe total</div>
                      <div class="val fw-bold" style="font-size:1.1rem;color:#696cff" id="cpe-importe">—</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {{-- Detalle de ítems --}}
            <div class="fw-semibold small mb-2">
              <i class="bx bx-list-ul me-1 text-primary"></i>Ítems
            </div>
            <div class="table-responsive" style="max-height:320px;overflow-y:auto">
              <table class="table table-sm table-hover mb-0" id="tabla-cpe-det">
                <thead style="position:sticky;top:0;z-index:1">
                  <tr>
                    <th>#</th>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th class="text-center">Cant.</th>
                    <th class="text-center">U.M.</th>
                    <th class="text-end">P. Unit.</th>
                    <th class="text-end">Subtotal</th>
                  </tr>
                </thead>
                <tbody id="cpe-det-body"></tbody>
              </table>
            </div>

            {{-- Totales --}}
            <div class="d-flex justify-content-end mt-3">
              <div style="min-width:220px">
                <table class="table table-sm mb-0">
                  <tbody id="cpe-totales"></tbody>
                </table>
              </div>
            </div>

            {{-- Info adicional (NC/ND) --}}
            <div id="cpe-ref-blk" class="alert alert-light py-2 px-3 mt-2" style="display:none;font-size:.78rem">
              <i class="bx bx-link me-1 text-primary"></i>
              Referencia: <strong id="cpe-ref-tipo"></strong> <code class="mn" id="cpe-ref-serie"></code>
            </div>
          </div>
        </div>
        <div id="cpe-error" class="alert alert-danger py-2" style="display:none;font-size:.82rem"></div>
      </div>
    </div>
  </div>
</div>

{{-- ── Modal: Errores del registro ────────────────────────── --}}
<div class="modal fade" id="modal-errores" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius:12px;border:none">
      <div class="modal-header py-2" style="border-bottom:1px solid rgba(0,0,0,.06)">
        <h6 class="modal-title fw-semibold">
          <i class="bx bx-bug me-2 text-danger"></i>Historial de errores — #<span id="modal-reg-id"></span>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0" id="tabla-errores">
            <thead>
              <tr>
                <th style="width:45px">#</th>
                <th style="width:95px">Código</th>
                <th>Mensaje</th>
                <th style="width:110px">Fecha</th>
                <th style="width:75px">Resuelto</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <div id="errores-empty" class="text-center py-4 text-muted" style="display:none">
          <i class="bx bx-check-circle text-success" style="font-size:1.5rem;display:block;margin-bottom:.4rem"></i>
          Sin errores registrados
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ── Modal: Forzar captura ───────────────────────────────── --}}
<div class="modal fade" id="modal-forzar" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content" style="border-radius:12px;border:none">
      <div class="modal-header py-2" style="border-bottom:1px solid rgba(0,0,0,.06)">
        <h6 class="modal-title fw-semibold">
          <i class="bx bx-upload me-2" style="color:#696cff"></i>Forzar captura
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-light py-2 px-3 mb-3" style="font-size:.75rem;border-radius:8px">
          <i class="bx bx-info-circle me-1 text-primary"></i>
          Procesa un documento específico por su ID interno de Soluflex, incluso si ya fue capturado
          o está debajo del cursor actual.
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold mb-1">Tienda</label>
          <input type="text" id="forzar-tienda-label" class="form-control form-control-sm" readonly
                 style="background:rgba(105,108,255,.05);color:#696cff;font-weight:600">
          <input type="hidden" id="forzar-tienda-codigo">
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold mb-1">IDTRANSACCION (Soluflex)</label>
          <input type="number" id="forzar-idtransaccion" class="form-control form-control-sm"
                 placeholder="Ej. 262943" min="1">
          <div class="form-text" style="font-size:.7rem">
            Debe estar cerrado (estado 12) y habilitado como electrónico.
          </div>
        </div>
        <div id="forzar-res" class="d-none p-2 rounded small mt-2" style="word-break:break-word"></div>
      </div>
      <div class="modal-footer py-2 gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-sm btn-primary" id="btn-forzar-submit">
          <i class="bx bx-send me-1"></i>Capturar
        </button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('footer')
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.1.1/dist/ag-grid-community.min.js"></script>
<script>
(function () {
  'use strict';

  const csrf = '{{ csrf_token() }}';

  // ── Tooltips ────────────────────────────────────────────────
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el, { trigger: 'hover', boundary: 'window' });
  });

  // ── Banner de operación en curso ─────────────────────────────
  function opStart(msg) {
    document.getElementById('op-banner-text').textContent = msg || 'Procesando…';
    document.getElementById('op-running-banner').classList.add('visible');
  }
  function opEnd() {
    document.getElementById('op-running-banner').classList.remove('visible');
  }

  // ── Sync time ────────────────────────────────────────────────
  function setTime() {
    const n = new Date();
    const el = document.getElementById('sync-time');
    el.textContent = [n.getHours(), n.getMinutes(), n.getSeconds()]
      .map(v => String(v).padStart(2,'0')).join(':');
    el.style.transition = 'none';
    el.style.opacity = '0.3';
    requestAnimationFrame(() => {
      el.style.transition = 'opacity .4s';
      el.style.opacity = '1';
    });
  }
  setTime();

  // ── Health dot ───────────────────────────────────────────────
  const dot = document.getElementById('health-dot');
  const errs = {{ $stats['errores'] }}, cuar = {{ $stats['cuarentena'] }};
  dot.className = 'h-dot ' + (errs > 0 ? 'error' : cuar > 0 ? 'warn' : 'ok');
  dot.setAttribute('title', errs > 0 ? 'Errores activos' : cuar > 0 ? 'Cuarentena pendiente' : 'Operativo');

  // ── Carrusel de tiendas ──────────────────────────────────────
  (function () {
    const PER_PAGE = 12;
    var tcPage = 0;
    var tcFiltered = [];
    var allItems = Array.from(document.querySelectorAll('#tc-grid .tc-item'));

    function tcRender() {
      var total = tcFiltered.length;
      var pages  = Math.max(1, Math.ceil(total / PER_PAGE));
      if (tcPage >= pages) tcPage = pages - 1;
      var start = tcPage * PER_PAGE;
      var end   = start + PER_PAGE;

      allItems.forEach(function(el) { el.style.display = 'none'; });
      tcFiltered.forEach(function(el, i) {
        el.style.display = (i >= start && i < end) ? '' : 'none';
      });

      document.getElementById('tc-page-info').textContent =
        total === 0 ? 'Sin resultados' : ('Página ' + (tcPage + 1) + ' de ' + pages + ' — ' + total + ' tienda' + (total !== 1 ? 's' : ''));
      document.getElementById('tc-prev').disabled = tcPage === 0;
      document.getElementById('tc-next').disabled = end >= total;
      document.getElementById('tc-empty').style.display = total === 0 ? '' : 'none';
      document.getElementById('tc-nav').style.display = total <= PER_PAGE && pages === 1 ? 'none' : '';
    }

    function tcFilter(q) {
      tcFiltered = q
        ? allItems.filter(function(el) {
            var chip = el.querySelector('.tc');
            return chip.dataset.codigo.includes(q) || chip.dataset.nombre.includes(q);
          })
        : allItems.slice();
      tcPage = 0;
      tcRender();
    }

    document.getElementById('tc-search').addEventListener('input', function () {
      tcFilter(this.value.toLowerCase().trim());
    });
    document.getElementById('tc-prev').addEventListener('click', function () {
      if (tcPage > 0) { tcPage--; tcRender(); }
    });
    document.getElementById('tc-next').addEventListener('click', function () {
      if ((tcPage + 1) * PER_PAGE < tcFiltered.length) { tcPage++; tcRender(); }
    });

    // Inicializar con todos los items
    tcFilter('');
  }());

  // ── Log filters ──────────────────────────────────────────────
  document.querySelectorAll('.lf-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      document.querySelectorAll('.lf-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      const level = this.dataset.level;
      const map = { I: 'INFO', W: 'WARN', E: 'ERROR' };
      document.querySelectorAll('#log-content .log-line').forEach(line => {
        if (level === 'ALL') { line.style.display = ''; return; }
        line.style.display = (map[line.dataset.level] === level) ? '' : 'none';
      });
    });
  });

  // ── Copy log ─────────────────────────────────────────────────
  document.getElementById('btn-copy-log').addEventListener('click', function (e) {
    e.stopPropagation();
    const lines = [...document.querySelectorAll('#log-content .log-line')]
      .filter(l => l.style.display !== 'none')
      .map(l => l.textContent.trim());
    navigator.clipboard.writeText(lines.join('\n')).then(() => {
      const ico = this.querySelector('i');
      ico.className = 'bx bx-check';
      setTimeout(() => { ico.className = 'bx bx-copy'; }, 1600);
    });
  });

  // ── AG Grid ──────────────────────────────────────────────────
  const rowCls = { CAPTURADO:'r-ok', PENDIENTE:'r-pend', ERROR_CAPTURA:'r-err', CUARENTENA:'r-warn' };
  const badgeHtml = {
    CAPTURADO:     '<span class="s-dot ok"></span><span class="badge" style="background:#4ADE80;color:#166534">Capturado</span>',
    PENDIENTE:     '<span class="s-dot pend"></span><span class="badge bg-secondary">Pendiente</span>',
    ERROR_CAPTURA: '<span class="s-dot err"></span><span class="badge bg-danger">Error</span>',
    CUARENTENA:    '<span class="s-dot warn"></span><span class="badge bg-warning text-dark">Cuarentena</span>',
  };
  const tipoMap = { '01':'FAC','03':'BOL','07':'NC','08':'ND' };
  const blBadge = {
    A: '<span class="badge" style="background:#4ADE80;color:#166534">✓ Aceptado</span>',
    R: '<span class="badge bg-danger">✕ Rechazado</span>',
    E: '<span class="badge bg-danger">✕ Error</span>',
    A: '<span class="badge" style="background:#4ADE80;color:#166534">✓ Aceptado</span>',
    P: '<span class="badge" style="background:#4ADE80;color:#166534">✓ Aceptado</span>',
    L: '<span class="badge bg-secondary">◷ Pendiente</span>',
  };

  function accionesHtml(r) {
    let b = '';
    if (r.estado === 'CAPTURADO' && r.serie_numero_bizlinks !== '—')
      b += `<button class="ac-btn ac-btn-cpe btn-ver-cpe" data-id="${r.id}" title="Ver CPE en Bizlinks"><i class="bx bx-file-blank"></i></button>`;
    if (['ERROR_CAPTURA','CUARENTENA'].includes(r.estado) || (r.estado === 'CAPTURADO' && ['E','L'].includes(r.estado_bizlinks)))
      b += `<button class="ac-btn ac-btn-retry btn-reset" data-id="${r.id}" title="Reintentar en Bizlinks"><i class="bx bx-refresh"></i></button>`;
    if (['PENDIENTE','ERROR_CAPTURA'].includes(r.estado))
      b += `<button class="ac-btn ac-btn-force btn-forzar-directo" data-id="${r.id}" title="Forzar captura ahora"><i class="bx bx-send"></i></button>`;
    b += `<button class="ac-btn ac-btn-info btn-errores" data-id="${r.id}" title="Ver historial de errores"><i class="bx bx-info-circle"></i></button>`;
    return b;
  }

  function bizlinksHtml(v, row) {
    if (!v || v === '—') return '<span class="text-muted">—</span>';
    const badge = blBadge[v] || `<span class="badge bg-secondary">${v}</span>`;
    if (row.mensaje_bizlinks) {
      const dataMsg  = encodeURIComponent(row.mensaje_bizlinks);
      const dataSeri = encodeURIComponent(row.serie_numero_bizlinks || '');
      return `${badge} <button class="btn-biz-detalle" style="background:none;border:none;padding:0;cursor:pointer" data-msg="${dataMsg}" data-serie="${dataSeri}" data-estado="${v}" title="Ver detalle"><i class="bx bx-info-circle" style="font-size:.85rem;color:#8592a3"></i></button>`;
    }
    return badge;
  }

  let gridApi;

  const colDefs = [
    { field:'id', headerName:'#', width:54, maxWidth:64, sortable:true, sort:'desc', suppressSizeToFit:true },
    { field:'codigo_tienda', headerName:'Tienda', width:84, maxWidth:100, suppressSizeToFit:true,
      cellRenderer: p => `<span class="badge bg-label-primary">${p.value}</span>` },
    { field:'serie_numero_bizlinks', headerName:'Serie / N°', flex:1.1, minWidth:118, maxWidth:150,
      cellRenderer: p => p.value==='—' ? '<span class="text-muted">—</span>' : `<code class="mn">${p.value}</code>` },
    { field:'tipo_documento_sunat', headerName:'Tipo', width:60, maxWidth:72, suppressSizeToFit:true,
      cellRenderer: p => `<span class="badge bg-label-info">${tipoMap[p.value]||p.value||'—'}</span>`,
      filterValueGetter: p => tipoMap[p.data.tipo_documento_sunat] || p.data.tipo_documento_sunat || '—' },
    { field:'fecha_venta', headerName:'Fecha venta', flex:0.8, minWidth:88, maxWidth:106, sortable:true },
    { field:'importe_total', headerName:'Importe', flex:0.7, minWidth:80, maxWidth:100, sortable:true,
      cellStyle:{ justifyContent:'flex-end', fontVariantNumeric:'tabular-nums', fontWeight:600 } },
    { field:'razon_social_cliente', headerName:'Cliente', flex:2, minWidth:130,
      cellRenderer: p => {
        const doc = p.data.numero_documento_cliente && p.data.numero_documento_cliente!=='—'
          ? ` <small class="text-muted">(${p.data.numero_documento_cliente})</small>` : '';
        return `<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block">${p.value||'—'}${doc}</span>`;
      }},
    { field:'estado', headerName:'Estado', flex:0.9, minWidth:105, maxWidth:125,
      cellRenderer: p => badgeHtml[p.value] || `<span class="badge bg-secondary">${p.value}</span>` },
    { field:'estado_bizlinks', headerName:'Bizlinks', flex:0.9, minWidth:105, maxWidth:130,
      cellRenderer: p => bizlinksHtml(p.value, p.data),
      filterValueGetter: p => ({ A:'Aceptado', P:'Aceptado', R:'Rechazado', E:'Error', L:'Pendiente' }[p.data.estado_bizlinks] || p.data.estado_bizlinks || '—') },
    { field:'fecha_captura', headerName:'Capturado', flex:0.9, minWidth:115, maxWidth:140, sortable:true,
      cellRenderer: p => p.value==='—' ? '<span class="text-muted">—</span>' : p.value },
    { field:'acciones', headerName:'Acciones', width:152, maxWidth:165, suppressSizeToFit:true,
      sortable:false, filter:false,
      cellRenderer: p => accionesHtml(p.data),
      cellStyle:{ justifyContent:'center', overflow:'visible' } },
  ];

  // Definir cargarDatos como function declaration (hoisted) para que
  // esté disponible cuando onGridReady la invoque durante createGrid
  function cargarDatos() {
    if (!gridApi) return;
    const p = new URLSearchParams({
      draw: 1, start: 0, length: 2000,
      estado:     document.getElementById('f-estado').value,
      tienda:     document.getElementById('f-tienda').value,
      buscar:     document.getElementById('f-buscar').value,
      fecha_ini:  document.getElementById('f-fecha-ini').value,
      fecha_fin:  document.getElementById('f-fecha-fin').value,
    });
    fetch(`{{ route('captura.registros') }}?${p}`, { headers:{ 'Accept':'application/json' } })
      .then(r => r.json())
      .then(d => {
        const rows = d.data || [];
        gridApi.setGridOption('rowData', rows);
        console.log('[Monitor] refresh', new Date().toLocaleTimeString(), '—', rows.length, 'filas');
        setTime();
      })
      .catch(e => console.error('[Monitor] cargarDatos error:', e));
  }
  window.cargarDatos = cargarDatos; // exponer para IIFEs externos

  const gridDiv = document.getElementById('ag-tabla-reg');
  gridDiv.classList.add('ag-theme-quartz');

  agGrid.createGrid(gridDiv, {
    columnDefs: colDefs,
    rowData: [],
    pagination: true,
    paginationPageSize: 25,
    defaultColDef: {
      resizable: true,
      sortable: true,
      filter: true,
      floatingFilter: true,
      suppressHeaderFilterButton: true,
      suppressHeaderMenuButton: true,
      wrapText: false,
      autoHeight: false,
      enableCellChangeFlash: true,
      cellStyle: { overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap' },
    },
    animateRows: true,
    getRowId: p => String(p.data.id),
    getRowClass: p => rowCls[p.data?.estado] || '',
    suppressCellFocus: true,
    enableCellTextSelection: true,
    ensureDomOrder: true,
    onGridReady: p => { gridApi = p.api; cargarDatos(); },
    onGridSizeChanged: p => { if (p.clientWidth > 0) p.api.sizeColumnsToFit(); },
    onFirstDataRendered: p => p.api.sizeColumnsToFit(),
  });

  // ag-theme-quartz se adapta al tema vía CSS variables — no necesita swap de clase

  ['f-estado','f-tienda','f-fecha-ini','f-fecha-fin'].forEach(id =>
    document.getElementById(id).addEventListener('change', cargarDatos)
  );
  let dbTimer;
  document.getElementById('f-buscar').addEventListener('input', () => {
    clearTimeout(dbTimer);
    dbTimer = setTimeout(cargarDatos, 380);
  });

  // ── Delegación de clicks en filas AG Grid ────────────────────
  document.getElementById('ag-tabla-reg').addEventListener('click', function (e) {
    const btn = e.target.closest('button[class]');
    if (!btn) return;
    const id = parseInt(btn.dataset.id, 10);
    if (btn.classList.contains('btn-reset'))              handleReset(id, btn);
    else if (btn.classList.contains('btn-forzar-directo')) handleForzarDirecto(id, btn);
    else if (btn.classList.contains('btn-errores'))        handleErrores(id);
    else if (btn.classList.contains('btn-ver-cpe'))        mostrarCpe(id);
  });

  // ── Botón: Reintentar ────────────────────────────────────────
  function handleReset(id, btn) {
    Swal.fire({
      title: '¿Reintentar?',
      text: `El registro #${id} vuelve a PENDIENTE y se procesa en el próximo ciclo.`,
      icon: 'question', showCancelButton: true,
      confirmButtonText: 'Sí, reintentar', cancelButtonText: 'Cancelar',
      confirmButtonColor: '#696cff',
    }).then(r => {
      if (!r.isConfirmed) return;
      if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
      opStart('Reintentando registro #' + id + '…');
      fetch(`{{ url('/captura/registros') }}/${id}/reset`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
      }).then(r => r.json()).then(d => {
        if (d.ok) toastr.success('Registro listo para reintento');
        else toastr.error('No se pudo resetear');
      }).catch(() => toastr.error('Error de comunicación'))
        .finally(() => { opEnd(); cargarDatos(); });
    });
  }

  // ── Botón: Forzar captura directa desde fila ────────────────
  function handleForzarDirecto(id, btn) {
    Swal.fire({
      title: '¿Forzar captura?',
      text: 'Se capturará este documento ahora mismo en Bizlinks.',
      icon: 'warning', showCancelButton: true,
      confirmButtonText: 'Sí, forzar', cancelButtonText: 'Cancelar',
      confirmButtonColor: '#ff3e1d',
    }).then(r => {
      if (!r.isConfirmed) return;
      if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
      opStart('Insertando en Bizlinks…');
      fetch(`{{ url('/captura/registros') }}/${id}/forzar`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
      }).then(r => r.json()).then(d => {
        if (d.ok) toastr.success('Documento capturado correctamente');
        else toastr.error(d.mensaje || 'Error al forzar captura');
      }).catch(() => toastr.error('Error de red al forzar captura'))
        .finally(() => { opEnd(); cargarDatos(); });
    });
  }

  // ── Botón: Ver errores ───────────────────────────────────────
  function handleErrores(id) {
    $('#modal-reg-id').text(id);
    $('#tabla-errores tbody').empty();
    $('#errores-empty').hide();
    fetch(`{{ url('/captura/registros') }}/${id}/errores`, {
      headers: { 'Accept': 'application/json' }
    }).then(r => r.json()).then(list => {
      if (!list.length) { $('#errores-empty').show(); return; }
      list.forEach(e => {
        const resuelto = e.resuelto
          ? '<span class="badge" style="background:#4ADE80;color:#166534">Sí</span>'
          : '<span class="badge bg-secondary">No</span>';
        const fecha = e.fecha_error ? e.fecha_error.substring(0,16) : '—';
        $('#tabla-errores tbody').append(
          `<tr>
            <td>${e.id}</td>
            <td><code class="mn">${e.codigo_error||'—'}</code></td>
            <td style="font-size:.76rem;word-break:break-all">${(e.mensaje_original||'').slice(0,300)}</td>
            <td style="font-size:.72rem">${fecha}</td>
            <td>${resuelto}</td>
          </tr>`
        );
      });
      new bootstrap.Modal(document.getElementById('modal-errores')).show();
    });
  }

  // ── Botón: Ver CPE ───────────────────────────────────────────
  const cpeModal = new bootstrap.Modal(document.getElementById('modal-cpe'));

  function mostrarCpe(id) {
    document.getElementById('cpe-loading').style.display = '';
    document.getElementById('cpe-content').style.display = 'none';
    document.getElementById('cpe-error').style.display = 'none';
    cpeModal.show();

    fetch(`{{ url('/captura/registros') }}/${id}/documento`, {
      headers: { 'Accept': 'application/json' }
    }).then(r => r.json()).then(d => {
      document.getElementById('cpe-loading').style.display = 'none';
      if (!d.ok) {
        const errEl = document.getElementById('cpe-error');
        errEl.textContent = d.error || 'Error al obtener el documento.';
        errEl.style.display = '';
        return;
      }

      const h = d.header, reg = d.registro;

      // Cabecera
      document.getElementById('cpe-serie').textContent  = reg.serie_numero;
      document.getElementById('cpe-serie2').textContent = reg.serie_numero;
      document.getElementById('cpe-subtitulo').textContent =
        (reg.tipo === '01' ? 'Factura' : reg.tipo === '03' ? 'Boleta' :
         reg.tipo === '07' ? 'Nota de Crédito' : reg.tipo === '08' ? 'Nota de Débito' : reg.tipo || '—')
        + ' — ' + (reg.cliente || '—');

      // Emisor
      document.getElementById('cpe-ruc-emisor').textContent = h.numeroDocumentoEmisor || h.rucEmisor || '—';
      document.getElementById('cpe-rs-emisor').textContent  = h.razonSocialEmisor || '—';
      document.getElementById('cpe-tipo').textContent =
        reg.tipo === '01' ? '01 — Factura electrónica' :
        reg.tipo === '03' ? '03 — Boleta de venta electrónica' :
        reg.tipo === '07' ? '07 — Nota de crédito electrónica' :
        reg.tipo === '08' ? '08 — Nota de débito electrónica' : reg.tipo || '—';
      document.getElementById('cpe-fecha').textContent = h.fechaEmision || reg.fecha_venta || '—';

      // Adquiriente
      document.getElementById('cpe-rs-cli').textContent  = h.razonSocialAdquiriente || reg.cliente || '—';
      document.getElementById('cpe-doc-cli').textContent = h.numeroDocumentoAdquiriente || reg.ruc_dni || '—';
      document.getElementById('cpe-importe').textContent = 'S/ ' + reg.importe;

      // Referencia NC/ND
      if (h.codigoSerieNumeroAfectado || h.numeroDocumentoReferenciaPrinc) {
        document.getElementById('cpe-ref-tipo').textContent =
          h.tipoDocumentoReferenciaPrincip === '01' ? 'Factura' :
          h.tipoDocumentoReferenciaPrincip === '03' ? 'Boleta' : h.tipoDocumentoReferenciaPrincip || '';
        document.getElementById('cpe-ref-serie').textContent =
          (h.codigoSerieNumeroAfectado || '') + '-' + (h.numeroDocumentoReferenciaPrinc || '');
        document.getElementById('cpe-ref-blk').style.display = '';
      } else {
        document.getElementById('cpe-ref-blk').style.display = 'none';
      }

      // Detalle
      const tbody = document.getElementById('cpe-det-body');
      tbody.innerHTML = '';
      (d.detalles || []).forEach((item, i) => {
        tbody.insertAdjacentHTML('beforeend', `<tr>
          <td>${i+1}</td>
          <td><code class="mn">${item.codigoProducto||item.codigoItem||'—'}</code></td>
          <td>${item.descripcionProducto||item.descripcionItem||item.descripcion||'—'}</td>
          <td class="text-center">${parseFloat(item.cantidad||0).toFixed(2)}</td>
          <td class="text-center">${item.unidadMedida||item.uom||'NIU'}</td>
          <td class="text-end">S/ ${parseFloat(item.precioUnitario||item.valorUnitario||0).toFixed(2)}</td>
          <td class="text-end fw-semibold">S/ ${parseFloat(item.importeTotal||item.subtotal||0).toFixed(2)}</td>
        </tr>`);
      });

      // Totales
      const totEl = document.getElementById('cpe-totales');
      const igv  = parseFloat(h.totalIgv||0).toFixed(2);
      const base = parseFloat(h.totalValorVenta||h.totalVentaGravadas||0).toFixed(2);
      const tot  = parseFloat(h.totalPrecioVenta||h.importeTotal||0).toFixed(2);
      totEl.innerHTML = `
        <tr><td class="text-muted small">Base imponible</td><td class="text-end">S/ ${base}</td></tr>
        <tr><td class="text-muted small">IGV (18%)</td><td class="text-end">S/ ${igv}</td></tr>
        <tr class="fw-bold border-top">
          <td>Total</td><td class="text-end" style="color:#696cff">S/ ${tot||reg.importe}</td>
        </tr>`;

      // Botones de descarga Bizlinks (PDF oficial, XML, CDR)
      const btnPdf = document.getElementById('btn-cpe-pdf');
      const btnXml = document.getElementById('btn-cpe-xml');
      const btnCdr = document.getElementById('btn-cpe-cdr');
      if (d.archivos && d.archivos.url_pdf) {
        btnPdf.href = d.archivos.url_pdf; btnPdf.classList.remove('d-none');
      } else { btnPdf.classList.add('d-none'); }
      if (d.archivos && d.archivos.url_ubl) {
        btnXml.href = d.archivos.url_ubl; btnXml.classList.remove('d-none');
      } else { btnXml.classList.add('d-none'); }
      if (d.archivos && d.archivos.url_cdr) {
        btnCdr.href = d.archivos.url_cdr; btnCdr.classList.remove('d-none');
      } else { btnCdr.classList.add('d-none'); }

      document.getElementById('cpe-content').style.display = '';
    }).catch(() => {
      document.getElementById('cpe-loading').style.display = 'none';
      const errEl = document.getElementById('cpe-error');
      errEl.textContent = 'Error de comunicación con el servidor.';
      errEl.style.display = '';
    });
  }

  // btn-ver-cpe handled by delegated listener above

  document.getElementById('btn-cpe-imprimir').addEventListener('click', () => {
    const content = document.getElementById('cpe-printable').innerHTML;
    const win = window.open('', '_blank', 'width=800,height=700');
    win.document.write(`<!DOCTYPE html><html><head><title>CPE</title>
      <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}">
      <style>body{padding:2rem;font-family:system-ui,sans-serif;font-size:13px}
      .mn{font-family:monospace;background:#f0f0f5;padding:1px 4px;border-radius:3px}
      table{border-collapse:collapse;width:100%}th,td{padding:5px 8px;border-bottom:1px solid #eee}
      th{background:#f8f9fa;font-weight:600}</style>
    </head><body>${content}</body></html>`);
    win.document.close();
    win.focus();
    setTimeout(() => win.print(), 400);
  });

  // ── Estado del motor (polling cada 5 s) ─────────────────────
  var motorCorriendoDesde = null;

  function aplicarEstadoMotor(corriendo, desde) {
    const btn    = document.getElementById('btn-ejecutar');
    const status = document.getElementById('motor-status');
    if (corriendo) {
      motorCorriendoDesde = desde || motorCorriendoDesde || new Date().toISOString();
      // Si lleva más de 10 min corriendo, mostrar opción de limpiar estado
      var minutos = desde ? Math.round((Date.now() - new Date(desde).getTime()) / 60000) : 0;
      var resetLink = minutos >= 10
        ? ' <a href="#" id="lnk-reset-motor" style="font-size:.75rem;vertical-align:middle" title="Limpiar estado atascado">limpiar</a>'
        : '';
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Motor corriendo…' + resetLink;
      status.style.display = 'none';
      var lnk = document.getElementById('lnk-reset-motor');
      if (lnk && !lnk._bound) {
        lnk._bound = true;
        lnk.addEventListener('click', function (e) {
          e.preventDefault();
          resetEstadoMotor();
        });
      }
    } else {
      motorCorriendoDesde = null;
      btn.disabled = false;
      btn.innerHTML = '<i class="bx bx-play-circle me-1"></i>Ejecutar ahora';
      status.style.display = 'none';
    }
  }

  function resetEstadoMotor() {
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    fetch('{{ route("captura.reset-estado-motor") }}', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    })
    .then(function () { verificarEstadoMotor(); })
    .catch(function () {});
  }

  function verificarEstadoMotor() {
    fetch('{{ route("captura.estado-motor") }}', { headers: { 'Accept': 'application/json' } })
      .then(r => r.json())
      .then(d => aplicarEstadoMotor(d.corriendo, d.desde))
      .catch(() => {});
  }

  verificarEstadoMotor();
  setInterval(verificarEstadoMotor, 5000);

  // ── Ejecutar motor ───────────────────────────────────────────
  $('#btn-ejecutar').on('click', function () {
    var tiendas = @json($tiendasActivas->map(function($t) { return ['codigo' => $t->codigo_tienda, 'nombre' => $t->nombre_tienda ?? $t->codigo_tienda]; })->values());

    var checksHtml = tiendas.map(function(t) {
      return '<label style="display:flex;align-items:center;gap:8px;padding:5px 2px;cursor:pointer;font-size:.85rem">'
        + '<input type="checkbox" class="swal-chk-tienda" value="' + t.codigo + '" style="width:15px;height:15px;cursor:pointer">'
        + '<span><strong>' + t.codigo + '</strong> — ' + t.nombre + '</span></label>';
    }).join('');

    Swal.fire({
      title: 'Ejecutar motor de captura',
      html: `
        <p style="margin:0 0 8px;font-size:.82rem;color:var(--swal2-html-container-color,inherit);text-align:left">
          Selecciona las tiendas a procesar. Sin selección = todas las activas.
        </p>
        <div style="display:flex;gap:10px;margin-bottom:6px">
          <label style="font-size:.8rem;cursor:pointer;display:flex;align-items:center;gap:5px">
            <input type="checkbox" id="swal-chk-todas" style="width:13px;height:13px">
            <span>Seleccionar todas</span>
          </label>
          <label style="font-size:.8rem;cursor:pointer;display:flex;align-items:center;gap:5px">
            <input type="checkbox" id="swal-chk-ninguna" style="width:13px;height:13px">
            <span>Ninguna (todas activas)</span>
          </label>
        </div>
        <div id="swal-tiendas-lista" style="
          max-height:220px;overflow-y:auto;border:1px solid #d9d9d9;border-radius:6px;
          padding:6px 10px;text-align:left;
        ">${checksHtml}</div>`,
      showCancelButton: true,
      confirmButtonText: '<i class="bx bx-play-circle me-1"></i>Ejecutar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#696cff',
      width: 460,
      didOpen: function() {
        var popup = document.querySelector('.swal2-popup');
        var lista = document.getElementById('swal-tiendas-lista');
        if (popup) {
          var cs = window.getComputedStyle(popup);
          lista.style.borderColor = cs.borderColor || '#d9d9d9';
        }
        document.getElementById('swal-chk-todas').addEventListener('change', function() {
          document.querySelectorAll('.swal-chk-tienda').forEach(function(c) { c.checked = true; });
          document.getElementById('swal-chk-ninguna').checked = false;
        });
        document.getElementById('swal-chk-ninguna').addEventListener('change', function() {
          document.querySelectorAll('.swal-chk-tienda').forEach(function(c) { c.checked = false; });
          document.getElementById('swal-chk-todas').checked = false;
        });
        document.querySelectorAll('.swal-chk-tienda').forEach(function(c) {
          c.addEventListener('change', function() {
            document.getElementById('swal-chk-todas').checked = false;
            document.getElementById('swal-chk-ninguna').checked = false;
          });
        });
      },
      preConfirm: function() {
        return Array.from(document.querySelectorAll('.swal-chk-tienda:checked')).map(function(c) { return c.value; });
      },
    }).then(function(r) {
      if (!r.isConfirmed) return;
      var seleccionadas = r.value || [];
      var label = seleccionadas.length ? seleccionadas.join(', ') : 'todas las activas';
      var btn = $('#btn-ejecutar');
      btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Ejecutando…');
      opStart('Ejecutando motor de captura (' + label + ')…');
      fetch('{{ route("captura.ejecutar") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ tiendas: seleccionadas }),
      }).then(function(r) {
        if (!r.ok) {
          return r.text().then(function(t) {
            throw new Error('HTTP ' + r.status + (t ? ': ' + t.substring(0,120).replace(/<[^>]+>/g,'') : ''));
          });
        }
        return r.json();
      }).then(function(d) {
        if (d.ok) { toastr.success(d.output || 'Motor iniciado'); verificarEstadoMotor(); }
        else toastr.error(d.error || 'Error al ejecutar');
      }).catch(function(e) {
        console.error('[ejecutar]', e);
        toastr.error(e && e.message ? e.message : 'Error de comunicación');
      }).then(function() { opEnd(); verificarEstadoMotor(); });
    });
  });

  // ── Sync Bizlinks ────────────────────────────────────────────
  $('#btn-sync-bizlinks').on('click', function () {
    const tienda = $('#f-tienda').val();
    const btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Sincronizando…');
    opStart('Sincronizando estado Bizlinks…');
    const body = {};
    if (tienda) body.codigo_tienda = tienda;
    fetch('{{ route("captura.sync-bizlinks") }}', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
      body: JSON.stringify(body),
    }).then(r => r.json()).then(d => {
      if (d.ok) { toastr.success(d.output || 'Estado Bizlinks actualizado'); cargarDatos(); }
      else toastr.error(d.error || 'Error al sincronizar');
    }).catch(() => toastr.error('Error de comunicación'))
      .finally(() => { opEnd(); btn.prop('disabled', false).html('<i class="bx bx-cloud-download me-1"></i>Sync Bizlinks'); });
  });

  // ── Reset masivo ─────────────────────────────────────────────
  $('#btn-reset-masivo').on('click', function () {
    const tienda = $('#f-tienda').val();
    const desc   = tienda ? `de la tienda <strong>${tienda}</strong>` : 'de <strong>todas las tiendas</strong>';
    Swal.fire({
      title: '¿Reintentar todos los errores?',
      html: `Se resetearán a PENDIENTE todos los registros con ERROR_CAPTURA o CUARENTENA ${desc}.`,
      icon: 'warning', showCancelButton: true,
      confirmButtonText: 'Sí, reintentar todos',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#ffab00',
    }).then(r => {
      if (!r.isConfirmed) return;
      const body = {};
      if (tienda) body.codigo_tienda = tienda;
      fetch('{{ route("captura.reset-masivo") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify(body),
      }).then(r => r.json()).then(d => {
        if (d.ok) {
          toastr.success(`${d.reseteados} registro${d.reseteados !== 1 ? 's' : ''} reseteado${d.reseteados !== 1 ? 's' : ''} a PENDIENTE`);
          cargarDatos();
        } else {
          toastr.error('No se pudo resetear');
        }
      }).catch(() => toastr.error('Error de comunicación'));
    });
  });

  // ── Reset rechazados SUNAT ───────────────────────────────────
  $('#btn-reset-rechazados').on('click', function () {
    var tienda = $('#f-tienda').val();
    var desc   = tienda ? 'de la tienda <strong>' + tienda + '</strong>' : 'de <strong>todas las tiendas</strong>';
    Swal.fire({
      title: '¿Reintentar rechazados por SUNAT?',
      html: 'Se re-enviarán a Bizlinks todos los documentos con estado <strong>Rechazado</strong> ' + desc + '.<br><small class="text-muted">Útil cuando el documento original ya fue aceptado por SUNAT (ej. error 2119 por timing).</small>',
      icon: 'warning', showCancelButton: true,
      confirmButtonText: 'Sí, reintentar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#ffab00',
    }).then(function(r) {
      if (!r.isConfirmed) return;
      var body = { tipo: 'bizlinks_rechazados' };
      if (tienda) body.codigo_tienda = tienda;
      fetch('{{ route("captura.reset-masivo") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify(body),
      }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.ok) {
          toastr.success(d.reseteados + ' documento' + (d.reseteados !== 1 ? 's' : '') + ' listo' + (d.reseteados !== 1 ? 's' : '') + ' para reintento');
          cargarDatos();
        } else {
          toastr.error('No se pudo resetear');
        }
      }).catch(function() { toastr.error('Error de comunicación'); });
    });
  });

  // ── CSV ──────────────────────────────────────────────────────
  $('#btn-csv').on('click', () => {
    const p = new URLSearchParams({
      estado: $('#f-estado').val(), tienda: $('#f-tienda').val(),
      buscar: $('#f-buscar').val(), export: 1, length: 5000, start: 0,
    });
    window.open(`{{ route('captura.registros') }}?${p}`);
  });

  // ── Auto-ciclo: capturar → sync → tabla (cada 60 s) ──────────
  (function () {
    var INTERVAL   = 60;
    var PHASE_MIN  = 900; // ms mínimo visible por fase
    var rfOn = true, remaining = INTERVAL, rfTimer;
    var cycling = false;
    var lbl  = document.getElementById('lbl-rf');
    var txt  = document.getElementById('txt-cd');
    var btnP = document.getElementById('btn-toggle-rf');
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '{{ csrf_token() }}';

    function fmtNow() {
      var n = new Date();
      return [n.getHours(), n.getMinutes(), n.getSeconds()]
        .map(function (v) { return String(v).padStart(2, '0'); }).join(':');
    }

    function wait(ms) {
      return new Promise(function (resolve) { setTimeout(resolve, ms); });
    }

    function setPhase(phase) {
      var icon = document.getElementById('rf-icon');
      if (phase === 'idle' || !phase) {
        lbl.classList.remove('running');
        if (icon) { icon.className = 'bx bx-refresh'; }
        txt.textContent = remaining + 's';
      } else {
        lbl.classList.add('running');
        if (icon) { icon.className = 'bx bx-loader-alt bx-spin'; }
        txt.textContent = phase + '…';
      }
    }

    function setCicloResultado(ok, msg) {
      var el = document.getElementById('ciclo-resultado');
      if (!el) return;
      el.style.display = '';
      el.style.color   = ok ? '#71dd37' : '#ff3e1d';
      el.textContent   = (ok ? '✓ ' : '✗ ') + msg + ' ' + fmtNow();
    }

    function runCycle() {
      if (cycling || !rfOn) return;
      cycling  = true;
      remaining = INTERVAL;
      console.log('[ciclo] iniciando ' + fmtNow());
      setPhase('capturando');

      var capturaOk = false, capturaError = '';

      var pCaptura = fetch('{{ route("captura.ejecutar") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({}),
      })
      .then(function (r) {
        console.log('[ciclo] captura HTTP ' + r.status);
        capturaOk = r.ok;
        return r.json();
      })
      .catch(function (e) {
        capturaError = e ? String(e) : 'error red';
        console.warn('[ciclo] captura error:', capturaError);
        return {};
      });

      Promise.all([pCaptura, wait(PHASE_MIN)])
      .then(function () {
        var el = document.getElementById('last-capture-time');
        if (el) { el.textContent = fmtNow(); }
        console.log('[ciclo] sincronizando...');
        setPhase('sincronizando');

        var pSync = fetch('{{ route("captura.sync-bizlinks") }}', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
          body: JSON.stringify({}),
        })
        .then(function (r) {
          console.log('[ciclo] sync HTTP ' + r.status);
          return r.json();
        })
        .catch(function (e) {
          console.warn('[ciclo] sync error:', e ? String(e) : 'error red');
          return {};
        });

        return Promise.all([pSync, wait(PHASE_MIN)]);
      })
      .then(function () {
        setTime();
        console.log('[ciclo] actualizando tabla...');
        setPhase('actualizando');
        cargarDatos();
        verificarEstadoMotor();
        setCicloResultado(true, 'ciclo OK');
        return wait(PHASE_MIN);
      })
      .catch(function (e) {
        console.error('[ciclo] error inesperado:', e ? String(e) : '?');
        setCicloResultado(false, 'error');
      })
      .then(function () {
        cycling = false;
        setPhase('idle');
        console.log('[ciclo] finalizado, próximo en ' + INTERVAL + 's');
      });
    }

    function tick() {
      if (!rfOn || cycling) return;
      remaining--;
      txt.textContent = remaining + 's';
      if (remaining <= 0) {
        remaining = INTERVAL;
        runCycle();
      }
    }
    rfTimer = setInterval(tick, 1000);

    btnP.addEventListener('click', function () {
      rfOn = !rfOn;
      if (rfOn) {
        remaining = INTERVAL;
        lbl.classList.remove('paused');
        btnP.innerHTML = '<i class="bx bx-pause"></i>';
        btnP.title = 'Pausar auto-ciclo';
        rfTimer = setInterval(tick, 1000);
        setPhase('idle');
      } else {
        clearInterval(rfTimer);
        lbl.classList.remove('running');
        lbl.classList.add('paused');
        txt.textContent = 'pausado';
        btnP.innerHTML = '<i class="bx bx-play"></i>';
        btnP.title = 'Reanudar auto-ciclo';
      }
    });

    // Primer ciclo a los 5 s de cargar la página
    setTimeout(runCycle, 5000);
  }());

})();

// ── Forzar captura ────────────────────────────────────────────
(function () {
  const modal = new bootstrap.Modal(document.getElementById('modal-forzar'));
  const csrf  = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-forzar');
    if (!btn) return;
    document.getElementById('forzar-tienda-codigo').value = btn.dataset.tienda;
    document.getElementById('forzar-tienda-label').value  = btn.dataset.tienda + ' — ' + btn.dataset.nombre;
    document.getElementById('forzar-idtransaccion').value = '';
    document.getElementById('forzar-res').className = 'd-none';
    document.getElementById('forzar-res').textContent = '';
    modal.show();
    setTimeout(() => document.getElementById('forzar-idtransaccion').focus(), 400);
  });

  document.getElementById('btn-forzar-submit').addEventListener('click', function () {
    const tienda = document.getElementById('forzar-tienda-codigo').value;
    const idtx   = parseInt(document.getElementById('forzar-idtransaccion').value, 10);
    const res    = document.getElementById('forzar-res');

    if (!idtx || idtx < 1) {
      res.className = 'p-2 rounded small';
      res.style.cssText = 'background:rgba(255,62,29,.08);color:#ff3e1d;word-break:break-word';
      res.textContent = 'Ingresá un IDTRANSACCION válido.';
      return;
    }

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando…';
    res.className = 'd-none';

    fetch('{{ route("captura.forzar") }}', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
      body: JSON.stringify({ codigo_tienda: tienda, idtransaccion: idtx }),
    }).then(r => r.json()).then(d => {
      res.className = 'p-2 rounded small';
      if (d.ok) {
        res.style.cssText = 'background:rgba(113,221,55,.1);color:#2d8a3e;word-break:break-word';
        res.innerHTML = '<i class="bx bx-check-circle me-1"></i>' + d.mensaje;
        setTimeout(() => {
          modal.hide();
          cargarDatos();
        }, 1800);
      } else {
        res.style.cssText = 'background:rgba(255,62,29,.08);color:#c0392b;word-break:break-word';
        res.innerHTML = '<i class="bx bx-error-circle me-1"></i>' + d.mensaje;
      }
    }).catch(() => {
      res.className = 'p-2 rounded small';
      res.style.cssText = 'background:rgba(255,62,29,.08);color:#c0392b';
      res.textContent = 'Error de comunicación.';
    }).finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bx bx-send me-1"></i>Capturar';
    });
  });

  document.getElementById('forzar-idtransaccion').addEventListener('keydown', e => {
    if (e.key === 'Enter') document.getElementById('btn-forzar-submit').click();
  });
})();

// ── Detalle respuesta Bizlinks ────────────────────────────────
(function () {
  const modal = new bootstrap.Modal(document.getElementById('modal-biz-detalle'));
  const estadoLabel = { A:'Aceptado', R:'Rechazado', E:'Error', P:'Pendiente', L:'Cargado' };
  const estadoColor = { A:'#4ADE80', R:'#ff3e1d', E:'#ff3e1d', P:'#8592a3', L:'#8592a3' };

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-biz-detalle');
    if (!btn) return;

    const msg    = decodeURIComponent(btn.dataset.msg);
    const serie  = decodeURIComponent(btn.dataset.serie);
    const estado = btn.dataset.estado;

    document.getElementById('biz-serie').textContent = serie || '—';
    const lbl = document.getElementById('biz-estado-label');
    lbl.textContent = estadoLabel[estado] || estado;
    lbl.style.color = estadoColor[estado] || '#8592a3';

    const pre = document.getElementById('biz-pre');
    // Intentar formatear como JSON; si no, mostrar texto plano
    try {
      const obj = JSON.parse(msg);
      pre.textContent = JSON.stringify(obj, null, 2);
    } catch (_) {
      // Intentar limpiar y mostrar como texto estructurado
      pre.textContent = msg;
    }

    modal.show();
  });
})();

// ── Cola pendiente por tienda ─────────────────────────────────
(function () {
  fetch('{{ route("captura.cola-por-tienda") }}', {
    headers: { 'Accept': 'application/json' }
  }).then(r => r.json()).then(data => {
    document.querySelectorAll('[data-tienda-cola]').forEach(slot => {
      const info = data[slot.dataset.tiendaCola];
      if (!info) { slot.innerHTML = ''; return; }

      if (!info.ok) {
        const msg = info.error ? info.error.substring(0, 200) : 'Sin conexión a Soluflex';
        slot.innerHTML = `<span class="badge" style="background:rgba(255,62,29,.1);color:#ff3e1d;font-size:.55rem"
          title="${msg.replace(/"/g,'&quot;')}"><i class="bx bx-wifi-off"></i> error</span>`;
        return;
      }

      const n = info.cola;
      if (n === 0) {
        slot.innerHTML = `<span class="badge" style="background:rgba(74,222,128,.12);color:#4ADE80;font-size:.55rem"
          title="Sin pendientes"><i class="bx bx-check"></i> 0</span>`;
      } else {
        const color = n > 50 ? '#ff3e1d' : n > 10 ? '#ffab00' : '#696cff';
        const bg    = n > 50 ? 'rgba(255,62,29,.1)' : n > 10 ? 'rgba(255,171,0,.12)' : 'rgba(105,108,255,.1)';
        slot.innerHTML = `<span class="badge" style="background:${bg};color:${color};font-size:.55rem"
          title="${n} en cola Soluflex">${n} pendiente${n !== 1 ? 's' : ''}</span>`;
      }

      // Colorear la tienda chip si hay muchos pendientes
      const chip = document.querySelector(`.tc[data-tienda="${slot.dataset.tiendaCola}"]`);
      if (chip && n > 50) chip.style.borderLeftColor = '#ff3e1d';
      else if (chip && n > 10) chip.style.borderLeftColor = '#ffab00';
    });
  }).catch(() => {
    document.querySelectorAll('[data-tienda-cola]').forEach(s => {
      s.innerHTML = `<span class="badge" style="background:rgba(133,146,163,.1);color:#8592a3;font-size:.55rem">?</span>`;
    });
  });
})();
</script>
@endsection
