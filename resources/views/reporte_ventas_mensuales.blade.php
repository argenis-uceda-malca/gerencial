@extends('layouts.base')
@section('title', 'Smart Brands')
@section('contenido')


    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Reporte /</span> Ventas</h4>


        <!-- Filtro de busquedas -->
        <div class="col-xl-12">
            <div class="card mb-4">
                <!-- Checkboxes and Radios -->
                <form action="{{ route('form.submit') }}" id="myform" method="POST" >
                    @csrf
                    <div class="card-body">
                        <div class="row gy-3">

                            <div class="col-md">
                                <label for="exampleFormControlSelect1" class="form-label">Buscar por:</label>
                                <div class="form-check mt-3">
                                    <input name="select_radio" class="form-check-input" type="radio" value=""
                                        id="defaultRadio1" />
                                    <label class="form-check-label" for="defaultRadio1"> Por Marcas </label>
                                </div>
                                <div class="form-check">
                                    <input name="select_radio" class="form-check-input" type="radio" value=""
                                        id="defaultRadio2" checked />
                                    <label class="form-check-label" for="defaultRadio2"> Por Tiendas </label>
                                </div>
                            </div>

                            <div class="col-md">
                                <label for="exampleFormControlSelect1" class="form-label">Seleccione las Marcas</label>
                                <select class="form-select" name="marca" id="multiselect"
                                    aria-label="Default select example">
                                    {{-- <option selected>Open this select menu</option> --}}
                                    <option value="1">One</option>
                                    <option value="2">Two</option>
                                    <option value="3">Three</option>
                                </select>
                            </div>

                            {{-- <div class="col-md">
                                <label for="exampleFormControlSelect1" class="form-label">Example select</label>
                                <select class="form-select" id="exampleFormControlSelect1" aria-label="Default select example">
                                    <option selected>Open this select menu</option>
                                    <option value="1">One</option>
                                    <option value="2">Two</option>
                                    <option value="3">Three</option>
                                </select>
                            </div> --}}

                            <div class="col-md">
                                {{-- <label for="exampleFormControlSelect1" class="form-label">Desde: </label> --}}
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Fecha inicio</label>
                                    <input type="date" name="fecha_inicio" class="form-control">
                                </div>
                            </div>
                            <div class="col-md">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Fecha fin</label>
                                    <input type="date" name="fecha_fin" class="form-control" value="">
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

        <!-- Bootstrap Table with Header - Light -->
        <div class="card">
            <h5 class="card-header">Tabla de prueba estática</h5>
            <div class="table-responsive text-nowrap" style="padding: 10px;"">
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
        </div>
        <!-- Bootstrap Table with Header - Light -->

        <!-- Grafico de dinamico -->
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
                                        <small class="text-muted d-block">Total Balance</small>
                                        <div class="d-flex align-items-center">
                                            <h6 class="mb-0 me-1">$459.10</h6>
                                            <small class="text-success fw-semibold">
                                                <i class="bx bx-chevron-up"></i>
                                                42.9%
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div id="incomeChart"></div>
                                <div class="d-flex justify-content-center pt-4 gap-2">
                                    <div class="flex-shrink-0">
                                        <div id="expensesOfWeek"></div>
                                    </div>
                                    <div>
                                        <p class="mb-n1 mt-1">Expenses This Week</p>
                                        <small class="text-muted">$39 less than last week</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
        <div class="card">
            <h5 class="card-header">Tabla de ventas</h5>
            <div class="table-responsive text-nowrap" style="padding: 10px;">
                <table class="table row-border order-column" id="mytable2">
                    <thead class="table-light">
                        <tr>
                            <th style="background: white;z-index: 999;">Sucursal Marca</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                            <th>Impuesto</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @foreach ($datos as $item)
                            <tr>
                                <td class="text-left" style="background: white;z-index: 999;"><i
                                        class="fab fa-angular fa-lg text-danger me-3"></i>
                                    <strong>{{ $item->sucursal_marca }}</strong>
                                </td>
                                <td>{{  (int)$item->cantidad }}</td>
                                <td>{{ $item->importe_subtotal }}</td>
                                <td>{{ $item->importe_impuesto }}</td>
                                <td>{{ $item->importe_total }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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
                    }
                });

                var table = $('#mytable2').DataTable({
                    scrollY: "300px",

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
                    }
                });

                // $('#myform').submit(function(event) {
                //     //event.prevetDefault();
                //     event.preventDefault();
                //     alert("asd");
                //     console.log($(this).serialize());
                //     $.ajax({
                //         url: $(this).attr('action'), // Form action URL
                //         type: $(this).attr('method'), // Form method (POST in this case)
                //         data: $(this).serialize(), // Serialize form data
                //         success: function(response) {
                //             alert("regreso del controlador");
                //         },
                //         error: function(xhr, status, error) {
                //             // Handle any errors that occur during the request
                //             alert("error ");
                //         }
                //     });
                // });
            });
        </script>

        <script></script>

        {{-- <script type="text/javascript">
            $(document).ready(function() {
                
            });
        </script> --}}
    @endsection
