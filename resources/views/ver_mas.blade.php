@extends('layouts.base')
@section('title', 'Smart Brands')
@section('contenido')


    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Reporte /</span> Ventas</h4>
        <!-- Formulario de busqueda -->
        <div class="col-xl-12">
            <div class="card mb-4">
                <!-- Checkboxes and Radios -->
                <form action="{{ route('form.submit') }}" id="myform" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row gy-3">

                            {{-- Este es mi formulario --}}
                            <div class="col-md">
                                <label for="exampleFormControlSelect1" class="form-label">Buscar por:</label>
                                <div class="form-check mt-3">
                                    <input name="select_radio" class="form-check-input" type="radio" value="1"
                                        id="defaultRadio1" />
                                    <label class="form-check-label" for="defaultRadio1"> Por Marcas </label>
                                </div>
                                <div class="form-check">
                                    <input name="select_radio" class="form-check-input" type="radio" value="2"
                                        id="defaultRadio2" checked />
                                    <label class="form-check-label" for="defaultRadio2"> Por Tiendas </label>
                                </div>
                            </div>

                            <div class="col-md">
                                <label for="exampleFormControlSelect1" class="form-label">Seleccione las Marcas</label>
                                <select multiple class="form-select" id="exampleFormControlSelect2"
                                    aria-label="Multiple select example" name="marca[]">
                                    {{-- <option selected>Open this select menu</option> --}}
                                    @foreach ($marcas as $item)
                                        <option value="{{ $item->id }}" selected>{{ $item->marca }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md">
                                {{-- <label for="exampleFormControlSelect1" class="form-label">Desde: </label> --}}
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Fecha inicio</label>
                                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control">
                                </div>
                            </div>
                            <div class="col-md">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Fecha fin</label>
                                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control"
                                        value="">
                                </div>
                            </div>
                        </div>

                        <div class="row gy-3" style="display: flex; justify-content: end;">
                            <div class="col-xl-3" style="display: flex; justify-content: end;">
                                <button type="submit" class="btn rounded-pill btn-secondary">
                                    <span class="tf-icons bx bx-bell"></span>&nbsp; Ver Reporte
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <br>

        <!-- Tabla Reporte de Ventas * -->
        <div class="card">
            <h5 class="card-header">Reporte de Ventas {{ $fecha }}</h5>
            <div class="table-responsive text-nowrap" style="padding: 10px;">
                <table class="table row-border order-column table-hover table-bordered" id="reporte_venta">
                    <thead class="table-light">
                        <tr>
                            <th style="padding: 5px 80px; z-index: 9999;" class="center head-table1"> Código</th>
                            <th class="center head-table1"> Código Padre</th>
                            <th class="center head-table1" style="width:20%"> Descripción</th>
                            <th class="center head-table1"> Cantidad</th>
                            <th class="center head-table1"> Precio Unit (S/.)</th>
                            <th class="center head-table1"> Importe (S/.)</th>
                            <th class="center head-table1"> Costo Venta (S/.)</th>
                            <th class="center head-table1"> Fecha</th>
                            <th class="center head-table1"> Comprobante</th>
                            <th class="center head-table1" style="width:20%"> Cliente</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0" id="tabla_ventas_body">
                        @foreach ($reporte as $item)
                            <tr role="row" class="odd" style="height: 51px;">
                                <td class=" alert-warning text-left" style="background: white;z-index: 999; ">
                                    {{ $item->codigo_producto }}</td>
                                <td class=" text-right">{{ $item->codigo_padre }}</td>
                                <td class=" text-right cuota">{{ $item->producto }}</td>
                                <td class=" text-right totales">{{ number_format($item->cantidad, 1) }}</td>
                                <td class=" text-right subtotales"> {{ number_format($item->costo_unitario, 3, '.', ',') }}</td>
                                <td class=" text-right"> {{ number_format($item->importe_subtotal, 3, '.', ',') }}</td>
                                <td class=" text-right tickets"> {{ number_format($item->costo_venta, 3, '.', ',') }}</td>
                                <td class=" text-right"> {{ $item->fecha_documento }}</td>
                                <td class=" text-right">{{ $item->comprobante }}</td>
                                <td class=" text-right">{{ $item->persona }}</td>

                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot class="alert alert-success">
                        <tr>
                            <th class="text-center" id="tfoot_total"></th> 
                            <th class="text-right"></th>
                            <th class="text-right"></th>
                            <th class="text-right "></th>
                            <th class="text-right "></th>
                            <th class="text-right "></th>
                            <th class="text-right "></th>
                            <th class="text-right "></th>
                            <th class="text-right "></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <!-- Bootstrap Table with Header - Light -->



       
        <script>
            const importess = [0,1,2];
            //console.log(importess);
        </script>


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

        <!-- Multiselec theme -->
        {{-- <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.0/dist/jquery.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js"></script> --}}

        <script>
            $(document).ready(function() {

                var table = $('#mytable').DataTable({
                    scrollY: "300px",
                    scrollX: true,
                    scrollCollapse: true,
                    paging: true,
                    fixedColumns: {
                        left: 1,
                    },
                    dom: 'Bfrtip',
                    buttons: [
                        'excel', 'pdf',
                    ],
                    buttons: [{
                        extend: 'excel',
                        text: 'Exportar a Excel',
                        className: 'btn rounded-pill btn-success'
                    }],
                    searchBuilder: {
                        container: '#mytable_filter',
                        text: 'Buscar',
                        button: {
                            className: 'form-control'

                        }
                    },
                    language: {
                        "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Spanish.json"
                    }

                });



                var table = $('#reporte_venta').DataTable({
                    scrollY: "600px",
                    scrollX: true,
                    scrollCollapse: true,
                    paging: true,
                    fixedColumns: {
                        left: 1,
                    },
                    dom: 'Bfrtip',
                    buttons: [
                        'excel', 'pdf',
                    ],
                    buttons: [{
                        extend: 'excel',
                        text: 'Exportar a Excel',
                        className: 'btn rounded-pill btn-success'
                    }],
                    order: [
                        [1, 'desc']
                    ],
                    searchBuilder: {
                        container: '#mytable_filter',
                        text: 'Buscar',
                        button: {
                            className: 'form-control'
                        }
                    }
                });

                var table = $('#mytable2').DataTable({
                    scrollY: "300px",

                    scrollCollapse: true,
                    paging: true,
                    fixedColumns: {
                        leftColumns: 1
                    },
                    dom: 'Bfrtip',
                    buttons: [
                        'excel', 'pdf',
                    ],
                    buttons: [{
                        extend: 'excel',
                        text: 'Exportar a Excel',
                        className: 'btn rounded-pill btn-success'
                    }],
                    searchBuilder: {
                        container: '#mytable_filter',
                        text: 'Buscar',
                        button: {
                            className: 'form-control'

                        }
                    }
                });


                setfecha();
                Obtener_ultimo_radiobtn();
                obtener_ultima_fecha_inicio();
                obtener_ultima_fecha_fin();
                suma_columnas();
            });

            function setfecha() {
                // Obtener el campo de entrada de fecha
                var fecha_inicio = document.getElementById('fecha_inicio');
                var fecha_fin = document.getElementById('fecha_fin');
                // Obtener la fecha actual
                var fechaActual = new Date();
                // Formatear la fecha en el formato requerido para el campo de entrada de fecha (AAAA-MM-DD)
                var mes = fechaActual.getMonth() + 1;
                var dia = fechaActual.getDate();
                if (mes < 10) {
                    mes = '0' + mes;
                }
                if (dia < 10) {
                    dia = '0' + dia;
                }
                var fechaFormateada = fechaActual.getFullYear() + '-' + mes + '-' + dia;
                // Establecer la fecha actual como valor predeterminado si el campo de fecha está vacío
                if (!fecha_inicio.value && !fecha_fin.value) {
                    fecha_inicio.value = fechaFormateada;
                    fecha_fin.value = fechaFormateada;
                }
            }

            function obtener_ultima_fecha_inicio() {
                // Obtener el elemento del input de fecha

                const fechaInicioInput = document.getElementById('fecha_inicio');

                // Obtener la fecha seleccionada del localStorage
                const fechainicioSeleccionada = localStorage.getItem('fechainicioSeleccionada');

                // Verificar si hay una fecha seleccionada almacenada en el localStorage
                if (fechainicioSeleccionada) {
                    // Establecer la fecha seleccionada en el input
                    fechaInicioInput.value = fechainicioSeleccionada;
                }

                // Escuchar el evento de cambio del input de fecha
                fechaInicioInput.addEventListener('change', function() {
                    // Obtener la nueva fecha seleccionada
                    const nuevaFechaSeleccionada = this.value;

                    // Almacenar la nueva fecha seleccionada en el localStorage
                    localStorage.setItem('fechainicioSeleccionada', nuevaFechaSeleccionada);
                });
            }

            function obtener_ultima_fecha_fin() {
                // Obtener el elemento del input de fecha

                const fechaFinInput = document.getElementById('fecha_fin');

                // Obtener la fecha seleccionada del localStorage
                const fechaSeleccionada = localStorage.getItem('fechaSeleccionada');

                // Verificar si hay una fecha seleccionada almacenada en el localStorage
                if (fechaSeleccionada) {
                    // Establecer la fecha seleccionada en el input
                    fechaFinInput.value = fechaSeleccionada;
                }

                // Escuchar el evento de cambio del input de fecha
                fechaFinInput.addEventListener('change', function() {
                    // Obtener la nueva fecha seleccionada
                    const nuevaFechaSeleccionada = this.value;

                    // Almacenar la nueva fecha seleccionada en el localStorage
                    localStorage.setItem('fechaSeleccionada', nuevaFechaSeleccionada);
                });
            }

            function Obtener_ultimo_radiobtn() {
                // Obtener los elementos de los radio buttons
                const radioButtons = document.getElementsByName('select_radio');

                // Obtener el valor seleccionado del localStorage
                const valorSeleccionado = localStorage.getItem('valorSeleccionado');

                // Verificar si hay un valor seleccionado almacenado en el localStorage
                if (valorSeleccionado) {
                    // Recorrer los radio buttons y establecer el valor seleccionado
                    radioButtons.forEach(function(radioButton) {
                        if (radioButton.value === valorSeleccionado) {
                            radioButton.checked = true;
                        }
                    });
                }

                // Escuchar el evento de cambio de los radio buttons
                radioButtons.forEach(function(radioButton) {
                    radioButton.addEventListener('change', function() {
                        // Obtener el valor del radio button seleccionado
                        const valorSeleccionado = this.value;

                        // Almacenar el valor seleccionado en el localStorage
                        localStorage.setItem('valorSeleccionado', valorSeleccionado);
                    });
                });
            }

            function suma_columnas() {
                // Obtener todas las filas de datos en el cuerpo de la tabla
                var filasDatos = $('#tabla_ventas_body').find('tr');

                // Calcular la suma para cada columna
                var sumas = Array.from({
                    length: 18
                }, () => 0); // Array para almacenar las sumas de cada columna

                filasDatos.each(function() {
                    var celdas = $(this).find(
                        '.text-right'); // Obtener todas las celdas con la clase "text-right" en la fila
                    celdas.each(function(index) {
                        var valor = $(this).text().replace(',', '');
                        sumas[index] += parseFloat(valor);
                    });
                });

                // Insertar las sumas en las celdas correspondientes en el tfoot
                $('#tfoot_total').nextAll('.tfoot').each(function(index) {
                    $(this).text(sumas[index].toFixed(3));
                });

            }
        </script>

        <script type="text/javascript">
            $('#exampleFormControlSelect2').multiselect({
                includeSelectAllOption: true,
                allSelectedText: 'Todos seleccionados'
            });
        </script>




    @endsection
