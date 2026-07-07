@extends('layouts.base')
@section('title', 'Smart Brands')
@section('contenido')


    <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-lg-10 mb-8 order-0">
                <div class="card">
                    <div class="d-flex align-items-end row">
                        <div class="col-sm-7">
                            <div class="card-body">
                                <h5 class="card-title text-primary">Bienvenido {{ session('first_name') }}! 🎉</h5>
                                <p class="mb-4">
                                    Resumen <span class="fw-bold">mensual</span> de las ventas Smart Brands                                                                                                                                                                                                                                                                                                 
                                </p>

                                {{-- <a href="{{ route('reportesb.index') }}" class="btn btn-sm btn-outline-primary">Ver
                                    Reporte</a> --}}
                                <a href="{{ route('tabla') }}" class="btn btn-sm btn-outline-primary">Ver
                                    Reporte</a>
                            </div>
                        </div>
                        <div class="col-sm-5 text-center text-sm-left">
                            <div class="card-body pb-0 px-0 px-md-4">
                                <img src="../assets/img/illustrations/man-with-laptop-light.png" height="140"
                                    alt="View Badge User" data-app-dark-img="illustrations/man-with-laptop-dark.png"
                                    data-app-light-img="illustrations/man-with-laptop-light.png" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 order-1">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-12 mb-4 mt-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <img src="../assets/img/icons/unicons/chart-success.png" alt="chart success"
                                            class="rounded" />
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn p-0" type="button" id="cardOpt3" data-bs-toggle="dropdown"
                                            aria-haspopup="true" aria-expanded="false">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        {{-- <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                                        <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                        <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                    </div> --}}
                                    </div>
                                </div>
                                <span class="fw-semibold d-block mb-1">Importe S/.</span>
                                <h3 class="card-title mb-2">{{ number_format($importeTotalSum, 0, '.', ',') }}</h3>
                                <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +%</small>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            <!-- Total Revenue -->
            <div class="col-12 col-lg-10 order-2 order-md-3 order-lg-2 mb-4">
                <div class="card">
                    <div class="row row-bordered g-0">
                        <div class="col-md-10">
                            <h5 class="card-header m-0 me-2 pb-3">Resumen mensual</h5>
                            <div id="totalRevenueChart" class="px-2"></div>
                        </div>
                        <div class="col-md-2">
                            <div class="card-body">
                                <div class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                                            id="growthReportId" data-bs-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="false">
                                            <?php 
                                            $año_actual = date('Y'); // Devuelve el año en formato completo, por ejemplo: 2025
                                            echo $año_actual;
                                            ?>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="growthReportId">
                                            <a class="dropdown-item" href="javascript:void(0);"> <?php 
                                            $año_actual = date('Y'); // Devuelve el año en formato completo, por ejemplo: 2025
                                            echo $año_actual;
                                            ?></a>
                                            {{-- <a class="dropdown-item" href="javascript:void(0);">2022</a>
                                            <a class="dropdown-item" href="javascript:void(0);">2020</a> --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- <div id="growthChart"></div>
                            <div class="text-center fw-semibold pt-3 mb-2"> Crecimiento sobre el mes anterior</div> --}}

                            <div class="d-flex px-xxl-4 px-lg-2 p-4 gap-xxl-3 gap-lg-1 gap-3 justify-content-center flex-wrap">
                                <div class="d-flex flex-column text-center">
                                    <div class="me-2 text-center">
                                        <span class="badge bg-label-primary p-2"><i
                                                class="bx bx-dollar text-primary"></i></span>
                                    </div>
                                    <div class="d-flex flex-column text-center">
                                        <small>Importe del mes anterior</small>
                                        <h6 class="mb-0">S/.{{ number_format($importeTotalSum_mes_aterior, 0, '.', ',') }}</h6>
                                    </div>
                                </div>
                                {{-- <div class="d-flex flex-column">
                                    <div class="me-2">
                                        <span class="badge bg-label-info p-2"><i class="bx bx-wallet text-info"></i></span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <small>Unidades
                                        </small>
                                        <h6 class="mb-0">{{ number_format($unidades_mes_anterior, 0, '.', ',') }}</h6>
                                    </div>
                                </div> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ Total Revenue -->
            <div class="col-12 col-md-8 col-lg-2 order-3 order-md-2">
                <div class="row">
                    {{-- <div class="col-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <img src="../assets/img/icons/unicons/paypal.png" alt="Credit Card"
                                            class="rounded" />
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn p-0" type="button" id="cardOpt4" data-bs-toggle="dropdown"
                                            aria-haspopup="true" aria-expanded="false">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt4">
                                            <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                        </div>
                                    </div>
                                </div>
                                <span class="d-block mb-1">Costos</span>
                                <h3 class="card-title mb-2">S/{{ number_format($costoVenta, 0, '.', ',') }}
                                </h3>
                                <small class="text-danger fw-semibold"><i class="bx bx-down-arrow-alt"></i> -%</small>
                            </div>
                        </div>
                    </div> --}}
                    <div class="col-12 col-md-12 col-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <img src="../assets/img/icons/unicons/cc-primary.png" alt="Credit Card"
                                            class="rounded" />
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn p-0" type="button" id="cardOpt1" data-bs-toggle="dropdown"
                                            aria-haspopup="true" aria-expanded="false">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        {{-- <div class="dropdown-menu" aria-labelledby="cardOpt1">
                                            <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                        </div> --}}
                                    </div>
                                </div>
                                <span class="fw-semibold d-block mb-1">Tickets</span>
                                <h3 class="card-title mb-2">{{ number_format($ticket, 0, '.', ',') }}</h3>
                                <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12 col-md-12 col-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0">
                                        <img src="../assets/img/icons/unicons/wallet-info.png" alt="Credit Card"
                                            class="rounded" />
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn p-0" type="button" id="cardOpt6" data-bs-toggle="dropdown"
                                            aria-haspopup="true" aria-expanded="false">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        {{-- <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt6">
                                        <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                        <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                    </div> --}}
                                    </div>
                                </div>
                                <span>Unidades</span>
                                <h3 class="card-title text-nowrap mb-1">{{ number_format($unidades, 0, '.', ',') }}</h3>
                                <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +%</small>
                            </div>
                        </div>
                    </div>
                    <!-- </div>
                        <div class="row"> -->
                    {{-- <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between flex-sm-row flex-column gap-3">
                                <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                                    <div class="card-title">
                                        <h5 class="text-nowrap mb-2">Profile Report</h5>
                                        <span class="badge bg-label-warning rounded-pill">Year 2021</span>
                                    </div>
                                    <div class="mt-sm-auto">
                                        <small class="text-success text-nowrap fw-semibold"><i class="bx bx-chevron-up"></i> 68.2%</small>
                                        <h3 class="mb-0">$84,686k</h3>
                                    </div>
                                </div>
                                <div id="profileReportChart"></div>
                            </div>
                        </div>
                    </div>
                </div> --}}
                </div>
            </div>
        </div>

    </div>
    <!-- / Content -->

    <script>
       const importess = {!! $importes_actual !!};
       const importess_anterior = {!! $importes_anterior !!}
       //console.log(importess);
    </script>

@endsection
