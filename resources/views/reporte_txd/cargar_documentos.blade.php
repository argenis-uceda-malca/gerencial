<!-- the fileinput plugin styling CSS file -->
<link href="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/css/fileinput.min.css" media="all"
    rel="stylesheet" type="text/css" />

<script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/js/locales/LANG.js"></script>

<script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/js/fileinput.min.js"></script>

@if (in_array('subir_data', session('permisos')) || in_array('acceso_reporte', session('permisos')))
    <form id="form-subir-documentos" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="col-form-label">
            <div class="row px-3">

                <div class="col-md-4 mb-3">
                    <h5 class="emp-title mb-0"></h5>
                </div>

               

                {{-- Input para subir múltiples archivos --}}
                <input id="input-b1" name="archivo[]" type="file" class="file d-none" multiple
                    data-browse-on-zone-click="true">

                <div class="col-12 mt-4 text-right">
                    <input type="button" value="Cancelar" class="btn btn-light" data-bs-dismiss="modal">
                    <input type="submit" value="Guardar" class="btn btn-dark">
                </div>

            </div>
        </div>
    </form>
@else
    <div class="col-md-4 mb-3">
        <h5 class="">No tienes permisos para esta acción</h5>
    </div>
@endif


<script>
    $(document).ready(function () {
        $('#form-subir-documentos').on('submit', function (e) {
            e.preventDefault();

            let form = $('#form-subir-documentos')[0];
            let formData = new FormData(form);
            formData.append('_token', '{{ csrf_token() }}');

            $.ajax({
                url: "{{ route('subir_documentos') }}", // Asegúrate que esta ruta esté bien
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function () {
                    // puedes mostrar un loader si deseas
                },
                success: function (response) {
                   
                    toastr.success('Datos cargados', 'Progress Bar', { "progressBar": true });
                    $('#commonModal').modal('hide'); // cerrar modal


                },
                error: function (xhr) {
                    let mensaje = 'Ocurrió un error al subir los archivos.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        mensaje = xhr.responseJSON.message;
                    }
                    toastr.error(mensaje, 'Progress Bar', { "progressBar": true });

                }
            });
        });
    });

</script>