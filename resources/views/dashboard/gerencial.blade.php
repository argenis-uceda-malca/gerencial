@extends('layouts.base')
@section('title', 'Dashboard Gerencial')
@section('contenido')

<div class="container-xxl flex-grow-1 container-p-y">

    <h4 class="mb-4">Dashboard Gerencial</h4>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('dashboard.gerencial') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Fecha inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="{{ $fechaInicio }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha fin</label>
                    <input type="date" name="fecha_fin" class="form-control" value="{{ $fechaFin }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Marca</label>
                    <select name="marca" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($marcas as $m)
                            <option value="{{ $m }}" @selected($marca === $m)>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Sucursal</th>
                            <th>Marca</th>
                            <th>Categoría</th>
                            <th class="text-end">Venta neta</th>
                            <th class="text-end">Meta</th>
                            <th class="text-end">% Cumplimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($resumen as $fila)
                            <tr>
                                <td>{{ $fila->fecha_documento }}</td>
                                <td>{{ $fila->sucursal_2 }} / {{ $fila->sucursal_3 }}</td>
                                <td>{{ $fila->marca }}</td>
                                <td>{{ $fila->categoria }}</td>
                                <td class="text-end">{{ number_format($fila->venta_neta, 2) }}</td>
                                <td class="text-end">{{ number_format($fila->meta, 2) }}</td>
                                <td class="text-end">{{ $fila->pct_cumplimiento }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Sin datos para el rango seleccionado</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@endsection
