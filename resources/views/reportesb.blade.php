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

        <!-- Bootstrap Table with Header - Light -->
        {{-- <div class="card">
            <h5 class="card-header">Tabla de prueba estática</h5>
            <div class="table-responsive text-nowrap" style="padding: 10px;">
                <table class="table row-border order-column" id="mytable">
                    <thead class="table-light">
                        <tr>
                            <th style="background: white;z-index: 999;">Marca</th>
                            <th>Importe S/.</th>
                            <th>Meta</th>
                            <th>Logro</th>
                            <th>Crec. vs AA (%)</th>
                            <th>Contribución (S/.)</th>
                            <th>Meta Contribución (S/.)</th>
                            <th>% logro</th>
                            <th>Crec. vs AA (%)</th>
                            <th>Status</th>
                            <th>Client</th>
                            <th>Users</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <tr>
                            <td class="text-left" style="background: white;z-index: 999;"><i
                                    class="fab fa-angular fa-lg text-danger me-3"></i>
                                <strong>Angular Project</strong>
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Albert Cook
                            </td>
                            <td>
                                <ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Lilian Fuller">
                                        <img src="../assets/img/avatars/5.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Sophia Wilkerson">
                                        <img src="../assets/img/avatars/6.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Christina Parker">
                                        <img src="../assets/img/avatars/7.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                </ul>
                            </td>
                            <td><span class="badge bg-label-primary me-1">Active</span></td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-edit-alt me-1"></i>
                                            Edit</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-trash me-1"></i>
                                            Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-left" style="background: white;z-index: 999;"><i
                                    class="fab fa-angular fa-lg text-danger me-3"></i>
                                <strong>Angular Project</strong>
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Albert Cook
                            </td>
                            <td>
                                <ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Lilian Fuller">
                                        <img src="../assets/img/avatars/5.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Sophia Wilkerson">
                                        <img src="../assets/img/avatars/6.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Christina Parker">
                                        <img src="../assets/img/avatars/7.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                </ul>
                            </td>
                            <td><span class="badge bg-label-primary me-1">Active</span></td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-edit-alt me-1"></i>
                                            Edit</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-trash me-1"></i>
                                            Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-left" style="background: white;z-index: 999;"><i
                                    class="fab fa-angular fa-lg text-danger me-3"></i>
                                <strong>Angular Project</strong>
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Albert Cook
                            </td>
                            <td>
                                <ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Lilian Fuller">
                                        <img src="../assets/img/avatars/5.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Sophia Wilkerson">
                                        <img src="../assets/img/avatars/6.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Christina Parker">
                                        <img src="../assets/img/avatars/7.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                </ul>
                            </td>
                            <td><span class="badge bg-label-primary me-1">Active</span></td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-edit-alt me-1"></i>
                                            Edit</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-trash me-1"></i>
                                            Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-left" style="background: white;z-index: 999;"><i
                                    class="fab fa-angular fa-lg text-danger me-3"></i>
                                <strong>Angular Project</strong>
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Albert Cook
                            </td>
                            <td>
                                <ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Lilian Fuller">
                                        <img src="../assets/img/avatars/5.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Sophia Wilkerson">
                                        <img src="../assets/img/avatars/6.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Christina Parker">
                                        <img src="../assets/img/avatars/7.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                </ul>
                            </td>
                            <td><span class="badge bg-label-primary me-1">Active</span></td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-edit-alt me-1"></i>
                                            Edit</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-trash me-1"></i>
                                            Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td class="text-left" style="background: white;z-index: 999;"><i
                                    class="fab fa-angular fa-lg text-danger me-3"></i>
                                <strong>Angular Project</strong>
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Albert Cook
                            </td>
                            <td>
                                <ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Lilian Fuller">
                                        <img src="../assets/img/avatars/5.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Sophia Wilkerson">
                                        <img src="../assets/img/avatars/6.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Christina Parker">
                                        <img src="../assets/img/avatars/7.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                </ul>
                            </td>
                            <td><span class="badge bg-label-primary me-1">Active</span></td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-edit-alt me-1"></i>
                                            Edit</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-trash me-1"></i>
                                            Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-left" style="background: white;z-index: 999;"><i
                                    class="fab fa-angular fa-lg text-danger me-3"></i>
                                <strong>Angular Project</strong>
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Albert Cook
                            </td>
                            <td>
                                <ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Lilian Fuller">
                                        <img src="../assets/img/avatars/5.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Sophia Wilkerson">
                                        <img src="../assets/img/avatars/6.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Christina Parker">
                                        <img src="../assets/img/avatars/7.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                </ul>
                            </td>
                            <td><span class="badge bg-label-primary me-1">Active</span></td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-edit-alt me-1"></i>
                                            Edit</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-trash me-1"></i>
                                            Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-left" style="background: white;z-index: 999;"><i
                                    class="fab fa-angular fa-lg text-danger me-3"></i>
                                <strong>Angular Project</strong>
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Albert Cook
                            </td>
                            <td>
                                <ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Lilian Fuller">
                                        <img src="../assets/img/avatars/5.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Sophia Wilkerson">
                                        <img src="../assets/img/avatars/6.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Christina Parker">
                                        <img src="../assets/img/avatars/7.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                </ul>
                            </td>
                            <td><span class="badge bg-label-primary me-1">Active</span></td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-edit-alt me-1"></i>
                                            Edit</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-trash me-1"></i>
                                            Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-left" style="background: white;z-index: 999;"><i
                                    class="fab fa-angular fa-lg text-danger me-3"></i>
                                <strong>Angular Project</strong>
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Albert Cook
                            </td>
                            <td>
                                <ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Lilian Fuller">
                                        <img src="../assets/img/avatars/5.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Sophia Wilkerson">
                                        <img src="../assets/img/avatars/6.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Christina Parker">
                                        <img src="../assets/img/avatars/7.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                </ul>
                            </td>
                            <td><span class="badge bg-label-primary me-1">Active</span></td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-edit-alt me-1"></i>
                                            Edit</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-trash me-1"></i>
                                            Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-left" style="background: white;z-index: 999;"><i
                                    class="fab fa-angular fa-lg text-danger me-3"></i>
                                <strong>Angular Project</strong>
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Albert Cook
                            </td>
                            <td>
                                <ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Lilian Fuller">
                                        <img src="../assets/img/avatars/5.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Sophia Wilkerson">
                                        <img src="../assets/img/avatars/6.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Christina Parker">
                                        <img src="../assets/img/avatars/7.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                </ul>
                            </td>
                            <td><span class="badge bg-label-primary me-1">Active</span></td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-edit-alt me-1"></i>
                                            Edit</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-trash me-1"></i>
                                            Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-left" style="background: white;z-index: 999;"><i
                                    class="fab fa-angular fa-lg text-danger me-3"></i>
                                <strong>Angular Project</strong>
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Albert Cook
                            </td>
                            <td>
                                <ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Lilian Fuller">
                                        <img src="../assets/img/avatars/5.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Sophia Wilkerson">
                                        <img src="../assets/img/avatars/6.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Christina Parker">
                                        <img src="../assets/img/avatars/7.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                </ul>
                            </td>
                            <td><span class="badge bg-label-primary me-1">Active</span></td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-edit-alt me-1"></i>
                                            Edit</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-trash me-1"></i>
                                            Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-left" style="background: white;z-index: 999;"><i
                                    class="fab fa-angular fa-lg text-danger me-3"></i>
                                <strong>Angular Project</strong>
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Angular Project
                            </td>
                            <td>
                                Albert Cook
                            </td>
                            <td>
                                <ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Lilian Fuller">
                                        <img src="../assets/img/avatars/5.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Sophia Wilkerson">
                                        <img src="../assets/img/avatars/6.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-xs pull-up" title="Christina Parker">
                                        <img src="../assets/img/avatars/7.png" alt="Avatar" class="rounded-circle" />
                                    </li>
                                </ul>
                            </td>
                            <td><span class="badge bg-label-primary me-1">Active</span></td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-edit-alt me-1"></i>
                                            Edit</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="bx bx-trash me-1"></i>
                                            Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div> --}}
        <!-- Bootstrap Table with Header - Light -->

        <br>

        <!-- Tabla Reporte de Ventas * -->
        <div class="card">
            <h5 class="card-header">{{ $titulo }} <span>{{ $fecha }}</span></h5>
            <div class="table-responsive text-nowrap" style="padding: 10px;">
                <table class="table row-border order-column table-hover table-bordered" id="reporte_venta">
                    <thead class="table-light">
                        <tr>
                            <th style="padding: 5px 80px; z-index: 9999;" rowspan="2" class="center head-table1"> Marca
                            </th>
                            <th colspan="5" class="center head-table1 text-center" style="background: #e77f4f1e">
                                Ventas
                            </th>
                            {{-- <th colspan="5" class="center head-table1"> Margen Contribución</th> --}}
                            <th style="padding: 5px 20px; background: #8b898924; " rowspan="2"
                                class="center head-table1"> Unidades</th>
                            <th style="padding: 5px 20px; background: #8b898924; " rowspan="2"
                                class="center head-table1"> Precio Prom (S/.)
                            </th>
                            <th style="padding: 5px 20px; background: #8b898924; " rowspan="2"
                                class="center head-table1"> Tickets</th>
                            <th style="padding: 5px 20px; background: #8b898924; " rowspan="2"
                                class="center head-table1"> Tickets <br>Prom
                            </th>
                            {{-- <th style="padding: 5px 30px" rowspan="2" class="center head-table1"> Und/Ticket</th> --}}
                            <th colspan="3" class="center head-table1 text-center" style="background: #3ca2bb1e">
                                Trafico
                                de Clientes</th>
                            <th rowspan="2" class="center head-table1 no-export"> </th>
                        </tr>
                        <tr>
                            <th style="padding: 5px 10px" class="center head-table1"> Importe (S/.)</th>
                            <th style="padding: 5px 10px" class="center head-table1"> Meta (S/.)</th>
                            <th style="padding: 5px 10px" class="center head-table1"> % Logro</th>
                            <th style="padding: 5px 10px" class="center head-table1"> Crec. vs AA (%)</th>
                            <th style="padding: 5px 10px" class="center head-table1"> GM Real (%)</th>
                            {{-- <th style="padding: 5px 30px" class="center head-table1"> Contribución (S/.)</th>
                            <th style="padding: 5px 30px" class="center head-table1"> Meta Contribución (S/.)</th>
                            <th style="padding: 5px 30px" class="center head-table1"> % Logro</th>
                            <th style="padding: 5px 30px" class="center head-table1"> Crec. vs AA (%)</th> --}}
                            <th style="padding: 5px 10px" class="center head-table1"> Potencia</th>
                            <th style="padding: 5px 10px" class="center head-table1"> Visitantes</th>
                            <th style="padding: 5px 10px" class="center head-table1"> Conversión (%)</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0" id="tabla_ventas_body">

                        @foreach ($reporte as $item)
                            <tr role="row" class="odd" style="height: 51px;">
                                <td class=" text-left" style="background: rgb(240, 240, 240);z-index: 999; ">
                                    {{ $item->nombre }}</td>
                                <td class=" text-right ">{{ $item->importe_total_sum }}</td>
                                <td class=" text-right cuota">{{ $item->meta }}</td>
                                <td class=" text-right totales">{{ number_format($item->logro, 2, '.', ',') }}</td>
                                <?php $valor = false;
                                $valor2 = false;
                                $conversion_visitantes = 1; ?>
                                @foreach ($anio_anterior as $value)
                                    @if ($value->id == $item->id)
                                        <?php
                                        
                                        $resultado = (floatval($item->importe_total_sum) / floatval($value->anterior) - 1) * 100;
                                        $valor = true;
                                        ?>
                                        <td class=" text-right subtotales">{{ number_format($resultado, 2, '.', ',') }}
                                        </td>
                                    @endif
                                @endforeach

                                @if ($valor == false)
                                    <td class=" text-right subtotales">0</td>
                                @endif
                                <td class=" text-right">{{ number_format($item->gm, 2, '.', ',') }}</td>
                                {{-- <td class=" text-right tickets">{{ number_format($item->contribucion, 3, '.', ',') }}</td>
                                <td class=" text-right">{{ number_format($item->meta_contribucion, 3, '.', ',') }}</td>
                                <td class=" text-right">{{ number_format($item->logro_c, 3, '.', ',') }}</td>
                                <td class=" text-right">0.00</td> --}}
                                <td class=" text-right">{{ number_format($item->unidades, 0, '.', ',') }}</td>
                                <td class=" text-right">{{ number_format($item->precio_promedio, 2, '.', ',') }}</td>
                                <td class=" text-right">{{ $item->ticket }}</td>
                                <td class=" text-right">{{ number_format($item->ticket_promedio, 2, '.', ',') }}</td>

                                {{-- <td class=" text-right">10.00</td> --}}
                                <td class=" text-right center" style="background: #ededed;"> -----------</td>
                                @foreach ($entradas as $entrada)
                                    @if ($entrada->id == $item->id)
                                        <?php $valor2 = true;
                                        $conversion_visitantes = $entrada->total; ?>
                                        <td class=" text-right">{{ $entrada->total }} </td>
                                    @endif
                                @endforeach

                                @if ($valor2 == false)
                                    <td class=" text-right subtotales">0 </td>
                                    <td class=" text-right subtotales">0 </td>
                                @else
                                    <?php
                                    $conversion = (floatval($item->ticket) / floatval($conversion_visitantes)) * 100;
                                    ?>
                                    <td class=" text-right subtotales">{{ number_format($conversion, 1, '.', ',') }}</td>
                                @endif






                                {{-- <td class="conversion text-right">0.00</td> --}}

                                <td class=" text-center"><a class="btn btn-xs btn-danger ver-mas-btn"
                                        href="/vermas?id={{ $item->id }}&fecha_inicio={{ $item->fecha_inicio }}&fecha_fin={{ $item->fecha_fin }}&tipo={{ $option }}&filtroonline={{ $filtroonline }}&filtroincluironline={{ $filtroincluironline }}&filtrotxd={{ $filtrotxd }}">Ver
                                        Mas </a></td>
                            </tr>
                        @endforeach

                    </tbody>
                    <tfoot class="alert alert-success">
                        <tr>
                            <th class="text-center" id="tfoot_total" style="background: #e4ffd6;z-index: 999;">Total</th>
                            <th class="text-right tfoot tfoot1"></th>
                            <th class="text-right tfoot tfoot2"></th>
                            <th class="text-right tfoot tfoot3"></th>
                            <th class="text-right tfoot tfoot4"></th>
                            <th class="text-right tfoot tfoot5"></th>
                            <th class="text-right tfoot tfoot6"></th>
                            <th class="text-right tfoot tfoot7"></th>
                            <th class="text-right tfoot tfoot8"></th>
                            <th class="text-right tfoot tfoot9"></th>
                            <th class="text-right tfoot tfoot10" style="color: transparent"></th>
                            <th class="text-right tfoot tfoot11"></th>
                            <th class="text-right tfoot tfoot12"></th>
                            <th class="text-right tfoot tfoot13" style="color: transparent"></th>
                            {{-- <th class="text-right tfoot tfoot14"></th>
                            <th class="text-right tfoot tfoot15"></th>
                            <th class="text-right tfoot tfoot16"></th>
                            <th class="text-right tfoot tfoot17"></th>
                            <th class="text-right tfoot tfoot18"></th> --}}
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <!-- Bootstrap Table with Header - Light -->



        <!-- Gráfica dinámica -->
        <div class="d-flex justify-content-center" style="margin-top: 15px">
            <div class="col-md-10 col-lg-8 order-1 mb-4 ">
                <div class="card h-100">
                    <div class="card-body px-0">
                        <div class="tab-content p-0">
                            <div class="tab-pane fade show active" id="navs-tabs-line-card-income" role="tabpanel">
                                <div class="d-flex p-4 pt-3">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <img src="../assets/img/icons/unicons/wallet.png" alt="User" />
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Balance Total</small>
                                        <div class="d-flex align-items-center">
                                            <h6 class="mb-0 me-1"></h6>
                                            <small class="text-success fw-semibold">
                                                <i class="bx bx-chevron-up"></i>
                                                S/.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div id="incomeChart"></div>
                                <div class="d-flex justify-content-center pt-4 gap-2">
                                    <div class="flex-shrink-0">
                                        {{-- <div id="expensesOfWeek"></div> --}}
                                    </div>
                                    {{-- <div>
                                        <p class="mb-n1 mt-1">Expenses This Week</p>
                                        <small class="text-muted">S/.39 less than last week</small>
                                    </div> --}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Scrip que almacena los importes actuales, para esta vista se setea en 0 --}}
    <script>
        const importess = [0, 1, 2];
        const importess_anterior = [0, 1, 2];
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
                responsive: true,
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
                order: [],
                fixedColumns: {
                    left: 1,
                },
                dom: 'Bfrtip',

                columnDefs: [{
                    targets: [1, 2, 10],
                    render: $.fn.dataTable.render.number(',', '.', 2, '')
                }],

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
                    url: '//cdn.datatables.net/plug-ins/1.13.5/i18n/es-ES.json',
                },
            });


            var table2 = $('#mytable2').DataTable({
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
            //btnvermas();
            //bntvermas_option();
            obtener_marcas_seleccionadas();
            //suma();
            suma_columnas2();
            $('#reporte_venta').DataTable().draw();

            // Llamar a la función suma_columnas2() al cargar la página //cambio
            // if ($('#filtroOnline').prop('checked')) {

            //     $('#reporte_venta').DataTable().draw();
            //     suma_columnas2();
            // }

            // $('#filtroOnline').on('change', function() {
            //     $('#reporte_venta').DataTable().draw();
            //     suma_columnas2();

            // });
            //end cambio

            //bloquearDesbloquearCheckbox(); //recien_comentado

            // Restaurar selecciones al cargar la página
            restoreSelectedOptions();

            cambiar_check_url(); //recien_comentado

            Obtener_ultimo_canal();

            bloqueartxd();

            activar_spiner();
        });

        function activar_spiner() {
            document.getElementById('spiner_btn').addEventListener('click', function() {
                var spinner = document.getElementById('spiner_div');
                spinner.classList.add('spinner-border');
                spinner.classList.add('spinner-border-sm');
                spinner.classList.add('text-light');
            })
        }

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

        function Obtener_ultimo_canal() {
            const filtroOnline = document.getElementById('filtroOnline');
            const filtroIncluirOnline = document.getElementById('filtroIncluirOnline');
            const filtroIncluirTxD = document.getElementById('filtroIncluirTxD');

            const valorSeleccionado1 = localStorage.getItem('valorSeleccionado1');
            const valorSeleccionado2 = localStorage.getItem('valorSeleccionado2');
            const valorSeleccionado3 = localStorage.getItem('valorSeleccionado3');
            //console.log(valorSeleccionado1);
            // Verificar si hay un valor seleccionado almacenado en el localStorage
            if (valorSeleccionado1 != null) {
                console.log("dentro del valor");
                // Convertir el valor almacenado en el localStorage a boolean
                var booleanValue1 = valorSeleccionado1 === 'true';
                // Establecer el valor del checkbox basado en el valor booleano
                filtroOnline.checked = booleanValue1;
            }

            if (valorSeleccionado2 != false) {
                // Convertir el valor almacenado en el localStorage a boolean
                var booleanValue2 = valorSeleccionado2 === 'true';
                // Establecer el valor del checkbox basado en el valor booleano
                filtroIncluirOnline.checked = booleanValue2;

            }

            if (valorSeleccionado3 != false) {
                // Convertir el valor almacenado en el localStorage a boolean
                var booleanValue3 = valorSeleccionado3 === 'true';
                // Establecer el valor del checkbox basado en el valor booleano
                filtroIncluirTxD.checked = booleanValue3;
            }



            // Escuchar el evento de cambio de los radio buttons
            filtroOnline.addEventListener('change', function() {
                // Obtener el valor del radio button seleccionado
                const valorSeleccionado1 = this.checked;
                // Almacenar el valor seleccionado en el localStorage
                localStorage.setItem('valorSeleccionado1', valorSeleccionado1);
            });

            // Escuchar el evento de cambio de los radio buttons
            filtroIncluirOnline.addEventListener('change', function() {
                // Obtener el valor del radio button 
                console.log("selecionado");
                const valorSeleccionado2 = this.checked;
                // Almacenar el valor seleccionado en el localStorage
                localStorage.setItem('valorSeleccionado2', valorSeleccionado2);
            });

            // Escuchar el evento de cambio de los radio buttons
            filtroIncluirTxD.addEventListener('change', function() {
                // Obtener el valor del radio button seleccionado
                const valorSeleccionado3 = this.checked;
                // Almacenar el valor seleccionado en el localStorage
                localStorage.setItem('valorSeleccionado3', valorSeleccionado3);
            });

        }

        function bloqueartxd() {
            const filtroIncluirTxD = document.getElementById('filtroIncluirTxD');

            if (filtroIncluirTxD.isChecked) {
                console.log("txd");
                defaultRadio2.disabled = true;
            }

            filtroIncluirTxD.addEventListener('change', function() {
                if (this.checked) {
                    defaultRadio2.disabled = true;
                } else {
                    defaultRadio2.disabled = false;
                }
            });

        }

        function obtener_marcas_seleccionadas() {

            // Obtener el elemento del multiselect
            const multiselect = document.getElementById('exampleFormControlSelect2');

            // Obtener las opciones seleccionadas del localStorage
            const opcionesSeleccionadas = JSON.parse(localStorage.getItem('opcionesSeleccionadas'));


            // Verificar si hay opciones seleccionadas almacenadas en el localStorage
            if (opcionesSeleccionadas) {
                // Recorrer las opciones del multiselect
                Array.from(multiselect.options).forEach(function(option) {
                    // Establecer la propiedad selected de cada opción según corresponda
                    option.selected = opcionesSeleccionadas.includes(option.value);
                });
            }

            // Escuchar el evento de cambio del multiselect
            multiselect.addEventListener('change', function() {

                // Obtener las nuevas opciones seleccionadas
                const nuevasOpcionesSeleccionadas = Array.from(this.options)
                    .filter(function(option) {
                        return option.selected;
                    })
                    .map(function(option) {
                        return option.value;
                    });

                // Almacenar las nuevas opciones seleccionadas en el localStorage
                localStorage.setItem('opcionesSeleccionadas', JSON.stringify(nuevasOpcionesSeleccionadas));
            });

        }


        function btnvermas() {
            var verMasBtns = document.getElementsByClassName('ver-mas-btn');

            for (var i = 0; i < verMasBtns.length; i++) {
                verMasBtns[i].addEventListener('click', function(e) {
                    e.preventDefault();
                    var fechaInicio = document.getElementById('fecha_inicio').value;
                    var fechaFin = document.getElementById('fecha_fin').value;
                    var href = this.getAttribute('href');
                    href += '&fecha_inicio=' + fechaInicio + '&fecha_fin=' + fechaFin;
                    window.location.href = href;
                });
            }
        }

        function bntvermas_option() {
            var verMasBtns = document.getElementsByClassName('ver-mas-btn');
            var seleccionarTienda = document.getElementById('defaultRadio2');
            var seleccionarMarca = document.getElementById('defaultRadio1');
            for (var i = 0; i < verMasBtns.length; i++) {
                verMasBtns[i].addEventListener('click', function(e) {
                    e.preventDefault();
                    var href = this.getAttribute('href');

                    if (seleccionarTienda.checked) {
                        href += '&tipo=tienda';
                    } else if (seleccionarMarca.checked) {
                        href += '&tipo=marca';
                    }

                    window.location.href = href;
                });
            }
        }

        function suma() {

            // // Obtener el cuerpo de la tabla
            // var tablaBody = document.getElementById('tabla_ventas_body');

            // // Obtener todas las filas del cuerpo de la tabla
            // var filas = tablaBody.getElementsByTagName('tr');

            // // Variables para almacenar las sumas de cada columna
            // var sumasColumnas = Array.from({
            //     length: filas[0].children.length
            // }, () => 0);

            // // Recorrer cada fila y sumar los valores de cada columna
            // for (var i = 0; i < filas.length; i++) {
            //     var celdas = filas[i].getElementsByTagName('td');

            //     // Sumar los valores de cada columna
            //     for (var j = 1; j < celdas.length; j++) { // Empezar desde 1 para omitir la primera celda de la fila
            //         sumasColumnas[j] += parseFloat(celdas[j].textContent.replace(',', ''));
            //     }
            // }

            // // Asignar las sumas a las celdas correspondientes en el elemento <tfoot>
            // var tfoot = document.querySelector('tfoot');
            // var celdasTfoot = tfoot.getElementsByTagName('th');

            // // Asignar las sumas a las celdas correspondientes en el elemento <tfoot>
            // for (var k = 1; k < celdasTfoot.length; k++) { // Empezar desde 1 para omitir la primera celda de la fila
            //     celdasTfoot[k].textContent = sumasColumnas[k].toFixed(3);
            // }

            // Obtener el cuerpo de la tabla
            var tablaBody = document.getElementById('tabla_ventas_body');

            // Obtener todas las filas del cuerpo de la tabla
            var filas = tablaBody.getElementsByTagName('tr');

            // Variables para almacenar las sumas de cada columna
            var sumasColumnas = Array.from({
                length: filas[0].children.length
            }, () => 0);

            // Recorrer cada fila y sumar los valores de cada columna
            for (var i = 0; i < filas.length; i++) {
                var celdas = filas[i].getElementsByTagName('td');

                // Sumar los valores de cada columna
                for (var j = 1; j < celdas.length; j++) { // Empezar desde 1 para omitir la primera celda de la fila
                    sumasColumnas[j] += parseFloat(celdas[j].textContent.replace(',', ''));
                }
            }

            // Obtener el elemento <tfoot>
            var tfoot = document.querySelector('tfoot');
            var tr = tfoot.querySelector('tr');
            var celdasTfoot = tr.getElementsByTagName('th');

            // Asignar las sumas a las celdas correspondientes en el elemento <tfoot>
            for (var k = 1; k < celdasTfoot.length; k++) { // Empezar desde 1 para omitir la primera celda de la fila
                //celdasTfoot[k].innerHTML = sumasColumnas[k].toFixed(3);
            }

            var celdas = document.querySelectorAll('.tfoot');

            // Insertar los valores en las celdas
            for (var i = 1; i < celdasTfoot.length; i++) {
                celdas[i].textContent = 112;
            }

        }

        function suma_importe() {
            // Obtener todas las filas de datos en el cuerpo de la tabla
            var filasDatos = $('#tabla_ventas_body').find('tr');

            // Calcular la suma de los importes
            var sumaImportes = 0;
            var meta = 0;

            filasDatos.each(function() {
                var importe = $(this).find('.text-right').eq(0).text().replace(',', '');
                sumaImportes += parseFloat(importe);


            });

            // Insertar la suma en la celda correspondiente en el tfoot
            $('#tfoot_total').next('.tfoot').text(sumaImportes.toFixed(3));
            //$('#tfoot_total').next('.tfoot').text(sumaImportes.toFixed(3));

        }

        function suma_columnas() {
            // Obtener todas las filas de datos en el cuerpo de la tabla
            var filasDatos = $('#tabla_ventas_body').find('tr');

            // Calcular la suma para cada columna
            var sumas = Array.from({
                length: 12
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
                $(this).text(sumas[index].toLocaleString());
            });

        }

        function suma_columnas2() {

            // Obtener la instancia de la tabla de DataTables
            var table = $('#reporte_venta').DataTable();

            // Obtener todas las filas de datos en la tabla, incluidas las páginas ocultas
            var filasDatos = table.rows({
                'search': 'applied'
            }).nodes();

            // Calcular la suma para cada columna
            var sumas = Array.from({
                length: 13
            }, () => 0); // Array para almacenar las sumas de cada columna

            $(filasDatos).each(function() {
                var celdas = $(this).find(
                    '.text-right'); // Obtener todas las celdas con la clase "text-right" en la fila
                celdas.each(function(index) {
                    var valor = removeCommas($(this).text());
                    sumas[index] += parseFloat(valor);
                });
            });

            // Insertar las sumas en las celdas correspondientes en el tfoot
            $('#tfoot_total').nextAll('.tfoot').each(function(index) {
                $(this).text(sumas[index].toLocaleString());
            });


        }

        function removeCommas(str) {
            return str.replace(/,/g, '');
        }


        /* Sección para activar o desactivar input del online */

        // Obtener los elementos del radio y el checkbox
        const defaultRadio1 = document.querySelector('#defaultRadio1');
        const defaultRadio2 = document.querySelector('#defaultRadio2');
        const filtroOnline = document.querySelector('#filtroOnline');

        // Función para bloquear o desbloquear el checkbox según el estado del radio
        function bloquearDesbloquearCheckbox() {
            filtroOnline.disabled = defaultRadio1.checked;
        }

        // Escuchar el evento de cambio del radio
        //defaultRadio1.addEventListener('change', bloquearDesbloquearCheckbox); //cambio
        //defaultRadio2.addEventListener('change', bloquearDesbloquearCheckbox); //cambio

        function cambiar_check_url() {
            // Obtener los parámetros de la URL
            const urlParams = new URLSearchParams(window.location.search);

            // Verificar si el valor del parámetro "tipo" es igual a "marca"
            if (urlParams.get('tipo') === 'marca') {
                // Obtener el elemento de radio por su ID
                const radioElement = document.getElementById('defaultRadio2');

                // Marcar el elemento de radio como seleccionado
                radioElement.checked = true;
                //bloquearDesbloquearCheckbox() //cambio
            }
        }
    </script>

    <script type="text/javascript">
        $('#exampleFormControlSelect2').multiselect({
            includeSelectAllOption: true,
            allSelectedText: 'Todos seleccionados'
        });

        $('#exampleFormControlSelect2_sucursales').multiselect({
            includeSelectAllOption: true,
            allSelectedText: 'Todos seleccionados'
        });
    </script>

    {{-- <script>
            $.fn.dataTable.ext.type.order['format-number-pre'] = function(value) {
                return value.replace(',', ''); // Solo se eliminan las comas durante la ordenación
            };
        </script> --}}

    {{-- Escript para ocultar los q tengan online --}}
    <script>
        // $.fn.dataTable.ext.search.push(function(settings, searchData, dataIndex) {
        //     var isChecked = $('#filtroOnline').is(':checked'); // Obtener el estado del checkbox

        //     var tienda = searchData[0].toLowerCase(); // Índice de columna "tienda" (empezando desde 0)

        //     if (!isChecked) {
        //         return true; // No hay filtro, mostrar todas las filas
        //         // } else if (isChecked && tienda.includes('online')) {
        //         //     return true; // Filtrar nombres que contienen la palabra "ONLINE"
        //     } else if (isChecked && !tienda.includes('online')) {
        //         return true; // Filtrar nombres que no contienen la palabra "ONLINE"
        //     }

        //     return false; // No coincide con ninguno de los filtros anteriores
        // });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Get the element select
            const miSelect = document.getElementById('miSelect');

            // Check if the element was found before adding the event listener
            if (miSelect) {
                miSelect.addEventListener('change', () => {
                    // Obtener las opciones seleccionadas y convertirlas en un arreglo
                    const selectedOptions = Array.from(miSelect.selectedOptions).map(option => option
                        .value);

                    // Guardar las opciones seleccionadas en el localStorage como una cadena JSON
                    localStorage.setItem('selectedOptions', JSON.stringify(selectedOptions));
                });
            }
        });


        // Función para restaurar las selecciones guardadas en el localStorage
        function restoreSelectedOptions() {
            const storedOptions = localStorage.getItem('selectedOptions');
            if (storedOptions) {
                const selectedOptions = JSON.parse(storedOptions);

                // Establecer las selecciones guardadas en el select
                selectedOptions.forEach(optionValue => {
                    const option = miSelect.querySelector(`option[value="${optionValue}"]`);
                    if (option) {
                        option.selected = true;
                    }
                });
            }
        }
    </script>



@endsection
