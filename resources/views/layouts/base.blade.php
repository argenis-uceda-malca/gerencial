<!DOCTYPE html>

<!-- =========================================================
* Sneat - Bootstrap 5 HTML Admin Template - Pro | v1.0.0
==============================================================
=========================================================
 -->
<!-- beautify ignore:start -->
<html
  lang="en"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="../assets/"
  data-template="vertical-menu-template-free"
>
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />

    <title>@yield('title')</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon2.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />

    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">

    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" />

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="../assets/js/config.js"></script>

    <!-- Data table -->
    <link href="https://cdn.datatables.net/v/bs5/dt-1.13.4/sc-2.1.1/datatables.min.css" rel="stylesheet"/>
    

    <!-- Multiple select -->
    {{-- <link href="https://unpkg.com/bootstrap-multiselect@0.9.13/dist/css/bootstrap-multiselect.css" rel="stylesheet"/>
    <script src="https://unpkg.com/bootstrap-multiselect@0.9.13/dist/js/bootstrap-multiselect.js"></script> --}}

    <script src="../assets/vendor/libs/jquery/jquery.js"></script>

    <link rel="stylesheet" href="../assets/vendor/libs/multiselect/prettify.min.css">
    <script src="../assets/vendor/libs/multiselect/bootstrap.bundle-4.5.2.min.js"></script>
    <script type="text/javascript" src="../assets/vendor/libs/multiselect/prettify.min.js"></script>
    <link rel="stylesheet" href="../assets/vendor/libs/multiselect/bootstrap-multiselect.css" type="text/css">
    <script type="text/javascript" src="../assets/vendor/libs/multiselect/bootstrap-multiselect.js"></script>
    
    {{-- Esta fue la unica linea q estaba sin comentar, antes de agreggar los nuevos multiselect --}}
    {{-- <link rel="stylesheet" href="	https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css"> --}}

    {{-- <!-- Multiselec Tema -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <!-- Or for RTL support -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
     --}}

     <style>
      /* ══════════════════════════════════════════════════════
         DARK MODE – Variables globales
         Se activa con data-theme="dark" en <html>
         ══════════════════════════════════════════════════════ */
      :root {
        --dm-bg:           #F4F5FB;
        --dm-surface:      #FFFFFF;
        --dm-surface-alt:  #F8F9FE;
        --dm-surface-warm: #FCF7F5;
        --dm-ink:          #1E2A3A;
        --dm-muted:        #8E9BB4;
        --dm-border:       #E8ECF5;
        --dm-shadow:       0 2px 16px rgba(30,42,58,.06);
        --dm-navbar-bg:    #FFFFFF;
        --dm-card-bg:      #FFFFFF;
        --dm-input-bg:     #FFFFFF;
        --chart-bar-stroke:   #FFFFFF;
        --chart-label-color:  #454545;
        --chart-axis-color:   #A1ACB8;
        --chart-border-color: #ECEEF1;
      }

      [data-theme="dark"] {
        --dm-bg:           #141824;
        --dm-surface:      #1F2438;
        --dm-surface-alt:  #282E44;
        --dm-surface-warm: #1F2438;
        --dm-ink:          #E2E6F0;
        --dm-muted:        #8B90A8;
        --dm-border:       #2E3450;
        --dm-shadow:       0 2px 16px rgba(0,0,0,.3);
        --dm-navbar-bg:    #1A1F33;
        --dm-card-bg:      #1F2438;
        --dm-input-bg:     #282E44;
        --chart-bar-stroke:   transparent;
        --chart-label-color:  #D1D5E0;
        --chart-axis-color:   #8B90A8;
        --chart-border-color: #2E3450;
      }

      /* Dark mode global overrides */
      [data-theme="dark"] body {
        background: var(--dm-bg) !important;
        color: var(--dm-ink);
      }
      [data-theme="dark"] .layout-page,
      [data-theme="dark"] .content-wrapper,
      [data-theme="dark"] .content-backdrop {
        background: var(--dm-bg);
      }
      [data-theme="dark"] .card,
      [data-theme="dark"] .card-body,
      [data-theme="dark"] .bg-navbar-theme {
        background: var(--dm-card-bg) !important;
        border-color: var(--dm-border) !important;
      }
      [data-theme="dark"] .navbar {
        background: var(--dm-navbar-bg) !important;
        box-shadow: var(--dm-shadow);
      }
      [data-theme="dark"] .navbar .nav-link,
      [data-theme="dark"] .navbar .navbar-nav .nav-link {
        color: var(--dm-muted);
      }
      [data-theme="dark"] .text-muted,
      [data-theme="dark"] .text-body,
      [data-theme="dark"] .small {
        color: var(--dm-muted) !important;
      }
      [data-theme="dark"] h1, [data-theme="dark"] h2, [data-theme="dark"] h3,
      [data-theme="dark"] h4, [data-theme="dark"] h5, [data-theme="dark"] h6,
      [data-theme="dark"] .h1, [data-theme="dark"] .h2, [data-theme="dark"] .h3,
      [data-theme="dark"] .h4, [data-theme="dark"] .h5, [data-theme="dark"] .h6 {
        color: var(--dm-ink);
      }
      [data-theme="dark"] .bg-menu-theme {
        background: #171C2E !important;
      }
      [data-theme="dark"] .menu-inner > .menu-item .menu-link {
        color: var(--dm-muted);
      }
      [data-theme="dark"] .menu-inner > .menu-item.active > .menu-link {
        color: var(--dm-ink);
        background: var(--dm-surface-alt);
      }
      [data-theme="dark"] .menu-inner > .menu-item:hover > .menu-link {
        background: var(--dm-surface-alt);
      }
      [data-theme="dark"] .app-brand {
        border-color: var(--dm-border) !important;
      }
      [data-theme="dark"] .app-brand-text {
        color: var(--dm-ink) !important;
      }
      [data-theme="dark"] .dropdown-menu {
        background: var(--dm-card-bg);
        border-color: var(--dm-border);
      }
      [data-theme="dark"] .dropdown-item {
        color: var(--dm-ink);
      }
      [data-theme="dark"] .dropdown-item:hover {
        background: var(--dm-surface-alt);
      }
      [data-theme="dark"] .dropdown-divider {
        border-color: var(--dm-border);
      }
      [data-theme="dark"] .footer {
        background: var(--dm-surface) !important;
        border-color: var(--dm-border) !important;
      }
      [data-theme="dark"] .footer a {
        color: var(--dm-muted);
      }
      [data-theme="dark"] .content-footer {
        border-color: var(--dm-border) !important;
      }
      [data-theme="dark"] .form-control,
      [data-theme="dark"] .form-select {
        background: var(--dm-input-bg);
        border-color: var(--dm-border);
        color: var(--dm-ink);
      }
      [data-theme="dark"] .form-control:focus,
      [data-theme="dark"] .form-select:focus {
        border-color: #696cff;
        box-shadow: 0 0 0 2px rgba(105,108,255,.15);
      }
      [data-theme="dark"] .nav-tabs {
        border-color: var(--dm-border);
        background: var(--dm-surface-alt);
      }
      [data-theme="dark"] .nav-tabs .nav-link {
        color: var(--dm-muted);
      }
      [data-theme="dark"] .nav-tabs .nav-link:hover {
        background: var(--dm-surface);
        color: var(--dm-ink);
      }
      [data-theme="dark"] .nav-tabs .nav-link.active {
        background: var(--dm-surface);
        color: var(--dm-ink);
      }
      [data-theme="dark"] .tab-content {
        background: var(--dm-surface);
      }
      [data-theme="dark"] ::-webkit-scrollbar-track {
        background: var(--dm-surface-alt);
      }
      [data-theme="dark"] ::-webkit-scrollbar-thumb {
        background: var(--dm-border);
      }
      [data-theme="dark"] ::-webkit-scrollbar-thumb:hover {
        background: var(--dm-muted);
      }

      /* ── Scrollbar personalizado ── */
      ::-webkit-scrollbar {
        width: 7px;
        height: 7px;
      }
      ::-webkit-scrollbar-track {
        background: #F0F2F8;
        border-radius: 4px;
      }
      ::-webkit-scrollbar-thumb {
        background: #D1D5E0;
        border-radius: 4px;
        transition: background .2s;
      }
      ::-webkit-scrollbar-thumb:hover {
        background: #B0B7C8;
      }
      [data-theme="dark"] ::-webkit-scrollbar-track {
        background: #1A1F33;
      }
      [data-theme="dark"] ::-webkit-scrollbar-thumb {
        background: #3A4060;
      }
      [data-theme="dark"] ::-webkit-scrollbar-thumb:hover {
        background: #505880;
      }

      /* Dark mode toggle button */
      .theme-toggle-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: none;
        background: transparent;
        color: #8E9BB4;
        cursor: pointer;
        transition: all .2s ease;
        font-size: 1.2rem;
      }
      .theme-toggle-btn:hover {
        background: #EEECFF;
        color: #6C63FF;
      }
      [data-theme="dark"] .theme-toggle-btn {
        color: #8B90A8;
      }
      [data-theme="dark"] .theme-toggle-btn:hover {
        background: #2D2A5C;
        color: #8B83FF;
      }

      .custom-select {
        display: inline-block;
        width: 100%;
        height: calc(1.5em + 0.75rem + 2px);
        padding: 0.375rem 1.75rem 0.375rem 0.75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #495057;
        vertical-align: middle;
        background: #fff url(data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='4' height='5' viewBox='0 0 4 5'%3e%3cpath fill='%23343a40' d='M2 0L0 2h4zm0 5L0 3h4z'/%3e%3c/svg%3e) no-repeat right 0.75rem center/8px 10px;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
      }
      .font-weight-bold{
        font-weight: bold;
      }
      @media only screen and (max-width: 767px) {
        .dt-buttons {
          display: flex;
          justify-content: center;
          padding-bottom: 10px;
        }
        #reporte_venta_filter label{
          display: grid;
          justify-content: center;
          text-align: center;
        }
      }

      .paginate_button{
        cursor: pointer;
        margin-left: 0.1875rem;
        border-radius: 10px;
        padding: 0.375rem 0.375rem;
      /* font-size: 0.75rem; */
        position: relative;
        /* display: block; */
        color: #697a8d;
        background-color: #f0f2f4;
        border: 0px solid #d9dee3;
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
      }

      /* =========================================================
         Colapso de sidebar en escritorio (custom)
         ========================================================= */
      :root {
        --sb-menu-width: 260px; /* fallback, se sobreescribe por JS con el valor real */
      }

      @media (min-width: 1200px) {
        #layout-menu {
          transition: width 0.25s ease-in-out,
                      margin-left 0.25s ease-in-out,
                      opacity 0.2s ease-in-out,
                      transform 0.25s ease-in-out;
          overflow: hidden;
        }

        .layout-page {
          transition: margin-left 0.25s ease-in-out, width 0.25s ease-in-out;
        }

        html.layout-menu-fixed .layout-wrapper.sidebar-collapsed #layout-menu {
          width: 0 !important;
          min-width: 0 !important;
          max-width: 0 !important;
          margin-left: calc(-1 * var(--sb-menu-width)) !important;
          opacity: 0;
          pointer-events: none;
          border: none !important;
        }

        html.layout-menu-fixed .layout-wrapper.sidebar-collapsed .layout-page {
          margin-left: 0 !important;
          width: 100% !important;
          max-width: 100% !important;
        }

        /* Cuando NO está colapsado, forzar explícitamente el tamaño normal
           para que la transición de "regreso" sea simétrica, sin depender
           de que el navegador adivine el valor "auto" previo */
        html.layout-menu-fixed .layout-wrapper:not(.sidebar-collapsed) #layout-menu {
          width: var(--sb-menu-width);
          min-width: var(--sb-menu-width);
          margin-left: 0;
          opacity: 1;
          pointer-events: auto;
        }

        html.layout-menu-fixed .layout-wrapper:not(.sidebar-collapsed) .layout-page {
          margin-left: var(--sb-menu-width) !important;
          width: calc(100% - var(--sb-menu-width)) !important;
        }

        .layout-wrapper.sidebar-collapsed .layout-menu-toggle i {
          transform: rotate(180deg);
          transition: transform 0.25s ease-in-out;
        }

        .toggle-sidebar-btn i {
          transition: transform 0.25s ease-in-out;
        }
      }
     </style>

 

    <!-- Boostrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Sweet Alert -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.0/css/toastr.css" integrity="sha512-uHcWLPmWocgP40ZlMSy5VOBbw2QZpYFy5xhPueT/bqlI6G50L8FidgfTAy8v1MoSJS9iIJo3I8d9WVZVZaTQjA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    @stack('styles')
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo">
            <a href="{{ route('inicio.index') }}" class="app-brand-link">
              <span class="app-brand-logo demo">
                <img src="../assets/img/favicon/favicon2.ico" alt="logo">
                {{-- <svg
                  width="25"
                  viewBox="0 0 25 42"
                  version="1.1"
                  xmlns="http://www.w3.org/2000/svg"
                  xmlns:xlink="http://www.w3.org/1999/xlink"
                  >
                  <defs>
                    <path
                      d="M13.7918663,0.358365126 L3.39788168,7.44174259 C0.566865006,9.69408886 -0.379795268,12.4788597 0.557900856,15.7960551 C0.68998853,16.2305145 1.09562888,17.7872135 3.12357076,19.2293357 C3.8146334,19.7207684 5.32369333,20.3834223 7.65075054,21.2172976 L7.59773219,21.2525164 L2.63468769,24.5493413 C0.445452254,26.3002124 0.0884951797,28.5083815 1.56381646,31.1738486 C2.83770406,32.8170431 5.20850219,33.2640127 7.09180128,32.5391577 C8.347334,32.0559211 11.4559176,30.0011079 16.4175519,26.3747182 C18.0338572,24.4997857 18.6973423,22.4544883 18.4080071,20.2388261 C17.963753,17.5346866 16.1776345,15.5799961 13.0496516,14.3747546 L10.9194936,13.4715819 L18.6192054,7.984237 L13.7918663,0.358365126 Z"
                      id="path-1"
                    ></path>
                    <path
                      d="M5.47320593,6.00457225 C4.05321814,8.216144 4.36334763,10.0722806 6.40359441,11.5729822 C8.61520715,12.571656 10.0999176,13.2171421 10.8577257,13.5094407 L15.5088241,14.433041 L18.6192054,7.984237 C15.5364148,3.11535317 13.9273018,0.573395879 13.7918663,0.358365126 C13.5790555,0.511491653 10.8061687,2.3935607 5.47320593,6.00457225 Z"
                      id="path-3"
                    ></path>
                    <path
                      d="M7.50063644,21.2294429 L12.3234468,23.3159332 C14.1688022,24.7579751 14.397098,26.4880487 13.008334,28.506154 C11.6195701,30.5242593 10.3099883,31.790241 9.07958868,32.3040991 C5.78142938,33.4346997 4.13234973,34 4.13234973,34 C4.13234973,34 2.75489982,33.0538207 2.37032616e-14,31.1614621 C-0.55822714,27.8186216 -0.55822714,26.0572515 -4.05231404e-15,25.8773518 C0.83734071,25.6075023 2.77988457,22.8248993 3.3049379,22.52991 C3.65497346,22.3332504 5.05353963,21.8997614 7.50063644,21.2294429 Z"
                      id="path-4"
                    ></path>
                    <path
                      d="M20.6,7.13333333 L25.6,13.8 C26.2627417,14.6836556 26.0836556,15.9372583 25.2,16.6 C24.8538077,16.8596443 24.4327404,17 24,17 L14,17 C12.8954305,17 12,16.1045695 12,15 C12,14.5672596 12.1403557,14.1461923 12.4,13.8 L17.4,7.13333333 C18.0627417,6.24967773 19.3163444,6.07059163 20.2,6.73333333 C20.3516113,6.84704183 20.4862915,6.981722 20.6,7.13333333 Z"
                      id="path-5"
                    ></path>
                  </defs>
                  <g id="g-app-brand" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <g id="Brand-Logo" transform="translate(-27.000000, -15.000000)">
                      <g id="Icon" transform="translate(27.000000, 15.000000)">
                        <g id="Mask" transform="translate(0.000000, 8.000000)">
                          <mask id="mask-2" fill="white">
                            <use xlink:href="#path-1"></use>
                          </mask>
                          <use fill="#696cff" xlink:href="#path-1"></use>
                          <g id="Path-3" mask="url(#mask-2)">
                            <use fill="#696cff" xlink:href="#path-3"></use>
                            <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-3"></use>
                          </g>
                          <g id="Path-4" mask="url(#mask-2)">
                            <use fill="#696cff" xlink:href="#path-4"></use>
                            <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-4"></use>
                          </g>
                        </g>
                        <g
                          id="Triangle"
                          transform="translate(19.000000, 11.000000) rotate(-300.000000) translate(-19.000000, -11.000000) "
                        >
                          <use fill="#696cff" xlink:href="#path-5"></use>
                          <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-5"></use>
                        </g>
                      </g>
                    </g>
                  </g>
                </svg> --}}
              </span>
              <span class="app-brand-text demo menu-text fw-bolder ms-2" style="text-transform: none;">Smart Sales</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
              <i class="bx bx-chevron-left bx-sm align-middle"></i>
            </a>
          </div>

          <div class="menu-inner-shadow"></div>

          <ul class="menu-inner py-1">
            <!-- Dashboard -->
            <!-- @if (in_array('acceso_gerencial', session('permisos')) &&
                    in_array('acceso_cubicaje', session('permisos')) &&
                    //in_array('particiopacion_linea_temporal', session('permisos')) ||
                    in_array('acceso_top_venta', session('permisos')) &&
                    in_array('acceso_vta_acumulada', session('permisos')) &&
                    in_array('reporte_vta_mensual', session('permisos')))
                        <li class="menu-item{{ request()->routeIs('entradas.index') ? ' active' : '' }}">
                            <a href="{{ route('entradas.index') }}" class="menu-link">
                              <i class="menu-icon tf-icons bx bx-home-circle"></i>
                              <div data-i18n="Analytics">Reporte BETA</div>
                            </a>
                        </li>
            @endif -->

            

            @if (in_array('acceso_gerencial', session('permisos')))
              <li class="menu-item{{ request()->routeIs('tabla') ? ' active' : '' }}">
                  {{-- <a href="{{ route('reportesb.index') }}" class="menu-link"> --}}
                  <a href="{{ route('tabla') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div data-i18n="Analytics">Reporte</div>
                  </a>
              </li>
            @endif

            <li class="menu-item{{ request()->routeIs('reporte_ventas') ? ' active' : '' }}">
                <a href="{{ route('reporte_ventas') }}" class="menu-link">
                  <i class="menu-icon tf-icons bx bx-home-circle"></i>
                  <div data-i18n="Analytics">Reporte Gerencial</div>
                </a>
            </li>

            @if (in_array('acceso_administrador', session('permisos', [])))
            <li class="menu-item{{ request()->routeIs('dashboard.ventas') ? ' active' : '' }}">
                <a href="{{ route('dashboard.ventas') }}" class="menu-link">
                  <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                  <div>Dashboard Ventas</div>
                </a>
            </li>

            <li class="menu-item{{ request()->routeIs('dashboard.reporte') ? ' active' : '' }}">
                <a href="{{ route('dashboard.reporte') }}" class="menu-link">
                  <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                  <div>Reporte Ventas</div>
                </a>
            </li>

            <li class="menu-item{{ request()->routeIs('dashboard.ffto') ? ' active' : '' }}">
                <a href="{{ route('dashboard.ffto') }}" class="menu-link">
                  <i class="menu-icon tf-icons bx bx-walk"></i>
                  <div>Follow-up FF vs TO</div>
                </a>
            </li>
            @endif

            {{-- <!-- Layouts -->
            <li class="menu-item">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-layout"></i>
                <div data-i18n="Layouts">Layouts</div>
              </a>

              <ul class="menu-sub">
                <li class="menu-item">
                  <a href="layouts-without-menu.html" class="menu-link">
                    <div data-i18n="Without menu">Without menu</div>
                  </a>
                </li>
                <li class="menu-item">
                  <a href="layouts-without-navbar.html" class="menu-link">
                    <div data-i18n="Without navbar">Without navbar</div>
                  </a>
                </li>
                <li class="menu-item">
                  <a href="layouts-container.html" class="menu-link">
                    <div data-i18n="Container">Container</div>
                  </a>
                </li>
                <li class="menu-item">
                  <a href="layouts-fluid.html" class="menu-link">
                    <div data-i18n="Fluid">Fluid</div>
                  </a>
                </li>
                <li class="menu-item">
                  <a href="layouts-blank.html" class="menu-link">
                    <div data-i18n="Blank">Blank</div>
                  </a>
                </li>
              </ul>
            </li> --}}


            <!-- @if (in_array('reporte_vta_mensual', session('permisos')) ||
                    in_array('acceso_cubicaje', session('permisos')) ||
                    //in_array('particiopacion_linea_temporal', session('permisos')) ||
                    in_array('acceso_top_venta', session('permisos')) ||
                    in_array('acceso_vta_acumulada', session('permisos')))
<li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                  <i class="menu-icon tf-icons bx bx-dock-top"></i>
                  <div data-i18n="Account Settings">Gestión de tiendas</div>
                </a>
                <ul class="menu-sub">
                  @if (in_array('reporte_vta_mensual', session('permisos')))
<li class="menu-item">
                    <a href="" class="menu-link">
                      <div data-i18n="Account">Ventas Mensuales</div>
                    </a>
                  </li>
@endif
                  @if (in_array('acceso_cubicaje', session('permisos')))
<li class="menu-item">
                    <a href="" class="menu-link">
                      <div data-i18n="Notifications">Cubicaje por Linea/Temporada</div>
                    </a>
                  </li>
@endif
                  @if (in_array('particiopacion_linea_temporal', session('permisos')))
<li class="menu-item">
                    <a href="" class="menu-link">
                      <div data-i18n="Connections">Participación por Linea/Temporada</div>
                    </a>
                  </li>
@endif
                  @if (in_array('acceso_top_venta', session('permisos')))
<li class="menu-item">
                    <a href="" class="menu-link">
                      <div data-i18n="reportes_top">Reportes Top</div>
                    </a>
                  </li>
@endif
                  @if (in_array('acceso_vta_acumulada', session('permisos')))
<li class="menu-item">
                    <a href="entradas" class="menu-link">
                      <div data-i18n="venta_acumulada">Venta Acumulada</div>
                    </a>
                  </li>
@endif
                </ul>
              </li>
@endif -->

@if (in_array('subir_data', session('permisos')) || in_array('acceso_reporte', session('permisos')))
<li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                  <i class="menu-icon tf-icons bx bx-lock-open-alt"></i>
                  <div data-i18n="Authentications">Reporte TxD</div>
                </a>
                <ul class="menu-sub">
                  @if (in_array('acceso_reporte', session('permisos')))
<li class="menu-item">
                    <a href="{{ route('reportetxd') }}" class="menu-link" target="_blank">
                      <div data-i18n="Basic">Reporte Ventas</div>
                    </a>
                  </li>
@endif
                  @if (in_array('subir_data', session('permisos')))
<li class="menu-item">
                    <a href="" class="menu-link" target="_blank">
                      <div data-i18n="Basic">Cargar Excel</div>
                    </a>
                  </li>
@endif
                </ul>
              </li>
@endif

<!-- @if (in_array('acceso_social', session('permisos')))
<li class="menu-item">
            <a
              href=""
              target="_blank"
              class="menu-link"
            >
              <i class="menu-icon tf-icons bx bx-file"></i>
              <div data-i18n="Documentation">Social</div>
            </a>
          </li>
@endif -->
@if (in_array('acceso_rfm', session('permisos')))
<li class="menu-item">
            <a
              href="{{ route('rfm') }}"
              target="_blank"
              class="menu-link"
            >
              <i class="menu-icon tf-icons bx bx-dock-top"></i>
              <div data-i18n="Documentation">Gestion Clientes RFM</div>
            </a>
          </li>
@endif
<!-- @if (in_array('acceso_stock_tiendas', session('permisos')))
<li class="menu-item">
            <a
              href=""
              target="_blank"
              class="menu-link"
            >
              <i class="menu-icon tf-icons bx bx-dock-top"></i>
              <div data-i18n="Documentation">Stock Tiendas</div>
            </a>
          </li>
@endif -->
@if (in_array('acceso_gerencial', session('permisos')) &&
        in_array('acceso_top_venta', session('permisos')) &&
        in_array('acceso_vta_acumulada', session('permisos')) &&
        in_array('reporte_vta_mensual', session('permisos')))
<!-- Misc -->
            <li class="menu-header small text-uppercase"><span class="menu-header-text">administracion</span></li>
            <li class="menu-item">
              <a
                href="javascript:void(0);" class="menu-link menu-toggle"
                target="_blank"
                class="menu-link"
              >
                <i class="menu-icon tf-icons bx bx-support"></i>
                <div data-i18n="Support">Administrador</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item">
                  <a href="{{ route('admin.index') }}" class="menu-link">
                    <div data-i18n="Basic Inputs">Lista de usuarios</div>
                  </a>
                </li>
                <li class="menu-item">
                  <a href="" class="menu-link">
                    <div data-i18n="Input groups">Franquisiadores</div>
                  </a>
                </li>
                <li class="menu-item">
                  <a href="" class="menu-link">
                    <div data-i18n="Input groups">Consignatario</div>
                  </a>
                </li>
              </ul>
            </li>
            {{-- <li class="menu-item">
              <a
                href=""
                target="_blank"
                class="menu-link"
              >
                <i class="menu-icon tf-icons bx bx-file"></i>
                <div data-i18n="Documentation">Documentation</div>
              </a>
            </li> --}}
@endif
          </ul>
        </aside>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

          <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
            {{-- <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 ">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div> --}}
            <div class="navbar-nav align-items-xl-center me-3 me-xl-0">
              <a class="nav-item nav-link px-0 me-xl-4 toggle-sidebar-btn" href="javascript:void(0)" onclick="toggleSidebar()">
                  <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
              <!-- Search -->
              {{-- <div class="navbar-nav align-items-center">
                <div class="nav-item d-flex align-items-center">
                  <i class="bx bx-search fs-4 lh-0"></i>
                  <input
                    type="text"
                    class="form-control border-0 shadow-none"
                    placeholder="Search..."
                    aria-label="Search..."
                  />
                </div>
              </div> --}}
              <!-- /Search -->

              <ul class="navbar-nav flex-row align-items-center ms-auto">
                <!-- Fullscreen Toggle -->
                <li class="nav-item" style="margin-right:4px;">
                  <button class="theme-toggle-btn" id="fs-toggle" title="Pantalla completa">
                    <i class="bx bx-expand" id="fs-icon-enter"></i>
                    <i class="bx bx-collapse" id="fs-icon-exit" style="display:none"></i>
                  </button>
                </li>

                <!-- Dark Mode Toggle -->
                <li class="nav-item" style="margin-right:4px;">
                  <button class="theme-toggle-btn" id="theme-toggle" title="Cambiar tema">
                    <i class="bx bx-moon" id="theme-icon-moon"></i>
                    <i class="bx bx-sun" id="theme-icon-sun" style="display:none"></i>
                  </button>
                </li>

                <!-- User -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                      <img src="../assets/img/avatars/user.png" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img src="../assets/img/avatars/user.png" alt class="w-px-40 h-auto rounded-circle" />
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <span class="fw-semibold d-block">{{ session('first_name') }} {{ session('last_name') }}</span>
                            {{-- <small class="text-muted">Admin</small> --}}
                          </div>
                        </div>
                      </a>
                    </li>
                    {{-- <li>
                        <div class="dropdown-divider"></div>
                      </li> --}}
                      {{-- <li>
                        <a class="dropdown-item" href="#">
                          <i class="bx bx-user me-2"></i>
                          <span class="align-middle">My Profile</span>
                        </a>
                      </li> --}}
                      {{-- <li>
                        <a class="dropdown-item" href="#">
                          <i class="bx bx-cog me-2"></i>
                          <span class="align-middle">Settings</span>
                        </a>
                      </li> --}}
                      {{-- <li>
                        <a class="dropdown-item" href="#">
                          <span class="d-flex align-items-center align-middle">
                            <i class="flex-shrink-0 bx bx-credit-card me-2"></i>
                            <span class="flex-grow-1 align-middle">Billing</span>
                            <span class="flex-shrink-0 badge badge-center rounded-pill bg-danger w-px-20 h-px-20">4</span>
                          </span>
                        </a>
                    </li> --}}
                    <li>
                      <div class="dropdown-divider"></div>
                    </li>
                    <li>
                      <a class="dropdown-item" id="botonCerrar" href="{{ route('cerrar') }}">
                        <i class="bx bx-power-off me-2"></i>
                        <span class="align-middle">Salir</span>
                      </a>
                    </li>
                  </ul>
                </li>
                <!--/ User -->
              </ul>
            </div>
          </nav>

          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">

            @yield('contenido')

            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                <div class="mb-2 mb-md-0">
                  ©
                  <script>
                      document.write(new Date().getFullYear());
                  </script>
                  , made with  by
                  <a href="#" target="_blank" class="footer-link fw-bolder">Smart Brands</a>
                </div>
                <div>
                  {{-- <a href="" class="footer-link me-4" target="_blank">License</a>
                  <a href="" target="_blank" class="footer-link me-4">More Themes</a>

                  <a
                    href=""
                    target="_blank"
                    class="footer-link me-4"
                    >Documentation</a
                  > --}}

                  {{-- <a
                    href=""
                    target="_blank"
                    class="footer-link me-4"
                    >Support</a
                  > --}}
                </div>
              </div>
            </footer>
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

    {{-- <div class="buy-now">
      <a
        href=""
        target="_blank"
        class="btn btn-danger btn-buy-now"
        ><i class="bi bi-gear"></i> Soporte SB</a
      >
    </div> --}}

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="../assets/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>

    <!-- Main JS -->
    <script src="../assets/js/main.js"></script>

    <!-- Page JS -->
    <script src="../assets/js/dashboards-analytics.js?v1.2"></script>

    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>

    <!-- Data table -->
    <script src="https://cdn.datatables.net/v/dt/dt-1.13.4/datatables.min.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/4.2.2/js/dataTables.fixedColumns.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>

    <script>
        // =========================================================
        // Toggle de sidebar (responsive: móvil usa el comportamiento
        // original de Sneat, escritorio colapsa el ancho del menú)
        // =========================================================
        (function () {
            const DESKTOP_BREAKPOINT = 1200; // coincide con el media query xl
            const STORAGE_KEY = 'sidebarCollapsed';
            const layoutWrapper = document.querySelector('.layout-wrapper');
            const layoutMenu = document.getElementById('layout-menu');

            function isDesktop() {
                return window.innerWidth >= DESKTOP_BREAKPOINT;
            }

            function captureMenuWidth() {
                if (!layoutMenu || !layoutWrapper) return;
                // Solo medir si el menú está actualmente expandido (visible),
                // para no capturar un 0 accidental.
                if (!layoutWrapper.classList.contains('sidebar-collapsed')) {
                    const widthPx = layoutMenu.getBoundingClientRect().width;
                    if (widthPx > 0) {
                        document.documentElement.style.setProperty('--sb-menu-width', widthPx + 'px');
                    }
                }
            }

            function applyStoredState() {
                if (!layoutWrapper || !isDesktop()) return;
                captureMenuWidth();
                const collapsed = localStorage.getItem(STORAGE_KEY) === '1';
                layoutWrapper.classList.toggle('sidebar-collapsed', collapsed);
            }

            window.toggleSidebar = function () {
                if (!layoutWrapper) return;

                if (isDesktop()) {
                    // Escritorio: colapsar/expandir el ancho del sidebar
                    const willCollapse = !layoutWrapper.classList.contains('sidebar-collapsed');
                    if (willCollapse) {
                        // Capturar el ancho real justo antes de colapsar
                        captureMenuWidth();
                    }
                    layoutWrapper.classList.toggle('sidebar-collapsed', willCollapse);
                    localStorage.setItem(STORAGE_KEY, willCollapse ? '1' : '0');
                } else {
                    // Móvil/tablet: usar Helpers de Sneat para consistencia
                    // con el overlay y botón X (trabajan sobre <html>)
                    window.Helpers.toggleCollapsed();
                }
            };

            // Restaurar el estado guardado al cargar (solo en escritorio)
            document.addEventListener('DOMContentLoaded', applyStoredState);
            window.addEventListener('load', captureMenuWidth);

            // Si el usuario cambia de tamaño de ventana, limpiar estados
            // inconsistentes entre vista móvil y escritorio
            window.addEventListener('resize', function () {
                if (!layoutWrapper) return;
                if (!isDesktop()) {
                    layoutWrapper.classList.remove('sidebar-collapsed');
                } else {
                    // Limpiar layout-menu-expanded de ambos elementos por si
                    // se usó el toggle en mobile (Helpers -> <html>)
                    document.documentElement.classList.remove('layout-menu-expanded');
                    layoutWrapper.classList.remove('layout-menu-expanded');
                    applyStoredState();
                }
            });
        })();

        // ══════════════════════════════════════════════════════════
        // FULLSCREEN TOGGLE
        // ══════════════════════════════════════════════════════════
        (function () {
          const btn = document.getElementById('fs-toggle');
          const iconEnter = document.getElementById('fs-icon-enter');
          const iconExit = document.getElementById('fs-icon-exit');

          function updateFSIcon() {
            const fs = document.fullscreenElement || document.webkitFullscreenElement;
            if (fs) {
              if (iconEnter) iconEnter.style.display = 'none';
              if (iconExit) iconExit.style.display = 'inline-block';
            } else {
              if (iconEnter) iconEnter.style.display = 'inline-block';
              if (iconExit) iconExit.style.display = 'none';
            }
          }

          function toggleFS() {
            const fs = document.fullscreenElement || document.webkitFullscreenElement;
            if (fs) {
              if (document.exitFullscreen) document.exitFullscreen();
              else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
            } else {
              const el = document.documentElement;
              if (el.requestFullscreen) el.requestFullscreen();
              else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
            }
          }

          if (btn) {
            btn.addEventListener('click', toggleFS);
          }

          document.addEventListener('fullscreenchange', updateFSIcon);
          document.addEventListener('webkitfullscreenchange', updateFSIcon);
          updateFSIcon();
        })();

        // ══════════════════════════════════════════════════════════
        // DARK MODE TOGGLE
        // ══════════════════════════════════════════════════════════
        (function () {
            const STORAGE_KEY = 'theme_preference';
            const html = document.documentElement;
            const btn = document.getElementById('theme-toggle');
            const iconMoon = document.getElementById('theme-icon-moon');
            const iconSun = document.getElementById('theme-icon-sun');

            function setTheme(dark) {
                if (dark) {
                    html.setAttribute('data-theme', 'dark');
                    if (iconMoon) iconMoon.style.display = 'none';
                    if (iconSun) iconSun.style.display = 'inline-block';
                } else {
                    html.removeAttribute('data-theme');
                    if (iconMoon) iconMoon.style.display = 'inline-block';
                    if (iconSun) iconSun.style.display = 'none';
                }
                try { localStorage.setItem(STORAGE_KEY, dark ? 'dark' : 'light'); } catch (e) {}
                if (typeof window.updateChartTheme === 'function') window.updateChartTheme();
            }

            // Apply saved preference
            try {
                const saved = localStorage.getItem(STORAGE_KEY);
                if (saved === 'dark') setTheme(true);
                else if (saved === 'light') setTheme(false);
                // else: no saved preference → respect OS preference
                else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    setTheme(true);
                }
            } catch (e) {}

            // Toggle on click
            if (btn) {
                btn.addEventListener('click', function () {
                    const isDark = html.getAttribute('data-theme') === 'dark';
                    setTheme(!isDark);
                });
            }

            // Listen for OS theme changes if no saved preference
            if (window.matchMedia) {
                const mq = window.matchMedia('(prefers-color-scheme: dark)');
                mq.addEventListener('change', function (e) {
                    try {
                        const saved = localStorage.getItem(STORAGE_KEY);
                        if (!saved) setTheme(e.matches);
                    } catch (ex) {}
                });
            }
        })();

        // Eliminar variables al dar click en cerrar
        // Obtén una referencia al botón de cerrar
        const botonCerrar = document.getElementById('botonCerrar');
        // Agrega un evento de clic al botón
        botonCerrar.addEventListener('click', function() {
            // Elimina la fecha seleccionada del localStorage
            // localStorage.removeItem('fechainicioSeleccionada');
            // localStorage.removeItem('fechaSeleccionada');
            // localStorage.removeItem('valorSeleccionado');
            console.log("cerrar");
            localStorage.clear();
        });

        // // Eliminar variables al cerrar pestaña
        // window.addEventListener('unload', function() {
        //     // Elimina la fecha seleccionada del localStorage
        //     localStorage.removeItem('fechainicioSeleccionada');
        // });
    </script>

    @yield('footer')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.0/js/toastr.js" integrity="sha512-7jcpjqSVjhATgn0Xkmzyxc4emfAYP81qnhsL9rxaSlqI+m3Yw/feChvPXfeiVI/K+Ji93wvAtSxCRhmAoOvdww==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <div class="modal fade" id="commonModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="body">
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="commonModalOver" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    </div>
                </div>
            </div>
        </div>
  </body>
</html>