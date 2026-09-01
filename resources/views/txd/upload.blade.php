@extends('layouts.base')

@section('title', 'Carga de archivos TXD')

@section('contenido')
<link href="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/css/fileinput.min.css" media="all" rel="stylesheet" type="text/css" />

<div class="container py-4">
    <h1 class="h4 mb-3">Carga de archivos TXD (SAGA / OECHSLE / RIPLEY)</h1>

    <div id="txd-alerts"></div>

    <form id="form-txd-upload" method="POST" action="{{ route('txd.upload.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="card mb-3">
            <div class="card-header">Archivos (sube solo los que tengas disponibles)</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Oechsle — Venta (Lun-Sáb, .csv)</label>
                        <input type="file" name="oechsle_venta" class="file form-control" accept=".csv,.txt" data-browse-on-zone-click="true">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Oechsle — Stock (Dom, .csv)</label>
                        <input type="file" name="oechsle_stock" class="file form-control" accept=".csv,.txt" data-browse-on-zone-click="true">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ripley (Lun-Dom, .xlsx, hoja "TD1")</label>
                        <input type="file" name="ripley" class="file form-control" accept=".xlsx,.xls" data-browse-on-zone-click="true">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Falabella — Stock (.xlsx, hoja "Product details")</label>
                        <input type="file" name="falabella_stock" class="file form-control" accept=".xlsx,.xls" data-browse-on-zone-click="true">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Falabella — Ventas (.xlsx, hoja "Sheet 1", export Seller Center)</label>
                        <input type="file" name="falabella_ventas" class="file form-control" accept=".xlsx,.xls" data-browse-on-zone-click="true">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Ejecutar pipeline completo después de cargar (opcional)</div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input type="checkbox" name="ejecutar_pipeline" value="1" class="form-check-input" id="ejecutar_pipeline">
                    <label class="form-check-label" for="ejecutar_pipeline">Correr <code>automatizacion_ejecutar_txd_completo()</code> apenas termine la carga</label>
                </div>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Fecha inicio</label><input type="date" name="p_fecha_ini" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Fecha fin</label><input type="date" name="p_fecha_fin" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Fecha stock</label><input type="date" name="p_fecha_stock" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Fecha lunes de la semana</label><input type="date" name="p_fecha_lunes" class="form-control"></div>
                </div>
                <small class="text-muted">Deja vacías las fechas para usar los defaults del SP.</small>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" id="btn-txd-submit">Cargar archivos</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/js/fileinput.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/js/locales/es.js"></script>
<script>
$(function(){
    $(".file").fileinput({showUpload:false, showPreview:false, language:"es", dropZoneEnabled:true});
    $('#form-txd-upload').on('submit', function(e){
        e.preventDefault();
        let formData = new FormData(this);
        let btn = $('#btn-txd-submit');
        btn.prop('disabled', true).text('Cargando...');
        $('#txd-alerts').html('');
        $.ajax({
            url: $(this).attr('action'),
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            headers: {'X-CSRF-TOKEN': $('input[name="_token"]').val()},
            success: function(res){
                let msg = res.status || res.message || 'Archivos procesados correctamente.';
                $('#txd-alerts').html('<div class="alert alert-success">'+msg+'</div>');
                if(window.toastr) toastr.success(msg,'TXD',{progressBar:true});
            },
            error: function(xhr){
                let msg = 'Error al procesar los archivos.';
                if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                else if(xhr.responseJSON && xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                $('#txd-alerts').html('<div class="alert alert-danger">'+msg+'</div>');
                if(window.toastr) toastr.error(msg,'TXD',{progressBar:true});
            },
            complete: function(){ btn.prop('disabled', false).text('Cargar archivos'); }
        });
    });
});
</script>
@endsection
