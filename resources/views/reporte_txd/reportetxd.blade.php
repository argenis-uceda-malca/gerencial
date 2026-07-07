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

        .ag-cell.align-left {
            text-align: left !important;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border: solid #00000054 1px;
            outline: 0;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/ag-charts-community@9.0.0/dist/umd/ag-charts-community.js"></script>

    <div class="container-xxl flex-grow-1 container-p-y">
        {{-- <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Reporte /</span> Ventas</h4> --}}
        <!-- Formulario de busqueda -->
        <div class="col-xl-12">
            <div class="card mb-4">
                <!-- Checkboxes and Radios -->
                <form id="myform" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row gy-3 gx-3 align-items-end flex-wrap">


                            <!-- Botón Exportar -->
                            <div class="col-12 col-md-3 col-lg-2 d-flex justify-content-center">
                                <button type="button" class="export btn btn-success w-100" id="exportar">Exportar
                                    Datos</button>
                            </div>

                            <!-- Botón Ver Reporte -->
                            <div class="col-12 col-md-3 col-lg-2 d-flex justify-content-center">
                                <button type="submit" class="btn rounded-pill btn-secondary w-100" id="spiner_btn">
                                    <div class="d-flex align-items-center justify-content-center">
                                        Ver Reporte
                                        <div class="ms-2" id="spiner_div" role="status"></div>
                                    </div>
                                </button>
                            </div>

                            <div class="col-12 col-md-4 col-lg-3 d-flex justify-content-center">
                                <a href="#" data-url="{{ url('cargar_documentos') }}" data-ajax-popup="true"
                                    class="btn rounded-pill btn-light w-100" data-title="Cargar Documentos Masivos"><span
                                        class="dash-micon"><i class="ti ti-device-floppy"></i></span> Cargar Documentos </a>
                            </div>

                        </div>
                    </div>
                </form>

            </div>
        </div>




        <script type="text/javascript">
            $('#exampleFormControlSelect2').multiselect({
                includeSelectAllOption: true,
                allSelectedText: 'Todos seleccionados'
            });
        </script>

        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="myGrid2" style="width: 100%; height: 450px" class="ag-theme-quartz"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        {{-- <button type="button" class="btn btn-primary">Save changes</button> --}}
                    </div>
                </div>
            </div>
        </div>



        {{-- <div id="myGrid" style="width: 100%; height: 550px" class="ag-theme-quartz">
        </div> --}}
        <div class="row">
            <!-- Panel lateral -->
            <div class="col-md-2">
                <div class="card mb-3">
                    <div class="card-header"
                        style="color: #696cff; background-color: rgba(105, 108, 255, 0.16) !important; font-weight: 600;">
                        Columnas del Reporte
                    </div>
                    <div class="card-body p-2" style="max-height: 600px; overflow-y: auto;">
                        <ul id="columnList" class="list-group">
                            <!-- Se llena dinámicamente -->
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Tabla ag-Grid -->
            <div class="col-md-10">
                <div id="myGrid" class="ag-theme-alpine" style="height: 600px; width: 100%;"></div>
            </div>
        </div>


    </div>





    <script>
        var __basePath = './';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.1.1/dist/ag-grid-community.min.js"></script>
    {{-- <script src="main_tabla.js"></script> --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.2/xlsx.full.min.js"></script>

    <script type="text/babel">

            $(document).ready(function() {
                $('#selectSucursales').select2({
                    width: '100%', // Esto hace que respete el tamaño del contenedor Bootstrap
                    placeholder: 'Seleccione las Sucursales'
                });
            });

            activar_spiner();
            function activar_spiner() {
                document.getElementById('spiner_btn').addEventListener('click', function() {
                    var spinner = document.getElementById('spiner_div');
                    spinner.classList.add('spinner-border');
                    spinner.classList.add('spinner-border-sm');
                    spinner.classList.add('text-light');
                })
            }

            function ocultar_spinner() {
                var spinner = document.getElementById('spiner_div');
                spinner.classList.remove('spinner-border');
                spinner.classList.remove('spinner-border-sm');
                spinner.classList.remove('text-light');
            }

            // Grid API: Access to Grid API methods
            let gridApi;
           

            const dateFormatter = (params) => {
                return new Date(params.value).toLocaleDateString('en-us', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                });
            };

            const numberFormatter = params => {
                if (params.value == null || params.value === '') return '';
                let value = parseFloat(params.value.toString().replace(',', ''));
                if (isNaN(value)) return params.value;

                // Si quieres resaltar negativos en rojo
                if (value < 0) {
                    return `<span style="color:red;">${value.toLocaleString('es-PE', { minimumFractionDigits: 2 })}</span>`;
                }

                return value.toLocaleString('es-PE', { minimumFractionDigits: 2 });
            };


            function percentFormatter(params) {
                if (params.value == null || isNaN(params.value)) return '';
                return Number(params.value).toFixed(1) + '%';
            }

            function currencyFormatter(params) {
                if (params.value == null || isNaN(params.value)) return '';
                return 'S/ ' + Number(params.value).toFixed(2).toLocaleString('es-PE');
            }


            // Suponiendo que tus datos vienen en una variable "rowData"
            function calcularFilaTotal(rowData) {
                let totalRow = {
                    SUCURSAL: 'TOTAL',
                    RECENCIA: 0,
                    FRECUENCIA: 0,
                    MONTO: 0,
                    CANT_TICKETS: 0,
                    CANT_ITEMS: 0,
                    TICK_PROM: 0
                };

                rowData.forEach(row => {
                    totalRow.RECENCIA += parseFloat(row.RECENCIA) || 0;
                    totalRow.FRECUENCIA += parseFloat(row.FRECUENCIA) || 0;
                    totalRow.MONTO += parseFloat(row.MONTO) || 0;
                    totalRow.CANT_TICKETS += parseFloat(row.CANT_TICKETS) || 0;
                    totalRow.CANT_ITEMS += parseFloat(row.CANT_ITEMS) || 0;
                    totalRow.TICK_PROM += parseFloat(row.TICK_PROM) || 0;
                });

                // Redondear a máximo dos decimales
                for (let key in totalRow) {
                    if (typeof totalRow[key] === 'number') {
                        totalRow[key] = Number(totalRow[key].toFixed(2));
                    }
                }

                return totalRow;
            }


            function actualizarGridConTotales(rowData) {
                if (rowData.length === 0) {
                    // Si no hay datos, muestra la tabla vacía y sin fila pinned
                    gridOptions.api.setRowData([]);
                    gridOptions.api.setPinnedBottomRowData([]);
                    return;
                }

                // Si hay datos, calcular totales
                let totalRow = calcularFilaTotal(rowData);

                // Mostrar datos y fila total
                gridOptions.api.setRowData(rowData);
                gridOptions.api.setPinnedBottomRowData([totalRow]);
            }



            /** @type {import('ag-grid-community').GridOptions} */
            const gridOptions = {
                // Data to be displayed
                suppressExcelExport: true,
                popupParent: document.body,
                getRowId: (params) => params.data.id,
                // Columns to be displayed (Should match rowData properties)
                columnDefs : [
                    { headerName: 'TXD', field: 'txd', width: 100, filter: true, floatingFilter: true },
                    { headerName: 'MARCA', field: 'marca', width: 120, filter: true, floatingFilter: true },

                    { headerName: 'Vta Sell In Act', field: 'vta_sell_in_act', width: 160, filter: true, floatingFilter: true, valueFormatter: numberFormatter },
                    { headerName: 'Vta Sell Out Act', field: 'vta_sell_out_act', width: 170, filter: true, floatingFilter: true, valueFormatter: numberFormatter },
                    { headerName: 'Vta Sell Out Hist', field: 'vta_sell_out_hist', width: 170, filter: true, floatingFilter: true, valueFormatter: numberFormatter },

                    { headerName: '%Var Vta', field: 'var_vta', width: 120, filter: true, floatingFilter: true, valueFormatter: percentFormatter },
                    { headerName: '%Part Vta Sell Out Act', field: 'part_vta_sell_out_act', width: 190, filter: true, floatingFilter: true, valueFormatter: percentFormatter },
                    { headerName: '%Part Vta Sell Out Hist', field: 'part_vta_sell_out_hist', width: 190, filter: true, floatingFilter: true, valueFormatter: percentFormatter },

                    { headerName: 'Meta Vta Sell Out Act', field: 'meta_vta_sell_out_act', width: 190, filter: true, floatingFilter: true, valueFormatter: numberFormatter },
                    { headerName: '%Cumpl. meta', field: 'cumpl_meta', width: 150, filter: true, floatingFilter: true, valueFormatter: percentFormatter },

                    { headerName: 'Vta Unid Sell Out Act', field: 'vta_unid_sell_out_act', width: 180, filter: true, floatingFilter: true, valueFormatter: numberFormatter },
                    { headerName: 'Vta Unid Sell Out Hist', field: 'vta_unid_sell_out_hist', width: 180, filter: true, floatingFilter: true, valueFormatter: numberFormatter },
                    { headerName: '%Var Vta Unid Sell Out', field: 'var_vta_unid_sell_out', width: 200, filter: true, floatingFilter: true, valueFormatter: percentFormatter },

                    { headerName: 'Pr Prom Sell Out Act', field: 'pr_prom_sell_out_act', width: 180, filter: true, floatingFilter: true, valueFormatter: currencyFormatter },
                    { headerName: 'Pr Prom Sell Out Hist', field: 'pr_prom_sell_out_hist', width: 180, filter: true, floatingFilter: true, valueFormatter: currencyFormatter },
                    { headerName: '%Var Pr Prom Sell Out', field: 'var_pr_prom_sell_out', width: 200, filter: true, floatingFilter: true, valueFormatter: percentFormatter },

                    { headerName: '%GM Sell Out', field: 'gm_sell_out', width: 140, filter: true, floatingFilter: true, valueFormatter: percentFormatter },
                    { headerName: '%GM Sell Out Hist', field: 'gm_sell_out_hist', width: 160, filter: true, floatingFilter: true, valueFormatter: percentFormatter },

                    { headerName: 'Contrib Sell Out C/Reb Act', field: 'contrib_sell_out_creb_act', width: 230, filter: true, floatingFilter: true, valueFormatter: numberFormatter },
                    { headerName: 'Contrib Sell Out C/Reb Hist', field: 'contrib_sell_out_creb_hist', width: 230, filter: true, floatingFilter: true, valueFormatter: numberFormatter },

                    { headerName: 'Inv Act', field: 'inv_act', width: 120, filter: true, floatingFilter: true, valueFormatter: numberFormatter },
                    { headerName: 'Inv Hst', field: 'inv_hst', width: 120, filter: true, floatingFilter: true, valueFormatter: numberFormatter },

                    { headerName: '%GM SI Act', field: 'gm_si_act', width: 150, filter: true, floatingFilter: true, valueFormatter: percentFormatter },
                    { headerName: 'Dscto Act', field: 'dscto_act', width: 130, filter: true, floatingFilter: true, valueFormatter: percentFormatter },
                    { headerName: 'Dscto Hist', field: 'dscto_hist', width: 130, filter: true, floatingFilter: true, valueFormatter: percentFormatter },

                    { headerName: 'Cobertura', field: 'cobertura', width: 130, filter: true, floatingFilter: true, valueFormatter: percentFormatter }
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
                   

                    gridApi = params.api;

                    const columnApi = params.columnApi;

                    const allColumns = columnApi.getAllGridColumns();
                    const listContainer = document.getElementById("columnList");
                    listContainer.innerHTML = '';

                    // Crear items <li> con checkboxes
                    allColumns.forEach((col) => {
                        const colId = col.getColId();
                        const headerName = col.getColDef().headerName || colId;
                        const isVisible = !col.isVisible() ? false : true;

                        const li = document.createElement("li");
                        li.classList.add("list-group-item", "d-flex", "align-items-center");
                        li.setAttribute("data-col-id", colId);
                        li.innerHTML = `
                            <input class="form-check-input me-2" type="checkbox" ${isVisible ? 'checked' : ''}>
                            <span>${headerName}</span>
                        `;
                        listContainer.appendChild(li);
                    });

                    // Habilitar Sortable.js en la lista
                    Sortable.create(listContainer, {
                        animation: 150,
                        onEnd: () => {
                            // Al terminar el drag: aplicar nuevo orden
                            const newOrder = [];
                            listContainer.querySelectorAll("li").forEach((li) => {
                                newOrder.push(li.getAttribute("data-col-id"));
                            });
                            columnApi.moveColumns(newOrder, 0); // Mueve todas desde la posición 0
                        }
                    });

                    // Eventos de visibilidad por checkbox
                    listContainer.querySelectorAll("input[type=checkbox]").forEach(chk => {
                        chk.addEventListener("change", (e) => {
                            const li = e.target.closest("li");
                            const colId = li.getAttribute("data-col-id");
                            const visible = e.target.checked;
                            columnApi.setColumnVisible(colId, visible);
                        });
                    });

                },
                getRowStyle: params => {
                    if (params.node.rowPinned) {
                        return { fontWeight: 'bold', background: '#f0f0f0' };
                    }
                    return null;
                }
            };
          

            document.getElementById('exportar').addEventListener('click', event => {
                //gridApi.exportDataAsCsv();
                const jsonData = gridApi.getModel().rowsToDisplay.map(row => row.data); // Obtener los datos de la tabla

                const worksheet = XLSX.utils.json_to_sheet(jsonData); // Convertir los datos a una hoja de trabajo de Excel
                const workbook = XLSX.utils.book_new(); // Crear un nuevo libro de trabajo
                XLSX.utils.book_append_sheet(workbook, worksheet, 'Reporte'); // Añadir la hoja de trabajo al libro de trabajo

                // Convertir el libro de trabajo a un archivo binario y descargarlo
                //XLSX.writeFile(workbook, 'datos.xlsx');
                const today = new Date();
                const formattedDate = today.getFullYear() + '-' + (today.getMonth() + 1) + '-' + today.getDate(); // Formatear la fecha
                XLSX.writeFile(workbook, 'datos_' + formattedDate + '.xlsx');
            });

            function BotonesRenderer(params) {
                // Crear el elemento del botón
                var button = document.createElement('button');
                button.innerHTML = 'Click me';
                button.addEventListener('click', function() {
                    // Lógica para manejar el click del botón, por ejemplo:
                    console.log('Botón clickeado para la fila:', params.data);
                });

                // Crear un contenedor div para el botón
                var container = document.createElement('div');
                container.appendChild(button);

                return container;
            }



            // Create Grid: Create new grid within the #myGrid div, using the Grid Options object
            // gridApi = agGrid.createGrid(document.querySelector('#myGrid'), gridOptions);

            const gridDiv = document.querySelector('#myGrid');
            agGrid.createGrid(gridDiv, gridOptions);
            console.log("Listo");

            

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

            // Perfecto ahora solo unas mejoras, toda la información se muestra bien, pero los datos solo se muestran despues de que el usuario selecciona unos fitro y le da en un btn,
            // y la fila de "TOTAL" sale como infefinido hasta q la tabla tenga datos, ademas los montos totales deben tener solo dos decimales como maximo 


            /*original*/
            $('#myform').on('submit', function(event) {
                event.preventDefault(); // Prevenir el envío tradicional

                let form = $(this);
                let formData = new FormData(this);

                $.ajax({
                    url: '/submit_txd', // Asegúrate que esta ruta exista
                    method: 'POST',
                    data: formData,
                    processData: false, // Necesario para FormData
                    contentType: false, // Necesario para FormData
                    beforeSend: function() {
                        console.log('Enviando...');
                        // Aquí puedes mostrar un spinner si quieres
                    },
                    success: function(data) {
                        ocultar_spinner();
                        let rowData = [];

                        if (Array.isArray(data)) {
                            rowData = data;
                        } else if (Array.isArray(data.data)) {
                            rowData = data.data;
                        } else if (Array.isArray(data.LISTA)) { // ✅ Esta es la que te falta
                            rowData = data.LISTA;
                        } else {
                            console.error('❌ La estructura de datos no es válida.');
                            return;
                        }

                        if (gridApi) {
                            let totalRow = calcularFilaTotal(rowData);

                            gridApi.setRowData(rowData);

                            // Pone la fila total como pinned row en la parte inferior
                            gridApi.setPinnedBottomRowData([totalRow]);
                            // actualizarGridConTotales(data);
                                                        
                        } else {
                            console.error('❌ La API no está lista.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error AJAX:', error);
                        console.log('Respuesta completa:', xhr.responseText);
                        // Aquí puedes mostrar un mensaje de error al usuario
                    }
                });
            });


           

            // const domContainer = document.querySelector('#app');
            // ReactDOM.render(React.createElement(ApexChart), domContainer);


             
        
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

    {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>


@endsection
