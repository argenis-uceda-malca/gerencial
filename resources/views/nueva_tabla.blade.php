@extends('layouts.base')
@section('title', 'Smart Brands')
@section('contenido')

<!-- ============================================================ -->
<!-- CSS Y ESTILOS -->
<!-- ============================================================ -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;700&amp;display=swap" rel="stylesheet" />

<style media="only screen">
    html, body {
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
        background-color: transparent;
    }
    .customExpandButton {
        float: right;
        margin-top: 2px;
        margin-left: 3px;
    }
    .expanded {
        animation-name: toExpanded;
        animation-duration: 1s;
        -webkit-transform: rotate(180deg);
        transform: rotate(180deg);
    }
    .fa-arrow-right {
        color: cornflowerblue;
    }
    .collapsed {
        animation-name: toCollapsed;
        animation-duration: 1s;
        -webkit-transform: rotate(0deg);
        transform: rotate(0deg);
    }
    .customHeaderMenuButton,
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
        from { transform: rotate(0deg); }
        to { transform: rotate(180deg); }
    }
    @keyframes toCollapsed {
        from { transform: rotate(180deg); }
        to { transform: rotate(0deg); }
    }
    .ag-cell.align-left {
        text-align: left !important;
    }

    /* ══════════════════════════════════════════════════════
       DARK MODE
       ══════════════════════════════════════════════════════ */
    :root { --positive-color: #00503c; }
    [data-theme="dark"] { --positive-color: #4ADE80; }

    [data-theme="dark"] #rango_fechas {
      background: var(--dm-input-bg, #282E44) !important;
      border-color: var(--dm-border, #2E3450) !important;
      color: var(--dm-ink, #E2E6F0) !important;
    }
    [data-theme="dark"] #rango_fechas::placeholder {
      color: var(--dm-muted, #8B90A8);
    }

    [data-theme="dark"] #barraProgresoReporte {
      background-color: #2E3450 !important;
      box-shadow: 0 2px 4px rgba(0,0,0,.25) !important;
    }

    [data-theme="dark"] #myGrid.ag-theme-quartz,
    [data-theme="dark"] #myGrid2.ag-theme-quartz {
      --ag-background-color: var(--dm-surface, #1F2438);
      --ag-header-background-color: var(--dm-surface-alt, #282E44);
      --ag-odd-row-background-color: #1C2135;
      --ag-row-hover-color: rgba(139,131,255,.08);
      --ag-selected-row-background-color: rgba(139,131,255,.15);
      --ag-border-color: var(--dm-border, #2E3450);
      --ag-foreground-color: var(--dm-ink, #E2E6F0);
      --ag-data-color: var(--dm-ink, #E2E6F0);
      --ag-secondary-foreground-color: var(--dm-muted, #8B90A8);
      --ag-header-foreground-color: var(--dm-ink, #E2E6F0);
    }
    [data-theme="dark"] #myGrid.ag-theme-quartz .ag-cell,
    [data-theme="dark"] #myGrid2.ag-theme-quartz .ag-cell {
      color: var(--dm-ink, #E2E6F0);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/ag-charts-community@9.0.0/dist/umd/ag-charts-community.js"></script>

<!-- ============================================================ -->
<!-- CONTENIDO HTML -->
<!-- ============================================================ -->
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="container-fluid px-3">

        <h4 class="fw-bold mb-3">
            <span class="text-muted fw-light">Reporte /</span> Ventas
        </h4>

        <div class="card">
            <div class="card-body">
                <form id="myform" method="POST">
                    @csrf

                    <div class="row g-2 align-items-end">

                        <!-- Canal -->
                        <div class="col-auto" style="min-width: 130px;">
                            <label class="form-label fw-semibold small text-muted mb-1">Filtro Canal</label>
                            <div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="filtroOnline" name="filtroonline" value="1" checked>
                                    <label class="form-check-label" for="filtroOnline">Moderno</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="filtroIncluirOnline" name="filtroincluironline" value="2">
                                    <label class="form-check-label" for="filtroIncluirOnline">Online</label>
                                </div>
                            </div>
                        </div>

                        <!-- Buscar por -->
                        <div class="col-auto" style="min-width: 120px;">
                            <label class="form-label fw-semibold small text-muted mb-1">Buscar por</label>
                            <div>
                                <div class="form-check">
                                    <input name="select_radio" class="form-check-input" type="radio" value="1" id="defaultRadio1" checked>
                                    <label class="form-check-label" for="defaultRadio1">Por Marcas</label>
                                </div>
                                <div class="form-check">
                                    <input name="select_radio" class="form-check-input" type="radio" value="2" id="defaultRadio2">
                                    <label class="form-check-label" for="defaultRadio2">Por Tiendas</label>
                                </div>
                            </div>
                        </div>

                        <!-- Marcas -->
                        <div class="col">
                            <label class="form-label fw-semibold small text-muted mb-1">Seleccione las Marcas</label>
                            <select multiple class="form-select form-select-sm" id="exampleFormControlSelect2" name="marca[]"
                                style="height: 65px; font-size: 13px; width: 100%;">
                                @foreach ($marcas as $item)
                                    <option value="{{ $item->idmarca }}" selected>{{ $item->marca }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Sucursales -->
                        <div class="col">
                            <label class="form-label fw-semibold small text-muted mb-1">Seleccione Sucursales</label>
                            <meta name="csrf-token" content="{{ csrf_token() }}">
                            <select multiple class="form-select form-select-sm" id="sucursalesSelect"
                                style="height: 65px; font-size: 13px; width: 100%;">
                                @foreach ($sucursales as $item)
                                    <option value="{{ $item->sucursal }}"
                                        {{ in_array($item->sucursal, $sucursalesActivas) ? 'selected' : '' }}>
                                        {{ $item->sucursal }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Fechas -->
                        <div class="col-auto" style="min-width: 270px;">
                            <label class="form-label fw-semibold small text-muted mb-1">Rango de Fechas</label>
                            <div class="position-relative">
                                <input type="text" id="rango_fechas" class="form-control form-control-sm"
                                    placeholder="Selecciona fecha" autocomplete="off"
                                    style="cursor:pointer; padding-left: 2.5rem; font-size: 14px; height: 38px; width: 100%; border: 2px solid #e9ecef; border-radius: 6px; background: white; transition: all 0.3s ease;">
                                <i class="bx bx-calendar"
                                    style="position:absolute; left:0.8rem; top:50%; transform:translateY(-50%); color:#6c757d; font-size: 1rem; pointer-events:none;"></i>
                            </div>
                            <input type="hidden" name="fecha_inicio" id="fecha_inicio">
                            <input type="hidden" name="fecha_fin" id="fecha_fin">
                        </div>

                        <!-- Botón Exportar -->
                        <div class="col-auto">
                            <label class="form-label fw-semibold small text-muted mb-1 invisible">Acción</label>
                            <button type="button" class="btn btn-success" id="exportar" style="height: 38px; font-size: 13px; font-weight: 600; white-space: nowrap; padding: 0 24px;">
                                <i class="bx bx-download me-1"></i> Exportar
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>
        <br>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL -->
    <!-- ============================================================ -->
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
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- BARRA DE PROGRESO Y GRID PRINCIPAL -->
    <!-- ============================================================ -->
    <div id="barraProgresoReporte" class="progress" 
        role="progressbar" 
        aria-label="Cargando reporte" 
        aria-valuenow="100" 
        aria-valuemin="0" 
        aria-valuemax="100"
        style="height: 8px; display: none; border-radius: 4px; background-color: #e9ecef; margin-bottom: 12px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
            style="width: 100%; height: 100%; transition: width 0.6s ease;">
            <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 11px; color: #fff; font-weight: 600; display: none;">
                Cargando...
            </span>
        </div>
    </div>

    <div id="myGrid" style="width: 100%; height: 450px" class="ag-theme-quartz"></div>

    <!-- ============================================================ -->
    <!-- GRÁFICOS -->
    <!-- ============================================================ -->
    <div class="row">
        <div id="app"></div>
    </div>

</div>

<!-- ============================================================ -->
<!-- SCRIPTS - SECCIÓN 1: VARIABLES Y FUNCIONES GLOBALES (NATIVO) -->
<!-- ============================================================ -->
<script>
    // ================================================================
    // VARIABLES GLOBALES
    // ================================================================
    window.gridApi = null;
    window.gridApi2 = null;
    window.tipo_contador = '';
    window.fecha_inicio_global = '';
    window.fecha_fin_global = '';
    window.checkbox_online = false;
    window.validador_btn = true;
    window.validador_entradas = false;
    window.debounceTimerGlobal = null;
    window.instanciaApexChartGlobal = null;
    window.dispararCargaConDebounce = null;
    window.cargaEnCurso = false;
    window.intervaloVistasIniciado = false;

    console.log('✅ Variables globales inicializadas');

    // ================================================================
    // FUNCIÓN: MOSTRAR/OCULTAR BARRA DE PROGRESO
    // ================================================================
    function mostrarBarraProgreso() {
        const barra = document.getElementById('barraProgresoReporte');
        if (barra) barra.style.display = '';
    }

    function ocultarBarraProgreso() {
        const barra = document.getElementById('barraProgresoReporte');
        if (barra) barra.style.display = 'none';
    }

    // ================================================================
    // FUNCIÓN: updateTotal - ACTUALIZA TRÁFICO EN TIEMPO REAL
    // ================================================================
    window.updateTotal = function() {
        if (typeof gridApi === 'undefined' || !gridApi) {
            return;
        }

        if (!window.tipo_contador || !window.fecha_inicio_global || !window.fecha_fin_global) {
            return;
        }

        if (window.checkbox_online) return;

        fetch(`/getapi_conteo/${window.tipo_contador}/${window.fecha_inicio_global}/${window.fecha_fin_global}`, {
            method: 'GET',
        })
        .then(response => response.json())
        .then(data => {
            if (!gridApi) return;

            Object.entries(data).forEach(([key, value]) => {
                const rowNode = gridApi.getRowNode(key);
                if (rowNode) {
                    rowNode.setDataValue('vistas', value);
                    gridApi.flashCells({
                        rowNodes: [rowNode],
                        columns: ['vistas'],
                        flashDelay: 1000,
                    });
                }
            });
        })
        .catch(error => console.error('Error en updateTotal:', error));
    };

    // ================================================================
    // FUNCIÓN: dispararCargaAuto - DISPARA CARGA CON DEBOUNCE
    // ================================================================
    window.dispararCargaAuto = function() {
        if (typeof window.dispararCargaConDebounce === 'function' && window.instanciaApexChartGlobal) {
            window.dispararCargaConDebounce(window.instanciaApexChartGlobal);
        } else {
            setTimeout(window.dispararCargaAuto, 50);
        }
    };

    // ================================================================
    // FUNCIÓN: cargarReporte - CARGA PRINCIPAL DE DATOS
    // ================================================================
    // ================================================================
    // FUNCIÓN: cargarReporte - CARGA PRINCIPAL DE DATOS
    // ================================================================
    window.cargarReporte = function(componenteApex) {
        const form = document.getElementById('myform');
        if (!form) {
            console.error("No se encontró el formulario");
            return;
        }

        if (window.cargaEnCurso) return;
        window.cargaEnCurso = true;
        mostrarBarraProgreso();

        const formData = new FormData(form);

        fetch('/submit', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('✅ Datos del reporte:', data);
            console.log('✅ Cantidad de registros:', data.length);

            // Actualizar estado del checkbox online
            const checkbox = document.getElementById("filtroIncluirOnline");
            window.checkbox_online = checkbox.checked;

            // Obtener fechas
            const fecha_inicio = formData.get('fecha_inicio');
            const fecha_fin = formData.get('fecha_fin');

            // Determinar tipo de contador
            if (formData.get('select_radio') == 1) {
                window.tipo_contador = 'marca';
                window.validador_btn = true;
            } else {
                window.tipo_contador = 'tienda';
                window.validador_btn = false;
            }
            window.fecha_inicio_global = fecha_inicio;
            window.fecha_fin_global = fecha_fin;

            // Iniciar updateTotal si es necesario
            if (!window.checkbox_online) {
                if (typeof window.updateTotal === 'function') {
                    window.updateTotal();
                    if (!window.intervaloVistasIniciado) {
                        window.intervaloVistasIniciado = true;
                        setInterval(window.updateTotal, 10000);
                    }
                }
            }

            // ✅ ACTUALIZAR GRID con los datos
            if (typeof gridApi !== 'undefined' && gridApi) {
                console.log('🔄 Actualizando grid con', data.length, 'registros');
                gridApi.setGridOption('rowData', data);
                
                // ⚠️ IMPORTANTE: Esperar a que el grid procese los datos
                console.log('⏳ Esperando 300ms para que el grid procese los datos...');
                setTimeout(() => {
                    console.log('🔄 Llamando a initializeGrid...');
                    if (typeof initializeGrid === 'function') {
                        window.initializeGrid(gridApi);
                    } else {
                        console.error('❌ initializeGrid no está definida');
                    }
                }, 300);
            } else {
                console.error('❌ gridApi no está disponible');
            }

            // Actualizar GRÁFICOS
            if (componenteApex && data.length > 0) {
                setTimeout(() => {
                    try {
                        const nombres = data.map(item => {
                            return typeof abreviarNombre === 'function' 
                                ? abreviarNombre(item.nombre) 
                                : item.nombre;
                        });

                        const seriesLine = [
                            { name: 'VENTAS', type: 'column', data: data.map(item => item.importe_total_sum) },
                            { name: 'METAS', type: 'area', data: data.map(item => item.meta) },
                            { name: '2023', type: 'column', data: data.map(item => item.anterior) }
                        ];

                        const optionsLine = {
                            ...componenteApex.state.optionsLine,
                            labels: nombres,
                            yaxis: {
                                ...componenteApex.state.optionsLine.yaxis,
                                labels: {
                                    ...componenteApex.state.optionsLine.yaxis.labels,
                                    formatter: function(value) {
                                        return typeof abreviarNumero === 'function' 
                                            ? abreviarNumero(value) 
                                            : value;
                                    }
                                }
                            },
                            tooltip: {
                                ...componenteApex.state.optionsLine.tooltip,
                                y: {
                                    ...componenteApex.state.optionsLine.tooltip.y,
                                    formatter: function(value) {
                                        return typeof abreviarNumero === 'function' 
                                            ? abreviarNumero(value) 
                                            : value;
                                    }
                                }
                            },
                        };

                        const polarValues = data.map(item => parseFloat(item.importe_total_sum));
                        const polarNames = data.map(item => {
                            return typeof abreviarNombre === 'function' 
                                ? abreviarNombre(item.nombre) 
                                : item.nombre;
                        });

                        const optionsPolarArea = {
                            ...componenteApex.state.optionsPolarArea,
                            labels: polarNames,
                        };

                        componenteApex.setState({
                            seriesLine,
                            optionsLine,
                            seriesPolarArea: polarValues,
                            optionsPolarArea
                        });
                    } catch (e) {
                        console.error('Error actualizando gráficos:', e);
                    }
                }, 100);
            }
        })
        .catch(error => {
            console.error('❌ Error en cargarReporte:', error);
        })
        .finally(() => {
            window.cargaEnCurso = false;
            ocultarBarraProgreso();
        });
    };

    console.log('✅ Funciones globales registradas');
</script>

<!-- ============================================================ -->
<!-- SCRIPTS - SECCIÓN 2: MULTISELECT Y EVENTOS (NATIVO) -->
<!-- ============================================================ -->
<script type="text/javascript">
    // ================================================================
    // MULTISELECT - MARCAS
    // ================================================================
    $('#exampleFormControlSelect2').multiselect({
        includeSelectAllOption: true,
        allSelectedText: 'Todos seleccionados',
        onChange: function() {
            if (typeof window.dispararCargaAuto === 'function') {
                window.dispararCargaAuto();
            }
        },
        onSelectAll: function() {
            if (typeof window.dispararCargaAuto === 'function') {
                window.dispararCargaAuto();
            }
        },
        onDeselectAll: function() {
            if (typeof window.dispararCargaAuto === 'function') {
                window.dispararCargaAuto();
            }
        }
    });

    // ================================================================
    // MULTISELECT - SUCURSALES
    // ================================================================
    $('#sucursalesSelect').multiselect({
        includeSelectAllOption: true,
        allSelectedText: 'Todos seleccionados',
        enableFiltering: true,
        buttonWidth: '100%',
        maxHeight: 300,
        onChange: function(option, checked) {
            guardarSucursales();
            if (typeof window.dispararCargaAuto === 'function') {
                window.dispararCargaAuto();
            }
        },
        onSelectAll: function() {
            guardarSucursales();
            if (typeof window.dispararCargaAuto === 'function') {
                window.dispararCargaAuto();
            }
        },
        onDeselectAll: function() {
            guardarSucursales();
            if (typeof window.dispararCargaAuto === 'function') {
                window.dispararCargaAuto();
            }
        }
    });

    // ================================================================
    // GUARDAR SUCURSALES
    // ================================================================
    function guardarSucursales() {
        let seleccionadas = $('#sucursalesSelect').val() || [];
        fetch('/config_sucursales', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ sucursales: seleccionadas })
        })
        .then(response => response.json())
        .then(data => console.log('Sucursales guardadas:', data))
        .catch(error => console.error('Error guardando sucursales:', error));
    }

    // ================================================================
    // EVENTOS DE CHECKBOX Y RADIO
    // ================================================================
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('filtroOnline')
            .addEventListener('change', function() {
                if (typeof window.dispararCargaAuto === 'function') {
                    window.dispararCargaAuto();
                }
            });

        document.getElementById('filtroIncluirOnline')
            .addEventListener('change', function() {
                if (typeof window.dispararCargaAuto === 'function') {
                    window.dispararCargaAuto();
                }
            });

        document.querySelectorAll('input[name="select_radio"]')
            .forEach(radio => {
                radio.addEventListener('change', function() {
                    if (typeof window.dispararCargaAuto === 'function') {
                        window.dispararCargaAuto();
                    }
                });
            });
    });

    // ================================================================
    // EXPORTAR A EXCEL
    // ================================================================
    document.getElementById('exportar').addEventListener('click', function() {
        if (typeof gridApi !== 'undefined' && gridApi) {
            const jsonData = gridApi.getModel().rowsToDisplay.map(row => row.data);
            const worksheet = XLSX.utils.json_to_sheet(jsonData);
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, 'Reporte');
            const today = new Date();
            const formattedDate = today.getFullYear() + '-' + (today.getMonth() + 1) + '-' + today.getDate();
            XLSX.writeFile(workbook, 'datos_' + formattedDate + '.xlsx');
        }
    });
</script>

<!-- ============================================================ -->
<!-- SCRIPTS - SECCIÓN 3: AG-GRID Y LÓGICA PRINCIPAL (BABEL) -->
<!-- ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.1.1/dist/ag-grid-community.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.2/xlsx.full.min.js"></script>

<script type="text/babel">
    // ================================================================
    // VARIABLES LOCALES
    // ================================================================
    let gridApi;
    let gridApi2;

    // ================================================================
    // FUNCIONES DE FORMATEO
    // ================================================================
    function abreviarNombre(nombre) {
        if (nombre === "MENTHA & CHOCOLATE") return "MCH";
        return nombre;
    }

    function abreviarNumero(numero) {
        if (numero >= 1000000) return (numero / 1000000).toFixed(1) + 'M';
        if (numero >= 1000) return (numero / 1000).toFixed(1) + 'k';
        return numero.toString();
    }

    function getStyledNumber(value, isPercentage = false) {
        if (value == null || isNaN(value)) return '';
        const formatted = parseFloat(value).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        const percent = isPercentage ? ' %' : '';
        const color = value < 0 ? '#F87171' : 'var(--positive-color)';
        return `<span style="color:${color};">${formatted}${percent}</span>`;
    }

    function getTrendIcon(value) {
        if (value == null || isNaN(value)) return '';
        if (value > 0) return '📈 ';
        if (value < 0) return '📉 ';
        return '➖ ';
    }

    function getStyledInteger(value) {
        if (value == null || isNaN(value)) return '';
        const color = value < 0 ? '#F87171' : 'var(--positive-color)';
        return `<span style="color:${color};">${parseInt(value).toLocaleString()}</span>`;
    }

    // ================================================================
    // VALUE GETTERS PARA COLUMNAS
    // ================================================================
    function abValueGetter(params) {
        if (params.data.anterior == 0) return 0;
        return ((params.data.importe_total_sum / params.data.anterior) - 1) * 100;
    }

    function calculo_convercion(params) {
        if (params.data.total == 0) return 0;
        return (params.data.ticket / params.data.total) * 100;
    }

    // ================================================================
    // RENDERER: TOTAL VALUE (BOTÓN "VER MÁS")
    // ================================================================
    class TotalValueRenderer {
        eGui;
        eButton;
        cellValue;
        eventListener;
        option;
        filtroincluironline;
        filtroonline;
        filtrotxd;

        init(params) {
            const formData = new FormData(document.getElementById('myform'));
            this.option = formData.get('option');
            this.filtroonline = formData.get('filtroonline');
            this.filtroincluironline = formData.get('filtroincluironline');
            this.filtrotxd = formData.get('filtrotxd');

            window.filtroonline_temp = formData.get('filtroonline');
            window.filtroincluironline_temp = formData.get('filtroincluironline');
            window.filtrotxd_temp = formData.get('filtrotxd');

            this.eGui = document.createElement('div');
            this.eGui.innerHTML = `
                <span>
                    <a class="btn btn-xs btn-danger ver-mas-btn" style="color: white">Ver Mas</a>
                </span>
            `;

            this.eButton = this.eGui.querySelector('.ver-mas-btn');

            this.eButton.addEventListener('click', () => {
                if (window.validador_btn) {
                    const rowData = params.data;
                    const token = document.querySelector('input[name="_token"]').value;

                    const formData2 = new FormData();
                    formData2.append('_token', token);
                    formData2.append('marca[]', rowData.id);
                    formData2.append('fecha_inicio', rowData.fecha_inicio);
                    formData2.append('fecha_fin', rowData.fecha_fin);
                    formData2.append('option', this.option);
                    formData2.append('filtroonline', window.filtroonline_temp || '');
                    formData2.append('filtroincluironline', window.filtroincluironline_temp || '');
                    formData2.append('filtrotxd', window.filtrotxd_temp || '');
                    formData2.append('select_radio', 2);

                    fetch('/submit', {
                        method: 'POST',
                        body: formData2
                    })
                    .then(response => response.json())
                    .then(data => {
                        const checkbox = document.getElementById("filtroIncluirOnline");
                        window.checkbox_online = checkbox.checked;

                        gridApi.setGridOption('rowData', data);
                        window.tipo_contador = 'tienda';
                        window.fecha_inicio_global = rowData.fecha_inicio;
                        window.fecha_fin_global = rowData.fecha_fin;
                        window.validador_btn = false;

                        this.eButton.removeAttribute('href');
                        this.eButton.setAttribute('data-bs-toggle', 'modal');
                        this.eButton.setAttribute('data-bs-target', '#exampleModal');

                        setTimeout(() => {
                            initializeGrid(gridApi);
                        }, 150);
                    })
                    .catch(error => console.error('Error:', error));
                } else {
                    const rowData = params.data;
                    const token = document.querySelector('input[name="_token"]').value;

                    const formData = new FormData();
                    formData.append('_token', token);
                    formData.append('id', rowData.id);
                    formData.append('fecha_inicio', rowData.fecha_inicio);
                    formData.append('fecha_fin', rowData.fecha_fin);

                    fetch(`/vermas_tienda`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        gridApi2.setRowData([]);
                        gridApi2.setGridOption('rowData', data);
                    })
                    .catch(error => console.error('Error:', error));
                }
            });

            if (!window.validador_btn) {
                this.eButton.removeAttribute('href');
                this.eButton.setAttribute('data-bs-toggle', 'modal');
                this.eButton.setAttribute('data-bs-target', '#exampleModal');
            }
        }

        getGui() { return this.eGui; }

        refresh(params) {
            if (window.validador_btn) {
                const url = `/vermas?id=${params.data.id}&fecha_inicio=${params.data.fecha_inicio}&fecha_fin=${params.data.fecha_fin}`;
                this.eButton.href = url;
            } else {
                this.eButton.removeAttribute('href');
                this.eButton.setAttribute('data-bs-toggle', 'modal');
                this.eButton.setAttribute('data-bs-target', '#exampleModal');
            }
            return true;
        }

        destroy() {
            if (this.eButton) {
                this.eButton.removeEventListener('click', this.eventListener);
            }
        }
    }

    // ================================================================
    // CUSTOM HEADER GROUP
    // ================================================================
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
                '<div class="customHeaderLabel">' + this.params.displayName + '</div>' +
                '<div class="customExpandButton"><i class="fa fa-arrow-right"></i></div>';

            this.onExpandButtonClickedListener = this.expandOrCollapse.bind(this);
            this.eExpandButton = this.eGui.querySelector('.customExpandButton');
            this.eExpandButton.addEventListener('click', this.onExpandButtonClickedListener);

            this.onExpandChangedListener = this.syncExpandButtons.bind(this);
            this.params.columnGroup.getProvidedColumnGroup().addEventListener('expandedChanged', this.onExpandChangedListener);
            this.syncExpandButtons();
        }

        getGui() { return this.eGui; }

        expandOrCollapse() {
            var currentState = this.params.columnGroup.getProvidedColumnGroup().isExpanded();
            this.params.setExpanded(!currentState);
        }

        syncExpandButtons() {
            function collapsed(toDeactivate) {
                toDeactivate.className = toDeactivate.className.split(' ')[0] + ' collapsed';
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
            this.eExpandButton.removeEventListener('click', this.onExpandButtonClickedListener);
        }
    }

    // ================================================================
    // INICIALIZAR GRID - CON SUMA TOTAL (TU VERSIÓN ORIGINAL)
    // ================================================================
    // ================================================================
    // INICIALIZAR GRID - CON SUMA TOTAL Y LOGS DE DEPURACIÓN
    // ================================================================
    function initializeGrid(api) {
        console.log('🔍 initializeGrid - INICIO');
        console.log('🔍 api existe?', !!api);
        
        if (!api) {
            console.error('❌ initializeGrid: api no está definida');
            return;
        }

        // Obtener TODOS los nodos actuales
        const allNodes = [];
        api.forEachNode(node => {
            allNodes.push(node);
        });
        console.log('🔍 Nodos en el grid:', allNodes.length);

        // Filtrar nodos que NO son "Suma Total:"
        const dataNodes = allNodes.filter(node => node.data.nombre !== 'Suma Total:');
        console.log('🔍 Nodos de datos (excluyendo suma):', dataNodes.length);

        if (dataNodes.length === 0) {
            console.warn('⚠️ No hay datos para sumar, esperando datos...');
            // Reintentar después de 500ms si no hay datos
            setTimeout(() => {
                console.log('🔄 Reintentando initializeGrid...');
                initializeGrid(api);
            }, 500);
            return;
        }

        // Calcular sumas
        let totalSum = 0;
        let metaSum = 0;
        let anteriorSum = 0;
        let unidadesSum = 0;
        let ticketSum = 0;
        let visitantesSum = 0;
        let costoVentaNeta = 0;

        dataNodes.forEach(node => {
            totalSum += parseFloat(node.data.importe_total_sum || 0);
            metaSum += parseFloat(node.data.meta || 0);
            anteriorSum += parseFloat(node.data.anterior || 0);
            unidadesSum += parseFloat(node.data.unidades || 0);
            ticketSum += parseFloat(node.data.ticket || 0);
            visitantesSum += parseFloat(node.data.vistas || 0);
            costoVentaNeta += parseFloat(node.data.costo_venta_neta || 0);
        });

        console.log('📊 Sumas calculadas:', {
            totalSum,
            metaSum,
            unidadesSum,
            ticketSum,
            visitantesSum
        });

        // Eliminar fila de suma anterior si existe
        const nodesToRemove = [];
        api.forEachNode(node => {
            if (node.data.nombre === 'Suma Total:') {
                nodesToRemove.push(node.data);
            }
        });

        if (nodesToRemove.length > 0) {
            console.log('🗑️ Eliminando fila de suma anterior:', nodesToRemove.length);
            api.applyTransaction({
                remove: nodesToRemove
            });
        }

        // Agregar la nueva fila de suma
        const result = api.applyTransaction({
            add: [{
                nombre: 'Suma Total:',
                importe_total_sum: totalSum,
                meta: metaSum,
                anterior: anteriorSum,
                unidades: unidadesSum,
                ticket: ticketSum,
                vistas: visitantesSum,
                costo_venta_neta: costoVentaNeta,
                logro: metaSum > 0 ? (totalSum / metaSum) * 100 : 0,
            }]
        });

        console.log('✅ Fila de suma agregada:', result);

        // Refrescar el grid
        api.refreshCells();

        // Actualizar tráfico en tiempo real
        if (typeof window.updateTotal === 'function') {
            setTimeout(() => {
                window.updateTotal();
            }, 100);
        }

        console.log('✅ initializeGrid - FINALIZADO');
    }

    // ⚠️ IMPORTANTE: Exponer initializeGrid globalmente
    window.initializeGrid = initializeGrid; 

    // ================================================================
    // INICIALIZAR GRID 2 (MODAL)
    // ================================================================
    function initializeGrid2(api) {
        let totalSum = 0;
        let metaSum = 0;
        let unidadesSum = 0;
        let ticketSum = 0;
        let visitantesSum = 0;

        api.forEachNode(node => {
            totalSum += parseFloat(node.data.importe_total_sum || 0);
            metaSum += parseFloat(node.data.meta || 0);
            unidadesSum += parseFloat(node.data.unidades || 0);
            ticketSum += parseFloat(node.data.ticket || 0);
            visitantesSum += parseFloat(node.data.vistas || 0);
        });

        api.applyTransaction({
            add: [{
                nombre: 'Suma Total:',
                importe_total_sum: totalSum,
                meta: metaSum,
                unidades: unidadesSum,
                ticket: ticketSum,
                vistas: visitantesSum,
            }]
        });
    }

    // ================================================================
    // CONFIGURACIÓN GRID PRINCIPAL
    // ================================================================
    const gridOptions = {
        suppressDragLeaveHidesColumns: true,
        rowData: [],
        suppressExcelExport: true,
        popupParent: document.body,
        getRowId: (params) => params.data.id,
        columnDefs: [
            {
                headerName: 'Marca',
                pinned: 'left',
                field: 'nombre',
                width: 150,
                filter: true,
                floatingFilter: true
            },
            {
                headerName: 'Ventas',
                children: [
                    {
                        field: 'importe_total_sum',
                        headerName: 'Venta',
                        width: 150,
                        cellStyle: { textAlign: "right" },
                        filter: true,
                        floatingFilter: true,
                        valueFormatter: params => params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : ''
                    },
                    {
                        field: 'anterior',
                        headerName: 'Venta AA',
                        width: 120,
                        cellStyle: { textAlign: "right" },
                        filter: true,
                        floatingFilter: true,
                        valueFormatter: params => params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : ''
                    },
                    {
                        headerName: 'Crec VS AA',
                        colId: 'crec_aa',
                        valueGetter: abValueGetter,
                        width: 120,
                        cellStyle: { textAlign: "left" },
                        filter: true,
                        floatingFilter: true,
                        cellRenderer: function(params) {
                            const val = parseFloat(params.value);
                            return getTrendIcon(val) + getStyledNumber(val, true);
                        }
                    },
                    {
                        headerName: 'GM %',
                        width: 130,
                        cellStyle: { textAlign: "right" },
                        filter: true,
                        floatingFilter: true,
                        cellRenderer: function(params) {
                            if (params.data.nombre === 'Suma Total:') return '';
                            if (!params.data) return '';
                            const total = parseFloat(params.data.importe_total_sum) || 0;
                            const costo = parseFloat(params.data.costo_venta_neta) || 0;
                            if (total === 0 || isNaN(costo)) return '';
                            const gm = ((total - costo) * 100) / total;
                            return getStyledNumber(gm, true);
                        }
                    },
                    {
                        field: 'meta',
                        width: 120,
                        filter: true,
                        floatingFilter: true,
                        cellStyle: { textAlign: "right" },
                        valueFormatter: params => params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : ''
                    },
                    {
                        field: 'logro',
                        headerName: 'Logro(%)',
                        width: 120,
                        cellStyle: { textAlign: "right" },
                        filter: true,
                        floatingFilter: true,
                        cellRenderer: function(params) {
                            const val = parseFloat(params.value);
                            return getStyledNumber(val, true);
                        }
                    },
                    {
                        field: 'costo_venta_neta',
                        headerName: 'costo_venta_neta',
                        hide: true,
                        width: 130,
                        cellStyle: { textAlign: "right" },
                        filter: true,
                        floatingFilter: true,
                        valueFormatter: params => params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : ''
                    }
                ]
            },
            {
                headerName: "Transacciones",
                children: [
                    {
                        field: 'unidades',
                        width: 110,
                        cellStyle: { textAlign: "right" },
                        filter: true,
                        floatingFilter: true,
                        cellRenderer: params => getStyledInteger(params.value)
                    },
                    {
                        headerName: 'Precio promedio',
                        field: 'precio_promedio',
                        width: 140,
                        cellStyle: { textAlign: "right" },
                        filter: true,
                        floatingFilter: true,
                        valueFormatter: params => params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : ''
                    },
                    {
                        headerName: '# Tikets',
                        field: 'ticket',
                        width: 100,
                        cellStyle: { textAlign: "right" },
                        filter: true,
                        floatingFilter: true,
                        cellRenderer: params => getStyledInteger(params.value)
                    },
                    {
                        headerName: 'Tickets promedio',
                        field: 'ticket_promedio',
                        width: 140,
                        cellStyle: { textAlign: "right" },
                        filter: true,
                        floatingFilter: true,
                        valueFormatter: params => params.value ? parseFloat(params.value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : ''
                    }
                ]
            },
            {
                headerName: "Trafico de Clientes",
                children: [
                    {
                        field: 'total',
                        headerName: 'Tráfico',
                        colId: 'vistas',
                        width: 100,
                        cellStyle: { textAlign: "right" },
                        filter: true,
                        floatingFilter: true,
                        cellRenderer: params => getStyledInteger(params.value)
                    },
                    {
                        headerName: 'Converión (%)',
                        width: 120,
                        cellStyle: { textAlign: "right" },
                        colId: 'ticket&total',
                        valueGetter: calculo_convercion,
                        filter: true,
                        floatingFilter: true,
                        valueFormatter: params => params.value ? parseFloat(params.value).toFixed(2) : ''
                    }
                ]
            },
            {
                field: '',
                minWidth: 190,
                maxWidth: 100,
                editable: false,
                cellRenderer: TotalValueRenderer
            },
            { field: 'id', pinned: 'left', width: 80, hide: true },
            { field: 'fecha_inicio', pinned: 'left', width: 80, hide: true },
            { field: 'fecha_fin', pinned: 'left', width: 80, hide: true }
        ],
        defaultColDef: {
            filter: true,
            editable: true,
        },
        rowSelection: 'multiple',
        onGridReady: function(params) {
            gridApi = params.api;
            window.gridApi = params.api;
            // ⚠️ NO llamamos a initializeGrid aquí porque aún no hay datos
            // initializeGrid se llama desde cargarReporte después de cargar los datos
        }
    };

    // ================================================================
    // CONFIGURACIÓN GRID 2 (MODAL)
    // ================================================================
    const gridOptions2 = {
        rowData: [],
        getRowId: (params) => params.data.id,
        columnDefs: [
            { field: 'codigo_producto', headerName: 'Codigo', width: 150, filter: true, floatingFilter: true },
            { field: 'codigo_padre', headerName: 'Código Padre', width: 150, filter: true, floatingFilter: true },
            { field: 'producto', headerName: 'Descripción', width: 150, filter: true, floatingFilter: true },
            { field: 'cantidad', headerName: 'Cantidad', width: 150, filter: true, floatingFilter: true },
            { field: 'costo_unitario', headerName: 'Precio Unit.', width: 150, filter: true, floatingFilter: true },
            { field: 'importe_subtotal', headerName: 'Importe', width: 150, filter: true, floatingFilter: true },
            { field: 'fecha_documento', headerName: 'Fecha', width: 150, filter: true, floatingFilter: true },
            { field: 'comprobante', headerName: 'Comprobante', width: 150, filter: true, floatingFilter: true },
            { field: 'persona', headerName: 'Cliente', width: 150, filter: true, floatingFilter: true },
            { field: 'idsucursal', headerName: 'id', pinned: 'left', width: 80, hide: true }
        ],
        defaultColDef: { filter: true, editable: true },
        rowSelection: 'multiple',
        onGridReady: function(params) {
            gridApi2 = params.api;
            window.gridApi2 = params.api;
            if (gridApi2) {
                initializeGrid2(gridApi2);
            }
        }
    };

    // ================================================================
    // CREAR GRIDS
    // ================================================================
    gridApi = agGrid.createGrid(document.querySelector('#myGrid'), gridOptions);
    window.gridApi = gridApi;

    gridApi2 = agGrid.createGrid(document.querySelector('#myGrid2'), gridOptions2);
    window.gridApi2 = gridApi2;

    // ================================================================
    // FUNCIÓN DE DEBOUNCE
    // ================================================================
    function dispararCargaConDebounce(componenteApex) {
        if (window.debounceTimerGlobal) {
            clearTimeout(window.debounceTimerGlobal);
        }
        window.debounceTimerGlobal = setTimeout(() => {
            window.cargarReporte(componenteApex);
        }, 300);
    }

    window.dispararCargaConDebounce = dispararCargaConDebounce;

    // ================================================================
    // COMPONENTE APEXCHART (GRÁFICOS)
    // ================================================================
    let cardColor, headingColor, axisColor, shadeColor, borderColor;

    class ApexChart extends React.Component {
        constructor(props) {
            super(props);
            this.loadData = this.loadData.bind(this);

            this.state = {
                seriesLine: [
                    { name: 'VENTAS', type: 'column', data: [23, 11, 22, 100, 13, 22] },
                    { name: 'METAS', type: 'area', data: [44, 55, 41, 67, 22, 43] },
                    { name: '2023', type: 'column', data: [50, 80, 66, 30, 50, 46] }
                ],
                seriesPolarArea: ["100", "100", "100", "100", "100"],
                optionsLine: {
                    chart: { height: 350, type: 'line', stacked: false },
                    stroke: { width: [0, 2, 5], curve: 'smooth' },
                    fill: {
                        opacity: [0.85, 0.25, 1],
                        gradient: {
                            inverseColors: false,
                            shade: 'light',
                            type: "vertical",
                            opacityFrom: 0.85,
                            opacityTo: 0.55,
                            stops: [0, 100, 100, 100]
                        }
                    },
                    labels: ['MCH', 'FINA', 'KORDA', 'MILK', 'OUTLET', 'EXIT'],
                    xaxis: { type: 'text' },
                    yaxis: { title: { text: 'Ventas' }, min: 0 },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: {
                            formatter: function(y) {
                                if (typeof y !== "undefined") {
                                    return y.toFixed(0) + " ventas";
                                }
                                return y;
                            }
                        }
                    },
                    plotOptions: { bar: { columnWidth: '50%' } },
                    dataLabels: {
                        formatter: function(val) { return abreviarNumero(val); },
                        style: { colors: ['#454545'] }
                    },
                    colors: ['#7367F0', '#EA5455', '#94dac1c9'],
                    legend: { show: true }
                },
                optionsPolarArea: {
                    chart: { type: 'polarArea' },
                    stroke: { colors: ['#fff'] },
                    fill: { opacity: 0.8 },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: { width: 200 },
                            legend: { position: 'bottom' }
                        }
                    }],
                    colors: ['#7367F0', '#DAA794', '#94D2DA', '#DA94DA', '#DA9494', '#DAD094']
                }
            };
        }

        componentDidMount() {
            window.instanciaApexChartGlobal = this;
            this.loadData();
        }

        loadData() {
            window.cargarReporte(this);
        }

        render() {
            return (
                <div>
                    <div class="row">
                        <div class="col-12 col-lg-8 col-sm-12 order-1 order-lg-1 mb-4">
                            <ReactApexChart
                                options={this.state.optionsLine}
                                series={this.state.seriesLine}
                                type="line"
                                height={350}
                            />
                        </div>
                        <div class="col-12 col-lg-4 col-sm-12 order-2 order-lg-2 mb-4">
                            <ReactApexChart
                                options={this.state.optionsPolarArea}
                                series={this.state.seriesPolarArea}
                                type="polarArea"
                                height={350}
                            />
                        </div>
                    </div>
                </div>
            );
        }
    }

    // ================================================================
    // RENDERIZAR APEXCHART
    // ================================================================
    const domContainer = document.querySelector('#app');
    ReactDOM.render(React.createElement(ApexChart), domContainer);

    // ================================================================
    // CARGA INICIAL AUTOMÁTICA
    // ================================================================
    setTimeout(() => {
        if (typeof window.dispararCargaAuto === 'function') {
            window.dispararCargaAuto();
        }
    }, 500);

    console.log('✅ Aplicación inicializada correctamente');
</script>

<!-- ============================================================ -->
<!-- SCRIPTS - SECCIÓN 4: LITEPICKER (NATIVO) -->
<!-- ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>

<script>
    let rangoFechasPicker;

    function formatearFechaYMD(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function inicializarRangoFechas() {
        const inputFechaInicio = document.getElementById('fecha_inicio');
        const inputFechaFin = document.getElementById('fecha_fin');
        const inputVisible = document.getElementById('rango_fechas');

        rangoFechasPicker = new Litepicker({
            element: inputVisible,
            singleMode: false,
            allowRepick: true,
            numberOfColumns: 2,
            numberOfMonths: 2,
            format: 'YYYY-MM-DD',
            lang: 'es-ES',
            tooltipText: { one: 'día', other: 'días' },
            tooltipNumber: (totalDays) => totalDays - 1,
            setup: (picker) => {
                picker.on('selected', (date1, date2) => {
                    const inicio = formatearFechaYMD(date1.dateInstance);
                    const fin = date2 ? formatearFechaYMD(date2.dateInstance) : inicio;

                    inputFechaInicio.value = inicio;
                    inputFechaFin.value = fin;

                    inputVisible.value = (inicio === fin) ? inicio : `${inicio} → ${fin}`;

                    if (typeof window.dispararCargaAuto === 'function') {
                        window.dispararCargaAuto();
                    }
                });
            }
        });

        if (!inputFechaInicio.value && !inputFechaFin.value) {
            const hoy = new Date();
            const hoyStr = formatearFechaYMD(hoy);
            inputFechaInicio.value = hoyStr;
            inputFechaFin.value = hoyStr;
            inputVisible.value = hoyStr;
            rangoFechasPicker.setDateRange(hoy, hoy);
        }
    }

    document.addEventListener('DOMContentLoaded', inicializarRangoFechas);
</script>

<!-- ============================================================ -->
<!-- SCRIPTS - SECCIÓN 5: DEPENDENCIAS (FOOTER) -->
<!-- ============================================================ -->
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/1.1.2/js/bootstrap-multiselect.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/react@16.12/umd/react.production.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/react-dom@16.12/umd/react-dom.production.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prop-types@15.7.2/prop-types.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/babel-core/5.8.34/browser.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/react-apexcharts@1.3.6/dist/react-apexcharts.iife.min.js"></script>

@endsection