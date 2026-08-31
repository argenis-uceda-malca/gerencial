@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Carga de archivos TXD (SAGA / OECHSLE / RIPLEY)</h1>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('txd.upload.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="card mb-3">
            <div class="card-header">Archivos (sube solo los que tengas disponibles)</div>
            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label">Oechsle — Venta (Lun-Sáb, .csv)</label>
                    <input type="file" name="oechsle_venta" class="form-control" accept=".csv,.txt">
                </div>

                <div class="mb-3">
                    <label class="form-label">Oechsle — Stock (Dom, .csv)</label>
                    <input type="file" name="oechsle_stock" class="form-control" accept=".csv,.txt">
                </div>

                <div class="mb-3">
                    <label class="form-label">Ripley (Lun-Dom, .xlsx, hoja "TD1")</label>
                    <input type="file" name="ripley" class="form-control" accept=".xlsx,.xls">
                </div>

                <div class="mb-3">
                    <label class="form-label">Falabella — Stock (.xlsx, hoja "Product details")</label>
                    <input type="file" name="falabella_stock" class="form-control" accept=".xlsx,.xls">
                </div>

                <div class="mb-3">
                    <label class="form-label">Falabella — Ventas (.xlsx, hoja "Sheet 1", export Seller Center)</label>
                    <input type="file" name="falabella_ventas" class="form-control" accept=".xlsx,.xls">
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                Ejecutar pipeline completo después de cargar (opcional)
            </div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input type="checkbox" name="ejecutar_pipeline" value="1" class="form-check-input" id="ejecutar_pipeline">
                    <label class="form-check-label" for="ejecutar_pipeline">
                        Correr <code>automatizacion_ejecutar_txd_completo()</code> apenas termine la carga
                    </label>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Fecha inicio</label>
                        <input type="date" name="p_fecha_ini" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha fin</label>
                        <input type="date" name="p_fecha_fin" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha stock</label>
                        <input type="date" name="p_fecha_stock" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha lunes de la semana</label>
                        <input type="date" name="p_fecha_lunes" class="form-control">
                    </div>
                </div>
                <small class="text-muted">Deja vacías las fechas para usar los defaults del SP (últimos 7 días).</small>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Cargar archivos</button>
    </form>
</div>
@endsection
