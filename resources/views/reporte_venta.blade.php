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
                                        <option value="{{ $item->id }}">{{ $item->marca }}</option>
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
            <h5 class="card-header">Reporte de Ventas </h5>
            <div class="table-responsive text-nowrap" style="padding: 10px;">
                <table class="table row-border order-column" id="reporte_venta">
                    <thead class="table-light">
                        <tr>
                            <th style="padding: 5px 80px; z-index: 9999;" rowspan="2" class="center head-table1"> Marca
                            </th>
                            <th colspan="4" class="center head-table1"> Ventas</th>
                            <th colspan="5" class="center head-table1"> Margen Contribución</th>
                            <th style="padding: 5px 20px" rowspan="2" class="center head-table1"> Unidades</th>
                            <th style="padding: 5px 30px" rowspan="2" class="center head-table1"> Precio Prom (S/.)
                            </th>
                            <th style="padding: 5px 30px" rowspan="2" class="center head-table1"> Tickets</th>
                            <th style="padding: 5px 30px" rowspan="2" class="center head-table1"> Tickets <br>Prom
                            </th>
                            <th style="padding: 5px 30px" rowspan="2" class="center head-table1"> Und/Ticket</th>
                            <th colspan="3" class="center head-table1"> Trafico de Clientes</th>
                            <th rowspan="2" class="center head-table1 no-export"> </th>
                        </tr>
                        <tr>
                            <th style="padding: 5px 40px" class="center head-table1"> Importe (S/.)</th>
                            <th style="padding: 5px 40px" class="center head-table1"> Meta (S/.)</th>
                            <th style="padding: 5px 30px" class="center head-table1"> % Logro</th>
                            <th style="padding: 5px 30px" class="center head-table1"> Crec. vs AA (%)</th>
                            <th style="padding: 5px 30px" class="center head-table1"> GM Real (%)</th>
                            <th style="padding: 5px 30px" class="center head-table1"> Contribución (S/.)</th>
                            <th style="padding: 5px 30px" class="center head-table1"> Meta Contribución (S/.)</th>
                            <th style="padding: 5px 30px" class="center head-table1"> % Logro</th>
                            <th style="padding: 5px 30px" class="center head-table1"> Crec. vs AA (%)</th>
                            <th style="padding: 5px 30px" class="center head-table1"> Potencia</th>
                            <th style="padding: 5px 30px" class="center head-table1"> Visitantes</th>
                            <th style="padding: 5px 30px" class="center head-table1"> Conversión (%)</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0" id="tabla_ventas_body">
                        @foreach ($reporte as $item)
                            <tr role="row" class="odd" style="height: 51px;">
                                <td class=" alert-warning text-left" style="background: white;z-index: 999; ">
                                    {{ $item->nombre }}</td>
                                <td class=" text-right">{{ $item->importe_total_sum }}</td>
                                <td class=" text-right cuota">29,240.53</td>
                                <td class=" text-right totales">79.74</td>
                                <td class=" text-right subtotales">-9.69</td>
                                <td class=" text-right">84.76</td>
                                <td class=" text-right tickets">19,762.82</td>
                                <td class=" text-right">15,129.92</td>
                                <td class=" text-right">130.62</td>
                                <td class=" text-right">34.89</td>
                                <td class=" text-right">468.00</td>
                                <td class=" text-right">58.79</td>
                                <td class=" text-right">198.00</td>
                                <td class=" text-right">138.95</td>
                                <td class=" text-right">2.36</td>
                                <td class=" text-right">0.00</td>
                                <td class=" text-right">0.00</td>
                                <td class=" text-right">0.00</td>
                                <td class=" text-center"><a class="btn btn-xs btn-danger"
                                        href="/reporte-por-marca/9?start_day=2023-06-13&amp;end_day=2023-06-13&amp;option=1">Ver
                                        Mas</a></td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot class="alert alert-success">
                        <tr>
                            <th class="text-center" id="tfoot_total">Total</th>
                            <th class="text-right"></th>
                            <th class="text-right"></th>
                            <th class="text-right "></th>
                            <th class="text-right "></th>
                            <th class="text-right "></th>
                            <th class="text-right "></th>
                            <th class="text-right "></th>
                            <th class="text-right "></th>
                            <th class="text-right "></th>
                            <th class="text-right "></th>
                            <th class="text-right "></th>
                            <th class="text-right "></th>
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

        <!-- Tabla de ventas-->
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
                                <td>{{ (int) $item->cantidad }}</td>
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
        </script>





    @endsection
