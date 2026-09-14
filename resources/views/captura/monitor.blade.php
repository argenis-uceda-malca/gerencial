@extends('layouts.base')

@section('title', 'Facturación Electrónica')

@push('styles')
<style>
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
.h-dot.ok    { background:#71dd37; }
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
.tc.activa   { border-left:3px solid #71dd37; }
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
.s-dot.ok   { background:#71dd37; }
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
#tabla-reg tr.r-ok   td:first-child { border-left:3px solid #71dd37; }
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
.rf-badge.paused { background:rgba(133,146,163,.1);color:#8592a3; }

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
</style>
@endpush

@section('contenido')
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
        <small class="text-muted" style="font-size:.7rem">Última sync: <span id="sync-time">—</span></small>
        <span class="rf-badge" id="lbl-rf"><i class="bx bx-refresh"></i><span id="txt-cd">30s</span></span>
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
              <i class="bx bx-cog" style="color:#71dd37"></i>
            </div>
            <div class="pipe-label" style="color:#71dd37">Motor</div>
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
            <i class="bx bx-check-shield" style="color:#71dd37"></i>
          </div>
          <div class="flex-grow-1">
            <div class="kpi-lbl">Capturados hoy</div>
            <div class="kpi-val" style="color:#71dd37">{{ $stats['capturados_hoy'] }}</div>
            <div class="kpi-hint text-muted">Enviados a Bizlinks</div>
          </div>
        </div>
        <div class="kpi-bar" style="background:linear-gradient(90deg,#71dd37,#54b72c)"></div>
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
          <div class="progress-bar bg-success" style="width:{{ $pctAct }}%"></div>
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
      <div class="row g-2" id="tc-grid">
        @foreach($tiendas as $tienda)
        <div class="col-6 col-sm-4 col-md-3 col-xl-2 tc-item">
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
                  <span class="badge bg-success" style="font-size:.55rem">ACTIVA</span>
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
      <div class="d-flex flex-wrap align-items-center gap-2 filter-bar">
        <span class="fw-semibold small me-2">
          <i class="bx bx-list-check me-1 text-primary"></i>Comprobantes
        </span>

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

        <button class="btn btn-sm btn-outline-secondary ms-auto" id="btn-csv"
                data-bs-toggle="tooltip" title="Exportar a CSV">
          <i class="bx bx-download me-1"></i>CSV
        </button>
      </div>

      {{-- Leyenda de estados --}}
      <div class="d-flex gap-3 mt-2 flex-wrap" style="font-size:.68rem">
        <span><span class="s-dot ok"></span>Capturado — enviado a Bizlinks</span>
        <span><span class="s-dot pend"></span>Pendiente — esperando motor</span>
        <span><span class="s-dot warn"></span>Cuarentena — revisión manual</span>
        <span><span class="s-dot err"></span>Error — ver detalle</span>
      </div>
    </div>

    <div class="table-responsive">
      <table id="tabla-reg" class="table table-sm table-hover mb-0" style="width:100%">
        <thead>
          <tr>
            <th style="width:48px">#</th>
            <th style="width:68px">Tienda</th>
            <th>Serie / N°</th>
            <th style="width:52px">Tipo</th>
            <th style="width:86px">Fecha</th>
            <th class="text-end" style="width:88px">Importe</th>
            <th>Cliente</th>
            <th style="width:108px">Estado</th>
            <th style="width:100px">Capturado</th>
            <th class="text-center" style="width:90px">Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
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
<script>
(function () {
  'use strict';

  const csrf = '{{ csrf_token() }}';

  // ── Tooltips ────────────────────────────────────────────────
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el, { trigger: 'hover', boundary: 'window' });
  });

  // ── Sync time ────────────────────────────────────────────────
  function setTime() {
    const n = new Date();
    document.getElementById('sync-time').textContent =
      [n.getHours(), n.getMinutes(), n.getSeconds()]
        .map(v => String(v).padStart(2,'0')).join(':');
  }
  setTime();

  // ── Health dot ───────────────────────────────────────────────
  const dot = document.getElementById('health-dot');
  const errs = {{ $stats['errores'] }}, cuar = {{ $stats['cuarentena'] }};
  dot.className = 'h-dot ' + (errs > 0 ? 'error' : cuar > 0 ? 'warn' : 'ok');
  dot.setAttribute('title', errs > 0 ? 'Errores activos' : cuar > 0 ? 'Cuarentena pendiente' : 'Operativo');

  // ── Tienda search ────────────────────────────────────────────
  document.getElementById('tc-search').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    let vis = 0;
    document.querySelectorAll('.tc-item').forEach(el => {
      const chip = el.querySelector('.tc');
      const match = !q || chip.dataset.nombre.includes(q) || chip.dataset.codigo.includes(q);
      el.style.display = match ? '' : 'none';
      if (match) vis++;
    });
    document.getElementById('tc-empty').style.display = vis === 0 ? '' : 'none';
  });

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

  // ── DataTable ────────────────────────────────────────────────
  const rowClass = {
    CAPTURADO: 'r-ok', PENDIENTE: 'r-pend',
    ERROR_CAPTURA: 'r-err', CUARENTENA: 'r-warn'
  };
  const badgeHtml = {
    CAPTURADO:     '<span class="s-dot ok"></span><span class="badge bg-success">Capturado</span>',
    PENDIENTE:     '<span class="s-dot pend"></span><span class="badge bg-secondary">Pendiente</span>',
    ERROR_CAPTURA: '<span class="s-dot err"></span><span class="badge bg-danger">Error</span>',
    CUARENTENA:    '<span class="s-dot warn"></span><span class="badge bg-warning text-dark">Cuarentena</span>',
  };
  const tipoMap = { '01':'FAC','03':'BOL','07':'NC','08':'ND' };

  const dt = $('#tabla-reg').DataTable({
    processing: true, serverSide: true,
    ajax: {
      url: '{{ route("captura.registros") }}',
      data: d => {
        d.estado = $('#f-estado').val();
        d.tienda = $('#f-tienda').val();
        d.buscar = $('#f-buscar').val();
      }
    },
    columns: [
      { data: 'id', width: '48px' },
      { data: 'codigo_tienda', width: '68px',
        render: v => `<span class="badge bg-label-primary">${v}</span>` },
      { data: 'serie_numero_bizlinks',
        render: v => v === '—' ? '<span class="text-muted">—</span>' : `<code class="mn">${v}</code>` },
      { data: 'tipo_documento_sunat', width: '52px',
        render: v => `<span class="badge bg-label-info">${tipoMap[v]||v||'—'}</span>` },
      { data: 'fecha_venta', width: '86px' },
      { data: 'importe_total', width: '88px', className: 'text-end fw-semibold' },
      { data: 'razon_social_cliente',
        render: (v, _, row) => {
          const doc = (row.numero_documento_cliente && row.numero_documento_cliente !== '—')
            ? `<br><small class="text-muted">${row.numero_documento_cliente}</small>` : '';
          return (v || '—') + doc;
        }},
      { data: 'estado', width: '108px', orderable: false,
        render: v => badgeHtml[v] || `<span class="badge bg-secondary">${v}</span>` },
      { data: 'fecha_captura', width: '100px',
        render: v => v === '—' ? '<span class="text-muted">—</span>' : v },
      { data: 'acciones', orderable: false, searchable: false, width: '90px', className: 'text-center' },
    ],
    rowCallback: (row, data) => {
      const cls = rowClass[data.estado];
      if (cls) $(row).addClass(cls);
    },
    drawCallback: setTime,
    pageLength: 25,
    order: [[0, 'desc']],
    language: {
      url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json',
      processing: '<span class="spinner-border spinner-border-sm text-primary"></span>'
    },
    dom: 'rtip',
  });

  $('#f-estado, #f-tienda').on('change', () => dt.ajax.reload());
  let dbTimer;
  $('#f-buscar').on('input', () => {
    clearTimeout(dbTimer);
    dbTimer = setTimeout(() => dt.ajax.reload(), 380);
  });

  // ── Botón: Reintentar ────────────────────────────────────────
  $('#tabla-reg').on('click', '.btn-reset', function () {
    const id = $(this).data('id');
    Swal.fire({
      title: '¿Reintentar?',
      text: `El registro #${id} vuelve a PENDIENTE y se procesa en el próximo ciclo.`,
      icon: 'question', showCancelButton: true,
      confirmButtonText: 'Sí, reintentar', cancelButtonText: 'Cancelar',
      confirmButtonColor: '#696cff',
    }).then(r => {
      if (!r.isConfirmed) return;
      fetch(`{{ url('/captura/registros') }}/${id}/reset`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
      }).then(r => r.json()).then(d => {
        if (d.ok) { toastr.success('Registro reseteado'); dt.ajax.reload(null, false); }
        else toastr.error('No se pudo resetear');
      });
    });
  });

  // ── Botón: Forzar captura directa desde fila ────────────────
  $('#tabla-reg').on('click', '.btn-forzar-directo', function () {
    const id = $(this).data('id');
    Swal.fire({
      title: '¿Forzar captura?',
      text: 'Se capturará este documento ahora mismo en Bizlinks.',
      icon: 'warning', showCancelButton: true,
      confirmButtonText: 'Sí, forzar', cancelButtonText: 'Cancelar',
      confirmButtonColor: '#ff3e1d',
    }).then(r => {
      if (!r.isConfirmed) return;
      fetch(`{{ url('/captura/registros') }}/${id}/forzar`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
      }).then(r => r.json()).then(d => {
        if (d.ok) { toastr.success('Documento capturado correctamente'); dt.ajax.reload(null, false); }
        else toastr.error(d.mensaje || 'Error al forzar captura');
      }).catch(() => toastr.error('Error de red al forzar captura'));
    });
  });

  // ── Botón: Ver errores ───────────────────────────────────────
  $('#tabla-reg').on('click', '.btn-errores', function () {
    const id = $(this).data('id');
    $('#modal-reg-id').text(id);
    $('#tabla-errores tbody').empty();
    $('#errores-empty').hide();
    fetch(`{{ url('/captura/registros') }}/${id}/errores`, {
      headers: { 'Accept': 'application/json' }
    }).then(r => r.json()).then(list => {
      if (!list.length) { $('#errores-empty').show(); return; }
      list.forEach(e => {
        const resuelto = e.resuelto
          ? '<span class="badge bg-success">Sí</span>'
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
  });

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

  $('#tabla-reg').on('click', '.btn-ver-cpe', function () {
    mostrarCpe($(this).data('id'));
  });

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

  // ── Ejecutar motor ───────────────────────────────────────────
  $('#btn-ejecutar').on('click', function () {
    Swal.fire({
      title: '¿Ejecutar ahora?',
      text: 'El motor procesará todas las tiendas activas.',
      icon: 'question', showCancelButton: true,
      confirmButtonText: 'Ejecutar', cancelButtonText: 'Cancelar',
      confirmButtonColor: '#696cff',
    }).then(r => {
      if (!r.isConfirmed) return;
      const btn = $(this);
      btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Ejecutando…');
      fetch('{{ route("captura.ejecutar") }}', {
        method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
      }).then(r => r.json()).then(d => {
        if (d.ok) { toastr.success(d.output || 'Captura completada'); location.reload(); }
        else toastr.error(d.error || 'Error al ejecutar');
      }).catch(() => toastr.error('Error de comunicación'))
        .finally(() => btn.prop('disabled', false)
          .html('<i class="bx bx-play-circle me-1"></i>Ejecutar ahora'));
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

  // ── Auto-refresh 30 s ────────────────────────────────────────
  let rfOn = true, remaining = 30, rfTimer;
  const lbl = document.getElementById('lbl-rf');
  const txt = document.getElementById('txt-cd');
  const btnP = document.getElementById('btn-toggle-rf');

  function tick() {
    remaining--;
    txt.textContent = remaining + 's';
    if (remaining <= 0) {
      dt.ajax.reload(null, false);
      remaining = 30;
      setTime();
    }
  }
  rfTimer = setInterval(tick, 1000);

  btnP.addEventListener('click', () => {
    rfOn = !rfOn;
    if (rfOn) {
      remaining = 30; txt.textContent = '30s';
      lbl.classList.remove('paused');
      btnP.innerHTML = '<i class="bx bx-pause"></i>';
      btnP.title = 'Pausar auto-refresh';
      rfTimer = setInterval(tick, 1000);
    } else {
      clearInterval(rfTimer);
      lbl.classList.add('paused');
      txt.textContent = 'pausado';
      btnP.innerHTML = '<i class="bx bx-play"></i>';
      btnP.title = 'Reanudar auto-refresh';
    }
  });

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
          if (typeof dt !== 'undefined') dt.ajax.reload(null, false);
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

// ── Cola pendiente por tienda ─────────────────────────────────
(function () {
  fetch('{{ route("captura.cola-por-tienda") }}', {
    headers: { 'Accept': 'application/json' }
  }).then(r => r.json()).then(data => {
    document.querySelectorAll('[data-tienda-cola]').forEach(slot => {
      const info = data[slot.dataset.tiendaCola];
      if (!info) { slot.innerHTML = ''; return; }

      if (!info.ok) {
        slot.innerHTML = `<span class="badge" style="background:rgba(255,62,29,.1);color:#ff3e1d;font-size:.55rem"
          title="Sin conexión a Soluflex"><i class="bx bx-wifi-off"></i></span>`;
        return;
      }

      const n = info.cola;
      if (n === 0) {
        slot.innerHTML = `<span class="badge" style="background:rgba(113,221,55,.12);color:#71dd37;font-size:.55rem"
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
