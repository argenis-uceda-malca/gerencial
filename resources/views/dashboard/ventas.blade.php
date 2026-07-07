@extends('layouts.base')
@section('title', 'Dashboard Ventas')

@section('contenido')
<style>
/* ── KPI Cards ── */
.kpi-card { border:none; box-shadow:0 2px 14px rgba(105,108,255,.1); transition:transform .2s,box-shadow .2s; }
.kpi-card:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(105,108,255,.18); }
.kpi-lbl { font-size:.7rem; text-transform:uppercase; letter-spacing:.6px; color:#697a8d; margin-bottom:.2rem; }
.kpi-val { font-size:1.55rem; font-weight:700; line-height:1.1; }
.kpi-sub { font-size:.75rem; margin-top:.15rem; }

/* ── Filters (sticky) ── */
.filter-card {
  background:linear-gradient(135deg,#f8f7ff 0%,#fff 100%);
  border:1px solid #e6e4ff;
  position:sticky;
  top:0;
  z-index:1000;
  box-shadow:0 4px 16px rgba(105,108,255,.12);
}
.btn-canal { border-radius:20px !important; padding:.25rem .85rem !important; font-size:.8rem !important; }
.btn-canal.active { box-shadow:0 4px 10px rgba(105,108,255,.35); }

/* ── Multi-select dropdown ── */
.ms-toggle-btn { min-width:150px; text-align:left; white-space:nowrap;
  overflow:hidden; text-overflow:ellipsis; max-width:200px; }
.ms-dropdown-menu { min-width:210px; box-shadow:0 6px 20px rgba(0,0,0,.12); }
.ms-list { max-height:200px; overflow-y:auto; }
.ms-list-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:2px; max-height:170px; }
.ms-item { display:flex; align-items:center; padding:.3rem .4rem; border-radius:5px;
  cursor:pointer; font-size:.83rem; white-space:nowrap; }
.ms-item:hover { background:#f0efff; }
.ms-item.hidden { display:none; }
.ms-ctrl-btn { font-size:.72rem; padding:.1rem .5rem; }

/* ── Chart range label ── */
.chart-range { font-size:.72rem; color:#697a8d; margin-top:.1rem; }

/* ── Section badges ── */
.section-badge { font-size:.72rem; padding:.3rem .75rem; border-radius:20px; }

/* ── AG Grid override ── */
.ag-theme-quartz {
  --ag-font-family: 'Public Sans', system-ui, sans-serif;
  --ag-font-size: 13px;
  --ag-header-background-color: #f5f5f9;
  --ag-row-hover-color: #f0efff;
  --ag-selected-row-background-color: #e7e7ff;
  --ag-border-color: #e7e7e7;
}
.pct-bar-wrap { display:flex; align-items:center; gap:7px; height:100%; }
.pct-bar-bg { width:55px; height:5px; background:#e8e8e8; border-radius:4px; overflow:hidden; flex-shrink:0; }
.pct-bar-fill { height:100%; border-radius:4px; }
.var-chip { font-size:.72rem; font-weight:600; padding:.1rem .45rem; border-radius:10px; }

/* ── Chart container min heights ── */
#chart-trend, #chart-meses { min-height:280px; }
#chart-radial { min-height:260px; }
#chart-treemap { min-height:280px; }

/* ══════════════════════════════════════════════════════════
   DARK MODE
   ══════════════════════════════════════════════════════════ */
[data-theme="dark"] .kpi-card {
  box-shadow:0 2px 14px rgba(0,0,0,.25);
}
[data-theme="dark"] .kpi-card:hover {
  box-shadow:0 8px 24px rgba(139,131,255,.15);
}
[data-theme="dark"] .kpi-lbl,
[data-theme="dark"] .chart-range {
  color:var(--dm-muted);
}
[data-theme="dark"] .filter-card {
  background:var(--dm-card-bg) !important;
  border-color:var(--dm-border) !important;
  box-shadow:0 4px 16px rgba(0,0,0,.25);
}
[data-theme="dark"] .btn-canal {
  border-color:var(--dm-border) !important;
  color:var(--dm-muted) !important;
  background:var(--dm-card-bg) !important;
}
[data-theme="dark"] .btn-canal:hover {
  border-color:var(--dm-primary,#696cff) !important;
  color:var(--dm-primary,#696cff) !important;
  background:rgba(105,108,255,.08) !important;
}
[data-theme="dark"] .btn-canal.active {
  border-color:var(--dm-primary,#696cff) !important;
  background:var(--dm-primary,#696cff) !important;
  color:#fff !important;
  box-shadow:0 4px 10px rgba(139,131,255,.25) !important;
}
[data-theme="dark"] .ms-toggle-btn {
  background:var(--dm-input-bg) !important;
  border-color:var(--dm-border) !important;
  color:var(--dm-ink) !important;
}
[data-theme="dark"] .ms-dropdown-menu {
  background:var(--dm-card-bg) !important;
  border-color:var(--dm-border) !important;
  box-shadow:0 6px 20px rgba(0,0,0,.35);
}
[data-theme="dark"] .ms-item {
  color:var(--dm-ink);
}
[data-theme="dark"] .ms-item:hover {
  background:var(--dm-surface-alt);
}
[data-theme="dark"] .ms-ctrl-btn {
  color:var(--dm-ink);
}
[data-theme="dark"] .ag-theme-quartz {
  --ag-background-color: var(--dm-surface);
  --ag-header-background-color: var(--dm-surface-alt);
  --ag-odd-row-background-color: #1C2135;
  --ag-row-hover-color: rgba(139,131,255,.08);
  --ag-selected-row-background-color: rgba(139,131,255,.15);
  --ag-border-color: var(--dm-border);
  --ag-foreground-color: var(--dm-ink);
  --ag-data-color: var(--dm-ink);
  --ag-secondary-foreground-color: var(--dm-muted);
  --ag-header-foreground-color: var(--dm-ink);
}
[data-theme="dark"] .ag-theme-quartz .ag-cell {
  color:var(--dm-ink);
}
[data-theme="dark"] .pct-bar-bg {
  background:#2E3450;
}
[data-theme="dark"] .section-badge {
  background:var(--dm-surface-alt);
  color:var(--dm-ink);
}
</style>

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- ── Header ── --}}
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
      <h4 class="mb-0 fw-bold">Dashboard <span class="text-muted fw-light">/ Ventas</span></h4>
      <small class="text-muted">Smart Brands S.A.C. · Actualizado: {{ now()->format('d M Y H:i') }}</small>
    </div>
    <span id="badge-periodo" class="badge bg-label-primary section-badge">—</span>
  </div>

  {{-- ── FILTROS ── --}}
  <div class="card filter-card mb-4">
    <div class="card-body py-3">
      <div class="row g-3 align-items-end">

        {{-- Período --}}
        <div class="col-auto">
          <div class="kpi-lbl mb-1">Período</div>
          <div class="btn-group" id="grp-period">
            <button class="btn btn-primary btn-sm" data-p="anual"   onclick="setPeriod('anual')">📅 Anual</button>
            <button class="btn btn-outline-primary btn-sm" data-p="mensual" onclick="setPeriod('mensual')">🗓 Mensual</button>
            <button class="btn btn-outline-primary btn-sm" data-p="semanal" onclick="setPeriod('semanal')">📆 Semanal</button>
          </div>
        </div>

        {{-- Canales --}}
        <div class="col-auto">
          <div class="kpi-lbl mb-1">Canal</div>
          <div class="d-flex gap-1" id="grp-canal">
            <button class="btn btn-sm btn-dark btn-canal active"    data-c="ALL"       onclick="toggleCanal(this)">Todos</button>
            <button class="btn btn-sm btn-outline-primary btn-canal" data-c="BOUTIQUES" onclick="toggleCanal(this)">Boutiques</button>
            <button class="btn btn-sm btn-outline-warning btn-canal" data-c="OUTLETS"   onclick="toggleCanal(this)">Outlets</button>
            <button class="btn btn-sm btn-outline-success btn-canal" data-c="WEB"       onclick="toggleCanal(this)">Web</button>
          </div>
        </div>

        {{-- Mes (anual) --}}
        <div class="col-auto" id="wrap-mes">
          <div class="kpi-lbl mb-1">Mes</div>
          <div class="dropdown">
            <button class="btn btn-sm btn-outline-primary dropdown-toggle ms-toggle-btn"
                    type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
              <span id="lbl-mes-btn">Todos los meses</span>
            </button>
            <div class="dropdown-menu p-2 ms-dropdown-menu">
              <input type="text" class="form-control form-control-sm mb-2"
                     placeholder="🔍 Buscar..." id="search-mes"
                     oninput="filterList('list-mes', this.value)">
              <div class="d-flex gap-1 mb-2">
                <button class="btn btn-sm btn-outline-secondary ms-ctrl-btn"
                        onclick="selectAll('mes', true)">Todos</button>
                <button class="btn btn-sm btn-outline-secondary ms-ctrl-btn"
                        onclick="selectAll('mes', false)">Ninguno</button>
              </div>
              <div id="list-mes" class="ms-list">
                @foreach($meses as $m)
                  <label class="ms-item" data-search="{{ strtolower($m['nom']) }}">
                    <input type="checkbox" class="form-check-input me-2 mes-chk"
                           value="{{ $m['n'] }}" data-nom="{{ $m['nom'] }}"
                           onchange="onMultiChange('mes')">
                    {{ $m['nom'] }}
                  </label>
                @endforeach
              </div>
            </div>
          </div>
        </div>

        {{-- Día equivalente --}}
        <div class="col-auto" id="wrap-dia">
          <div class="kpi-lbl mb-1">Día del mes</div>
          <div class="dropdown">
            <button class="btn btn-sm btn-outline-primary dropdown-toggle ms-toggle-btn"
                    type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
              <span id="lbl-dia-btn">Todos los días</span>
            </button>
            <div class="dropdown-menu p-2 ms-dropdown-menu">
              <input type="text" class="form-control form-control-sm mb-2"
                     placeholder="🔍 Buscar día..." id="search-dia"
                     oninput="filterList('list-dia', this.value)">
              <div class="d-flex gap-1 mb-2">
                <button class="btn btn-sm btn-outline-secondary ms-ctrl-btn"
                        onclick="selectAll('dia', true)">Todos</button>
                <button class="btn btn-sm btn-outline-secondary ms-ctrl-btn"
                        onclick="selectAll('dia', false)">Ninguno</button>
              </div>
              <div id="list-dia" class="ms-list ms-list-grid">
                {{-- populated by JS --}}
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- ── KPIs ── --}}
  <div class="row g-3 mb-4">
    <div class="col-xl col-md-4 col-6">
      <div class="card kpi-card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="kpi-lbl">Venta Neta</p>
              <div class="kpi-val text-primary" id="kpi-venta">—</div>
              <div class="kpi-sub text-muted" id="kpi-venta2"></div>
            </div>
            <span class="avatar-initial rounded-2 bg-label-primary p-2"><i class="bx bx-dollar-circle fs-4"></i></span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl col-md-4 col-6">
      <div class="card kpi-card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="kpi-lbl">Meta</p>
              <div class="kpi-val text-warning" id="kpi-meta">—</div>
              <div class="kpi-sub text-muted" id="kpi-meta2"></div>
            </div>
            <span class="avatar-initial rounded-2 bg-label-warning p-2"><i class="bx bx-target-lock fs-4"></i></span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl col-md-4 col-6">
      <div class="card kpi-card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="kpi-lbl">% Cumplimiento</p>
              <div class="kpi-val" id="kpi-pct">—</div>
              <div class="kpi-sub" id="kpi-pct2"></div>
            </div>
            <span class="avatar-initial rounded-2 bg-label-success p-2"><i class="bx bx-check-shield fs-4"></i></span>
          </div>
          <div style="height:4px;background:#e8e8e8;border-radius:4px;overflow:hidden;margin-top:.6rem">
            <div id="kpi-pct-bar" style="height:100%;width:0%;border-radius:4px;transition:width .7s ease"></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl col-md-4 col-6">
      <div class="card kpi-card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="kpi-lbl">Utilidad Bruta</p>
              <div class="kpi-val text-success" id="kpi-util">—</div>
              <div class="kpi-sub text-muted" id="kpi-util2"></div>
            </div>
            <span class="avatar-initial rounded-2 bg-label-success p-2"><i class="bx bx-trending-up fs-4"></i></span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl col-md-4 col-6">
      <div class="card kpi-card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="kpi-lbl">Foot Fall (mes)</p>
              <div class="kpi-val text-info" id="kpi-ff">—</div>
              <div class="kpi-sub" id="kpi-ff2"></div>
            </div>
            <span class="avatar-initial rounded-2 bg-label-info p-2"><i class="bx bx-walk fs-4"></i></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Charts fila 1 ── --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-8 col-12">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center pb-1">
          <div>
            <h5 class="mb-0">Tendencia Diaria — Venta vs Meta</h5>
            <div class="chart-range" id="range-trend"></div>
          </div>
          <div class="btn-group btn-group-sm" id="grp-trend">
            <button class="btn btn-sm btn-outline-secondary active" data-tv="30"  onclick="setTrendView('30',this)">30d</button>
            <button class="btn btn-sm btn-outline-secondary"        data-tv="60"  onclick="setTrendView('60',this)">60d</button>
            <button class="btn btn-sm btn-outline-secondary"        data-tv="all" onclick="setTrendView('all',this)">Todo</button>
          </div>
        </div>
        <div class="card-body p-2"><div id="chart-trend"></div></div>
      </div>
    </div>
    <div class="col-xl-4 col-12">
      <div class="card h-100">
        <div class="card-header pb-1">
          <h5 class="mb-0">Cumplimiento por Canal</h5>
          <div class="chart-range" id="range-radial"></div>
        </div>
        <div class="card-body d-flex align-items-center justify-content-center">
          <div id="chart-radial" style="width:100%"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Charts fila 2 ── --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-5 col-12">
      <div class="card h-100">
        <div class="card-header pb-1">
          <h5 class="mb-0">Ventas por Marca (Treemap)</h5>
          <div class="chart-range" id="range-treemap"></div>
        </div>
        <div class="card-body p-2"><div id="chart-treemap"></div></div>
      </div>
    </div>
    <div class="col-xl-7 col-12">
      <div class="card h-100">
        <div class="card-header pb-1">
          <h5 class="mb-0">Venta vs Meta por Mes</h5>
          <div class="chart-range" id="range-meses"></div>
        </div>
        <div class="card-body p-2"><div id="chart-meses"></div></div>
      </div>
    </div>
  </div>

  {{-- ── AG Grid Resumen ── --}}
  <div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <h5 class="mb-0">Resumen por Canal / Marca</h5>
        <div class="chart-range" id="range-resumen"></div>
      </div>
      <input type="text" class="form-control form-control-sm"
        placeholder="🔍 Buscar..." style="max-width:200px"
        oninput="if(gridResumen) gridResumen.setGridOption('quickFilterText', this.value)">
    </div>
    <div class="card-body p-0">
      <div id="grid-resumen" class="ag-theme-quartz" style="height:300px;width:100%"></div>
    </div>
  </div>

  {{-- ── AG Grid Tiendas ── --}}
  <div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <h5 class="mb-0">🏬 Detalle por Tienda <small class="text-muted fw-normal">(mes actual)</small></h5>
        <small class="text-muted" id="tiendas-count"></small>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <input type="text" class="form-control form-control-sm"
          placeholder="🔍 Buscar tienda..." style="max-width:200px"
          oninput="if(gridTiendas) gridTiendas.setGridOption('quickFilterText', this.value)">
        <button class="btn btn-sm btn-outline-secondary" type="button"
          data-bs-toggle="collapse" data-bs-target="#tiendas-wrap">
          <i class="bx bx-chevron-down"></i>
        </button>
      </div>
    </div>
    <div class="collapse show" id="tiendas-wrap">
      <div class="card-body p-0">
        <div id="grid-tiendas" class="ag-theme-quartz" style="height:430px;width:100%"></div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('footer')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.3.2/styles/ag-grid.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.3.2/styles/ag-theme-quartz.css">
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.3.2/dist/ag-grid-community.min.noStyle.js"></script>

<script>
// ════════════════════════════════════════════════════════
//  DATA FROM PHP
// ════════════════════════════════════════════════════════
const ALL_ROWS = @json($rows);
const TIENDAS  = @json($tiendas);
const FF       = @json($ff);
const TODAY    = '{{ $today }}';
const INI_ANUAL= '{{ $iniAnual }}';
const MES_INI  = '{{ $mesIni }}';

// ════════════════════════════════════════════════════════
//  STATE
// ════════════════════════════════════════════════════════
let activePeriod   = 'anual';
let activeCanales  = [];   // empty = all
let activeMeses    = [];   // empty = all months
let activeDias     = [];   // empty = all days
let trendView      = '30';
let chartTrend, chartRadial, chartTreemap, chartMeses;
let gridResumen, gridTiendas;

// ════════════════════════════════════════════════════════
//  FORMAT HELPERS
// ════════════════════════════════════════════════════════
const fmtS = v => {
  const s = v < 0 ? '-' : '';
  const a = Math.abs(v);
  if (a >= 1e6) return `${s}S/ ${(a/1e6).toFixed(2)}M`;
  if (a >= 1e3) return `${s}S/ ${(a/1e3).toFixed(1)}K`;
  return `${s}S/ ${a.toFixed(0)}`;
};
const fmtFull = v => {
  const s = v < 0 ? '-' : '';
  return `${s}S/ ${Math.abs(v).toLocaleString('es-PE',{minimumFractionDigits:0,maximumFractionDigits:0})}`;
};
const pctColor = v => v >= 100 ? '#71dd37' : v >= 80 ? '#ffab00' : '#ff3e1d';
const pctBgCls = v => v >= 100 ? '#d5f5d0' : v >= 80 ? '#fff3cd' : '#ffd5d0';

// ════════════════════════════════════════════════════════
//  DATE RANGE BY PERIOD
// ════════════════════════════════════════════════════════
function getDateRange() {
  const t = new Date(TODAY + 'T00:00:00');
  if (activePeriod === 'anual') return { ini: INI_ANUAL, fin: TODAY };
  if (activePeriod === 'mensual') {
    const y = t.getFullYear(), m = String(t.getMonth()+1).padStart(2,'0');
    return { ini: `${y}-${m}-01`, fin: TODAY };
  }
  // semanal — Monday of current week
  const day = t.getDay();
  const diff = day === 0 ? -6 : 1 - day;
  const mon = new Date(t); mon.setDate(t.getDate() + diff);
  const ini = mon.toISOString().slice(0,10);
  return { ini, fin: TODAY };
}

// ════════════════════════════════════════════════════════
//  FILTER ROWS
// ════════════════════════════════════════════════════════
function getFilteredRows() {
  const { ini, fin } = getDateRange();
  return ALL_ROWS.filter(r => {
    if (r.fecha < ini || r.fecha > fin) return false;
    if (activeCanales.length && !activeCanales.includes(r.canal)) return false;
    if (activeMeses.length && !activeMeses.includes(r.mes_n)) return false;
    if (activeDias.length  && !activeDias.includes(r.dia_eq)) return false;
    return true;
  });
}

// ════════════════════════════════════════════════════════
//  AGGREGATE HELPERS
// ════════════════════════════════════════════════════════
function aggByDate(rows) {
  const m = {};
  rows.forEach(r => {
    if (!m[r.fecha]) m[r.fecha] = { venta: 0, meta: 0 };
    m[r.fecha].venta += r.venta;
    m[r.fecha].meta  += r.meta;
  });
  return Object.entries(m).sort(([a],[b]) => a.localeCompare(b))
    .map(([f,v]) => ({ fecha: f, ...v }));
}

function aggByCanal(rows) {
  const m = { BOUTIQUES:{v:0,meta:0}, OUTLETS:{v:0,meta:0}, WEB:{v:0,meta:0} };
  rows.forEach(r => { if (m[r.canal]) { m[r.canal].v += r.venta; m[r.canal].meta += r.meta; } });
  return m;
}

function aggByMarca(rows) {
  const m = {};
  rows.forEach(r => { m[r.marca] = (m[r.marca] || 0) + r.venta; });
  return Object.entries(m).sort(([,a],[,b]) => b - a);
}

function aggByMes(rows) {
  const m = {};
  rows.forEach(r => {
    const k = r.mes_n;
    if (!m[k]) m[k] = { nom: r.mes, mes_n: r.mes_n, venta: 0, meta: 0 };
    m[k].venta += r.venta; m[k].meta += r.meta;
  });
  return Object.values(m).sort((a,b) => a.mes_n - b.mes_n);
}

function aggByKey(rows) {
  const m = {};
  rows.forEach(r => {
    const k = r.canal + '||' + r.marca;
    if (!m[k]) m[k] = { canal: r.canal, marca: r.marca, venta: 0, util: 0, meta: 0 };
    m[k].venta += r.venta; m[k].util += r.util; m[k].meta += r.meta;
  });
  return Object.values(m).map(r => ({
    ...r,
    pct: r.meta > 0 ? Math.round(r.venta/r.meta*1000)/10 : 0,
    gm:  r.venta > 0 ? Math.round(r.util/r.venta*1000)/10 : 0,
    var: r.meta > 0 ? Math.round((r.venta-r.meta)/r.meta*1000)/10 : 0,
  })).sort((a,b) => b.venta - a.venta);
}

// ════════════════════════════════════════════════════════
//  UPDATE KPIs
// ════════════════════════════════════════════════════════
function updateKPIs(rows) {
  const totV = rows.reduce((s,r) => s+r.venta, 0);
  const totM = rows.reduce((s,r) => s+r.meta, 0);
  const totU = rows.reduce((s,r) => s+r.util, 0);
  const pct  = totM > 0 ? totV/totM*100 : 0;
  const gm   = totV > 0 ? totU/totV*100 : 0;
  const col  = pctColor(pct);

  document.getElementById('kpi-venta').textContent = fmtS(totV);
  document.getElementById('kpi-venta2').textContent = fmtFull(totV);
  document.getElementById('kpi-meta').textContent  = fmtS(totM);
  document.getElementById('kpi-meta2').textContent = fmtFull(totM);

  const pctEl = document.getElementById('kpi-pct');
  pctEl.textContent = pct.toFixed(1) + '%';
  pctEl.style.color = col;
  document.getElementById('kpi-pct2').innerHTML =
    `<span style="color:${col}">${pct >= 100 ? '✓ En meta' : '✗ Bajo meta'}</span>`;
  document.getElementById('kpi-pct-bar').style.cssText =
    `height:100%;width:${Math.min(pct,100)}%;background:${col};border-radius:4px`;

  document.getElementById('kpi-util').textContent = fmtS(totU);
  document.getElementById('kpi-util2').textContent = `GM: ${gm.toFixed(1)}%`;

  // FF (fixed, mensual)
  const ffA = FF.act, ffV = FF.var;
  document.getElementById('kpi-ff').textContent = ffA > 0 ? (ffA/1000).toFixed(1)+'K' : 'N/D';
  document.getElementById('kpi-ff2').innerHTML  = ffA > 0
    ? `<span style="color:${ffV>=0?'#71dd37':'#ff3e1d'}">${ffV>=0?'↑':'↓'} ${Math.abs(ffV).toFixed(1)}% vs año ant.</span>`
    : '<span class="text-muted">Sin dato anterior</span>';

  // Badge período
  const { ini, fin } = getDateRange();
  document.getElementById('badge-periodo').textContent =
    `${ini} → ${fin}  ·  ${rows.length} registros`;
}

// ════════════════════════════════════════════════════════
//  APEX CHART CONFIGS (shared)
// ════════════════════════════════════════════════════════
const ANIM = { enabled: true, easing: 'easeinout', speed: 600 };
const GRID_OPT = { borderColor: '#f0efff', strokeDashArray: 4 };
const TOOLBAR = { show: false };
const FONT = 'Public Sans, system-ui, sans-serif';

// ════════════════════════════════════════════════════════
//  CHART: TREND (area + line)
// ════════════════════════════════════════════════════════
function buildTrendData(rows) {
  let byDate = aggByDate(rows);
  if (trendView === '30') byDate = byDate.slice(-30);
  else if (trendView === '60') byDate = byDate.slice(-60);
  return {
    cats:  byDate.map(d => d.fecha),
    venta: byDate.map(d => Math.round(d.venta)),
    meta:  byDate.map(d => Math.round(d.meta)),
  };
}

function initChartTrend(rows) {
  const { cats, venta, meta } = buildTrendData(rows);
  const opts = {
    chart: { type:'area', height:280, animations:ANIM, toolbar:TOOLBAR, fontFamily:FONT },
    series: [
      { name:'Venta Real', data: venta, type:'area' },
      { name:'Meta',       data: meta,  type:'line' },
    ],
    colors: ['#696cff','#ff3e1d'],
    fill: {
      type: ['gradient','solid'],
      gradient: { shade:'dark', type:'vertical', shadeIntensity:.4,
        gradientToColors:['#a5a8fe'], opacityFrom:.65, opacityTo:.05, stops:[0,100] },
    },
    stroke: { curve:'smooth', width:[2,2], dashArray:[0,5] },
    markers: { size:0 },
    xaxis: { categories:cats, labels:{ rotate:-30, style:{ fontSize:'11px' },
      formatter: v => v ? v.slice(5) : v }, axisBorder:{show:false} },
    yaxis: { labels:{ formatter: v => fmtS(v) } },
    grid: GRID_OPT,
    tooltip: { shared:true, intersect:false,
      y:{ formatter: v => fmtFull(v) } },
    legend: { show:true, position:'top' },
    dataLabels: { enabled:false },
  };
  chartTrend = new ApexCharts(document.getElementById('chart-trend'), opts);
  chartTrend.render();
}

function updateChartTrend(rows) {
  const { cats, venta, meta } = buildTrendData(rows);
  chartTrend.updateOptions({ xaxis:{ categories:cats } }, false, false);
  chartTrend.updateSeries([{ data:venta },{ data:meta }], false);
}

// ════════════════════════════════════════════════════════
//  CHART: RADIAL (cumplimiento por canal)
// ════════════════════════════════════════════════════════
function initChartRadial(rows) {
  const c = aggByCanal(rows);
  const labels = ['Boutiques','Outlets','Web'];
  const vals   = ['BOUTIQUES','OUTLETS','WEB'].map(k =>
    c[k].meta > 0 ? Math.min(Math.round(c[k].v/c[k].meta*1000)/10, 150) : 0
  );
  const opts = {
    chart: { type:'radialBar', height:270, animations:ANIM, fontFamily:FONT, toolbar:TOOLBAR },
    series: vals,
    labels,
    colors: ['#696cff','#ffab00','#71dd37'],
    plotOptions: { radialBar: {
      hollow: { size:'28%', background:'transparent' },
      track:  { background:'#f0efff', strokeWidth:'90%' },
      dataLabels: {
        name:  { fontSize:'12px', fontWeight:600 },
        value: { fontSize:'14px', fontWeight:700, formatter: v => v+'%' },
        total: { show:true, label:'Promedio',
          formatter: () => (vals.reduce((a,b)=>a+b,0)/vals.filter(v=>v>0).length).toFixed(1)+'%' }
      },
      startAngle:-135, endAngle:135,
    }},
    stroke: { lineCap:'round' },
    legend: { show:true, position:'bottom', fontSize:'12px' },
    tooltip: { y:{ formatter: v => v+'%' } },
  };
  chartRadial = new ApexCharts(document.getElementById('chart-radial'), opts);
  chartRadial.render();
}

function updateChartRadial(rows) {
  const c = aggByCanal(rows);
  const vals = ['BOUTIQUES','OUTLETS','WEB'].map(k =>
    c[k].meta > 0 ? Math.min(Math.round(c[k].v/c[k].meta*1000)/10, 150) : 0
  );
  chartRadial.updateSeries(vals, false);
}

// ════════════════════════════════════════════════════════
//  CHART: TREEMAP (ventas por marca)
// ════════════════════════════════════════════════════════
function initChartTreemap(rows) {
  const data = aggByMarca(rows).map(([x,value]) => ({ x, y: Math.round(value) }));
  const opts = {
    chart: { type:'treemap', height:280, animations:ANIM, fontFamily:FONT, toolbar:TOOLBAR },
    series: [{ data }],
    colors: ['#696cff','#03c3ec','#ffab00','#71dd37','#ff3e1d','#8592a3','#ff69b4'],
    plotOptions: { treemap: { distributed:true, enableShades:false } },
    dataLabels: {
      enabled:true, style:{ fontSize:'13px', fontWeight:700 },
      formatter: (txt, { value }) => [txt, fmtS(value)],
    },
    tooltip: { y:{ formatter: v => fmtFull(v) } },
    legend: { show:false },
  };
  chartTreemap = new ApexCharts(document.getElementById('chart-treemap'), opts);
  chartTreemap.render();
}

function updateChartTreemap(rows) {
  const data = aggByMarca(rows).map(([x,value]) => ({ x, y: Math.round(value) }));
  chartTreemap.updateSeries([{ data }], false);
}

// ════════════════════════════════════════════════════════
//  CHART: BAR meses (grouped)
// ════════════════════════════════════════════════════════
function initChartMeses(rows) {
  const mes = aggByMes(rows);
  const opts = {
    chart: { type:'bar', height:280, animations:ANIM, fontFamily:FONT, toolbar:TOOLBAR },
    series: [
      { name:'Venta Real', data: mes.map(m => Math.round(m.venta)) },
      { name:'Meta',       data: mes.map(m => Math.round(m.meta))  },
    ],
    colors: ['#696cff','#ff3e1d'],
    xaxis: { categories: mes.map(m => m.nom?.substring(0,3) || ''),
      labels:{ style:{ fontSize:'12px' } }, axisBorder:{show:false} },
    yaxis: { labels:{ formatter: v => fmtS(v) } },
    grid: GRID_OPT,
    plotOptions: { bar: { columnWidth:'55%', borderRadius:4, borderRadiusApplication:'end' } },
    dataLabels: { enabled:false },
    stroke: { show:true, width:0 },
    fill: {
      type:'gradient',
      gradient:{ shade:'light', type:'vertical', shadeIntensity:.15,
        opacityFrom:.9, opacityTo:.75 }
    },
    tooltip: { shared:true, intersect:false, y:{ formatter: v => fmtFull(v) } },
    legend: { show:true, position:'top' },
  };
  chartMeses = new ApexCharts(document.getElementById('chart-meses'), opts);
  chartMeses.render();
}

function updateChartMeses(rows) {
  const mes = aggByMes(rows);
  chartMeses.updateOptions({ xaxis:{ categories: mes.map(m => m.nom?.substring(0,3) || '') } }, false, false);
  chartMeses.updateSeries([
    { data: mes.map(m => Math.round(m.venta)) },
    { data: mes.map(m => Math.round(m.meta)) },
  ], false);
}

// ════════════════════════════════════════════════════════
//  TOTALS ROW HELPER
// ════════════════════════════════════════════════════════
function calcTotals(data, labelField) {
  if (!data.length) return [];
  const totV = data.reduce((s, r) => s + (r.venta || 0), 0);
  const totM = data.reduce((s, r) => s + (r.meta  || 0), 0);
  const totU = data.reduce((s, r) => s + (r.util  || 0), 0);
  const pct  = totM > 0 ? Math.round(totV / totM * 1000) / 10 : 0;
  const gm   = totV > 0 ? Math.round(totU / totV * 1000) / 10 : 0;
  const varr = totM > 0 ? Math.round((totV - totM) / totM * 1000) / 10 : 0;
  const row  = { venta: Math.round(totV*100)/100, meta: Math.round(totM*100)/100,
                 util: Math.round(totU*100)/100, pct, gm, var: varr,
                 canal: '', marca: '', sucursal: '' };
  row[labelField] = '▶ TOTAL';
  return [row];
}

const totalRowStyle = params =>
  params.node.rowPinned === 'bottom'
    ? { fontWeight:'700', background:'var(--dm-surface-alt)', borderTop:'2px solid var(--dm-primary,#696cff)' }
    : null;

// ════════════════════════════════════════════════════════
//  AG GRID — CELL RENDERERS
// ════════════════════════════════════════════════════════
function canalRenderer(p) {
  const cls = { BOUTIQUES:'bg-label-primary', OUTLETS:'bg-label-warning', WEB:'bg-label-success' };
  return `<span class="badge ${cls[p.value] || 'bg-label-secondary'}">${p.value}</span>`;
}

function pctRenderer(p) {
  const v = p.value || 0;
  const col = pctColor(v);
  const bg  = pctBgCls(v);
  return `<div class="pct-bar-wrap">
    <div class="pct-bar-bg"><div class="pct-bar-fill" style="width:${Math.min(v,100)}%;background:${col}"></div></div>
    <span style="color:${col};font-weight:600;font-size:.82rem">${v.toFixed(1)}%</span>
  </div>`;
}

function varRenderer(p) {
  const v = p.value || 0;
  const col = v >= 0 ? '#71dd37' : '#ff3e1d';
  const bg  = v >= 0 ? '#d5f5d0' : '#ffd5d0';
  return `<span class="var-chip" style="color:${col};background:${bg}">${v>=0?'+':''}${v.toFixed(1)}%</span>`;
}

function gmRenderer(p) {
  const v = p.value || 0;
  const col = v >= 30 ? '#71dd37' : v >= 20 ? '#ffab00' : '#ff3e1d';
  return `<span style="color:${col};font-weight:600">${v.toFixed(1)}%</span>`;
}

function moneyRenderer(p) {
  return `<span style="font-variant-numeric:tabular-nums">${fmtFull(p.value||0)}</span>`;
}

// ════════════════════════════════════════════════════════
//  AG GRID — RESUMEN
// ════════════════════════════════════════════════════════
function initGridResumen(data) {
  const colDefs = [
    { field:'canal',  headerName:'Canal',       width:120, cellRenderer:canalRenderer,
      sortable:true, filter:true },
    { field:'marca',  headerName:'Marca',       width:90,  sortable:true, filter:true,
      cellStyle:{ fontWeight:600 } },
    { field:'venta',  headerName:'Venta Real',  width:140, sortable:true,
      cellRenderer:moneyRenderer, type:'numericColumn' },
    { field:'meta',   headerName:'Meta',        width:140, sortable:true,
      cellRenderer:moneyRenderer, type:'numericColumn' },
    { field:'pct',    headerName:'% Cumpl.',    width:150, sortable:true,
      cellRenderer:pctRenderer },
    { field:'util',   headerName:'Utilidad',    width:130, sortable:true,
      cellRenderer:moneyRenderer, type:'numericColumn' },
    { field:'gm',     headerName:'GM%',         width:90,  sortable:true,
      cellRenderer:gmRenderer, type:'numericColumn' },
    { field:'var',    headerName:'Var Meta',    width:110, sortable:true,
      cellRenderer:varRenderer, type:'numericColumn' },
  ];
  gridResumen = agGrid.createGrid(document.getElementById('grid-resumen'), {
    columnDefs: colDefs,
    rowData: data,
    pinnedBottomRowData: calcTotals(data, 'canal'),
    defaultColDef:{ resizable:true, sortable:true },
    animateRows:true,
    rowHeight:42,
    headerHeight:44,
    suppressCellFocus:true,
    rowSelection:'single',
    getRowStyle: totalRowStyle,
  });
}

function updateGridResumen(rows) {
  const data = aggByKey(rows);
  if (!gridResumen) return;
  gridResumen.setGridOption('rowData', data);
  gridResumen.setGridOption('pinnedBottomRowData', calcTotals(data, 'canal'));
}

// ════════════════════════════════════════════════════════
//  AG GRID — TIENDAS (dinámico vía AJAX)
// ════════════════════════════════════════════════════════
let tiendasDebounce = null;

function initGridTiendas() {
  const colDefs = [
    { field:'sucursal', headerName:'Sucursal',  flex:2, sortable:true, filter:true,
      cellStyle:{ fontWeight:600 } },
    { field:'canal',    headerName:'Canal',     width:120, cellRenderer:canalRenderer,
      sortable:true, filter:true },
    { field:'marca',    headerName:'Marca',     width:90,  sortable:true, filter:true },
    { field:'venta',    headerName:'Venta Real',width:135, sortable:true,
      cellRenderer:moneyRenderer, type:'numericColumn' },
    { field:'meta',     headerName:'Meta',      width:135, sortable:true,
      cellRenderer:moneyRenderer, type:'numericColumn' },
    { field:'pct',      headerName:'% Cumpl.',  width:150, sortable:true,
      cellRenderer:pctRenderer },
    { field:'util',     headerName:'Utilidad',  width:130, sortable:true,
      cellRenderer:moneyRenderer, type:'numericColumn' },
    { field:'gm',       headerName:'GM%',       width:90,  sortable:true,
      cellRenderer:gmRenderer, type:'numericColumn' },
    { field:'var',      headerName:'Var Meta',  width:110, sortable:true,
      cellRenderer:varRenderer, type:'numericColumn' },
  ];
  gridTiendas = agGrid.createGrid(document.getElementById('grid-tiendas'), {
    columnDefs: colDefs,
    rowData: [],
    pinnedBottomRowData: [],
    defaultColDef:{ resizable:true, sortable:true },
    animateRows:true,
    rowHeight:42,
    headerHeight:44,
    suppressCellFocus:true,
    pagination:true,
    paginationPageSize:20,
    paginationPageSizeSelector:[10,20,50,100],
    getRowStyle: totalRowStyle,
    overlayLoadingTemplate: '<span class="text-muted">Cargando tiendas...</span>',
    overlayNoRowsTemplate:  '<span class="text-muted">Sin datos para el filtro seleccionado</span>',
  });
}

function fetchTiendas() {
  clearTimeout(tiendasDebounce);
  tiendasDebounce = setTimeout(async () => {
    if (!gridTiendas) return;
    gridTiendas.showLoadingOverlay();

    const { ini, fin } = getDateRange();
    const params = new URLSearchParams({ ini, fin });
    activeCanales.forEach(c => params.append('canales[]', c));
    activeMeses.forEach(m  => params.append('meses[]', m));
    activeDias.forEach(d   => params.append('dias[]', d));

    try {
      const resp = await fetch(`/dashboard/ventas/tiendas?${params}`);
      const data = await resp.json();
      gridTiendas.setGridOption('rowData', data);
      gridTiendas.setGridOption('pinnedBottomRowData', calcTotals(data, 'sucursal'));
      document.getElementById('tiendas-count').textContent =
        `${data.length} locales · ${ini} → ${fin}`;
    } catch(e) {
      console.error('fetchTiendas:', e);
      gridTiendas.showNoRowsOverlay();
    }
  }, 350);
}

// ════════════════════════════════════════════════════════
//  RANGE LABELS EN CADA GRÁFICO
// ════════════════════════════════════════════════════════
function updateRangeLabels(rows) {
  try {
    // Rango efectivo: min/max fecha de las filas ya filtradas
    let ini, fin;
    if (rows && rows.length) {
      ini = rows.reduce((m, r) => r.fecha < m ? r.fecha : m, rows[0].fecha);
      fin = rows.reduce((m, r) => r.fecha > m ? r.fecha : m, rows[0].fecha);
    } else {
      const r = getDateRange();
      ini = r.ini; fin = r.fin;
    }
    const parts = [`📅 ${ini} → ${fin}`];
    if (activeCanales.length) parts.push(activeCanales.join(' + '));
    if (activeMeses.length) {
      const noms = [...document.querySelectorAll('.mes-chk:checked')].map(c => c.dataset.nom.substring(0,3));
      parts.push(noms.join(', '));
    }
    if (activeDias.length) {
      parts.push(activeDias.length <= 5 ? `días: ${activeDias.join(',')}` : `${activeDias.length} días`);
    }
    const lbl = parts.join(' · ');
    ['trend','radial','treemap','meses','resumen'].forEach(id => {
      const el = document.getElementById('range-' + id);
      if (el) el.textContent = lbl;
    });
  } catch(e) { console.warn('updateRangeLabels:', e); }
}

// ════════════════════════════════════════════════════════
//  APPLY ALL FILTERS
// ════════════════════════════════════════════════════════
function applyFilters(init = false) {
  const rows = getFilteredRows();
  updateKPIs(rows);
  updateRangeLabels(rows);

  if (init) {
    initChartTrend(rows);
    initChartRadial(rows);
    initChartTreemap(rows);
    initChartMeses(rows);
    initGridResumen(aggByKey(rows));
    initGridTiendas();
  } else {
    updateChartTrend(rows);
    updateChartRadial(rows);
    updateChartTreemap(rows);
    updateChartMeses(rows);
    updateGridResumen(rows);
  }

  fetchTiendas();

  // Mostrar filtros de mes/día solo en período Anual
  const isAnual = activePeriod === 'anual';
  document.getElementById('wrap-mes').style.display = isAnual ? '' : 'none';
  document.getElementById('wrap-dia').style.display = isAnual ? '' : 'none';
}

// ════════════════════════════════════════════════════════
//  CONTROLS
// ════════════════════════════════════════════════════════
function setPeriod(p) {
  activePeriod = p;
  document.querySelectorAll('#grp-period button').forEach(b => {
    b.classList.toggle('btn-primary', b.dataset.p === p);
    b.classList.toggle('btn-outline-primary', b.dataset.p !== p);
  });
  if (p !== 'anual') {
    activeMeses = []; activeDias = [];
    document.querySelectorAll('.mes-chk, .dia-chk').forEach(c => c.checked = false);
    document.getElementById('lbl-mes-btn').textContent = 'Todos los meses';
    document.getElementById('lbl-dia-btn').textContent = 'Todos los días';
  }
  applyFilters();
}

function toggleCanal(btn) {
  const c = btn.dataset.c;
  if (c === 'ALL') {
    activeCanales = [];
    document.querySelectorAll('#grp-canal button').forEach(b => {
      const isAll = b.dataset.c === 'ALL';
      b.classList.toggle('active', isAll);
      // toggle solid vs outline
      const cols = { BOUTIQUES:'primary', OUTLETS:'warning', WEB:'success' };
      const col  = cols[b.dataset.c] || 'dark';
      b.className = `btn btn-sm btn-${isAll ? 'dark' : 'outline-'+col} btn-canal${isAll ? ' active' : ''}`;
    });
  } else {
    // deactivate ALL
    const allBtn = document.querySelector('#grp-canal button[data-c="ALL"]');
    allBtn.classList.remove('active');
    allBtn.className = 'btn btn-sm btn-outline-dark btn-canal';

    btn.classList.toggle('active');
    const cols  = { BOUTIQUES:'primary', OUTLETS:'warning', WEB:'success' };
    const col   = cols[c] || 'secondary';
    const on    = btn.classList.contains('active');
    btn.className = `btn btn-sm btn-${on ? col : 'outline-'+col} btn-canal${on ? ' active' : ''}`;

    activeCanales = [...document.querySelectorAll('#grp-canal button.active')]
      .map(b => b.dataset.c).filter(x => x !== 'ALL');

    if (!activeCanales.length) {
      // nothing selected → all
      activeCanales = [];
      allBtn.classList.add('active');
      allBtn.className = 'btn btn-sm btn-dark btn-canal active';
    }
  }
  applyFilters();
}

// ════════════════════════════════════════════════════════
//  MULTI-SELECT HELPERS
// ════════════════════════════════════════════════════════
function filterList(listId, q) {
  document.querySelectorAll(`#${listId} .ms-item`).forEach(el => {
    const match = (el.dataset.search || el.textContent).toLowerCase().includes(q.toLowerCase());
    el.classList.toggle('hidden', !match);
  });
}

function selectAll(type, checked) {
  document.querySelectorAll(`.${type}-chk`).forEach(c => { c.checked = checked; });
  onMultiChange(type);
}

function onMultiChange(type) {
  if (type === 'mes') {
    activeMeses = [...document.querySelectorAll('.mes-chk:checked')].map(c => parseInt(c.value));
    const sel = [...document.querySelectorAll('.mes-chk:checked')];
    document.getElementById('lbl-mes-btn').textContent = sel.length
      ? sel.map(c => c.dataset.nom.substring(0,3)).join(', ')
      : 'Todos los meses';
  } else {
    activeDias = [...document.querySelectorAll('.dia-chk:checked')].map(c => parseInt(c.value));
    document.getElementById('lbl-dia-btn').textContent = activeDias.length
      ? (activeDias.length <= 5 ? activeDias.join(', ') : `${activeDias.length} días`)
      : 'Todos los días';
  }
  applyFilters();
}

function initDiaList() {
  const dias = [...new Set(ALL_ROWS.map(r => r.dia_eq))].sort((a,b) => a-b);
  const container = document.getElementById('list-dia');
  container.innerHTML = dias.map(d =>
    `<label class="ms-item" data-search="${d}">
      <input type="checkbox" class="form-check-input dia-chk" value="${d}"
             data-nom="${d}" onchange="onMultiChange('dia')"> ${d}
    </label>`
  ).join('');
}


function setTrendView(v, btn) {
  trendView = v;
  document.querySelectorAll('#grp-trend button').forEach(b => {
    b.classList.toggle('active', b.dataset.tv === v);
  });
  updateChartTrend(getFilteredRows());
}

// ════════════════════════════════════════════════════════
//  INIT
// ════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
  initDiaList();
  applyFilters(true);
});
</script>
@endsection
