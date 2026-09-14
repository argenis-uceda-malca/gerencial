@extends('layouts.base')

@section('title', 'Monitor de Ventas')

@push('styles')
<style>
/* ── KPI tiles ─────────────────────────────────────────────── */
.kpi-card {
  border-radius: 12px;
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  background: var(--dm-card-bg, #fff);
  border: 1px solid var(--dm-border, #E8ECF5);
}
.kpi-label  { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing:.06em; color: var(--dm-muted, #8E9BB4); }
.kpi-value  { font-size: 26px; font-weight: 700; line-height:1.1; color: var(--dm-ink, #1E2A3A); }
.kpi-sub    { font-size: 12px; color: var(--dm-muted, #8E9BB4); }

/* ── Estado general (banner) ───────────────────────────────── */
.estado-banner {
  border-radius: 12px;
  padding: 14px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 600;
  font-size: 15px;
}
.estado-banner.ok      { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
.estado-banner.warning { background:#fffbeb; color:#92400e; border:1px solid #fde68a; }
.estado-banner.critico { background:#fff1f2; color:#991b1b; border:1px solid #fecaca; }

[data-theme="dark"] .estado-banner.ok      { background:#052e16; color:#86efac; border-color:#14532d; }
[data-theme="dark"] .estado-banner.warning { background:#1c1a08; color:#fbbf24; border-color:#854d0e; }
[data-theme="dark"] .estado-banner.critico { background:#1e0a0a; color:#fca5a5; border-color:#7f1d1d; }

/* ── Alerta cards ──────────────────────────────────────────── */
.alerta-card {
  border-radius: 10px;
  padding: 14px 16px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
}
.alerta-card.critico { background:#fff1f2; border:1px solid #fecaca; }
.alerta-card.warning { background:#fffbeb; border:1px solid #fde68a; }
.alerta-card.info    { background:#eff6ff; border:1px solid #bfdbfe; }
[data-theme="dark"] .alerta-card.critico { background:#1e0a0a; border-color:#7f1d1d; }
[data-theme="dark"] .alerta-card.warning { background:#1c1a08; border-color:#854d0e; }
[data-theme="dark"] .alerta-card.info    { background:#0c1a2e; border-color:#1e3a5f; }
.alerta-icon  { font-size: 20px; flex-shrink:0; padding-top:1px; }
.alerta-title { font-size: 14px; font-weight: 600; margin-bottom:2px; }
.alerta-card.critico .alerta-title { color:#991b1b; }
.alerta-card.warning .alerta-title { color:#92400e; }
.alerta-card.info    .alerta-title { color:#1d4ed8; }
[data-theme="dark"] .alerta-card.critico .alerta-title { color:#fca5a5; }
[data-theme="dark"] .alerta-card.warning .alerta-title { color:#fbbf24; }
.alerta-detail { font-size: 12px; color: var(--dm-muted, #6b7280); line-height:1.5; }

/* ── Tiendas grid ──────────────────────────────────────────── */
.tiendas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
  gap: 10px;
}
.tienda-tile {
  border-radius: 10px;
  padding: 12px 14px;
  border: 1px solid var(--dm-border, #E8ECF5);
  background: var(--dm-card-bg, #fff);
  position: relative;
  transition: box-shadow .15s;
}
.tienda-tile:hover { box-shadow: 0 2px 12px rgba(0,0,0,.08); }
.tienda-tile.verde  { border-left: 4px solid #22c55e; }
.tienda-tile.rojo   { border-left: 4px solid #ef4444; }
.tienda-tile.gris   { border-left: 4px solid #9ca3af; opacity:.75; }
.tienda-nombre  { font-size: 12px; font-weight: 600; color: var(--dm-ink, #1E2A3A); line-height:1.3; margin-bottom:4px; }
.tienda-marca   { font-size: 10px; text-transform:uppercase; letter-spacing:.05em; color: var(--dm-muted, #8E9BB4); }
.tienda-venta   { font-size: 15px; font-weight: 700; margin-top: 6px; }
.tienda-tile.verde .tienda-venta { color: #16a34a; }
.tienda-tile.rojo  .tienda-venta { color: #dc2626; }
.tienda-tile.gris  .tienda-venta { color: var(--dm-muted, #9ca3af); }
.tienda-prom    { font-size: 10px; color: var(--dm-muted, #9ca3af); }

/* ── Log ETL table ─────────────────────────────────────────── */
.log-table { font-size: 12px; }
.badge-ok      { background:#dcfce7; color:#166534; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:600; }
.badge-error   { background:#fee2e2; color:#991b1b; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:600; }
[data-theme="dark"] .badge-ok    { background:#052e16; color:#86efac; }
[data-theme="dark"] .badge-error { background:#1e0a0a; color:#fca5a5; }

/* ── Refresh bar ───────────────────────────────────────────── */
.refresh-bar { font-size: 11px; color: var(--dm-muted, #9ca3af); display:flex; align-items:center; gap:6px; }
.refresh-dot { width:7px; height:7px; border-radius:50%; background:#22c55e; animation:pulse 2s infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.35} }

/* ── Ratio gauge ───────────────────────────────────────────── */
.ratio-bar-wrap { background: var(--dm-border,#E8ECF5); border-radius:6px; height:6px; overflow:hidden; margin-top:6px; }
.ratio-bar-fill { height:100%; border-radius:6px; transition:width .4s; }
</style>
@endpush

@section('contenido')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- ── Cabecera ─────────────────────────────────────────────────── --}}
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-0">Monitor de Ventas</h4>
      <div class="refresh-bar mt-1">
        <span class="refresh-dot"></span>
        <span>Actualizado: {{ $ahora->format('d/m/Y H:i:s') }} (Lima) · Auto-refresh en <span id="countdown">300</span>s</span>
      </div>
    </div>
    <a href="{{ route('monitor.ventas') }}" class="btn btn-sm btn-outline-secondary">
      <i class="bx bx-refresh me-1"></i>Actualizar ahora
    </a>
  </div>

  {{-- ── Banner de estado general ─────────────────────────────────── --}}
  @php
    $criticos = array_filter($alertas, fn($a) => $a['nivel'] === 'critico');
    $warnings = array_filter($alertas, fn($a) => $a['nivel'] === 'warning');
    if (count($criticos))        $estadoClase = 'critico';
    elseif (count($warnings))    $estadoClase = 'warning';
    else                         $estadoClase = 'ok';
  @endphp
  <div class="estado-banner {{ $estadoClase }} mb-4">
    @if($estadoClase === 'ok')
      <i class="bx bx-check-circle fs-4"></i>
      <span>Sistema operando con normalidad</span>
    @elseif($estadoClase === 'warning')
      <i class="bx bx-error fs-4"></i>
      <span>{{ count($warnings) }} aviso(s) · revisar abajo</span>
    @else
      <i class="bx bx-x-circle fs-4"></i>
      <span>{{ count($criticos) }} alerta(s) CRÍTICA(S) · acción requerida</span>
      @if(count($warnings))
        <span class="ms-2 fw-normal fs-6">+ {{ count($warnings) }} aviso(s)</span>
      @endif
    @endif
  </div>

  {{-- ── KPI tiles ─────────────────────────────────────────────────── --}}
  @php
    $ratio    = ($stats['promedio'] > 0 && $ventas['total'] > 0)
                ? $ventas['total'] / $stats['promedio']
                : 0;
    $ratioFmt = number_format($ratio, 2) . '×';
    $ratioColor = $ratio > \App\Services\MonitoreoVentasService::UMBRAL_ALTO ? '#ef4444'
                : ($ratio < \App\Services\MonitoreoVentasService::UMBRAL_BAJO && $hora >= 16 ? '#ef4444'
                : ($ratio > 0.8 ? '#22c55e' : '#f59e0b'));
    $ratioW   = min(100, round($ratio / max(\App\Services\MonitoreoVentasService::UMBRAL_ALTO, $ratio) * 100));

    $tiendasConVenta = count(array_filter((array)$tiendas, fn($t) => (float)$t->venta_hoy > 0));
    $totalTiendas    = count($tiendas);
    $tiendasSilenciosas = array_filter((array)$tiendas, fn($t) => (float)$t->venta_hoy == 0 && (int)$t->dias_semana >= 3);
  @endphp

  <div class="row g-3 mb-4">
    {{-- ETL --}}
    <div class="col-6 col-md-3">
      <div class="kpi-card">
        <div class="kpi-label"><i class="bx bx-cog me-1"></i>ETL — última ejecución</div>
        <div class="kpi-value {{ $etl['ok'] ? 'text-success' : 'text-danger' }}">
          {{ $etl['horas'] !== null ? $etl['horas'] . 'h' : '—' }}
        </div>
        <div class="kpi-sub">
          @if($etl['ok'])
            <i class="bx bx-check text-success"></i> OK · {{ $etl['fecha_lima'] }}
          @else
            <i class="bx bx-x text-danger"></i> Atrasado · {{ $etl['fecha_lima'] ?? $etl['error'] }}
          @endif
        </div>
      </div>
    </div>

    {{-- Venta hoy --}}
    <div class="col-6 col-md-3">
      <div class="kpi-card">
        <div class="kpi-label"><i class="bx bx-dollar me-1"></i>Venta hoy</div>
        <div class="kpi-value">S/ {{ number_format($ventas['total'], 0, '.', ',') }}</div>
        <div class="kpi-sub">{{ number_format($ventas['filas']) }} transacciones</div>
      </div>
    </div>

    {{-- Ratio --}}
    <div class="col-6 col-md-3">
      <div class="kpi-card">
        <div class="kpi-label"><i class="bx bx-stats me-1"></i>Ratio vs 30d</div>
        <div class="kpi-value" style="color:{{ $ratioColor }}">{{ $ratioFmt }}</div>
        <div class="kpi-sub">Prom: S/ {{ number_format($stats['promedio'], 0, '.', ',') }}</div>
        <div class="ratio-bar-wrap">
          <div class="ratio-bar-fill" style="width:{{ $ratioW }}%;background:{{ $ratioColor }}"></div>
        </div>
      </div>
    </div>

    {{-- Tiendas --}}
    <div class="col-6 col-md-3">
      <div class="kpi-card">
        <div class="kpi-label"><i class="bx bx-store me-1"></i>Tiendas activas</div>
        <div class="kpi-value">{{ $tiendasConVenta }} / {{ $totalTiendas }}</div>
        <div class="kpi-sub">
          @if(count($tiendasSilenciosas) > 0)
            <span class="text-danger"><i class="bx bx-error-circle"></i> {{ count($tiendasSilenciosas) }} sin ventas hoy</span>
          @else
            <span class="text-success"><i class="bx bx-check"></i> Todas reportaron hoy</span>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- ── Alertas activas ──────────────────────────────────────────── --}}
  @if(count($alertas))
  <div class="mb-4">
    <h6 class="fw-semibold mb-2"><i class="bx bx-bell me-1 text-danger"></i>Alertas detectadas</h6>
    <div class="d-flex flex-column gap-2">
      @foreach($alertas as $a)
      <div class="alerta-card {{ $a['nivel'] }}">
        <div class="alerta-icon">
          @if($a['nivel'] === 'critico') 🔴
          @elseif($a['nivel'] === 'warning') 🟡
          @else 🔵
          @endif
        </div>
        <div>
          <div class="alerta-title">{{ $a['titulo'] }}</div>
          <div class="alerta-detail">{{ $a['detalle'] }}</div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- ── Tiendas silenciosas ──────────────────────────────────────── --}}
  @if(count($tiendasSilenciosas) && $hora >= \App\Services\MonitoreoVentasService::HORA_TIENDAS)
  <div class="mb-4 p-3 rounded-3" style="background:#fff1f2;border:1px solid #fecaca;">
    <div class="fw-semibold text-danger mb-1 fs-6"><i class="bx bx-store-off me-1"></i>Tiendas sin ventas hoy (activas la semana pasada)</div>
    <div class="d-flex flex-wrap gap-2 mt-2">
      @foreach($tiendasSilenciosas as $t)
        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-1 rounded-pill" style="font-size:12px">
          {{ $t->sucursal_2 }}
          <small class="ms-1 opacity-75">({{ $t->dias_semana }}d)</small>
        </span>
      @endforeach
    </div>
  </div>
  @endif

  {{-- ── Grid de tiendas ─────────────────────────────────────────── --}}
  <div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between py-2">
      <h6 class="mb-0 fw-semibold"><i class="bx bx-grid-alt me-1"></i>Estado por tienda — hoy vs semana</h6>
      <div class="d-flex gap-3" style="font-size:11px;color:var(--dm-muted)">
        <span><span style="color:#22c55e;font-size:15px">●</span> Con ventas</span>
        <span><span style="color:#ef4444;font-size:15px">●</span> Sin ventas (esperada)</span>
        <span><span style="color:#9ca3af;font-size:15px">●</span> Sin ventas (ocasional)</span>
      </div>
    </div>
    <div class="card-body pt-2">
      <div class="tiendas-grid">
        @forelse($tiendas as $t)
          @php
            $venta    = (float)$t->venta_hoy;
            $diasSem  = (int)$t->dias_semana;
            $clase    = $venta > 0 ? 'verde' : ($diasSem >= 3 ? 'rojo' : 'gris');
            $promTile = $t->promedio_diario > 0
                        ? 'S/ ' . number_format($t->promedio_diario, 0, '.', ',') . '/día prom'
                        : 'sin historial';
          @endphp
          <div class="tienda-tile {{ $clase }}">
            <div class="tienda-marca">{{ $t->marca }}</div>
            <div class="tienda-nombre">{{ $t->sucursal_2 }}</div>
            <div class="tienda-venta">
              @if($venta > 0)
                S/ {{ number_format($venta, 0, '.', ',') }}
              @else
                Sin ventas
              @endif
            </div>
            <div class="tienda-prom">{{ $promTile }} · {{ $diasSem }}d/7</div>
          </div>
        @empty
          <p class="text-muted small">Sin datos de tiendas esta semana.</p>
        @endforelse
      </div>
    </div>
  </div>

  {{-- ── Log ETL ──────────────────────────────────────────────────── --}}
  <div class="card mb-4">
    <div class="card-header py-2">
      <h6 class="mb-0 fw-semibold"><i class="bx bx-list-ul me-1"></i>Últimas ejecuciones del ETL</h6>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm log-table mb-0">
          <thead>
            <tr>
              <th class="ps-3">Fecha (Lima)</th>
              <th>Tipo</th>
              <th>Rango procesado</th>
              <th>Seg.</th>
              <th>Estado</th>
              <th>Detalle</th>
            </tr>
          </thead>
          <tbody>
            @forelse($logEtl as $row)
            <tr>
              <td class="ps-3 text-nowrap">{{ \Carbon\Carbon::parse($row->fecha_lima)->format('d/m H:i') }}</td>
              <td>{{ $row->tipo_ejecucion }}</td>
              <td class="text-nowrap">
                @if($row->p_fecha_ini)
                  {{ \Carbon\Carbon::parse($row->p_fecha_ini)->format('d/m') }} →
                  {{ \Carbon\Carbon::parse($row->p_fecha_fin)->format('d/m') }}
                @else
                  —
                @endif
              </td>
              <td>{{ $row->seg }}</td>
              <td>
                @if($row->estado === 'OK')
                  <span class="badge-ok">OK</span>
                @else
                  <span class="badge-error">{{ $row->estado }}</span>
                @endif
              </td>
              <td class="text-muted" style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                {{ $row->mensaje_error ?? '—' }}
              </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-3">Sin registros de ejecución.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ── Alertas pg pendientes ────────────────────────────────────── --}}
  @if(count($alertasPendientes))
  <div class="card mb-4">
    <div class="card-header py-2">
      <h6 class="mb-0 fw-semibold text-danger"><i class="bx bx-error-circle me-1"></i>Alertas del orquestador sin atender</h6>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm log-table mb-0">
          <thead><tr><th class="ps-3">ID</th><th>Mensaje</th><th>Fecha</th></tr></thead>
          <tbody>
            @foreach($alertasPendientes as $a)
            <tr>
              <td class="ps-3">{{ $a->id }}</td>
              <td>{{ $a->mensaje ?? '—' }}</td>
              <td>{{ isset($a->created_at) ? \Carbon\Carbon::parse($a->created_at)->format('d/m H:i') : '—' }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

</div>
@endsection

@push('scripts')
<script>
// Auto-refresh cada 5 minutos con cuenta regresiva
(function () {
  let secs = 300;
  const el = document.getElementById('countdown');
  setInterval(() => {
    secs--;
    if (el) el.textContent = secs;
    if (secs <= 0) window.location.reload();
  }, 1000);
})();
</script>
@endpush
