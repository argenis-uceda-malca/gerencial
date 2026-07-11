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
                            <th rowspan="3" class="text-center" style="z-index: 999;">Usuario</th>
                            <th rowspan="3" class="text-center" style="background: #aaaaaa2a;">Nombres</th>
                            <th rowspan="3" class="text-center" style="background: #aaaaaa2a">Email</th>
                            <th rowspan="3" class="text-center" style="background: #aaaaaa2a">Activo</th>
                            <th rowspan="2" colspan="2" class="text-center" style="background: #3ca2bb33">Ultimo Ingreso</th>
                            <th class="text-center" style="background: #23916933" colspan="6">Permisos</th>
                            <th rowspan="3" class="text-center" style="background: #3ca2bb31">Marcas</th>
                        </tr>
                        <tr>
                            <th class="text-center" style="background: #e77f4f2a" rowspan="2">Dashboard Ventas</th>
                            <th class="text-center" style="background: #e77f4f2a" rowspan="2">Reporte Ventas</th>
                            <th class="text-center" style="background: #e77f4f2a" rowspan="2">Follow-up FF vs TO</th>
                            <th class="text-center" style="background: #e77f4f2a" colspan="2">Reporte TxD</th>
                            <th class="text-center" style="background: #e77f4f2a" rowspan="2">Gestion Clientes RFM</th>
                        </tr>
                        <tr>
                            <th class="text-center" style="background: #3ca2bb3a">Hora</th>
                            <th class="text-center" style="background: #3ca2bb3a">Fecha</th>
                            <th class="text-center" style="background: #e77f4f2c">Reporte Ventas</th>
                            <th class="text-center" style="background: #e77f4f2c">Cargar Excel</th>
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
                                {{-- Dashboard Ventas (ID 166) --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" method="POST">
                                                @csrf
                                                <input type="checkbox" class="form-check-input permiso-checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="166"
                                                    {{ $item->Auth_permission->contains(166) ? 'checked' : '' }}>
                                                <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                {{-- Reporte Ventas (ID 167) --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" method="POST">
                                                @csrf
                                                <input class="form-check-input permiso-checkbox" type="checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="167"
                                                    {{ $item->Auth_permission->contains(167) ? 'checked' : '' }} />
                                                    <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                {{-- Follow-up FF vs TO (ID 168) --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" method="POST">
                                                @csrf
                                                <input class="form-check-input permiso-checkbox" type="checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="168"
                                                    {{ $item->Auth_permission->contains(168) ? 'checked' : '' }} />
                                                    <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                {{-- Reporte TxD - Reporte Ventas (ID 97) --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" method="POST">
                                                @csrf
                                                <input class="form-check-input permiso-checkbox" type="checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="97"
                                                    {{ $item->Auth_permission->contains(97) ? 'checked' : '' }} />
                                                    <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                {{-- Reporte TxD - Cargar Excel (ID 57) --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" method="POST">
                                                @csrf
                                                <input class="form-check-input permiso-checkbox" type="checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="57"
                                                    {{ $item->Auth_permission->contains(57) ? 'checked' : '' }} />
                                                    <input type="hidden" name="idusuario" value="{{ $item->id }}">
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                {{-- Gestion Clientes RFM (ID 140) --}}
                                <td>
                                    <div class="text-center">
                                        <div class="form-switch">
                                            <form action="{{ route('admin.cambiar') }}" method="POST">
                                                @csrf
                                                <input class="form-check-input permiso-checkbox" type="checkbox"
                                                    data-usuario-id="{{ $item->id }}" data-permisoid="140"
                                                    {{ $item->Auth_permission->contains(140) ? 'checked' : '' }} />
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

            });
        </script>

    @endsection
