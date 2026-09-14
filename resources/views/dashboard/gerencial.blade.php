@extends('layouts.base')
@section('title', 'Dashboard Gerencial')

@push('styles')
<style>
.kpi-card { border-left: 4px solid; }
.kpi-card.venta  { border-color: #696cff; }
.kpi-card.var    { border-color: #03c3ec; }
.kpi-card.canal  { border-color: #ffab00; }
.kpi-card.marca  { border-color: #71dd37; }
.kpi-val  { font-size: 1.6rem; font-weight: 700; line-height: 1; }
.kpi-lbl  { font-size: .75rem; color: #a1acb8; text-transform: uppercase; letter-spacing: .04em; margin-top: .25rem; }
.kpi-sub  { font-size: .82rem; margin-top: .35rem; }
.bar-wrap { background: #e8e8e8; border-radius: 4px; height: 8px; }
.bar-fill { height: 8px; border-radius: 4px; background: #696cff; }
.badge-var-pos { background: #e8f8e0; color: #3a7d44; border-radius: 20px; padding: 2px 8px; font-size: .78rem; }
.badge-var-neg { background: #fde8e8; color: #c0392b; border-radius: 20px; padding: 2px 8px; font-size: .78rem; }
</style>
@endpush

@section('contenido')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- Cabecera --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">Dashboard Gerencial</h4>
        <span class="text-muted small">{{ \Carbon\Carbon::parse($fechaInicio)->format('d M') }} – {{ \Carbon\Carbon::parse($fechaFin)->format('d M Y') }}</span>
    </div>

    {{-- Filtros --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('dashboard.gerencial') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="{{ $fechaInicio }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Fin</label>
                    <input type="date" name="fecha_fin" class="form-control form-control-sm" value="{{ $fechaFin }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Marca</label>
                    <select name="marca" class="form-select form-select-sm">
                        <option value="">Todas las marcas</option>
                        @foreach ($marcas as $m)
                            <option value="{{ $m }}" @selected($marcaFiltro === $m)>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Canal</label>
                    <select name="canal" class="form-select form-select-sm">
                        <option value="">Todos los canales</option>
                        @foreach ($canales as $c)
                            <option value="{{ $c }}" @selected($canalFiltro === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filtrar</button>
                    <a href="{{ route('dashboard.gerencial') }}" class="btn btn-outline-secondary btn-sm">↺</a>
                </div>
            </form>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="row g-3 mb-4">
        {{-- Venta total --}}
        <div class="col-6 col-md-3">
            <div class="card kpi-card venta h-100">
                <div class="card-body">
                    <div class="kpi-lbl">Venta neta</div>
                    <div class="kpi-val">S/ {{ number_format($ventaTotal, 0) }}</div>
                    @if ($pctMeta !== null)
                    <div class="kpi-sub text-muted">{{ $pctMeta }}% de meta</div>
                    @else
                    <div class="kpi-sub text-muted">Sin meta cargada</div>
                    @endif
                </div>
            </div>
        </div>
        {{-- Variación vs período anterior --}}
        <div class="col-6 col-md-3">
            <div class="card kpi-card var h-100">
                <div class="card-body">
                    <div class="kpi-lbl">Variación período ant.</div>
                    @if ($variacion !== null)
                        <div class="kpi-val {{ $variacion >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $variacion >= 0 ? '+' : '' }}{{ $variacion }}%
                        </div>
                        <div class="kpi-sub text-muted">Ant: S/ {{ number_format($ventaAnterior, 0) }}</div>
                    @else
                        <div class="kpi-val text-muted">—</div>
                        <div class="kpi-sub text-muted">Sin período anterior</div>
                    @endif
                </div>
            </div>
        </div>
        {{-- Canal top --}}
        <div class="col-6 col-md-3">
            <div class="card kpi-card canal h-100">
                <div class="card-body">
                    <div class="kpi-lbl">Canal top</div>
                    @if ($porCanal->count())
                        <div class="kpi-val" style="font-size:1.1rem;">{{ $porCanal->first()->canal }}</div>
                        <div class="kpi-sub text-muted">S/ {{ number_format($porCanal->first()->venta, 0) }}</div>
                    @else
                        <div class="kpi-val text-muted">—</div>
                    @endif
                </div>
            </div>
        </div>
        {{-- Marca top --}}
        <div class="col-6 col-md-3">
            <div class="card kpi-card marca h-100">
                <div class="card-body">
                    <div class="kpi-lbl">Marca top</div>
                    @if ($porMarca->count())
                        <div class="kpi-val" style="font-size:1.1rem;">{{ $porMarca->first()->marca }}</div>
                        <div class="kpi-sub text-muted">S/ {{ number_format($porMarca->first()->venta, 0) }}</div>
                    @else
                        <div class="kpi-val text-muted">—</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Gráficos --}}
    <div class="row g-3 mb-4">
        {{-- Tendencia diaria --}}
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold small">Tendencia diaria</span>
                </div>
                <div class="card-body">
                    <canvas id="chartTendencia" height="200"></canvas>
                </div>
            </div>
        </div>
        {{-- Venta por marca --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header py-2">
                    <span class="fw-semibold small">Venta por marca</span>
                </div>
                <div class="card-body d-flex flex-column justify-content-center gap-2">
                    @php $maxMarca = $porMarca->max('venta') ?: 1; @endphp
                    @foreach ($porMarca as $m)
                    <div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ $m->marca }}</span>
                            <span class="text-muted">S/ {{ number_format($m->venta, 0) }}</span>
                        </div>
                        <div class="bar-wrap">
                            <div class="bar-fill" style="width: {{ round($m->venta / $maxMarca * 100) }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla detalle por canal × marca --}}
    <div class="card">
        <div class="card-header py-2">
            <span class="fw-semibold small">Detalle por canal y marca</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Canal</th>
                            <th>Marca</th>
                            <th class="text-end">Venta neta</th>
                            <th class="text-end">% del total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($detalle as $canal => $filas)
                            @php $totalCanal = $filas->sum('venta'); @endphp
                            @foreach ($filas as $i => $f)
                            <tr>
                                @if ($i === 0)
                                <td rowspan="{{ $filas->count() }}" class="fw-semibold align-middle border-end">
                                    {{ $canal }}<br>
                                    <small class="text-muted">S/ {{ number_format($totalCanal, 0) }}</small>
                                </td>
                                @endif
                                <td>{{ $f->marca }}</td>
                                <td class="text-end">S/ {{ number_format($f->venta, 2) }}</td>
                                <td class="text-end">
                                    {{ $ventaTotal > 0 ? round($f->venta / $ventaTotal * 100, 1) : 0 }}%
                                </td>
                            </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Sin datos para el período seleccionado</td></tr>
                        @endforelse
                    </tbody>
                    @if ($ventaTotal > 0)
                    <tfoot class="table-light">
                        <tr class="fw-semibold">
                            <td colspan="2">Total</td>
                            <td class="text-end">S/ {{ number_format($ventaTotal, 2) }}</td>
                            <td class="text-end">100%</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function () {
    const labels  = @json($tendencia->pluck('fecha_documento'));
    const ventas  = @json($tendencia->pluck('venta'));
    const metas   = @json($tendencia->pluck('meta'));
    const hasMeta = metas.some(v => v > 0);

    const ctx = document.getElementById('chartTendencia');
    if (!ctx) return;

    const datasets = [{
        label: 'Venta neta',
        data: ventas,
        borderColor: '#696cff',
        backgroundColor: 'rgba(105,108,255,.08)',
        fill: true,
        tension: 0.3,
        pointRadius: labels.length > 20 ? 0 : 3,
    }];

    if (hasMeta) {
        datasets.push({
            label: 'Meta',
            data: metas,
            borderColor: '#ffab00',
            borderDash: [5, 5],
            fill: false,
            tension: 0.3,
            pointRadius: 0,
        });
    }

    new Chart(ctx, {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: hasMeta } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 10, maxRotation: 0 } },
                y: {
                    grid: { color: 'rgba(0,0,0,.05)' },
                    ticks: { callback: v => 'S/ ' + v.toLocaleString('es-PE', { maximumFractionDigits: 0 }) }
                }
            }
        }
    });
})();
</script>
@endpush
