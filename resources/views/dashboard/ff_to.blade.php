@extends('layouts.base')
@section('title', 'Follow-up FF vs TO')

@push('styles')
<style>
/* ══════════════════════════════════════════════════════════
   PALETA — base PPT "Follow-up Compacto" + tratamiento moderno
══════════════════════════════════════════════════════════ */
:root {
  --c-ink:      #0F172A;
  --c-slate:    #1E293B;
  --c-muted:    #64748B;
  --c-muted2:   #94A3B8;
  --c-bg:       #F1F2F8;
  --c-card:     #FFFFFF;
  --c-border:   #E4E5EC;
  /* Primario de la web */
  --c-teal:     #696cff;
  --c-teal2:    #8f91ff;
  --c-teal-lt:  rgba(105,108,255,.16);
  --c-primary-dk:#4b4eea;
  --c-green:    #16a34a;
  --c-green-bg: #dcfce7;
  --c-green-tx: #15803d;
  --c-red:      #ef4444;
  --c-red-dark: #dc2626;
  --c-red-bg:   #fee2e2;
  --c-red-tx:   #991b1b;
  --c-amber:    #f59e0b;
  --c-amber-dk: #b45309;
  --c-blue:     #5a5fc7;
  --c-blue-lt:  #c7c9ff;
  --c-blue-bg:  #f2f2ff;
  --radius:     16px;
  --radius-sm:  11px;
  --ease:       cubic-bezier(.4,0,.2,1);
  --font:       inherit;
  --font-num:   inherit;
  --shadow:     0 1px 3px rgba(67,70,120,.06), 0 8px 24px rgba(67,70,120,.06);
  --shadow-lg:  0 12px 32px rgba(105,108,255,.16);
}

body { background:var(--c-bg) !important; }
.ffto-root { font-family:var(--font); }

@keyframes ffUp   { from{opacity:0; transform:translateY(10px);} to{opacity:1; transform:translateY(0);} }
@keyframes ffGrow { from{transform:scaleY(0);} to{transform:scaleY(1);} }
.ff-anim { animation: ffUp .4s var(--ease) both; }
.ff-anim:nth-child(2){ animation-delay:.05s; } .ff-anim:nth-child(3){ animation-delay:.1s; }
.ff-anim:nth-child(4){ animation-delay:.15s; }

/* ══ HEADER — gradiente teal moderno (reemplaza el negro) ══ */
.ff-header {
  background:linear-gradient(120deg,#3f4658 0%,#4b5266 55%,#5b6377 100%);
  border-radius:var(--radius); padding:18px 24px; margin-bottom:18px;
  display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px;
  box-shadow:0 8px 24px rgba(51,58,74,.22); position:relative; overflow:hidden;
}
.ff-header::after {
  content:''; position:absolute; right:-40px; top:-40px; width:220px; height:220px;
  background:radial-gradient(circle,rgba(255,255,255,.1),transparent 70%); pointer-events:none;
}
.ff-header h1 { color:#fff; font-size:1.22rem; font-weight:800; margin:0; letter-spacing:.01em; }
.ff-header h1 i { color:var(--c-teal2); }
.ff-header .ff-sub { color:rgba(255,255,255,.8); font-size:.74rem; margin-top:3px; }

.periodo-pill {
  font-size:.74rem; font-weight:600; padding:6px 14px; border-radius:20px;
  color:#fff !important; border:1.5px solid rgba(255,255,255,.35) !important;
  background:rgba(255,255,255,.12) !important; transition:all .18s var(--ease); cursor:pointer;
}
.periodo-pill:hover  { background:rgba(255,255,255,.22) !important; border-color:#fff !important; }
.periodo-pill.active { background:#fff !important; color:var(--c-teal) !important; border-color:#fff !important;
                       font-weight:700; box-shadow:0 3px 10px rgba(0,0,0,.14); }

/* ══ KPI CARDS — icono grande centrado (estilo slide 2) ══ */
.kpi-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:16px; }
@media (max-width:1200px){ .kpi-grid{ grid-template-columns:repeat(3,1fr);} }
@media (max-width:640px){  .kpi-grid{ grid-template-columns:repeat(2,1fr);} }
.kpi-card {
  background:var(--c-card); border-radius:var(--radius); padding:20px 16px 18px;
  box-shadow:var(--shadow); text-align:center; position:relative; overflow:hidden;
  transition:transform .2s var(--ease), box-shadow .2s var(--ease); border:1px solid var(--c-border);
}
.kpi-card:hover { transform:translateY(-4px); box-shadow:var(--shadow-lg); }
.kpi-card::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; background:var(--c-teal); }
.kpi-icon {
  width:56px; height:56px; margin:0 auto 12px; border-radius:16px;
  background:var(--c-teal-lt); color:var(--c-teal); display:flex; align-items:center; justify-content:center;
  font-size:1.9rem;
}
.kpi-label { font-size:.72rem; font-weight:700; color:var(--c-muted); text-transform:uppercase; letter-spacing:.05em; }
.kpi-value { font-family:var(--font-num); font-size:2rem; font-weight:700; color:var(--c-ink); line-height:1.1; margin:4px 0 2px; }
.kpi-sub   { font-size:.7rem; color:var(--c-muted2); }
.kpi-var   { display:inline-flex; align-items:center; gap:3px; font-size:.78rem; font-weight:700;
             padding:3px 12px; border-radius:11px; margin-top:9px; }
.kpi-var.pos { color:var(--c-green-tx); background:var(--c-green-bg); }
.kpi-var.neg { color:var(--c-red-tx);   background:var(--c-red-bg); }
.kpi-tag { font-size:.64rem; color:var(--c-muted2); text-transform:uppercase; letter-spacing:.06em; margin-top:8px; font-weight:600; }

/* ══ CARD genérica ══ */
.ff-card { background:var(--c-card); border-radius:var(--radius); box-shadow:var(--shadow); border:1px solid var(--c-border); overflow:hidden; }
.ff-card-head { padding:16px 20px 4px; }
.ff-card-title { font-size:.95rem; font-weight:800; color:var(--c-ink); }
.ff-card-desc  { font-size:.74rem; color:var(--c-muted); margin-top:2px; }
.ff-card-body  { padding:16px 20px 20px; }

/* Leyenda */
.ff-legend { display:flex; gap:16px; align-items:center; margin-top:8px; }
.ff-legend span { display:inline-flex; align-items:center; gap:6px; font-size:.72rem; font-weight:600; color:var(--c-muted); }
.ff-legend i.sw { width:12px; height:12px; border-radius:3px; display:inline-block; }

/* ══ GAP CHART — barras VERTICALES pareadas (slide 3) ══ */
.gap-scroll { overflow-x:auto; padding-bottom:6px; }
.gap-scroll::-webkit-scrollbar { height:7px; }
.gap-scroll::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
.gap-chart {
  display:flex; align-items:flex-end; gap:16px; height:260px; padding:26px 6px 0; min-width:min-content;
}
.gap-col { display:flex; flex-direction:column; align-items:center; flex:0 0 auto; height:100%; justify-content:flex-end; }
.gap-bars { display:flex; align-items:flex-end; gap:4px; height:200px; }
.gap-bar { width:16px; border-radius:5px 5px 0 0; position:relative; transform-origin:bottom; animation:ffGrow .5s var(--ease) both; }
.gap-bar.y25 { background:var(--c-blue-lt); }
.gap-bar.up  { background:linear-gradient(180deg,var(--c-teal2),var(--c-teal)); }
.gap-bar.down{ background:linear-gradient(180deg,#F87171,var(--c-red)); }
.gap-bar .bar-val { position:absolute; top:-17px; left:50%; transform:translateX(-50%);
                    font-size:.6rem; font-weight:700; color:var(--c-ink); white-space:nowrap; }
.gap-col .gap-name { margin-top:9px; font-size:.62rem; font-weight:600; color:var(--c-muted);
                     max-width:74px; text-align:center; line-height:1.15; height:26px; overflow:hidden; }
.gap-col .gap-delta { font-size:.64rem; font-weight:800; margin-top:2px; }
.gap-col .gap-delta.up { color:var(--c-teal); } .gap-col .gap-delta.down { color:var(--c-red-dark); }

/* ══ DETALLE POR SUCURSAL — carrusel (2 por vista) ══ */
.store-carousel-wrap { position:relative; }
.store-carousel {
  display:flex; gap:18px; overflow-x:auto; scroll-snap-type:x mandatory; scroll-behavior:smooth;
  padding:4px 2px 12px; -ms-overflow-style:none; scrollbar-width:none;
}
.store-carousel::-webkit-scrollbar { display:none; }
.store-card {
  flex:0 0 calc(50% - 9px); scroll-snap-align:start;
  background:var(--c-card); border-radius:var(--radius); box-shadow:var(--shadow); border:1px solid var(--c-border);
  overflow:hidden; transition:transform .2s var(--ease), box-shadow .2s var(--ease);
}
@media (max-width:900px){ .store-card{ flex-basis:88%; } }
.store-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-lg); }
.store-card.up   { border-top:4px solid var(--c-green); }
.store-card.down { border-top:4px solid var(--c-red); }
.store-card.flat { border-top:4px solid var(--c-muted2); }

.store-nav {
  position:absolute; top:50%; transform:translateY(-50%); z-index:5; width:38px; height:38px; border-radius:50%;
  border:1px solid var(--c-border); background:#fff; box-shadow:0 4px 14px rgba(67,70,120,.16);
  color:var(--c-teal); display:flex; align-items:center; justify-content:center; cursor:pointer;
  font-size:1.3rem; transition:all .15s var(--ease);
}
.store-nav:hover { background:var(--c-teal); color:#fff; }
.store-nav.prev { left:-8px; } .store-nav.next { right:-8px; }

.store-top { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:16px 20px; }
.store-top.up   { background:linear-gradient(180deg,var(--c-green-bg),#fff 88%); }
.store-top.down { background:linear-gradient(180deg,var(--c-red-bg),#fff 88%); }
.store-name { font-size:1.05rem; font-weight:800; color:var(--c-ink); line-height:1.2; }
.store-canal { font-size:.72rem; color:var(--c-muted); margin-top:2px; }
.store-badge { font-size:.7rem; font-weight:800; letter-spacing:.03em; padding:6px 13px; border-radius:20px;
               text-transform:uppercase; white-space:nowrap; display:inline-flex; align-items:center; gap:4px; }
.store-badge.up   { background:var(--c-green); color:#fff; box-shadow:0 3px 10px rgba(22,163,74,.35); }
.store-badge.down { background:var(--c-red);   color:#fff; box-shadow:0 3px 10px rgba(239,68,68,.35); }
.store-badge.flat { background:var(--c-muted2); color:#fff; }

.store-stats { display:grid; grid-template-columns:repeat(5,1fr); gap:0; }
.stat { padding:12px 8px; text-align:center; border-right:1px solid var(--c-border); }
.stat:last-child { border-right:none; }
.stat-l { font-size:.63rem; font-weight:700; color:var(--c-muted2); text-transform:uppercase; letter-spacing:.04em; }
.stat-v { font-family:var(--font-num); font-size:1.05rem; font-weight:700; color:var(--c-ink); margin-top:3px; }
.stat-x { font-size:.66rem; font-weight:800; margin-top:2px; }
.stat-x.pos { color:var(--c-green); } .stat-x.neg { color:var(--c-red); } .stat-x.na { color:var(--c-muted2); font-weight:500; }

/* Barras horizontales 2026 vs 2025 (ratio TO/FF) */
.store-bars { padding:14px 20px 4px; }
.store-bars-title { font-size:.68rem; font-weight:800; color:var(--c-slate); text-transform:uppercase; letter-spacing:.04em; margin-bottom:10px; }
.hbar-row { display:flex; align-items:center; gap:10px; margin-bottom:9px; }
.hbar-yr { width:38px; font-size:.72rem; font-weight:700; color:var(--c-muted); flex-shrink:0; }
.hbar-track { flex:1; height:22px; background:#F1F5F9; border-radius:7px; overflow:hidden; }
.hbar-fill { height:100%; border-radius:7px; display:flex; align-items:center; justify-content:flex-end;
             padding-right:8px; color:#fff; font-size:.72rem; font-weight:700; transition:width .6s var(--ease); min-width:34px; }
.hbar-fill.y25 { background:var(--c-blue-lt); color:var(--c-blue); }
.hbar-fill.y26up   { background:linear-gradient(90deg,var(--c-teal),var(--c-teal2)); }
.hbar-fill.y26down { background:linear-gradient(90deg,var(--c-red-dark),var(--c-red)); }

.store-foot { display:flex; align-items:center; justify-content:space-between; gap:10px;
              padding:12px 20px 16px; }
.store-gap { display:flex; align-items:baseline; gap:8px; }
.store-gap .g-lbl { font-size:.66rem; font-weight:700; color:var(--c-muted2); text-transform:uppercase; }
.store-gap .g-val { font-family:var(--font-num); font-size:1.3rem; font-weight:700; }
.store-gap .g-val.pos { color:var(--c-teal); } .store-gap .g-val.neg { color:var(--c-red-dark); }
.store-gap .g-pct { font-size:.72rem; font-weight:700; }
.store-gap .g-pct.pos { color:var(--c-green); } .store-gap .g-pct.neg { color:var(--c-red); }
.store-summary { font-size:.72rem; color:var(--c-muted); text-align:right; max-width:48%; line-height:1.35; }

/* ══ RANKING (slide 28) ══ */
.rank-head { font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; padding:12px 16px; color:#fff; display:flex; align-items:center; gap:6px; }
.rank-head.up { background:linear-gradient(120deg,var(--c-teal),var(--c-teal2)); }
.rank-head.down { background:linear-gradient(120deg,var(--c-red-dark),var(--c-red)); }
.rank-head.growth { background:linear-gradient(120deg,var(--c-amber-dk),var(--c-amber)); }
.rank-item { display:flex; align-items:center; gap:12px; padding:11px 16px; border-bottom:1px solid var(--c-border); font-size:.8rem; }
.rank-item:last-child { border-bottom:none; }
.rank-num { width:24px; height:24px; border-radius:50%; background:#F1F5F9; color:var(--c-slate);
            font-size:.72rem; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.rank-item:first-child .rank-num { background:var(--c-amber); color:#fff; }
.rank-name { flex:1; font-weight:700; color:var(--c-ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.rank-canal { font-size:.66rem; color:var(--c-muted2); font-weight:500; }
.rank-val { font-family:var(--font-num); font-weight:700; font-size:.9rem; }

.ff-loader { display:none; position:absolute; inset:0; background:rgba(241,245,249,.92); z-index:10;
             align-items:center; justify-content:center; border-radius:var(--radius); }
.ff-loader.show { display:flex; }

@media (max-width:768px){
  .container-p-y{ padding-left:.6rem !important; padding-right:.6rem !important; }
  .ff-header h1{ font-size:1.05rem; }
  .store-summary{ display:none; }
}

/* ══════════════════════════════════════════════════════════
   DARK MODE
   ══════════════════════════════════════════════════════════ */
[data-theme="dark"] {
  --c-ink:      #E2E6F0;
  --c-slate:    #D1D5E0;
  --c-muted:    #8B90A8;
  --c-muted2:   #6B7088;
  --c-bg:       #141824;
  --c-card:     #1F2438;
  --c-border:   #2E3450;
  --c-teal:     #8B83FF;
  --c-teal2:    #A9A3FF;
  --c-teal-lt:  rgba(139,131,255,.18);
  --c-primary-dk:#6B63E0;
  --c-green:    #4ADE80;
  --c-green-bg: #1A3D28;
  --c-green-tx: #4ADE80;
  --c-red:      #F87171;
  --c-red-dark: #EF4444;
  --c-red-bg:   #3D2028;
  --c-red-tx:   #F87171;
  --c-amber:    #FBBF24;
  --c-amber-dk: #F59E0B;
  --c-blue:     #8B83FF;
  --c-blue-lt:  #3A3860;
  --c-blue-bg:  #25224F;
  --shadow:     0 1px 3px rgba(0,0,0,.25), 0 8px 24px rgba(0,0,0,.3);
  --shadow-lg:  0 12px 32px rgba(139,131,255,.2);
}

[data-theme="dark"] .ff-header {
  background:linear-gradient(120deg,#252A3E 0%,#2D324A 55%,#383D55 100%);
  box-shadow:0 8px 24px rgba(0,0,0,.35);
}
[data-theme="dark"] .ff-header h1 { color:#E2E6F0; }
[data-theme="dark"] .ff-header h1 i { color:var(--c-teal2); }
[data-theme="dark"] .ff-header .ff-sub { color:rgba(226,230,240,.7); }
[data-theme="dark"] .periodo-pill {
  color:#E2E6F0 !important;
  border-color:rgba(226,230,240,.25) !important;
  background:rgba(226,230,240,.08) !important;
}
[data-theme="dark"] .periodo-pill:hover {
  background:rgba(226,230,240,.16) !important;
  border-color:#E2E6F0 !important;
}
[data-theme="dark"] .periodo-pill.active {
  background:var(--c-teal) !important;
  color:#fff !important;
  border-color:var(--c-teal) !important;
}
[data-theme="dark"] .kpi-card,
[data-theme="dark"] .ff-card,
[data-theme="dark"] .store-card {
  background:var(--c-card);
  border-color:var(--c-border);
  box-shadow:var(--shadow);
}
[data-theme="dark"] .kpi-value,
[data-theme="dark"] .ff-card-title,
[data-theme="dark"] .store-name,
[data-theme="dark"] .stat-v,
[data-theme="dark"] .rank-name {
  color:var(--c-ink);
}
[data-theme="dark"] .kpi-label,
[data-theme="dark"] .ff-card-desc,
[data-theme="dark"] .store-canal,
[data-theme="dark"] .ff-legend span,
[data-theme="dark"] .gap-name,
[data-theme="dark"] .hbar-yr,
[data-theme="dark"] .store-summary {
  color:var(--c-muted);
}
[data-theme="dark"] .kpi-sub,
[data-theme="dark"] .kpi-tag,
[data-theme="dark"] .stat-l,
[data-theme="dark"] .rank-canal,
[data-theme="dark"] .store-gap .g-lbl {
  color:var(--c-muted2);
}
[data-theme="dark"] .stat,
[data-theme="dark"] .rank-item {
  border-color:var(--c-border);
}
[data-theme="dark"] .kpi-icon {
  background:var(--c-teal-lt);
  color:var(--c-teal);
}
[data-theme="dark"] .kpi-var.pos {
  color:var(--c-green-tx);
  background:var(--c-green-bg);
}
[data-theme="dark"] .kpi-var.neg {
  color:var(--c-red-tx);
  background:var(--c-red-bg);
}
[data-theme="dark"] .gap-bar.y25 { background:var(--c-blue-lt); }
[data-theme="dark"] .gap-bar .bar-val { color:var(--c-ink); }
[data-theme="dark"] .store-top.up {
  background:linear-gradient(180deg,var(--c-green-bg),var(--c-card) 88%);
}
[data-theme="dark"] .store-top.down {
  background:linear-gradient(180deg,var(--c-red-bg),var(--c-card) 88%);
}
[data-theme="dark"] .store-nav {
  background:var(--c-card);
  border-color:var(--c-border);
  box-shadow:0 4px 14px rgba(0,0,0,.3);
}
[data-theme="dark"] .store-nav:hover {
  background:var(--c-teal);
  color:#fff;
}
[data-theme="dark"] .hbar-track {
  background:#2E3450;
}
[data-theme="dark"] .hbar-fill.y25 {
  background:var(--c-blue-lt);
  color:var(--c-teal2);
}
[data-theme="dark"] .rank-num {
  background:#2E3450;
  color:var(--c-slate);
}
[data-theme="dark"] .rank-item:first-child .rank-num {
  background:var(--c-amber);
  color:#fff;
}
[data-theme="dark"] .ff-loader {
  background:rgba(20,24,36,.92);
}
[data-theme="dark"] .gap-scroll::-webkit-scrollbar-thumb {
  background:#3A4060;
}
[data-theme="dark"] .gap-scroll::-webkit-scrollbar-track {
  background:#1A1F33;
}
</style>
@endpush

@section('contenido')
<div class="container-xxl flex-grow-1 container-p-y ffto-root">

  {{-- HEADER --}}
  <div class="ff-header ff-anim">
    <div>
      <h1><i class="bx bx-line-chart me-1"></i>Follow-up FF vs TO</h1>
      <div class="ff-sub" id="lbl-rango">Cargando período…</div>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      @foreach($meses as $m)
        @php
          $ini  = \Carbon\Carbon::create(2026,$m['n'],1)->startOfMonth()->toDateString();
          $finM = \Carbon\Carbon::create(2026,$m['n'],1)->endOfMonth();
          $fin  = $finM->gt(\Carbon\Carbon::today()) ? $today : $finM->toDateString();
        @endphp
        <button class="btn periodo-pill {{ $loop->last ? 'active' : '' }}"
                data-ini="{{ $ini }}" data-fin="{{ $fin }}">{{ $m['nom'] }}</button>
      @endforeach
      <button class="btn periodo-pill"
              data-ini="{{ \Carbon\Carbon::today()->startOfYear()->toDateString() }}"
              data-fin="{{ $today }}">Acumulado</button>
    </div>
  </div>

  {{-- KPIs GLOBALES (slide 2) --}}
  <div class="kpi-grid mb-4 ff-anim" id="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-icon"><i class="bx bx-walk"></i></div>
      <div class="kpi-label">Footfall (FF)</div>
      <div class="kpi-value" id="kpi-ff">-</div>
      <div class="kpi-sub" id="kpi-ff-sub">vs -</div>
      <div class="kpi-var" id="kpi-ff-var"></div>
      <div class="kpi-tag">visitas</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon"><i class="bx bx-dollar-circle"></i></div>
      <div class="kpi-label">Ventas (TO)</div>
      <div class="kpi-value" id="kpi-to">-</div>
      <div class="kpi-sub" id="kpi-to-sub">vs -</div>
      <div class="kpi-var" id="kpi-to-var"></div>
      <div class="kpi-tag">ingresos</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon"><i class="bx bx-receipt"></i></div>
      <div class="kpi-label">Tickets</div>
      <div class="kpi-value" id="kpi-tk">-</div>
      <div class="kpi-sub" id="kpi-tk-sub">vs -</div>
      <div class="kpi-var" id="kpi-tk-var"></div>
      <div class="kpi-tag">transacciones</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon"><i class="bx bx-purchase-tag"></i></div>
      <div class="kpi-label">ATV promedio</div>
      <div class="kpi-value" id="kpi-atv">-</div>
      <div class="kpi-sub" id="kpi-atv-sub">vs -</div>
      <div class="kpi-var" id="kpi-atv-var"></div>
      <div class="kpi-tag">ticket promedio</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon"><i class="bx bx-target-lock"></i></div>
      <div class="kpi-label">CR promedio</div>
      <div class="kpi-value" id="kpi-cr">-</div>
      <div class="kpi-sub" id="kpi-cr-sub">vs -</div>
      <div class="kpi-var" id="kpi-cr-var"></div>
      <div class="kpi-tag">conversión</div>
    </div>
  </div>

  {{-- GAP CHART (slide 3) --}}
  <div class="ff-card position-relative mb-4 ff-anim">
    <div class="ff-loader show" id="ffto-loader">
      <div class="text-center">
        <div class="spinner-border mb-2" style="color:#696cff" role="status"></div>
        <p class="mb-0" style="font-size:.84rem;color:var(--c-muted)">Cargando tráfico y ventas…</p>
      </div>
    </div>
    <div class="ff-card-head">
      <div class="ff-card-title">Gap FF vs TO — Ratio de eficiencia por sucursal</div>
      <div class="ff-card-desc">Soles de venta generados por cada persona que entra (TO ÷ FF), 2025 vs 2026.</div>
      <div class="ff-legend">
        <span><i class="sw" style="background:var(--c-blue-lt)"></i>2025</span>
        <span><i class="sw" style="background:var(--c-teal)"></i>2026 mejora</span>
        <span><i class="sw" style="background:var(--c-red)"></i>2026 deterioro</span>
      </div>
    </div>
    <div class="ff-card-body">
      <div class="gap-scroll"><div class="gap-chart" id="gap-chart"></div></div>
    </div>
  </div>

  {{-- DETALLE POR SUCURSAL — 2 por fila --}}
  <div class="ff-card mb-4 ff-anim">
    <div class="ff-card-head">
      <div class="ff-card-title">Detalle por sucursal</div>
      <div class="ff-card-desc">FF · TO · Tickets · ATV · CR y ratio de eficiencia, comparados contra 2025.</div>
    </div>
    <div class="ff-card-body store-carousel-wrap">
      <button class="store-nav prev" id="store-prev" type="button"><i class="bx bx-chevron-left"></i></button>
      <div class="store-carousel" id="store-carousel"></div>
      <button class="store-nav next" id="store-next" type="button"><i class="bx bx-chevron-right"></i></button>
    </div>
  </div>

  {{-- RANKING (slide 28) --}}
  <div class="row g-3 mb-4 ff-anim">
    <div class="col-md-4">
      <div class="ff-card">
        <div class="rank-head up"><i class="bx bx-trending-up"></i>Mejor gap TO/FF</div>
        <div id="rank-up"></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="ff-card">
        <div class="rank-head down"><i class="bx bx-trending-down"></i>Peor gap TO/FF</div>
        <div id="rank-down"></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="ff-card">
        <div class="rank-head growth"><i class="bx bx-line-chart"></i>Mayor crecimiento TO</div>
        <div id="rank-growth"></div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('footer')
<script>
'use strict';

var activeIni = '{{ $mesIni }}';
var activeFin = '{{ $today }}';

function nfInt(v){ return v==null ? '-' : Math.round(v).toLocaleString('es-PE'); }
function nfMoney(v){ return v==null ? '-' : 'S/ '+Math.round(v).toLocaleString('es-PE'); }
function nfMoneyK(v){
  if(v==null) return '-';
  if(Math.abs(v) >= 1000) return 'S/ '+(v/1000).toLocaleString('es-PE',{maximumFractionDigits:0})+'K';
  return 'S/ '+Math.round(v).toLocaleString('es-PE');
}
function nfPct(v, d){ return v==null ? '-' : v.toFixed(d==null?1:d)+'%'; }
function escHtml(s){ return (''+s).replace(/[&<>]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;'}[c];}); }

/* ══ KPIs ══ */
function setKpi(prefix, actual, anterior, fmt, isPctPoints){
  document.getElementById('kpi-'+prefix).textContent = fmt(actual);
  document.getElementById('kpi-'+prefix+'-sub').textContent = 'vs '+fmt(anterior);
  var varEl = document.getElementById('kpi-'+prefix+'-var');
  var v;
  if(isPctPoints){ v = (actual!=null && anterior!=null) ? (actual-anterior) : null; }
  else { v = (actual!=null && anterior!=null && anterior!=0) ? (actual-anterior)/Math.abs(anterior)*100 : null; }
  if(v==null){ varEl.textContent=''; varEl.className='kpi-var'; return; }
  varEl.className = 'kpi-var '+(v>=0?'pos':'neg');
  varEl.textContent = (v>=0?'▲ +':'▼ ')+v.toFixed(1)+(isPctPoints?' pp':'%');
}
function renderKpis(g){
  setKpi('ff',  g.ff_act,  g.ff_hst,  nfInt,   false);
  setKpi('to',  g.to_act,  g.to_hst,  nfMoneyK, false);
  setKpi('tk',  g.tickets_act, g.tickets_hst, nfInt, false);
  setKpi('atv', g.atv_act, g.atv_hst, nfMoney, false);
  setKpi('cr',  g.cr_act,  g.cr_hst,  function(v){ return nfPct(v,2); }, true);
}

/* ══ GAP CHART — barras verticales pareadas 2025 vs 2026 (slide 3) ══ */
function renderGapChart(sucursales){
  var conGap = sucursales.filter(function(r){ return r.gap!=null && r.ratio_act!=null && r.ratio_hst!=null; })
                          .sort(function(a,b){ return b.gap - a.gap; });
  var el = document.getElementById('gap-chart');
  el.innerHTML = '';
  if(!conGap.length){
    el.innerHTML = '<div class="text-muted" style="font-size:.82rem;padding:20px">Sin datos comparables de 2025 en este período.</div>';
    return;
  }
  var maxRatio = Math.max.apply(null, conGap.map(function(r){ return Math.max(r.ratio_act, r.ratio_hst); }));
  maxRatio = Math.max(1, maxRatio);
  conGap.forEach(function(r){
    var up = r.gap >= 0;
    var h25 = (r.ratio_hst / maxRatio) * 100;
    var h26 = (r.ratio_act / maxRatio) * 100;
    var col = document.createElement('div');
    col.className = 'gap-col';
    col.innerHTML =
      '<div class="gap-bars">'+
        '<div class="gap-bar y25" style="height:'+h25.toFixed(1)+'%"><span class="bar-val">'+r.ratio_hst.toFixed(1)+'</span></div>'+
        '<div class="gap-bar '+(up?'up':'down')+'" style="height:'+h26.toFixed(1)+'%"><span class="bar-val">'+r.ratio_act.toFixed(1)+'</span></div>'+
      '</div>'+
      '<div class="gap-name" title="'+escHtml(r.sucursal)+'">'+escHtml(r.sucursal)+'</div>'+
      '<div class="gap-delta '+(up?'up':'down')+'">'+(up?'▲ +':'▼ ')+r.gap.toFixed(2)+'</div>';
    el.appendChild(col);
  });
}

/* ══ DETALLE POR SUCURSAL — tarjetas 2 por fila ══ */
function storeStatus(toVar){
  if(toVar==null) return {cls:'flat', label:'Sin comparativo'};
  if(toVar > 5)  return {cls:'up',   label:'TO en alza'};
  if(toVar < -5) return {cls:'down', label:'TO en caída'};
  return {cls:'flat', label:'TO estable'};
}
function statTile(label, val, varv){
  var x = varv==null
    ? '<div class="stat-x na">–</div>'
    : '<div class="stat-x '+(varv>=0?'pos':'neg')+'">'+(varv>=0?'▲+':'▼')+varv.toFixed(1)+'%</div>';
  return '<div class="stat"><div class="stat-l">'+label+'</div><div class="stat-v">'+val+'</div>'+x+'</div>';
}
function storeSummary(r){
  if(r.to_var==null || r.gap==null) return 'Sin comparativo 2025 cargado para este período.';
  var toTxt  = (r.to_var>=0?'creció +':'cayó ')+r.to_var.toFixed(1)+'%';
  var ffTxt  = r.ff_var==null ? 'sin dato' : ((r.ff_var>=0?'subió +':'bajó ')+r.ff_var.toFixed(1)+'%');
  var gapTxt = (r.gap>=0?'mejoró +':'se deterioró ')+r.gap.toFixed(2);
  return 'TO '+toTxt+', FF '+ffTxt+'. Eficiencia TO/FF '+gapTxt+'.';
}
function renderStores(sucursales){
  var el = document.getElementById('store-carousel');
  el.innerHTML = '';
  sucursales.slice().sort(function(a,b){ return b.to_act - a.to_act; }).forEach(function(r){
    var st = storeStatus(r.to_var);
    var hasRatio = (r.ratio_act!=null && r.ratio_hst!=null);
    var maxR = hasRatio ? Math.max(r.ratio_act, r.ratio_hst, 0.01) : 1;
    var w25 = hasRatio ? (r.ratio_hst/maxR*100) : 0;
    var w26 = hasRatio ? (r.ratio_act/maxR*100) : 0;
    var up  = r.gap!=null && r.gap>=0;

    var barsHtml = hasRatio
      ? '<div class="store-bars">'+
          '<div class="store-bars-title">Ratio TO / FF — soles por visitante</div>'+
          '<div class="hbar-row"><span class="hbar-yr">2026</span><div class="hbar-track">'+
            '<div class="hbar-fill '+(up?'y26up':'y26down')+'" style="width:'+w26.toFixed(1)+'%">S/ '+r.ratio_act.toFixed(2)+'</div></div></div>'+
          '<div class="hbar-row"><span class="hbar-yr">2025</span><div class="hbar-track">'+
            '<div class="hbar-fill y25" style="width:'+w25.toFixed(1)+'%">S/ '+r.ratio_hst.toFixed(2)+'</div></div></div>'+
        '</div>'
      : '<div class="store-bars"><div class="store-bars-title">Ratio TO / FF — soles por visitante</div>'+
        '<div class="text-muted" style="font-size:.74rem;padding:6px 0">Sin comparativo 2025 disponible.</div></div>';

    var footHtml = '<div class="store-foot">'+
        '<div class="store-gap">'+
          '<span class="g-lbl">Gap</span>'+
          '<span class="g-val '+(up?'pos':'neg')+'">'+(r.gap!=null?((r.gap>=0?'+':'')+r.gap.toFixed(2)):'–')+'</span>'+
          (r.gap_pct!=null?'<span class="g-pct '+(r.gap_pct>=0?'pos':'neg')+'">'+(r.gap_pct>=0?'+':'')+r.gap_pct.toFixed(1)+'%</span>':'')+
        '</div>'+
        '<div class="store-summary">'+storeSummary(r)+'</div>'+
      '</div>';

    var card = document.createElement('div');
    card.className = 'store-card ' + st.cls;
    card.innerHTML =
      '<div class="store-top '+st.cls+'">'+
        '<div><div class="store-name">'+escHtml(r.sucursal)+'</div><div class="store-canal">'+escHtml(r.canal||'')+'</div></div>'+
        '<span class="store-badge '+st.cls+'">'+st.label+'</span>'+
      '</div>'+
      '<div class="store-stats">'+
        statTile('FF', nfInt(r.ff_act), r.ff_var) +
        statTile('TO', nfMoneyK(r.to_act), r.to_var) +
        statTile('Tickets', nfInt(r.tickets_act), r.tickets_var) +
        statTile('ATV', nfMoney(r.atv_act), r.atv_var) +
        statTile('CR', nfPct(r.cr_act,1), r.cr_var) +
      '</div>'+
      barsHtml + footHtml;
    el.appendChild(card);
  });
}

/* ══ RANKING ══ */
function renderRankingList(elId, list, valueKey, fmt){
  var el = document.getElementById(elId);
  el.innerHTML = '';
  if(!list.length){ el.innerHTML = '<div class="rank-item text-muted">Sin datos</div>'; return; }
  list.forEach(function(r, i){
    var div = document.createElement('div');
    div.className = 'rank-item';
    div.innerHTML =
      '<span class="rank-num">'+(i+1)+'</span>'+
      '<span class="rank-name">'+escHtml(r.sucursal)+'<div class="rank-canal">'+escHtml(r.canal||'')+'</div></span>'+
      '<span class="rank-val">'+fmt(r[valueKey])+'</span>';
    el.appendChild(div);
  });
}
function renderRanking(sucursales){
  var conGap = sucursales.filter(function(r){ return r.gap!=null; });
  var porGapAsc  = conGap.slice().sort(function(a,b){ return b.gap - a.gap; });
  var porGapDesc = conGap.slice().sort(function(a,b){ return a.gap - b.gap; });
  var conCrec = sucursales.filter(function(r){ return r.to_var!=null; }).sort(function(a,b){ return b.to_var - a.to_var; });
  renderRankingList('rank-up',   porGapAsc.slice(0,5),  'gap', function(v){ return (v>=0?'+':'')+v.toFixed(2); });
  renderRankingList('rank-down', porGapDesc.slice(0,5), 'gap', function(v){ return (v>=0?'+':'')+v.toFixed(2); });
  renderRankingList('rank-growth', conCrec.slice(0,5),  'to_var', function(v){ return (v>=0?'+':'')+v.toFixed(1)+'%'; });
}

/* ══ CARGA ══ */
async function loadData(){
  document.getElementById('ffto-loader').classList.add('show');
  document.getElementById('lbl-rango').textContent = activeIni + ' → ' + activeFin;
  try {
    var r = await fetch('{{ route('dashboard.ffto.data') }}?' + new URLSearchParams({ini:activeIni, fin:activeFin}));
    var json = await r.json();
    renderKpis(json.global);
    renderGapChart(json.sucursales);
    renderStores(json.sucursales);
    renderRanking(json.sucursales);
  } catch(e){ console.error(e); }
  finally { document.getElementById('ffto-loader').classList.remove('show'); }
}

document.querySelectorAll('.periodo-pill').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('.periodo-pill').forEach(function(b){ b.classList.remove('active'); });
    this.classList.add('active');
    activeIni = this.dataset.ini; activeFin = this.dataset.fin;
    loadData();
  });
});

/* Carrusel de sucursales — desplaza ~2 tarjetas por click */
function storeScroll(dir){
  var c = document.getElementById('store-carousel');
  c.scrollBy({ left: dir * (c.clientWidth * 0.92), behavior:'smooth' });
}
document.getElementById('store-prev').addEventListener('click', function(){ storeScroll(-1); });
document.getElementById('store-next').addEventListener('click', function(){ storeScroll(1); });

document.addEventListener('DOMContentLoaded', function(){
  var a = document.querySelector('.periodo-pill.active');
  if(a){ activeIni = a.dataset.ini; activeFin = a.dataset.fin; }
  loadData();
});
</script>
@endsection
