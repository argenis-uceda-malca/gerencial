<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Gerencial — Anual / Mensual / Semanal</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --navy:   #0F2942;
    --blue:   #1A5C9E;
    --sky:    #3B8ED4;
    --terra:  #D97B4A;
    --gold:   #F5A623;
    --green:  #27AE60;
    --red:    #E74C3C;
    --gray:   #8899AA;
    --light:  #F2F6FA;
    --white:  #FFFFFF;
    --text:   #1A2B3C;
    --muted:  #556677;
  }

  body {
    font-family: 'Inter', sans-serif;
    background: #0A1929;
    color: var(--white);
    min-height: 100vh;
    padding: 0;
  }

  /* ── HEADER ── */
  header {
    background: linear-gradient(135deg, #0F2942 0%, #1A5C9E 100%);
    padding: 26px 40px 0;
    border-bottom: 3px solid var(--gold);
  }
  .header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 18px;
  }
  header h1 {
    font-size: 1.7rem;
    font-weight: 800;
    letter-spacing: -0.5px;
    color: #fff;
  }
  header h1 span { color: var(--gold); }
  .header-meta {
    text-align: right;
    font-size: 0.8rem;
    color: #8AB4D8;
    line-height: 1.6;
  }
  .header-meta strong { color: var(--gold); font-weight: 600; }

  /* ── TABS ── */
  .tabs {
    display: flex;
    gap: 6px;
  }
  .tab-btn {
    appearance: none;
    border: none;
    background: rgba(255,255,255,0.05);
    color: #8AB4D8;
    font-family: 'Inter', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 11px 26px;
    border-radius: 8px 8px 0 0;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
    border-bottom: 3px solid transparent;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .tab-btn:hover { background: rgba(255,255,255,0.09); color: #fff; }
  .tab-btn.active {
    background: rgba(245,166,35,0.12);
    color: var(--gold);
    border-bottom: 3px solid var(--gold);
  }
  .tab-icon { font-size: 1rem; }

  /* ── LAYOUT ── */
  main { padding: 24px 36px 48px; }
  .tab-panel { display: none; flex-direction: column; gap: 28px; }
  .tab-panel.active { display: flex; }

  .period-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(59,142,212,0.1);
    border: 1px solid rgba(59,142,212,0.25);
    color: #8AB4D8;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 7px 16px;
    border-radius: 20px;
    width: fit-content;
  }
  .period-badge .dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: var(--gold);
  }

  /* ── KPI STRIP ── */
  .kpi-row {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 14px;
  }
  .kpi {
    background: linear-gradient(145deg, #132D4A, #1A3D5C);
    border: 1px solid rgba(255,255,255,0.08);
    border-top: 3px solid var(--sky);
    border-radius: 10px;
    padding: 18px 16px 14px;
    position: relative;
    overflow: hidden;
  }
  .kpi::before {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 60px; height: 60px;
    background: radial-gradient(circle, rgba(59,142,212,0.15) 0%, transparent 70%);
    border-radius: 0 0 0 100%;
  }
  .kpi-label {
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #8AB4D8;
    margin-bottom: 8px;
  }
  .kpi-value { font-size: 1.4rem; font-weight: 800; color: #fff; line-height: 1; }
  .kpi-sub { font-size: 0.72rem; color: var(--gray); margin-top: 6px; }
  .kpi-delta {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    margin-top: 7px;
  }
  .delta-neg { background: rgba(231,76,60,0.18); color: #FF6B5B; }
  .delta-pos { background: rgba(39,174,96,0.18); color: #2ECC71; }

  /* ── SECTION TITLE ── */
  .section-title {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--gold);
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(245,166,35,0.25);
  }

  /* ── CHART GRID ── */
  .chart-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

  /* ── CHART CARD ── */
  .card {
    background: linear-gradient(145deg, #132D4A, #0F2336);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px;
    padding: 22px 24px 18px;
    position: relative;
  }
  .card-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 4px;
  }
  .card-sub {
    font-size: 0.7rem;
    color: var(--gray);
    margin-bottom: 18px;
  }
  .canvas-wrap { position: relative; }

  /* ── LEGEND ── */
  .legend {
    display: flex;
    gap: 20px;
    margin-top: 14px;
    justify-content: center;
  }
  .legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.72rem;
    color: #8AB4D8;
    font-weight: 500;
  }
  .legend-dot {
    width: 10px; height: 10px;
    border-radius: 2px;
  }

  /* ── VARIATION PILL TABLE ── */
  .var-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .var-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
  .var-table th {
    text-align: left;
    padding: 8px 12px;
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #8AB4D8;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    white-space: nowrap;
  }
  .var-table td { padding: 9px 12px; border-bottom: 1px solid rgba(255,255,255,0.04); white-space: nowrap; }
  .var-table tr:last-child td { border-bottom: none; }
  .var-table tr:hover td { background: rgba(59,142,212,0.06); }
  .brand-name { font-weight: 600; color: #fff; }
  .canal-tag {
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .tag-boutiques { background: rgba(59,142,212,0.2); color: #5BB8E8; }
  .tag-outlets   { background: rgba(245,166,35,0.2); color: #F5A623; }
  .tag-web       { background: rgba(39,174,96,0.2);  color: #2ECC71; }
  .pill {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 0.73rem;
    font-weight: 700;
  }
  .pill-neg { background: rgba(231,76,60,0.18); color: #FF6B5B; }
  .pill-pos { background: rgba(39,174,96,0.18); color: #2ECC71; }
  .num { color: #BDD7F0; font-variant-numeric: tabular-nums; }

  /* ── COLLAPSIBLE SECTION ── */
  .collapsible {
    background: linear-gradient(145deg, #132D4A, #0F2336);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px;
    overflow: hidden;
  }
  .collapsible-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    cursor: pointer;
    user-select: none;
    transition: background 0.15s;
  }
  .collapsible-header:hover { background: rgba(255,255,255,0.03); }
  .collapsible-header-left { display: flex; align-items: center; gap: 12px; }
  .collapsible-title { font-size: 0.9rem; font-weight: 700; color: #fff; }
  .collapsible-sub   { font-size: 0.72rem; color: var(--gray); margin-top: 2px; }
  .collapsible-chevron {
    width: 26px; height: 26px;
    border-radius: 50%;
    background: rgba(245,166,35,0.12);
    color: var(--gold);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem;
    transition: transform 0.25s ease;
    flex-shrink: 0;
  }
  .collapsible.open .collapsible-chevron { transform: rotate(180deg); }
  .collapsible-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
  }
  .collapsible.open .collapsible-body { max-height: 6000px; }
  .collapsible-body-inner { padding: 4px 24px 24px; display: flex; flex-direction: column; gap: 20px; }

  .store-filter-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 4px;
  }
  .store-filter-btn {
    appearance: none;
    border: 1px solid rgba(255,255,255,0.12);
    background: rgba(255,255,255,0.03);
    color: #8AB4D8;
    font-family: 'Inter', sans-serif;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.15s;
  }
  .store-filter-btn:hover { background: rgba(255,255,255,0.08); }
  .store-filter-btn.active {
    background: rgba(245,166,35,0.15);
    border-color: var(--gold);
    color: var(--gold);
  }

  @media (max-width: 600px) {
    .collapsible-header     { padding: 14px 16px; }
    .collapsible-title      { font-size: 0.8rem; }
    .collapsible-sub        { font-size: 0.66rem; }
    .collapsible-body-inner { padding: 4px 14px 18px; gap: 14px; }
    .store-filter-btn       { font-size: 0.66rem; padding: 5px 11px; }
  }

  /* ── TABLET (≤ 900px) ── */
  @media (max-width: 900px) {
    header { padding: 20px 20px 0; }
    main   { padding: 18px 16px 40px; }
    .tab-panel.active { gap: 18px; }

    .kpi-row        { grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .chart-grid-2   { grid-template-columns: 1fr; }

    .card { padding: 18px 16px 14px; }
  }

  /* ── MOBILE (≤ 600px) ── */
  @media (max-width: 600px) {
    .header-top {
      flex-direction: column;
      align-items: flex-start;
      gap: 10px;
      padding-bottom: 14px;
    }
    header h1       { font-size: 1.2rem; }
    .header-meta    { text-align: left; font-size: 0.75rem; }
    header          { padding: 18px 14px 0; }

    /* Tabs: ocupan ancho completo */
    .tabs { gap: 4px; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .tab-btn {
      flex: 1;
      justify-content: center;
      padding: 10px 8px;
      font-size: 0.74rem;
      white-space: nowrap;
    }
    .tab-icon { font-size: 0.9rem; }

    main { padding: 14px 12px 36px; }
    .tab-panel.active { gap: 14px; }

    .period-badge { font-size: 0.7rem; padding: 6px 12px; }

    /* KPIs: 2 por fila en móvil */
    .kpi-row {
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
    }
    .kpi              { padding: 14px 12px 10px; border-radius: 8px; }
    .kpi-label        { font-size: 0.62rem; letter-spacing: 0.5px; margin-bottom: 5px; }
    .kpi-value        { font-size: 1.1rem; }
    .kpi-sub          { font-size: 0.65rem; margin-top: 4px; }
    .kpi-delta        { font-size: 0.68rem; padding: 2px 6px; margin-top: 5px; }

    /* Grids → columna única */
    .chart-grid-2     { grid-template-columns: 1fr; gap: 14px; }

    /* Cards más compactas */
    .card             { padding: 16px 14px 12px; border-radius: 10px; }
    .card-title       { font-size: 0.78rem; }
    .card-sub         { font-size: 0.66rem; margin-bottom: 12px; }

    /* Canvas: altura reducida para que quepan bien */
    canvas            { max-height: 220px; }

    /* Leyenda */
    .legend           { gap: 14px; margin-top: 10px; }
    .legend-item      { font-size: 0.68rem; }
    .legend-dot       { width: 8px; height: 8px; }

    /* Tabla */
    .var-table        { font-size: 0.72rem; min-width: 620px; }
    .var-table th,
    .var-table td     { padding: 7px 8px; }
    .canal-tag        { font-size: 0.58rem; padding: 1px 6px; }
    .pill             { font-size: 0.65rem; padding: 2px 6px; }

    /* Section title */
    .section-title    { font-size: 0.62rem; letter-spacing: 1.5px; margin-bottom: 10px; }
  }

  /* ── EXTRA SMALL (≤ 380px) ── */
  @media (max-width: 380px) {
    .kpi-row          { grid-template-columns: 1fr 1fr; }
    .kpi-value        { font-size: 1rem; }
    header h1         { font-size: 1.05rem; }
    canvas            { max-height: 190px; }
  }

  /* ── PRINT ── */
  @media print {
    body { background: #fff; color: #000; }
    header { background: #0F2942 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .card  { background: #f8fafd !important; border: 1px solid #dde; -webkit-print-color-adjust: exact; }
    .kpi   { background: #f0f4f8 !important; -webkit-print-color-adjust: exact; }
    .tab-panel { display: flex !important; }
  }
</style>
</head>
<body>

<!-- HEADER -->
<header>
  <div class="header-top">
    <div>
      <h1>Dashboard <span>Gerencial</span></h1>
      <div style="font-size:0.8rem;color:#8AB4D8;margin-top:4px;font-weight:400;">Comparativo · Boutiques · Outlets · Web</div>
    </div>
    <div class="header-meta">
      <strong>Moneda: Soles (S/)</strong><br>
      Actualizado: Junio 2026<br>
      Canales activos: 3 · Marcas: 7
    </div>
  </div>
  <div class="tabs">
    <button class="tab-btn active" data-tab="anual" onclick="switchTab('anual')"><span class="tab-icon">📅</span> Anual</button>
    <button class="tab-btn" data-tab="mensual" onclick="switchTab('mensual')"><span class="tab-icon">🗓️</span> Mensual</button>
    <button class="tab-btn" data-tab="semanal" onclick="switchTab('semanal')"><span class="tab-icon">📆</span> Semanal</button>
  </div>
</header>

<main>
  <div class="tab-panel active" id="panel-anual"></div>
  <div class="tab-panel" id="panel-mensual"></div>
  <div class="tab-panel" id="panel-semanal"></div>
</main>

<script>
// ════════════════════════════════════════════════
// DATOS — Origen: Detalle anual / Detalle Mensual / Detalles Semanal
// ════════════════════════════════════════════════
const PERIODS = {
  anual: {
    badge: '📅 Acumulado 2026  vs  Período equivalente 2025',
    marcas:   ['MCH','EXIT','MILK','FINA','KORDA','OUTLETS','WEB'],
    vta25:    [3658648.67, 2258647.83, 1203174.86, 543444.27, 5602.13, 874162.17, 865565.33],
    vta26:    [3130084.83, 1866632.70,  619007.95, 451837.41,       0, 696171.64, 726672.19],
    ut25:     [2012072.14, 1211413.88,  576860.67, 305264.57, 1591.28,  99873.27, 339754.48],
    ut26:     [1748588.33, 1003132.90,  291223.18, 253307.55,       0,-726609.73, 287303.34],
    gm25:     [54.99, 53.63, 47.94, 56.17, 28.40, 11.43, 39.25],
    gm26:     [55.86, 53.74, 47.05, 56.06,  0.00,-104.37, 39.54],
    varVta:   [-14.44,-17.36,-48.52,-16.86,-100.00,-20.36,-16.05],
    marcasFF: ['MCH','EXIT','MILK','FINA'],
    ff25:     [489579, 432711, 206728, 0],
    ff26:     [455438, 391561, 157127, 43853],
    cr25:     [4.33, 3.76, 4.92, 0],
    cr26:     [4.07, 3.52, 3.52, 5.94],
    canal: { labels:['Boutiques','Outlets','Web'], values:[6067562.88, 696171.64, 726672.19], total: 7490406.71 },
    kpis: {
      ventaNeta:  { v25: 9409245.27, v26: 7490406.71, varPct: -20.39 },
      utilidad:   { v25: 4546830.29, v26: 2856945.57, varPct: -37.17 },
      gm:         { v25: 48.32, v26: 38.14, deltaPP: -10.18 },
      canalLider: { nombre: 'Boutiques', share: 81.0, varPct: -20.89 },
      footFall:   { v25: 1129018, v26: 1047979, varPct: -7.18 },
      convRate:   { v25: 5.73, v26: 5.24, deltaPP: -0.49 },
    },
    table: [
      ['BOUTIQUES','MCH',   3658648.67, 3130084.83, -14.44, 2012072.14, 1748588.33, 54.99, 55.86, -13.10, 489579, 455438,  4.33,  4.07],
      ['BOUTIQUES','EXIT',  2258647.83, 1866632.70, -17.36, 1211413.88, 1003132.90, 53.63, 53.74, -17.20, 432711, 391561,  3.76,  3.52],
      ['BOUTIQUES','MILK',  1203174.86,  619007.95, -48.52,  576860.67,  291223.18, 47.94, 47.05, -49.54, 206728, 157127,  4.92,  3.52],
      ['BOUTIQUES','FINA',   543444.27,  451837.41, -16.86,  305264.57,  253307.55, 56.17, 56.06, -16.99,      0,  43853,  0.00,  5.94],
      ['BOUTIQUES','KORDA',    5602.13,          0,-100.00,    1591.28,          0, 28.40,  0.00,-100.00,      0,      0,  0.00,  0.00],
      ['OUTLETS','OUTLETS',  874162.17,  696171.64, -20.36,   99873.27, -726609.73, 11.43,-104.37,-827.53,     0,      0,  0.00,  0.00],
      ['WEB','WEB',          865565.33,  726672.19, -16.05,  339754.48,  287303.34, 39.25, 39.54, -15.44,      0,      0,  0.00,  0.00],
    ]
  },

  mensual: {
    badge: '🗓️ Junio 2026  vs  Junio 2025',
    marcas:   ['MCH','EXIT','MILK','FINA','OUTLETS','WEB'],
    vta25:    [992217.29, 681100.71, 377878.95, 135200.98, 236172.09, 229562.65],
    vta26:    [952510.58, 657934.95, 225593.07, 143856.79, 180532.54, 214715.32],
    ut25:     [571164.58, 369081.15, 187710.79,  81511.15,  35778.82,  93931.33],
    ut26:     [536056.53, 364126.35, 107476.16,  82874.97, -87604.64,  96335.08],
    gm25:     [57.56, 54.19, 49.67, 60.29, 15.15, 40.92],
    gm26:     [56.28, 55.34, 47.64, 57.61,-48.53, 44.87],
    varVta:   [-4.00, -3.40,-40.30,  6.40,-23.56, -6.47],
    marcasFF: ['MCH','EXIT','MILK','FINA'],
    ff25:     [52124, 48112, 24625, 0],
    ff26:     [32588, 27582, 11865, 3432],
    cr25:     [10.41, 10.21, 12.54, 0],
    cr26:     [16.99, 16.92, 16.42, 24.27],
    canal: { labels:['Boutiques','Outlets','Web'], values:[1979895.40, 180532.54, 214715.32], total: 2375143.25 },
    kpis: {
      ventaNeta:  { v25: 2652132.67, v26: 2375143.25, varPct: -10.44 },
      utilidad:   { v25: 1339177.83, v26: 1099264.45, varPct: -17.91 },
      gm:         { v25: 50.49, v26: 46.28, deltaPP: -4.21 },
      canalLider: { nombre: 'Boutiques', share: 83.4, varPct: -9.44 },
      footFall:   { v25: 124861, v26: 75467, varPct: -39.56 },
      convRate:   { v25: 14.40, v26: 22.49, deltaPP: 8.09 },
    },
    table: [
      ['BOUTIQUES','MCH',    992217.29, 952510.58,  -4.00, 571164.58, 536056.53, 57.56, 56.28,  -6.15, 52124, 32588, 10.41, 16.99],
      ['BOUTIQUES','EXIT',   681100.71, 657934.95,  -3.40, 369081.15, 364126.35, 54.19, 55.34,  -1.34, 48112, 27582, 10.21, 16.92],
      ['BOUTIQUES','MILK',   377878.95, 225593.07, -40.30, 187710.79, 107476.16, 49.67, 47.64, -42.73, 24625, 11865, 12.54, 16.42],
      ['BOUTIQUES','FINA',   135200.98, 143856.79,   6.40,  81511.15,  82874.97, 60.29, 57.61,   1.67,     0,  3432,  0.00, 24.27],
      ['OUTLETS','OUTLETS',  236172.09, 180532.54, -23.56,  35778.82, -87604.64, 15.15,-48.53,-344.86,     0,     0,  0.00,  0.00],
      ['WEB','WEB',          229562.65, 214715.32,  -6.47,  93931.33,  96335.08, 40.92, 44.87,   2.56,     0,     0,  0.00,  0.00],
    ]
  },

  semanal: {
    badge: '📆 Semana 25 · 2026  vs  Semana equivalente 2025',
    marcas:   ['MCH','EXIT','MILK','FINA','OUTLETS','WEB'],
    vta25:    [331710.21, 226201.67, 133795.62, 35269.97, 77190.39, 47899.26],
    vta26:    [312994.66, 227870.69,  74334.09, 51411.20, 67266.20, 55183.47],
    ut25:     [189050.06, 120613.12, 62184.91, 20997.02, 14064.32, 22863.42],
    ut26:     [171303.67, 123004.29, 35002.53, 28993.48,-21662.72, 26192.62],
    gm25:     [56.99, 53.32, 46.48, 59.53, 18.22, 47.73],
    gm26:     [54.73, 53.98, 47.09, 56.40,-32.20, 47.46],
    varVta:   [-5.64,  0.74,-44.44, 45.76,-12.86, 15.21],
    marcasFF: ['MCH','EXIT','MILK','FINA'],
    ff25:     [17150, 15669, 8622, 0],
    ff26:     [0, 0, 0, 0],
    cr25:     [10.73, 10.58, 13.42, 0],
    cr26:     [0, 0, 0, 0],
    canal: { labels:['Boutiques','Outlets','Web'], values:[666610.64, 67266.20, 55183.47], total: 789060.31 },
    kpis: {
      ventaNeta:  { v25: 852067.12, v26: 789060.31, varPct: -7.39 },
      utilidad:   { v25: 429772.84, v26: 362833.86, varPct: -15.57 },
      gm:         { v25: 50.44, v26: 45.98, deltaPP: -4.46 },
      canalLider: { nombre: 'Boutiques', share: 84.5, varPct: -8.30 },
      footFall:   { v25: 41441, v26: 0, varPct: 0 },
      convRate:   { v25: 14.26, v26: 0, deltaPP: 0 },
    },
    table: [
      ['BOUTIQUES','MCH',   331710.21, 312994.66,  -5.64, 189050.06, 171303.67, 56.99, 54.73,  -9.38, 17150,     0, 10.73,  0.00],
      ['BOUTIQUES','EXIT',  226201.67, 227870.69,   0.74, 120613.12, 123004.29, 53.32, 53.98,   1.98, 15669,     0, 10.58,  0.00],
      ['BOUTIQUES','MILK',  133795.62,  74334.09, -44.44,  62184.91,  35002.53, 46.48, 47.09, -43.71,  8622,     0, 13.42,  0.00],
      ['BOUTIQUES','FINA',   35269.97,  51411.20,  45.76,  20997.02,  28993.48, 59.53, 56.40,  38.08,     0,     0,  0.00,  0.00],
      ['OUTLETS','OUTLETS',  77190.39,  67266.20, -12.86,  14064.32, -21662.72, 18.22,-32.20,-254.03,     0,     0,  0.00,  0.00],
      ['WEB','WEB',          47899.26,  55183.47,  15.21,  22863.42,  26192.62, 47.73, 47.46,  14.55,     0,     0,  0.00,  0.00],
    ]
  }
};

// ════════════════════════════════════════════════
// DETALLE POR TIENDA — Junio 2026 vs Junio 2025
// Origen: hoja "Detalle Mensual Tiendas"
// ════════════════════════════════════════════════
const TIENDAS = [
  // [canal, marca, sucursal, vta25, vta26, varVta, ut25, ut26, varUt, gm25, gm26, ff25, ff26, cr25, cr26]
  ['BOUTIQUES','MCH','MCH RP Salaverry', 131053.55, 132046.88, 0.76, 76428.81, 74406.21, -2.65, 58.32, 56.35, 7782, 5165, 9.66, 15.61],
  ['BOUTIQUES','MCH','MCH Plaza San Miguel', 151061.56, 131051.53, -13.25, 87733.48, 73850.35, -15.82, 58.08, 56.35, 7288, 4056, 10.7, 18.27],
  ['BOUTIQUES','MCH','MCH Jockey Plaza', 144931.18, 118115.33, -18.5, 84094.13, 67101.46, -20.21, 58.02, 56.81, 8184, 4242, 10.04, 15.94],
  ['BOUTIQUES','MCH','MCH Caminos del Inca', 0, 94773.7, -100.0, 0, 53964.67, 0.0, 0.0, 56.94, 6, 2327, 0.0, 22.48],
  ['BOUTIQUES','MCH','MCH Roble', 89700.89, 83750.17, -6.63, 51476.66, 47508.06, -7.71, 57.39, 56.73, 1779, 612, 23.44, 61.93],
  ['BOUTIQUES','MCH','MCH LR San Borja', 100799.05, 80457.36, -20.18, 59037.2, 45757.04, -22.49, 58.57, 56.87, 5675, 3188, 9.82, 15.53],
  ['BOUTIQUES','MCH','MCH RP Primavera', 91833.1, 63871.16, -30.45, 52228.15, 36350.95, -30.4, 56.87, 56.91, 5628, 3118, 9.67, 13.6],
  ['BOUTIQUES','MCH','MCH Plaza Norte', 69135.38, 62868.12, -9.07, 40046.31, 35924.58, -10.29, 57.92, 57.14, 3824, 1995, 10.25, 18.65],
  ['BOUTIQUES','MCH','MCH MP Trujillo', 71149.24, 58111.01, -18.33, 40508.14, 31190.11, -23.0, 56.93, 53.67, 4908, 3530, 9.03, 10.91],
  ['BOUTIQUES','MCH','MCH Cronos', 61023.36, 48515.43, -20.5, 33055.49, 26579.21, -19.59, 54.17, 54.79, 1149, 619, 23.5, 37.8],
  ['BOUTIQUES','MCH','MCH Plaza Lima Sur', 36722.3, 40235.77, 9.57, 21245.45, 22764.83, 7.15, 57.85, 56.58, 2756, 1852, 7.58, 13.71],
  ['BOUTIQUES','MCH','MCH RP Piura', 44807.69, 38714.12, -13.6, 25310.76, 20659.06, -18.38, 56.49, 53.36, 3145, 1884, 7.6, 13.16],
  ['BOUTIQUES','EXIT','EXIT Jockey Plaza', 62839.84, 72492.05, 15.36, 34882.33, 41046.15, 17.67, 55.51, 56.62, 4978, 2837, 8.94, 16.6],
  ['BOUTIQUES','EXIT','EXIT Plaza Norte', 76648.61, 69810.06, -8.92, 41354.58, 38828.4, -6.11, 53.95, 55.62, 5370, 2847, 9.46, 15.6],
  ['BOUTIQUES','EXIT','EXIT MP Trujillo', 76807.76, 68644.16, -10.63, 42039.38, 37862.78, -9.93, 54.73, 55.16, 5800, 3462, 10.1, 15.71],
  ['BOUTIQUES','EXIT','EXIT RP Salaverry', 53978.31, 59097.71, 9.48, 29863.34, 33370.35, 11.74, 55.32, 56.47, 4665, 2603, 9.0, 15.83],
  ['BOUTIQUES','EXIT','EXIT RP Primavera', 60969.96, 54245.89, -11.03, 33304.11, 28331.07, -14.93, 54.62, 52.23, 7225, 3664, 5.92, 11.63],
  ['BOUTIQUES','EXIT','EXIT LR San Borja', 74534.15, 53925.92, -27.65, 40753.92, 30586.22, -24.95, 54.68, 56.72, 5158, 2531, 10.41, 16.55],
  ['BOUTIQUES','EXIT','EXIT RP Piura', 33155.15, 51795.36, 56.22, 15838.6, 27179.71, 71.6, 47.77, 52.48, 2308, 1904, 12.22, 19.07],
  ['BOUTIQUES','EXIT','EXIT RP Chiclayo', 34276.0, 37962.25, 10.75, 18210.998, 20413.01, 12.09, 53.13, 53.77, 3228, 1223, 8.36, 23.55],
  ['BOUTIQUES','EXIT','EXIT Mall del Sur', 49587.79, 36585.46, -26.22, 27319.88, 21032.35, -23.01, 55.09, 57.49, 2934, 1701, 11.0, 15.11],
  ['BOUTIQUES','EXIT','EXIT RP Cusco', 36847.02, 33590.32, -8.84, 19562.8, 17572.82, -10.17, 53.09, 52.32, 3995, 2124, 7.33, 11.02],
  ['BOUTIQUES','EXIT','EXIT Caminos del Inca', 0, 30423.19, -100.0, 0, 16590.58, 0.0, 0.0, 54.53, 0, 1065, 0.0, 20.47],
  ['BOUTIQUES','EXIT','EXIT Plaza Lima Sur', 26665.7, 30121.17, 12.96, 14910.62, 17345.76, 16.33, 55.92, 57.59, 2451, 1621, 6.94, 12.09],
  ['BOUTIQUES','EXIT','EXIT MA San Juan de Lurigancho', 24311.04, 23302.28, -4.15, 13369.56, 13378.73, 0.07, 54.99, 57.41, 0, 0, 0.0, 0.0],
  ['BOUTIQUES','EXIT','EXIT La Molina', 41490.17, 20024.08, -51.74, 22854.15, 11317.29, -50.48, 55.08, 56.52, 0, 0, 0.0, 0.0],
  ['BOUTIQUES','EXIT','EXIT RP Puruchuco', 28989.2, 15915.03, -45.1, 14816.89, 9271.12, -37.43, 51.11, 58.25, 0, 0, 0.0, 0.0],
  ['BOUTIQUES','MILK','MILK RP Piura', 52973.28, 42814.98, -19.18, 24616.67, 20647.87, -16.12, 46.47, 48.22, 4323, 2785, 11.89, 14.47],
  ['BOUTIQUES','MILK','MILK Plaza Norte', 46183.52, 37378.52, -19.07, 23928.41, 17950.7, -24.98, 51.81, 48.02, 3946, 2193, 8.21, 13.59],
  ['BOUTIQUES','MILK','MILK RP Chiclayo', 30762.21, 27714.53, -9.91, 15946.92, 14422.08, -9.56, 51.84, 52.04, 2714, 1334, 9.32, 17.39],
  ['BOUTIQUES','MILK','MILK Mall del Sur', 35492.15, 26436.25, -25.52, 18514.73, 13267.43, -28.34, 52.17, 50.19, 4244, 2402, 6.01, 8.66],
  ['BOUTIQUES','MILK','MILK LR Brasil', 25216.78, 23567.9, -6.54, 13623.0, 11445.36, -15.99, 54.02, 48.56, 2900, 1609, 6.97, 12.43],
  ['BOUTIQUES','MILK','MILK OP Piura', 40592.09, 21176.73, -47.83, 18834.8, 10713.55, -43.12, 46.4, 50.59, 2036, 449, 16.6, 41.87],
  ['BOUTIQUES','MILK','MILK Jockey Plaza', 49691.1, 19148.44, -61.47, 25288.6, 9207.14, -63.59, 50.89, 48.08, 4462, 1093, 8.52, 13.99],
  ['BOUTIQUES','MILK','MILK MA Iquitos', 13908.87, 14477.16, 4.09, 3577.63, 3414.74, -4.55, 25.72, 23.59, 0, 0, 0.0, 0.0],
  ['BOUTIQUES','MILK','MILK RP Puruchuco', 19302.13, 12878.57, -33.28, 9803.53, 6407.29, -34.64, 50.79, 49.75, 0, 0, 0.0, 0.0],
  ['BOUTIQUES','MILK','MILK MP Trujillo', 63756.82, 0, -100.0, 33576.5, 0, -100.0, 52.66, 0.0, 0, 0, 0.0, 0.0],
  ['BOUTIQUES','FINA','FINA Jockey Plaza', 97950.75, 58146.23, -40.64, 58623.9, 33508.67, -42.84, 59.85, 57.63, 0, 1195, 0.0, 24.85],
  ['BOUTIQUES','FINA','FINA Caminos del Inca', 0, 54359.5, -100.0, 0, 31398.76, 0.0, 0.0, 57.76, 0, 1693, 0.0, 20.5],
  ['BOUTIQUES','FINA','FINA El Polo II', 37250.23, 31351.06, -15.84, 22887.25, 17967.54, -21.5, 61.44, 57.31, 0, 544, 0.0, 34.74],
  ['OUTLETS','OUTLETS','Outlet Vulcano II', 49945.04, 47418.95, -5.06, 7464.81, -17767.78, -338.02, 14.95, -37.47, 0, 0, 0.0, 0.0],
  ['OUTLETS','OUTLETS','Outlet OP Atocongo', 34461.76, 32648.12, -5.26, 5675.36, -15568.22, -374.31, 16.47, -47.68, 0, 0, 0.0, 0.0],
  ['OUTLETS','OUTLETS','Outlet Arauco Arequipa', 21426.31, 28963.66, 35.18, 2933.1, -6529.5, -322.61, 13.69, -22.54, 0, 0, 0.0, 0.0],
  ['OUTLETS','OUTLETS','Outlet Minka', 34351.84, 28780.93, -16.22, 6040.01, -12808.24, -312.06, 17.58, -44.5, 0, 0, 0.0, 0.0],
  ['OUTLETS','OUTLETS','Outlet OP La Marina', 35317.29, 25405.29, -28.07, 5586.32, -16853.7, -401.7, 15.82, -66.34, 0, 0, 0.0, 0.0],
  ['OUTLETS','OUTLETS','Outlet San Juan de Lurigancho', 29721.26, 17315.58, -41.74, 2966.32, -18077.2, -709.42, 9.98, -104.4, 0, 0, 0.0, 0.0],
  ['OUTLETS','OUTLETS','Outlet RP Huánuco', 30948.59, 0, -100.0, 5112.9, 0, -100.0, 16.52, 0.0, 0, 0, 0.0, 0.0],
  ['WEB','WEB','MCH Online', 73363.7, 96432.23, 31.44, 34947.51, 46881.21, 34.15, 47.64, 48.62, 0, 0, 0.0, 0.0],
  ['WEB','WEB','EXIT Online', 83439.62, 57159.92, -31.5, 33391.34, 27144.61, -18.71, 40.02, 47.49, 0, 0, 0.0, 0.0],
  ['WEB','WEB','FINA Online', 25083.85, 35456.02, 41.35, 11852.26, 16412.4, 38.47, 47.25, 46.29, 0, 0, 0.0, 0.0],
  ['WEB','WEB','MILK Online', 47675.48, 25667.15, -46.16, 13740.22, 5896.85, -57.08, 28.82, 22.97, 0, 0, 0.0, 0.0],
];
const TIENDAS_PERIODO = '🗓️ Junio 2026 vs Junio 2025 · 51 locales';

// ════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════
function fmtSoles(v) {
  const sign = v < 0 ? '-' : '';
  const abs = Math.abs(v);
  if (abs >= 1000000) return `${sign}S/ ${(abs/1000000).toFixed(2)}M`;
  if (abs >= 1000)    return `${sign}S/ ${(abs/1000).toFixed(0)}K`;
  return `${sign}S/ ${abs.toFixed(0)}`;
}
function fmtSolesFull(v) {
  const sign = v < 0 ? '-' : '';
  return `${sign}S/ ${Math.abs(v).toLocaleString('es-PE', {maximumFractionDigits:0})}`;
}
function fmtPct(v, decimals=1) {
  return `${v.toFixed(decimals)}%`;
}
function fmtPP(v) {
  return `${v.toFixed(2)} pp`;
}
function deltaClass(v) { return v >= 0 ? 'delta-pos' : 'delta-neg'; }
function deltaArrow(v) { return v >= 0 ? '▲' : '▼'; }
function pillClass(v)  { return v >= 0 ? 'pill-pos' : 'pill-neg'; }
const tagMap = { BOUTIQUES:'tag-boutiques', OUTLETS:'tag-outlets', WEB:'tag-web' };

// ── CHART DEFAULTS ──
Chart.defaults.color = '#8AB4D8';
Chart.defaults.font.family = 'Inter';
Chart.defaults.font.size = 11;
const gridColor = 'rgba(255,255,255,0.06)';
const blue25 = '#3B8ED4';
const terra26 = '#D97B4A';
const isMobile = () => window.innerWidth <= 600;

function barDataset(label, data, color, alpha=0.85) {
  return {
    label, data,
    backgroundColor: color + Math.round(alpha*255).toString(16).padStart(2,'0'),
    borderColor: color,
    borderWidth: 1.5,
    borderRadius: isMobile() ? 3 : 5,
    borderSkipped: false,
  };
}
const axisStyle = {
  grid: { color: gridColor, drawBorder: false },
  ticks: { color: '#6A8BA8', padding: 6, maxRotation: 35, font: { size: isMobile() ? 9 : 11 } }
};
function baseOptions(yFormatter) {
  return {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ` ${yFormatter(ctx.parsed.y)}` } }
    },
    scales: {
      x: { ...axisStyle },
      y: { ...axisStyle, ticks: { ...axisStyle.ticks, callback: v => yFormatter(v) } }
    }
  };
}

// ════════════════════════════════════════════════
// RENDER: KPI ROW
// ════════════════════════════════════════════════
function renderKPIs(p) {
  const k = p.kpis;
  return `
  <div class="kpi-row">
    <div class="kpi" style="border-top-color: var(--sky)">
      <div class="kpi-label">Venta Neta</div>
      <div class="kpi-value">${fmtSoles(k.ventaNeta.v26)}</div>
      <div class="kpi-sub">vs ${fmtSoles(k.ventaNeta.v25)} año anterior</div>
      <div class="kpi-delta ${deltaClass(k.ventaNeta.varPct)}">${deltaArrow(k.ventaNeta.varPct)} ${fmtPct(Math.abs(k.ventaNeta.varPct))}</div>
    </div>
    <div class="kpi" style="border-top-color: var(--terra)">
      <div class="kpi-label">Utilidad</div>
      <div class="kpi-value">${fmtSoles(k.utilidad.v26)}</div>
      <div class="kpi-sub">vs ${fmtSoles(k.utilidad.v25)} año anterior</div>
      <div class="kpi-delta ${deltaClass(k.utilidad.varPct)}">${deltaArrow(k.utilidad.varPct)} ${fmtPct(Math.abs(k.utilidad.varPct))}</div>
    </div>
    <div class="kpi" style="border-top-color: var(--gold)">
      <div class="kpi-label">Gross Margin</div>
      <div class="kpi-value">${k.gm.v26.toFixed(1)}%</div>
      <div class="kpi-sub">vs ${k.gm.v25.toFixed(1)}% año anterior</div>
      <div class="kpi-delta ${deltaClass(k.gm.deltaPP)}">${deltaArrow(k.gm.deltaPP)} ${fmtPP(Math.abs(k.gm.deltaPP))}</div>
    </div>
    <div class="kpi" style="border-top-color: #9B59B6">
      <div class="kpi-label">Canal Líder</div>
      <div class="kpi-value">${k.canalLider.nombre}</div>
      <div class="kpi-sub">${k.canalLider.share.toFixed(1)}% del total ventas</div>
      <div class="kpi-delta ${deltaClass(k.canalLider.varPct)}">${deltaArrow(k.canalLider.varPct)} ${fmtPct(Math.abs(k.canalLider.varPct))}</div>
    </div>
    <div class="kpi" style="border-top-color: var(--green)">
      <div class="kpi-label">Foot Fall</div>
      <div class="kpi-value">${(k.footFall.v26/1000).toFixed(1)}K</div>
      <div class="kpi-sub">vs ${(k.footFall.v25/1000).toFixed(1)}K año anterior</div>
      <div class="kpi-delta ${deltaClass(k.footFall.varPct)}">${deltaArrow(k.footFall.varPct)} ${fmtPct(Math.abs(k.footFall.varPct))}</div>
    </div>
    <div class="kpi" style="border-top-color: #E91E63">
      <div class="kpi-label">Conv. Rate</div>
      <div class="kpi-value">${k.convRate.v26.toFixed(1)}%</div>
      <div class="kpi-sub">vs ${k.convRate.v25.toFixed(1)}% año anterior</div>
      <div class="kpi-delta ${deltaClass(k.convRate.deltaPP)}">${deltaArrow(k.convRate.deltaPP)} ${fmtPP(Math.abs(k.convRate.deltaPP))}</div>
    </div>
  </div>`;
}

// ════════════════════════════════════════════════
// RENDER: FULL PANEL HTML
// ════════════════════════════════════════════════
function renderPanel(key, p) {
  const tableRows = p.table.map(([canal, marca, v25, v26, varV, ut25, ut26, gm25, gm26, varU, ff25, ff26, cr25, cr26]) => {
    return `
      <tr>
        <td><span class="canal-tag ${tagMap[canal]}">${canal}</span></td>
        <td class="brand-name">${marca}</td>
        <td style="text-align:right" class="num">${fmtSolesFull(v25)}</td>
        <td style="text-align:right" class="num">${fmtSolesFull(v26)}</td>
        <td style="text-align:center"><span class="pill ${pillClass(varV)}">${deltaArrow(varV)} ${fmtPct(Math.abs(varV))}</span></td>
        <td style="text-align:right" class="num">${fmtSolesFull(ut26)}</td>
        <td style="text-align:center" class="num">${gm26.toFixed(1)}%</td>
        <td style="text-align:center"><span class="pill ${pillClass(varU)}">${deltaArrow(varU)} ${fmtPct(Math.abs(varU))}</span></td>
        <td style="text-align:right" class="num">${ff25.toLocaleString('es-PE')}</td>
        <td style="text-align:right" class="num">${ff26.toLocaleString('es-PE')}</td>
        <td style="text-align:center" class="num">${cr25.toFixed(1)}%</td>
        <td style="text-align:center" class="num">${cr26.toFixed(1)}%</td>
      </tr>`;
  }).join('');

  return `
    <div class="period-badge"><span class="dot"></span>${p.badge}</div>

    <div class="card">
      <div class="section-title">Resumen Ejecutivo por Marca</div>
      <div class="var-table-wrap">
      <table class="var-table">
        <thead>
          <tr>
            <th>Canal</th>
            <th>Marca</th>
            <th style="text-align:right">Vta 2025</th>
            <th style="text-align:right">Vta 2026</th>
            <th style="text-align:center">%Var Vta</th>
            <th style="text-align:right">Utilidad 26</th>
            <th style="text-align:center">GM 26</th>
            <th style="text-align:center">%Var Ut.</th>
            <th style="text-align:right">FF 2025</th>
            <th style="text-align:right">FF 2026</th>
            <th style="text-align:center">CR 2025</th>
            <th style="text-align:center">CR 2026</th>
          </tr>
        </thead>
        <tbody>${tableRows}</tbody>
      </table>
      </div>
    </div>

    ${key === 'mensual' ? renderTiendasSection() : ''}

    ${renderKPIs(p)}

    <div class="chart-grid-2">
      <div class="card">
        <div class="card-title">Venta Neta por Marca</div>
        <div class="card-sub">Comparativo 2025 vs 2026 · En Soles (S/)</div>
        <div class="canvas-wrap"><canvas id="${key}-chartVentas" height="200"></canvas></div>
        <div class="legend">
          <div class="legend-item"><div class="legend-dot" style="background:${blue25}"></div>2025</div>
          <div class="legend-item"><div class="legend-dot" style="background:${terra26}"></div>2026</div>
        </div>
      </div>
      <div class="card">
        <div class="card-title">Utilidad por Marca</div>
        <div class="card-sub">Comparativo 2025 vs 2026 · En Soles (S/)</div>
        <div class="canvas-wrap"><canvas id="${key}-chartUtilidad" height="200"></canvas></div>
        <div class="legend">
          <div class="legend-item"><div class="legend-dot" style="background:${blue25}"></div>2025</div>
          <div class="legend-item"><div class="legend-dot" style="background:${terra26}"></div>2026</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-title">Variación % Ventas 2026 vs 2025 — por Marca</div>
      <div class="card-sub">Marcas con mayor caída vs crecimiento · Línea cero como referencia</div>
      <div class="canvas-wrap"><canvas id="${key}-chartVariacion" height="100"></canvas></div>
    </div>

    

    <div class="chart-grid-2">
      <div class="card">
        <div class="card-title">Gross Margin % por Marca</div>
        <div class="card-sub">Rentabilidad comparada 2025 vs 2026</div>
        <div class="canvas-wrap"><canvas id="${key}-chartGM" height="200"></canvas></div>
        <div class="legend">
          <div class="legend-item"><div class="legend-dot" style="background:${blue25}"></div>2025</div>
          <div class="legend-item"><div class="legend-dot" style="background:${terra26}"></div>2026</div>
        </div>
      </div>
      <div class="card">
        <div class="card-title">Participación de Ventas por Canal — 2026</div>
        <div class="card-sub">Distribución del total de ventas netas</div>
        <div class="canvas-wrap" style="max-width:320px;margin:0 auto;"><canvas id="${key}-chartCanal" height="200"></canvas></div>
      </div>
    </div>

    <div class="chart-grid-2">
      <div class="card">
        <div class="card-title">Foot Fall por Marca — Boutiques</div>
        <div class="card-sub">Tráfico de visitas 2025 vs 2026</div>
        <div class="canvas-wrap"><canvas id="${key}-chartFF" height="200"></canvas></div>
        <div class="legend">
          <div class="legend-item"><div class="legend-dot" style="background:${blue25}"></div>2025</div>
          <div class="legend-item"><div class="legend-dot" style="background:${terra26}"></div>2026</div>
        </div>
      </div>
      <div class="card">
        <div class="card-title">Conversion Rate por Marca — Boutiques</div>
        <div class="card-sub">% de visitas que concretaron compra</div>
        <div class="canvas-wrap"><canvas id="${key}-chartCR" height="200"></canvas></div>
        <div class="legend">
          <div class="legend-item"><div class="legend-dot" style="background:${blue25}"></div>2025</div>
          <div class="legend-item"><div class="legend-dot" style="background:${terra26}"></div>2026</div>
        </div>
      </div>
    </div>

    

    
  `;
}

// ════════════════════════════════════════════════
// RENDER: SECCIÓN COLAPSABLE DETALLE POR TIENDA
// ════════════════════════════════════════════════
function renderTiendasSection() {
  const storeRows = TIENDAS.map(([canal, marca, sucursal, v25, v26, varV, ut25, ut26, varU, gm25, gm26, ff25, ff26, cr25, cr26]) => `
    <tr data-canal="${canal}">
      <td><span class="canal-tag ${tagMap[canal]}">${canal}</span></td>
      <td class="brand-name">${sucursal}</td>
      <td style="text-align:right" class="num">${fmtSolesFull(v25)}</td>
      <td style="text-align:right" class="num">${fmtSolesFull(v26)}</td>
      <td style="text-align:center"><span class="pill ${pillClass(varV)}">${deltaArrow(varV)} ${fmtPct(Math.abs(varV))}</span></td>
      <td style="text-align:right" class="num">${fmtSolesFull(ut26)}</td>
      <td style="text-align:center" class="num">${gm26.toFixed(1)}%</td>
      <td style="text-align:center"><span class="pill ${pillClass(varU)}">${deltaArrow(varU)} ${fmtPct(Math.abs(varU))}</span></td>
      <td style="text-align:right" class="num">${ff26.toLocaleString('es-PE')}</td>
      <td style="text-align:center" class="num">${cr26.toFixed(1)}%</td>
    </tr>`).join('');

  return `
    <div class="collapsible" id="tiendas-collapsible">
      <div class="collapsible-header" onclick="toggleTiendas()">
        <div class="collapsible-header-left">
          <div>
            <div class="collapsible-title">🏬 Detalle por Tienda / Sucursal</div>
            <div class="collapsible-sub">${TIENDAS_PERIODO} · Click para expandir</div>
          </div>
        </div>
        <div class="collapsible-chevron">▾</div>
      </div>
      <div class="collapsible-body">
        <div class="collapsible-body-inner">

          <div class="store-filter-row" id="store-filters">
            <button class="store-filter-btn active" data-canal="ALL" onclick="filterTiendas('ALL')">Todas</button>
            <button class="store-filter-btn" data-canal="BOUTIQUES" onclick="filterTiendas('BOUTIQUES')">Boutiques</button>
            <button class="store-filter-btn" data-canal="OUTLETS" onclick="filterTiendas('OUTLETS')">Outlets</button>
            <button class="store-filter-btn" data-canal="WEB" onclick="filterTiendas('WEB')">Web</button>
          </div>



          <div class="card" style="padding:18px 20px 14px;">
            <div class="section-title">Tabla completa — ${TIENDAS.length} locales</div>
            <div class="var-table-wrap">
              <table class="var-table" id="tabla-tiendas">
                <thead>
                  <tr>
                    <th>Canal</th>
                    <th>Sucursal</th>
                    <th style="text-align:right">Vta 2025</th>
                    <th style="text-align:right">Vta 2026</th>
                    <th style="text-align:center">%Var Vta</th>
                    <th style="text-align:right">Utilidad 26</th>
                    <th style="text-align:center">GM 26</th>
                    <th style="text-align:center">%Var Ut.</th>
                    <th style="text-align:right">FF 2026</th>
                    <th style="text-align:center">CR 2026</th>
                  </tr>
                </thead>
                <tbody>${storeRows}</tbody>
              </table>
            </div>
          </div>

          <div class="chart">
            <div class="card" style="padding:18px 20px 14px;">
              <div class="card-title">Top 10 Tiendas por Venta Neta — Junio 2026</div>
              <div class="card-sub">Ranking de locales con mayor facturación</div>
              <div class="canvas-wrap"><canvas id="chartTiendasTop" height="240"></canvas></div>
            </div>
            
          </div>

        </div>
      </div>
    </div>
  `;
}

// ════════════════════════════════════════════════
// CHARTS
// ════════════════════════════════════════════════
function createCharts(key) {
  const p = PERIODS[key];

  // 1. Ventas
  new Chart(document.getElementById(`${key}-chartVentas`), {
    type: 'bar',
    data: { labels: p.marcas, datasets: [ barDataset('Venta 2025', p.vta25, blue25), barDataset('Venta 2026', p.vta26, terra26) ] },
    options: baseOptions(fmtSoles)
  });

  // 2. Utilidad
  new Chart(document.getElementById(`${key}-chartUtilidad`), {
    type: 'bar',
    data: { labels: p.marcas, datasets: [ barDataset('Utilidad 2025', p.ut25, blue25), barDataset('Utilidad 2026', p.ut26, terra26) ] },
    options: baseOptions(fmtSoles)
  });

  // 3. Variación %
  const varColors  = p.varVta.map(v => v >= 0 ? 'rgba(39,174,96,0.8)'  : 'rgba(231,76,60,0.8)');
  const varBorders = p.varVta.map(v => v >= 0 ? '#27AE60' : '#E74C3C');
  new Chart(document.getElementById(`${key}-chartVariacion`), {
    type: 'bar',
    data: {
      labels: p.marcas,
      datasets: [{
        label: '%Var Ventas 26 vs 25',
        data: p.varVta,
        backgroundColor: varColors,
        borderColor: varBorders,
        borderWidth: 1.5,
        borderRadius: isMobile() ? 3 : 5,
        borderSkipped: false,
      }]
    },
    options: {
      ...baseOptions(v => v.toFixed(1) + '%'),
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y.toFixed(1)}%` } } }
    }
  });

  // 4. Gross Margin
  new Chart(document.getElementById(`${key}-chartGM`), {
    type: 'bar',
    data: { labels: p.marcas, datasets: [ barDataset('%GM 2025', p.gm25, blue25), barDataset('%GM 2026', p.gm26, terra26) ] },
    options: baseOptions(v => v.toFixed(0) + '%')
  });

  // 5. Canal Donut
  const canalTotal = p.canal.total;
  new Chart(document.getElementById(`${key}-chartCanal`), {
    type: 'doughnut',
    data: {
      labels: p.canal.labels,
      datasets: [{
        data: p.canal.values,
        backgroundColor: ['#3B8ED4CC', '#D97B4ACC', '#27AE60CC'],
        borderColor:     ['#3B8ED4',   '#D97B4A',   '#27AE60'],
        borderWidth: 2,
        hoverOffset: 8,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      cutout: '62%',
      plugins: {
        legend: { position: 'bottom', labels: { color: '#8AB4D8', padding: 14, font: { size: isMobile() ? 10 : 11 } } },
        tooltip: { callbacks: { label: ctx => ` ${fmtSoles(ctx.parsed)}  (${(ctx.parsed/canalTotal*100).toFixed(1)}%)` } }
      }
    }
  });

  // 6. Foot Fall
  new Chart(document.getElementById(`${key}-chartFF`), {
    type: 'bar',
    data: { labels: p.marcasFF, datasets: [ barDataset('FF 2025', p.ff25.map(v=>v/1000), blue25), barDataset('FF 2026', p.ff26.map(v=>v/1000), terra26) ] },
    options: {
      ...baseOptions(v => v.toFixed(0) + 'K'),
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${(ctx.parsed.y*1000).toLocaleString('es-PE',{maximumFractionDigits:0})} visitas` } } }
    }
  });

  // 7. Conversion Rate
  new Chart(document.getElementById(`${key}-chartCR`), {
    type: 'bar',
    data: { labels: p.marcasFF, datasets: [ barDataset('CR 2025', p.cr25, blue25), barDataset('CR 2026', p.cr26, terra26) ] },
    options: baseOptions(v => v.toFixed(1) + '%')
  });
}

// ════════════════════════════════════════════════
// SECCIÓN TIENDAS: toggle, filtro y gráficos
// ════════════════════════════════════════════════
let tiendasChartsCreated = false;

function toggleTiendas() {
  const el = document.getElementById('tiendas-collapsible');
  const wasOpen = el.classList.contains('open');
  el.classList.toggle('open');
  if (!wasOpen && !tiendasChartsCreated) {
    createTiendasCharts();
    tiendasChartsCreated = true;
  }
}

function filterTiendas(canal) {
  document.querySelectorAll('#store-filters .store-filter-btn').forEach(b => {
    b.classList.toggle('active', b.dataset.canal === canal);
  });
  document.querySelectorAll('#tabla-tiendas tbody tr').forEach(tr => {
    tr.style.display = (canal === 'ALL' || tr.dataset.canal === canal) ? '' : 'none';
  });
}

function createTiendasCharts() {
  // Top 10 tiendas por venta 2026
  const sorted = [...TIENDAS].sort((a,b) => b[4] - a[4]).slice(0, 10);
  const topLabels = sorted.map(t => t[2].length > 18 ? t[2].slice(0,16)+'…' : t[2]);
  const topData   = sorted.map(t => t[4]);
  const topColors = sorted.map(t => t[0]==='BOUTIQUES' ? blue25 : (t[0]==='OUTLETS' ? '#F5A623' : '#27AE60'));

  new Chart(document.getElementById('chartTiendasTop'), {
    type: 'bar',
    data: {
      labels: topLabels,
      datasets: [{
        label: 'Venta Neta 2026',
        data: topData,
        backgroundColor: topColors.map(c => c + 'CC'),
        borderColor: topColors,
        borderWidth: 1.5,
        borderRadius: isMobile() ? 3 : 5,
        borderSkipped: false,
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ` ${fmtSoles(ctx.parsed.x)}` } }
      },
      scales: {
        x: { ...axisStyle, ticks: { ...axisStyle.ticks, callback: v => fmtSoles(v), maxRotation: 0 } },
        y: { ...axisStyle, ticks: { ...axisStyle.ticks, font: { size: isMobile() ? 8 : 10 } } }
      }
    }
  });

  // Variación: 5 mayores caídas + 5 mayores subidas
  const byVar = [...TIENDAS].sort((a,b) => a[5] - b[5]);
  const worst5 = byVar.slice(0, 5);
  const best5  = byVar.slice(-5).reverse();
  const varSet = [...worst5, ...best5];
  const varLabels = varSet.map(t => t[2].length > 18 ? t[2].slice(0,16)+'…' : t[2]);
  const varData   = varSet.map(t => t[5]);
  const varColors  = varData.map(v => v >= 0 ? 'rgba(39,174,96,0.8)' : 'rgba(231,76,60,0.8)');
  const varBorders = varData.map(v => v >= 0 ? '#27AE60' : '#E74C3C');

  new Chart(document.getElementById('chartTiendasVar'), {
    type: 'bar',
    data: {
      labels: varLabels,
      datasets: [{
        label: '%Var Venta',
        data: varData,
        backgroundColor: varColors,
        borderColor: varBorders,
        borderWidth: 1.5,
        borderRadius: isMobile() ? 3 : 5,
        borderSkipped: false,
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.x.toFixed(1)}%` } }
      },
      scales: {
        x: { ...axisStyle, ticks: { ...axisStyle.ticks, callback: v => v.toFixed(0) + '%', maxRotation: 0 } },
        y: { ...axisStyle, ticks: { ...axisStyle.ticks, font: { size: isMobile() ? 8 : 10 } } }
      }
    }
  });
}

// ════════════════════════════════════════════════
// INIT + TAB SWITCHING
// ════════════════════════════════════════════════
const chartsCreated = { anual: false, mensual: false, semanal: false };

function switchTab(key) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === key));
  document.querySelectorAll('.tab-panel').forEach(pnl => pnl.classList.toggle('active', pnl.id === `panel-${key}`));
  if (!chartsCreated[key]) {
    createCharts(key);
    chartsCreated[key] = true;
  }
}

// Render all panel HTML up front
Object.keys(PERIODS).forEach(key => {
  document.getElementById(`panel-${key}`).innerHTML = renderPanel(key, PERIODS[key]);
});

// Create charts for the default (visible) tab
createCharts('anual');
chartsCreated.anual = true;
</script>
</body>
</html>