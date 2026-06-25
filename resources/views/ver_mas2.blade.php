@extends('layouts.base')
@section('title', 'Smart Brands')
@section('contenido')

    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;700&amp;display=swap" rel="stylesheet" />
    <style media="only screen">
        html,
        body {
            height: 100%;
            width: 100%;
            margin: 0;
            box-sizing: border-box;
            -webkit-overflow-scrolling: touch;
        }

        html {
            position: absolute;
            top: 0;
            left: 0;
            padding: 0;
            overflow: auto;
        }

        body {
            padding: 16px;
            overflow: auto;
            background-color: transparent
        }

        /*Estilos para spandir columnas*/

        .customExpandButton {
            float: right;
            margin-top: 2px;
            margin-left: 3px;
        }

        .expanded {
            animation-name: toExpanded;
            animation-duration: 1s;
            -webkit-transform: rotate(180deg);
            /* Chrome, Safari, Opera */
            transform: rotate(180deg);
        }

        .fa-arrow-right {
            color: cornflowerblue;
        }

        .collapsed {
            animation-name: toCollapsed;
            animation-duration: 1s;
            -webkit-transform: rotate(0deg);
            /* Chrome, Safari, Opera */
            transform: rotate(0deg);
        }

        .customHeaderMenuButton,
        .customHeaderLabel,
        .customHeaderLabel,
        .customSortDownLabel,
        .customSortUpLabel,
        .customSortRemoveLabel {
            margin-top: 2px;
            margin-left: 4px;
            float: left;
        }

        .customSortDownLabel {
            margin-left: 10px;
        }

        .customSortUpLabel {
            margin-left: 1px;
        }

        .customSortRemoveLabel {
            float: left;
            font-size: 11px;
        }

        @keyframes toExpanded {
            from {
                -webkit-transform: rotate(0deg);
                /* Chrome, Safari, Opera */
                transform: rotate(0deg);
            }

            to {
                -webkit-transform: rotate(180deg);
                /* Chrome, Safari, Opera */
                transform: rotate(180deg);
            }
        }

        @keyframes toCollapsed {
            from {
                -webkit-transform: rotate(180deg);
                /* Chrome, Safari, Opera */
                transform: rotate(180deg);
            }

            to {
                -webkit-transform: rotate(0deg);
                /* Chrome, Safari, Opera */
                transform: rotate(0deg);
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/ag-charts-community@9.0.0/dist/umd/ag-charts-community.js"></script>

    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Reporte /</span> Ventas</h4>
        <!-- Formulario de busqueda -->
        <div class="col-xl-12">
            <div class="card mb-4">
                <!-- Checkboxes and Radios -->
                <form id="myform" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row gy-3">

                            {{-- Este es mi formulario --}}

                            <div class="col-md ">
                                <label for="exampleFormControlSelect1" class="form-label">Filtro Canal:</label>
                                <div class="form-check ">
                                    <label for="exampleFormControlSelect1" class="form-label">Moderno:</label>
                                    <input type="checkbox" class="form-check-input" id="filtroOnline" name="filtroonline"
                                        value="1" checked>
                                </div>

                                <div class="form-check ">
                                    <label for="exampleFormControlSelect1" class="form-label"> Online:</label>
                                    <input type="checkbox" class="form-check-input" id="filtroIncluirOnline"
                                        name="filtroincluironline" value="2">
                                </div>

                                <div class="form-check ">
                                    <label for="exampleFormControlSelect1" class="form-label"> TxD:</label>
                                    <input type="checkbox" class="form-check-input" id="filtroIncluirTxD" name="filtrotxd"
                                        value="3">
                                </div>

                            </div>

                            <div class="col-md">
                                <label for="exampleFormControlSelect1" class="form-label">Buscar por:</label>
                                <div class="form-check mt-3">
                                    <input name="select_radio" class="form-check-input" type="radio" value="1"
                                        id="defaultRadio1" checked />
                                    <label class="form-check-label" for="defaultRadio1"> Por Marcas </label>
                                </div>
                                <div class="form-check">
                                    <input name="select_radio" class="form-check-input" type="radio" value="2"
                                        id="defaultRadio2" />
                                    <label class="form-check-label" for="defaultRadio2"> Por Tiendas </label>
                                </div>
                            </div>



                            <div class="col-md">
                                <div>
                                    <label for="exampleFormControlSelect1" class="form-label">Seleccione las Marcas</label>
                                </div>
                                <select multiple class="form-select" id="exampleFormControlSelect2"
                                    aria-label="Multiple select example" name="marca[]">
                                    {{-- <option selected>Open this select menu</option> --}}
                                    @foreach ($marcas as $item)
                                        <option value="{{ $item->idmarca }}" selected>{{ $item->marca }}</option>
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

                            <div class="col-md d-flex justify-content-center align-items-center">
                                <button type="submit" class="btn rounded-pill btn-secondary" id="spiner_btn">
                                    <div class="d-flex align-items-center">
                                        <span class="tf-icons bx bx-bell"></span>&nbsp; Ver Reporte &nbsp;
                                        <div class="" id="spiner_div" role="status">
                                        </div>
                                    </div>
                                </button>
                            </div>


                        </div>

                        {{-- <div class="row gy-3" style="display: flex; justify-content: end;">
                            <div class="col-xl-3" style="display: flex; justify-content: end;">
                                <button type="submit" class="btn rounded-pill btn-secondary">
                                    <span class="tf-icons bx bx-bell"></span>&nbsp; Ver Reporte
                                </button>
                            </div>
                        </div> --}}
                    </div>
                </form>
            </div>
        </div>


        <br>


        <script>
            const importess = [0, 1, 2];
            const importess_anterior = [0, 1, 2];
        </script>

        <script type="text/javascript">
            $('#exampleFormControlSelect2').multiselect({
                includeSelectAllOption: true,
                allSelectedText: 'Todos seleccionados'
            });
        </script>

        <div id="myGrid" style="width: 100%; height: 450px" class="ag-theme-quartz">
        </div>


        <script>
            // Replace Math.random() with a pseudo-random number generator to get reproducible results in e2e tests
            // Based on https://gist.github.com/blixt/f17b47c62508be59987b
            var _seed = 42;
            Math.random = function() {
                _seed = _seed * 16807 % 2147483647;
                return (_seed - 1) / 2147483646;
            };
        </script>





        <br><br>
        <div class="row">
            <div id="app"></div>
        </div>

        <script>
            var __basePath = './';
        </script>
        <script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.1.1/dist/ag-grid-community.min.js"></script>
        {{-- <script src="main_tabla.js"></script> --}}

        <script type="text/babel">
            // Grid API: Access to Grid API methods
            let gridApi2;

            
            /*Fin de agregado del btn*/
            class CustomHeaderGroup {
                params;
                eGui;
                onExpandButtonClickedListener;
                eExpandButton;
                onExpandChangedListener;

                init(params) {
                    this.params = params;
                    this.eGui = document.createElement('div');
                    this.eGui.className = 'ag-header-group-cell-label';
                    this.eGui.innerHTML =
                        '' +
                        '<div class="customHeaderLabel">' +
                        this.params.displayName +
                        '</div>' +
                        '<div class="customExpandButton"><i class="fa fa-arrow-right"></i></div>';

                    this.onExpandButtonClickedListener = this.expandOrCollapse.bind(this);
                    this.eExpandButton = this.eGui.querySelector('.customExpandButton');
                    this.eExpandButton.addEventListener(
                        'click',
                        this.onExpandButtonClickedListener
                    );

                    this.onExpandChangedListener = this.syncExpandButtons.bind(this);
                    this.params.columnGroup
                        .getProvidedColumnGroup()
                        .addEventListener('expandedChanged', this.onExpandChangedListener);

                    this.syncExpandButtons();
                }

                getGui() {
                    return this.eGui;
                }

                expandOrCollapse() {
                    var currentState = this.params.columnGroup
                        .getProvidedColumnGroup()
                        .isExpanded();
                    this.params.setExpanded(!currentState);
                }

                syncExpandButtons() {
                    function collapsed(toDeactivate) {
                        toDeactivate.className =
                            toDeactivate.className.split(' ')[0] + ' collapsed';
                    }

                    function expanded(toActivate) {
                        toActivate.className = toActivate.className.split(' ')[0] + ' expanded';
                    }

                    if (this.params.columnGroup.getProvidedColumnGroup().isExpanded()) {
                        expanded(this.eExpandButton);
                    } else {
                        collapsed(this.eExpandButton);
                    }
                }

                destroy() {
                    this.eExpandButton.removeEventListener(
                        'click',
                        this.onExpandButtonClickedListener
                    );
                }
            }



            /**Funciones para operacion entre columnas */
            function abValueGetter(params) {
                if (params.data.anterior == 0) {
                    return 0
                }
                return ((params.data.importe_total_sum / params.data.anterior) - 1) * 100;
            }

            function potenciavistas(params) {
                if (params.data.total == 0) {
                    return 0
                }
                return ((params.data.ticket / params.data.total)) * 100;
            }

            function calculo_convercion(params) {
                if (params.data.total == 0) {
                    return 0
                }
                return (params.data.ticket / params.data.total) * 100;
            }

            /** @type {import('ag-grid-community').GridOptions} */
            const gridOptions2 = {
                // Data to be displayed
                rowData: [{
                    // id: 'total', // ID para identificar la fila de suma total
                    // nombre: 'Suma Total:',
                    // total: 0
                }],
                getRowId: (params) => params.data.id,
                // Columns to be displayed (Should match rowData properties)
                columnDefs: [{
                        headerName: 'Marca',
                        pinned: 'left',
                        field: 'nombre',
                        width: 150,
                        //cellRenderer: CompanyLogoRenderer,
                        filter: true,
                        floatingFilter: true
                    },
                    {
                        headerName: 'Ventas',
                        children: [

                            {
                                field: 'importe_total_sum',
                                headerName: 'Importe Sub.Total',
                                width: 150,
                                //cellRenderer: CompanyLogoRenderer,
                                filter: true,
                                floatingFilter: true,
                                valueFormatter: params => {
                                    // Formatear el valor a dos decimales
                                    return params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                                }

                            },
                            {
                                field: 'meta',
                                width: 120,
                                filter: true,
                                floatingFilter: true,
                                //columnGroupShow: 'open',
                                valueFormatter: params => {
                                    // Formatear el valor a dos decimales
                                    return params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                                }
                            },
                            {
                                field: 'logro',
                                headerName: 'Logro(%)',
                                width: 120,
                                filter: true,
                                floatingFilter: true,
                                columnGroupShow: 'open',
                                valueFormatter: params => {
                                    // Formatear el valor a dos decimales
                                    return params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                                }
                            },
                            {
                                //field: 'anterior',
                                headerName: 'Crec. VS Aa',
                                colId: 'crec_aa',
                                valueGetter: abValueGetter,
                                width: 120,
                                filter: true,
                                floatingFilter: true,
                                columnGroupShow: 'open',
                                valueFormatter: params => {
                                    // Formatear el valor a dos decimales
                                    return params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                                }
                            },
                            {
                                field: 'gm',
                                headerName: 'GM Real(%)',
                                width: 130,
                                filter: true,
                                floatingFilter: true,
                                //columnGroupShow: 'open',
                                valueFormatter: params => {
                                    // Formatear el valor a dos decimales
                                    return params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                                }
                            },
                        ]
                    },
                    {
                        headerName: "Transacciones",
                        children: [{
                                field: 'unidades',
                                width: 110,
                                filter: true,
                                floatingFilter: true,
                                valueFormatter: params => {
                                    // Formatear el valor a dos decimales
                                    return params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                                }
                            },
                            {
                                field: 'precio_promedio',
                                width: 140,
                                filter: true,
                                floatingFilter: true,
                                columnGroupShow: 'open',
                                valueFormatter: params => {
                                    // Formatear el valor a dos decimales
                                    return params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                                }
                            },
                            {
                                field: 'ticket',
                                width: 100,
                                filter: true,
                                floatingFilter: true,
                                valueFormatter: params => {
                                    // Formatear el valor a dos decimales
                                    return params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                                }
                            },
                            {
                                field: 'ticket_promedio',
                                width: 140,
                                filter: true,
                                floatingFilter: true,
                                valueFormatter: params => {
                                    // Formatear el valor a dos decimales
                                    return params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                                }
                            },
                        ]
                    },
                    {
                        headerName: "Trafico de Clientes",
                        children: [{
                                headerName: 'Potencia',
                                colId: 'potencia',
                                width: 100,
                                valueGetter: potenciavistas,
                                filter: true,
                                floatingFilter: true,
                                valueFormatter: params => {
                                    // Formatear el valor a dos decimales
                                    return params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                                }
                            },
                            {
                                field: 'total',
                                headerName: 'Visitantes',
                                colId: 'vistas',
                                width: 100,
                                filter: true,
                                floatingFilter: true,
                                valueFormatter: params => {
                                    // Formatear el valor a dos decimales
                                    return params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                                }
                            },
                            {
                                headerName: 'Converión (%)',
                                width: 120,
                                colId: 'ticket&total',
                                valueGetter: calculo_convercion,
                                filter: true,
                                floatingFilter: true,
                                valueFormatter: params => {
                                    // Formatear el valor a dos decimales
                                    return params.value ? parseFloat(params.value).toFixed(2) : '';
                                }
                            },
                        ]
                    },
                    
                    {
                        headerName: 'id',
                        field: 'id',
                        pinned: 'left',
                        width: 80,
                        hide: true
                    },
                    {
                        //headerName: 'id',
                        field: 'fecha_inicio',
                        pinned: 'left',
                        width: 80,
                        hide: true
                    },
                    {
                        //headerName: 'id',
                        field: 'fecha_fin',
                        pinned: 'left',
                        width: 80,
                        hide: true
                    },

                ],

                // Configurations applied to all columns
                defaultColDef: {

                    filter: true,
                    editable: true,
                },

                // Grid Options & Callbacks
                //pagination: true,
                rowSelection: 'multiple',
                onSelectionChanged: (event) => {
                    console.log('Row Selection Event!');
                },
                onCellValueChanged: (event) => {
                    console.log(`New Cell Value: ${event.value}`);
                },


                onGridReady: function(params) {

                    gridApi2 = params.api;
                  
                    // Función para agregar la fila de "Suma Total" al final de los datos
                    if (gridApi2) {
                        initializeGrid2(gridApi2);
                    }

                }
            };


            function initializeGrid2(api) {
                let totalSum = 0;
                let metaSum = 0;
                let crec_aaSum = 0;
                let unidadesSum = 0;
                let ticketSum = 0;
                let visitantesSum = 0;
                api.forEachNode(node => {
                    totalSum += parseFloat(node.data.importe_total_sum || 0); // Sumar el valor de la columna "id"
                });
                api.forEachNode(node => {
                    metaSum += parseFloat(node.data.meta || 0); // Sumar el valor de la columna "id"
                });
                api.forEachNode(node => {
                    crec_aaSum += parseFloat(node.data.crec_aa || 0); // Sumar el valor de la columna "id"
                });
                api.forEachNode(node => {
                    unidadesSum += parseFloat(node.data.unidades || 0); // Sumar el valor de la columna "id"
                });
                api.forEachNode(node => {
                    ticketSum += parseFloat(node.data.ticket || 0); // Sumar el valor de la columna "id"
                });
                api.forEachNode(node => {
                    visitantesSum += parseFloat(node.data.vistas || 0); // Sumar el valor de la columna "id"
                });
                // Agregar la fila de "Suma Total" al final de los datos
                api.applyTransaction({
                    add: [{
                        nombre: 'Suma Total:',
                        //id: 'id', // Identificador único para la fila de suma total
                        importe_total_sum: totalSum,
                        meta: metaSum,
                        unidades: unidadesSum,
                        ticket: ticketSum,
                        vistas: visitantesSum,
                    }]
                });
            }

            // Create Grid: Create new grid within the #myGrid div, using the Grid Options object
            gridApi2 = agGrid.createGrid(document.querySelector('#myGrid2'), gridOptions2);


            /********* Actualizar en tiempo real los datos de la columna entradas *********/
            let validador_entradas = false;

            function abreviarNombre(nombre) {
                // Ejemplo de regla: Reemplazar "MENTHA & CHOCOLATE" por "MCH"
                if (nombre === "MENTHA & CHOCOLATE") {
                    return "MCH";
                }
                // Agrega más reglas de abreviación según sea necesario
                // Si ninguna regla coincide, devuelve el nombre original
                return nombre;
            }

            function abreviarNumero(numero) {
                if (numero >= 1000000) {
                    return (numero / 1000000).toFixed(1) + 'M';
                } else if (numero >= 1000) {
                    return (numero / 1000).toFixed(1) + 'k';
                } else {
                    return numero.toString();
                }
            }

            

            setfecha();
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

        
        </script>


    </div>


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


    <script src="https://cdn.jsdelivr.net/npm/react@16.12/umd/react.production.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/react-dom@16.12/umd/react-dom.production.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prop-types@15.7.2/prop-types.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-core/5.8.34/browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/react-apexcharts@1.3.6/dist/react-apexcharts.iife.min.js"></script>


@endsection
