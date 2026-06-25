@extends('layouts.base')
@section('title', 'Smart Brands')
@section('contenido')


    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Lista /</span> Usuarios


        </h4>


        <!-- Default Checkboxes and radios & Default checkboxes and radios -->
        <div class="col-xl-12">
            <div class="card mb-4">
                <!-- Checkboxes and Radios -->
                <form action="{{ route('form.usuario') }}" id="myform" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row gy-3">
                            <div class="col-md">
                                <div class="mb-3">
                                    <input type="text" name="nombre" class="form-control"
                                        placeholder="Ingrese Nombre">
                                </div>
                            </div>

                            <div class="col-md">
                                <div class="mb-3">
                                    <input type="text" name="apellido" class="form-control"
                                        placeholder="Ingrese Apellidos">
                                </div>
                            </div>

                            <div class="col-md">
                                <div class="mb-3">
                                    <input type="text" name="username" class="form-control"
                                        placeholder="Ingrese nombre de Usuario">
                                </div>
                            </div>

                            <div class="col-md d-flex justify-content-center align-items-center">
                                <div class="mb-3">
                                    <button type="submit" class="btn rounded-pill btn-secondary">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bootstrap Table with Header - Light -->
        <div class="card">
            <h5 class="card-header">Lista de Usuarios</h5>
            <div class="table-responsive text-nowrap" style="padding: 10px;">
                <table class="table  order-column" id="mytable">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="3" class="text-center" style=" z-index: 999;">Usuario</th>
                            <th rowspan="3" class="text-center" style="background: #aaaaaa2a; ">Nombres</th>
                            <th rowspan="3" class="text-center" style="background: #aaaaaa2a">Email</th>
                            <th rowspan="3" class="text-center" style="background: #aaaaaa2a">Activo</th>
                            <th rowspan="2" colspan="2" class="text-center" style="background: #3ca2bb33">Ultimo
                                Ingreso
                            </th>
                            <th class="text-center" style="background: #23916933" colspan="12">Permisos</th>
                            <th rowspan="3" class="text-center" style="background: #3ca2bb31">Marcas</th>
                        </tr>
                        <tr>
                            <th rowspan="2" class="text-center" style="background: #e77f4f2a">Gerencial</th>
                            <th colspan="5" class="text-center" style="background: #e77f4f2a">Gestion Tiendas</th>
                            <th rowspan="2" class="text-center" style="background: #e77f4f2a">Social</th>
                            <th rowspan="2" class="text-center" style="background: #e77f4f2a">RFM</th>
                            <th rowspan="2" class="text-center" style="background: #e77f4f2a">Stock</th>
                            <th rowspan="2" class="text-center" style="background: #e77f4f2a">Mapa</th>
                            <th colspan="2" class="text-center" style="background: #aaaaaa2a">Ripley</th>
                        </tr>
                        <tr>
                            <th class="text-center" style="background: #3ca2bb3a">Hora</th>
                            <th class="text-center" style="background: #3ca2bb3a">Fecha</th>
                            <th class="text-center" style="background: #e77f4f2c">Ventas Mensual</th>
                            <th class="text-center" style="background: #e77f4f2c">Cubicaje Lin/Temp</th>
                            <th class="text-center" style="background: #e77f4f2c">Participacion Lin/Temp</th>
                            <th class="text-center" style="background: #e77f4f2c">Top Vta/Contrb</th>
                            <th class="text-center" style="background: #e77f4f2c">Venta Acumulada</th>
                            <th class="text-center" style="background: #aaaaaa2d">Lectura</th>
                            <th class="text-center" style="background: #aaaaaa2d">Subir Data</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">

                        @foreach ($lista_usuario as $item)
                            <tr>
                                <td style="background: #ffffff; z-index: 999">{{ $item->username }}</td>
                                <td>{{ $item->first_name }} {{ $item->last_name }}</td>
                                <td>{{ $item->email }}</td>
                                <td class="text-center">
                                    <i class="bi bi-check-circle"></i>
                                </td>
                                <td class="text-center">09:30</td>
                                <td class="text-center">13/06/23</td>
                                {{-- Gerencial --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" id="form_permiso1"
                                                method="POST">
                                                @csrf
                                                <input type="checkbox" class="form-check-input permiso-checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="46"
                                                    {{ $item->Auth_permission->contains(46) ? 'checked' : '' }}>
                                                <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                {{-- Venta mensual --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" id="form_permiso2"
                                                method="POST">
                                                @csrf
                                                <input class="form-check-input permiso-checkbox" type="checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="114"
                                                    {{ $item->Auth_permission->contains(114) ? 'checked' : '' }} />
                                                    <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                {{-- Cubicaje --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" id="form_permiso3"
                                                method="POST">
                                                @csrf
                                                <input class="form-check-input permiso-checkbox" type="checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="115"
                                                    {{ $item->Auth_permission->contains(115) ? 'checked' : '' }} />
                                                    <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                {{-- Participacion --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <input class="form-check-input permiso-checkbox" type="checkbox" id="" />
                                            {{-- <form action="{{ route('admin.cambiar') }}" id="form_permiso4"
                                                method="POST">
                                                @csrf
                                                <input class="form-check-input checkpermiso" type="checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid=""
                                                    {{ $item->Auth_permission->contains() ? 'checked' : '' }} />
                                            </form> --}}
                                        </div>
                                    </div>
                                </td>
                                {{-- Top Vta --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" id="form_permiso5"
                                                method="POST">
                                                @csrf
                                                <input class="form-check-input permiso-checkbox" type="checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="116"
                                                    {{ $item->Auth_permission->contains(116) ? 'checked' : '' }} />
                                                    <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                {{-- Venta Acumulada --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" id="form_permiso6"
                                                method="POST">
                                                @csrf
                                                <input class="form-check-input permiso-checkbox" type="checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="117"
                                                    {{ $item->Auth_permission->contains(117) ? 'checked' : '' }} />
                                                    <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                {{-- Social --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            
                                            <form action="{{ route('admin.cambiar') }}" id="form_permiso7"
                                                method="POST">
                                                @csrf
                                                <input class="form-check-input permiso-checkbox" type="checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="128"
                                                    {{ $item->Auth_permission->contains(128) ? 'checked' : '' }} />
                                                    <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                {{-- RFM --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" id="form_permiso8"
                                                method="POST">
                                                @csrf
                                                <input class="form-check-input permiso-checkbox" type="checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="140"
                                                    {{ $item->Auth_permission->contains(140) ? 'checked' : '' }} />
                                                    <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                {{-- Stock --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" id="form_permiso9"
                                                method="POST">
                                                @csrf
                                                <input class="form-check-input permiso-checkbox" type="checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="165"
                                                    {{ $item->Auth_permission->contains(165) ? 'checked' : '' }} />
                                                    <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                {{-- Mapa --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" id="form_permiso10"
                                                method="POST">
                                                @csrf
                                                <input class="form-check-input permiso-checkbox" type="checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="130"
                                                    {{ $item->Auth_permission->contains(130) ? 'checked' : '' }} />
                                                    <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                {{-- Ripley lectura --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" id="form_permiso11"
                                                method="POST">
                                                @csrf
                                                <input class="form-check-input permiso-checkbox" type="checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="97"
                                                    {{ $item->Auth_permission->contains(97) ? 'checked' : '' }} />
                                                    <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                {{-- Ripley sibir data --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" id="form_permiso12"
                                            method="POST">
                                            @csrf
                                            <input class="form-check-input permiso-checkbox" type="checkbox"
                                                data-usuario-id="{{ $item->id }}" data-permisoid="57"
                                                {{ $item->Auth_permission->contains(57) ? 'checked' : '' }} />
                                                <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                        </form>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a id="brand_permission_check_1498" href="/administracion/franquiciador/1498"
                                        class="check_brand load-in-modal">
                                        14
                                    </a>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <br>

        {{-- {{ $lista_usuario->onEachSide(5)->links() }} --}}
        {{ $lista_usuario->onEachSide(3)->links() }}

        <!-- Bootstrap Table with Header - Light -->


    @endsection

    @section('footer')

        <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/1.1.2/js/bootstrap-multiselect.min.js">
        </script>

        <script>
            $(document).ready(function() {

                $(document).on('change', '.permiso-checkbox', function() {
                    var usuarioid = $(this).data('usuario-id');
                    var permisoid = $(this).data('permisoid');
                    //var isChecked = $(this).is(':checked');

                    $.ajax({
                        url: '{{ route('admin.cambiar') }}',
                        type: 'POST',
                        data: {
                            usuarioid: usuarioid,
                            permisoid: permisoid,
                            //isChecked: isChecked,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            // Manejar la respuesta del controlador si es necesario
                            console.log("regreso del controlaror " + response.message)
                        },
                        error: function(xhr) {
                            // Manejar errores si los hay
                            console.log("error ")
                        }
                    });
                });

                // handleCheckpermisoChange(1);
                // handleCheckpermisoChange(2);
                // handleCheckpermisoChange(3);

            });

            function crearcheckpermiso(idformulario) {
                id_formulario = "'#" + idformulario + "'"
                console.log(id_formulario);
                $('.checkpermiso').change(function() {
                    var formData = $('#' + idformulario + '').serialize();
                    var formData = $('#idformulario').serialize();
                    console.log('Posting the following: ', formData);

                    $.ajax({
                        url: '/someurl',
                        data: formData,
                        type: 'post',
                        dataType: 'json',
                        success: function(data) {
                            //  ... do something with the data...
                            console.log(data);
                        }
                    });
                });
            }


            function handleCheckpermisoChange(formNumber) {
                $('#checkpermiso' + formNumber).change(function() {
                    var formData = $('#form_permiso' + formNumber).serialize();
                    console.log('Posting the following: ', formData);

                    $.ajax({
                        url: '/cambiar_permisos',
                        data: formData,
                        type: 'post',
                        dataType: 'json',
                        success: function(data) {
                            // ... hacer algo con los datos...
                            console.log(data);
                            if (data == "cambiado") {
                                //alert("actualizado");
                                Swal.fire({
                                    position: 'top-end',
                                    icon: 'success',
                                    title: 'Error al actualizar',
                                    showConfirmButton: false,
                                    timer: 1000
                                });
                            } else {
                                // Swal.fire({
                                //     position: 'top-end',
                                //     icon: 'success',
                                //     title: 'Error al actualizar',
                                //     showConfirmButton: false,
                                //     timer: 1000
                                // }) ;
                                // alert("actualizado") 
                            }
                        }
                    });

                });
            }
        </script>

    @endsection
