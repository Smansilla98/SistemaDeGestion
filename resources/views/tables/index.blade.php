@extends('layouts.app')

@section('title', 'Gestión de Mesas')

@push('styles')
<style>
    /* Estilos generales para botones de mesas */
    .table-actions {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        width: 100%;
        margin-top: 1rem;
    }
    
    .table-actions .btn {
        width: 100%;
        min-height: 48px;
        padding: 0.875rem 1.25rem;
        font-size: 0.9375rem;
        font-weight: 700;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.625rem;
        border: none !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        transition: all 0.2s ease;
    }
    
    .table-actions .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .table-actions .btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }
    
    .table-actions .btn i {
        font-size: 1.25rem;
    }
    
    /* Colores específicos para cada acción */
    .btn-reservar {
        background: linear-gradient(135deg, #17a2b8, #138496) !important;
        color: white !important;
    }
    
    .btn-ocupar {
        background: linear-gradient(135deg, #28a745, #218838) !important;
        color: white !important;
    }
    
    .btn-pedido {
        background: linear-gradient(135deg, #007bff, #0056b3) !important;
        color: white !important;
    }
    
    .btn-ver-pedidos {
        background: linear-gradient(135deg, #ffc107, #e0a800) !important;
        color: #000 !important;
        font-weight: 800 !important;
    }
    
    .btn-cerrar {
        background: linear-gradient(135deg, #dc3545, #c82333) !important;
        color: white !important;
    }
    
    .btn-editar {
        background: linear-gradient(135deg, #6c757d, #5a6268) !important;
        color: white !important;
    }
    
    .btn-cambiar-estado {
        background: linear-gradient(135deg, #6610f2, #520dc2) !important;
        color: white !important;
    }
    
    /* Mejoras para móvil */
    @media (max-width: 768px) {
        /* Header más compacto */
        .tables-header {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 1rem;
        }
        
        .tables-header h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .tables-header .text-muted {
            font-size: 0.875rem;
        }
        
        .tables-header-actions {
            width: 100%;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .tables-header-actions .btn {
            flex: 1;
            min-width: 120px;
            font-size: 0.875rem;
            padding: 0.625rem 1rem;
        }
        
        /* Tarjetas de mesas más grandes y táctiles */
        .table-card {
            margin-bottom: 1rem !important;
            border-radius: 16px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
            transition: all 0.2s ease;
        }
        
        .table-card:active {
            transform: scale(0.98);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
        }
        
        .table-card-body {
            padding: 1.25rem !important;
        }
        
        .table-card-title {
            font-size: 1.25rem !important;
            font-weight: 700 !important;
            margin-bottom: 0.75rem !important;
        }
        
        .table-status-badge {
            font-size: 0.875rem !important;
            padding: 0.5rem 0.75rem !important;
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Botones más grandes y táctiles (mínimo 48x48px) */
        .table-card .btn {
            min-height: 48px;
            padding: 0.875rem 1.25rem !important;
            font-size: 0.9375rem !important;
            font-weight: 700 !important;
            border-radius: 14px !important;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            flex: 1;
            min-width: 0;
            border: none !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            transition: all 0.2s ease;
        }
        
        .table-card .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .table-card .btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }
        
        .table-card .btn i {
            font-size: 1.25rem;
        }
        
        /* Información de mesa más clara */
        .table-info {
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: rgba(0, 0, 0, 0.02);
            border-radius: 10px;
        }
        
        .table-info p {
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .table-info p:last-child {
            margin-bottom: 0;
        }
        
        /* Sector header más compacto */
        .sector-header {
            padding: 1rem !important;
        }
        
        .sector-header h5 {
            font-size: 1.125rem !important;
        }
        
        /* Modales fullscreen en móvil */
        .modal-dialog {
            margin: 0;
            max-width: 100%;
            height: 100%;
        }
        
        .modal-content {
            height: 100%;
            border-radius: 0;
            border: none;
        }
        
        .modal-header {
            padding: 1rem 1.25rem;
            border-bottom: 2px solid var(--mosaic-border);
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }
        
        .modal-title {
            font-size: 1.125rem;
            font-weight: 700;
        }
        
        .modal-body {
            padding: 1.25rem;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .modal-footer {
            padding: 1rem 1.25rem;
            border-top: 2px solid var(--mosaic-border);
            position: sticky;
            bottom: 0;
            background: white;
            z-index: 10;
        }
        
        .modal-footer .btn {
            min-height: 48px;
            font-size: 1rem;
            font-weight: 600;
            flex: 1;
        }
        
        /* Formularios en móvil */
        .form-label {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .form-control,
        .form-select {
            min-height: 48px;
            font-size: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 12px;
        }
        
        /* Modal de nuevo pedido optimizado para móvil */
        #newOrderModal .modal-dialog {
            height: 100vh;
        }
        
        #newOrderModal .modal-body {
            padding: 1rem;
        }
        
        #newOrderModal .row {
            flex-direction: column;
        }
        
        #newOrderModal .col-lg-7,
        #newOrderModal .col-lg-5 {
            width: 100%;
            max-width: 100%;
        }
        
        #newOrderModal .product-item {
            width: 100% !important;
            margin-bottom: 0.75rem;
        }
        
        #newOrderModal .product-item .btn {
            min-height: 44px;
            min-width: 44px;
        }
        
        #productSearch {
            max-width: 100% !important;
            min-height: 44px;
            font-size: 1rem;
        }
        
        .accordion-button {
            min-height: 48px;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.75rem 1rem;
        }
        
        /* Botón de confirmar pedido fijo en móvil */
        #newOrderModal .col-lg-5 {
            position: sticky;
            bottom: 0;
            background: white;
            padding-top: 1rem;
            border-top: 2px solid var(--mosaic-border);
            margin-top: 1rem;
        }
        
        #confirmOrderBtn {
            width: 100%;
            min-height: 52px;
            font-size: 1.125rem;
            font-weight: 700;
        }
        
        /* Mejoras de scroll */
        .modal-body::-webkit-scrollbar {
            width: 6px;
        }
        
        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .modal-body::-webkit-scrollbar-thumb {
            background: #1e8081;
            border-radius: 3px;
        }
        
        /* Espaciado mejorado */
        .card-body {
            padding: 1rem !important;
        }
        
        .mb-4 {
            margin-bottom: 1.5rem !important;
        }
        
        /* Badges más grandes */
        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
        }
    }
    
    /* Mejoras generales para touch */
    @media (hover: none) and (pointer: coarse) {
        .btn:active {
            transform: scale(0.95);
        }
        
        .table-card:active {
            transform: scale(0.98);
        }
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12 tables-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-table"></i> Gestión de Mesas</h1>
        </div>
        <div class="tables-header-actions">
            @can('create', App\Models\Table::class)
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTableModal">
                <i class="bi bi-plus-circle"></i> <span class="d-none d-sm-inline">Nueva Mesa</span>
            </button>
            @endcan
            <a href="{{ route('tables.layout') }}" class="btn btn-outline-primary">
                <i class="bi bi-diagram-3"></i> <span class="d-none d-sm-inline">Layout</span>
            </a>
        </div>
    </div>
</div>

<!-- Búsqueda de mesas -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <input type="text" id="tableSearch" class="form-control" placeholder="🔍 Buscar mesa por número o nombre...">
            </div>
        </div>
    </div>
</div>

@foreach($sectors as $sector)
<div class="card mb-4">
    <div class="card-header sector-header">
        <h5 class="mb-0">
            <i class="bi bi-door-open"></i> {{ $sector->name }}
            @if($sector->description)
                <small class="text-muted d-none d-sm-inline">- {{ $sector->description }}</small>
            @endif
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            @forelse($sector->tables as $table)
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-3 table-item" 
                 data-table-number="{{ strtolower($table->number) }}"
                 data-sector-name="{{ strtolower($sector->name) }}">
                <div class="card h-100 table-card border-{{ $table->status === 'LIBRE' ? 'success' : ($table->status === 'OCUPADA' ? 'warning' : 'secondary') }}">
                    <div class="card-body table-card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title table-card-title mb-0">{{ $table->number }}</h5>
                            @can('update', $table)
                            <button type="button" 
                                    class="badge bg-{{ $table->status === 'LIBRE' ? 'success' : ($table->status === 'OCUPADA' ? 'warning' : ($table->status === 'RESERVADA' ? 'info' : 'secondary')) }} border-0 table-status-badge"
                                    style="cursor: pointer;"
                                    onclick="openChangeStatusModal({{ $table->id }}, '{{ $table->status }}', {{ $table->capacity }})">
                                {{ $table->status }}
                            </button>
                            @else
                            <span class="badge bg-{{ $table->status === 'LIBRE' ? 'success' : ($table->status === 'OCUPADA' ? 'warning' : ($table->status === 'RESERVADA' ? 'info' : 'secondary')) }} table-status-badge">
                                {{ $table->status }}
                            </span>
                            @endcan
                        </div>
                        
                        <div class="table-info">
                            <p class="text-muted mb-2">
                                <i class="bi bi-people"></i> <strong>Capacidad:</strong> {{ $table->capacity }} personas
                            </p>
                            @if($table->status === 'OCUPADA' && $table->currentSession)
                                @if($table->currentSession->waiter)
                                <p class="mb-1">
                                    <span class="badge bg-info">
                                        <i class="bi bi-person-badge"></i> Mozo: {{ $table->currentSession->waiter->name }}
                                    </span>
                                </p>
                                @endif
                                @if($table->currentSession->started_at)
                                <p class="mb-0 text-muted" style="font-size: 0.75rem;">
                                    <i class="bi bi-clock"></i> Abierta: {{ $table->currentSession->started_at->format('H:i') }}
                                </p>
                                @endif
                            @endif
                        </div>
                        
                        <div class="table-actions">
                            @if($table->status === 'LIBRE')
                            {{-- Mesa LIBRE: Solo puede reservar o cambiar estado a OCUPADA --}}
                            <a href="{{ route('tables.reserve', $table) }}" class="btn btn-reservar">
                                <i class="bi bi-calendar-check"></i> <span>Reservar</span>
                            </a>
                            @can('update', $table)
                            <button type="button" 
                                    class="btn btn-ocupar"
                                    onclick="openChangeStatusModal({{ $table->id }}, '{{ $table->status }}', {{ $table->capacity }})">
                                <i class="bi bi-check-circle"></i> <span>Marcar Ocupada</span>
                            </button>
                            @endcan
                            @elseif($table->status === 'OCUPADA')
                            {{-- Mesa OCUPADA: Puede tomar pedidos o cerrar mesa --}}
                            @if(in_array(auth()->user()->role, ['ADMIN', 'MOZO']))
                            <button type="button"
                                    class="btn btn-pedido"
                                    onclick="openNewOrderModal({{ $table->id }}, '{{ $table->number }}')">
                                <i class="bi bi-plus-circle"></i> <span>Nuevo Pedido</span>
                            </button>
                            @endif
                            <a href="{{ route('tables.orders', $table) }}" class="btn btn-ver-pedidos">
                                <i class="bi bi-receipt"></i> <span>Ver Pedidos</span>
                            </a>
                            @can('update', $table)
                            <form action="{{ route('tables.close', $table) }}" method="POST" class="d-inline w-100" id="closeTableForm{{ $table->id }}">
                                @csrf
                                <button type="button" class="btn btn-cerrar w-100" onclick="confirmCloseTable({{ $table->id }})">
                                    <i class="bi bi-x-circle"></i> <span>Cerrar Mesa</span>
                                </button>
                            </form>
                            @endcan
                            @elseif($table->status === 'RESERVADA')
                            {{-- Mesa RESERVADA: Puede cambiar estado --}}
                            @can('update', $table)
                            <button type="button" 
                                    class="btn btn-cambiar-estado"
                                    onclick="openChangeStatusModal({{ $table->id }}, '{{ $table->status }}', {{ $table->capacity }})">
                                <i class="bi bi-arrow-repeat"></i> <span>Cambiar Estado</span>
                            </button>
                            @endcan
                            @endif
                            @can('update', $table)
                            <a href="{{ route('tables.edit', $table) }}" class="btn btn-editar">
                                <i class="bi bi-pencil"></i> <span>Editar</span>
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <p class="text-muted text-center py-4">No hay mesas en este sector</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endforeach

@can('create', App\Models\Table::class)
<!-- Modal Crear Mesa -->
<div class="modal fade" id="createTableModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ route('tables.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Mesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="sector_id" class="form-label">Sector</label>
                        <select class="form-select" id="sector_id" name="sector_id" required>
                            <option value="">Seleccionar sector</option>
                            @foreach($sectors as $sector)
                            <option value="{{ $sector->id }}">{{ $sector->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="number" class="form-label">Número de Mesa</label>
                        <input type="text" class="form-control" id="number" name="number" required>
                    </div>
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacidad</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="position_x" class="form-label">Posición X (opcional)</label>
                            <input type="number" class="form-control" id="position_x" name="position_x">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="position_y" class="form-label">Posición Y (opcional)</label>
                            <input type="number" class="form-control" id="position_y" name="position_y">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Mesa</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@if(in_array(auth()->user()->role, ['ADMIN', 'MOZO']))
<!-- Modal Cambiar Estado de Mesa -->
<div class="modal fade" id="changeStatusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="changeStatusForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar Estado de Mesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">Estado</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="LIBRE">Libre</option>
                            <option value="OCUPADA">Ocupada</option>
                            <option value="RESERVADA">Reservada</option>
                            <option value="CERRADA">Cerrada</option>
                        </select>
                        <small class="text-muted" id="statusHelp"></small>
                    </div>
                    <div class="mb-3" id="waiterContainer" style="display: none;">
                        <label for="waiter_id" class="form-label">Asignar Mozo <span class="text-danger">*</span></label>
                        <select class="form-select" id="waiter_id" name="waiter_id">
                            <option value="">Seleccionar mozo...</option>
                            @foreach($waiters as $waiter)
                            <option value="{{ $waiter->id }}">{{ $waiter->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Selecciona el mozo que atenderá esta mesa</small>
                    </div>
                    <div class="mb-3" id="guestsCountContainer">
                        <label for="guests_count" class="form-label">Cantidad de Personas</label>
                        <input type="number" class="form-control" id="guests_count" name="guests_count" min="1" value="1">
                        <small class="text-muted">Capacidad máxima: <span id="maxCapacity">0</span> personas</small>
                    </div>
                    <input type="hidden" id="tableId" name="table_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if(in_array(auth()->user()->role, ['ADMIN', 'MOZO']))
<!-- Modal Nuevo Pedido (desde Mesas) -->
<div class="modal fade" id="newOrderModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen-md-down modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="newOrderForm">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle"></i> Nuevo Pedido
                        <small class="text-muted d-none d-sm-inline">- <span id="newOrderTableLabel"></span></small>
                        <span class="d-inline d-sm-none"><span id="newOrderTableLabelMobile"></span></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="newOrderTableId" />

                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 gap-2">
                                <h6 class="mb-0"><i class="bi bi-card-list"></i> Productos</h6>
                                <input type="text" class="form-control" id="productSearch" placeholder="Buscar producto..." style="max-width: 100%;">
                            </div>

                            <div id="productsAccordion">
                                @foreach($products as $categoryName => $categoryProducts)
                                    <div class="category-section-modal mb-4" data-category-name="{{ strtolower($categoryName) }}">
                                        <div class="d-flex align-items-center mb-3" style="background: linear-gradient(135deg, #1e8081, #138496); padding: 0.75rem 1rem; border-radius: 8px;">
                                            <h6 class="mb-0 text-white" style="font-weight: 700;">
                                                <i class="bi bi-tag-fill"></i> {{ $categoryName }}
                                            </h6>
                                            <span class="badge bg-light text-dark ms-auto">{{ $categoryProducts->count() }} productos</span>
                                        </div>
                                        <div class="row g-2">
                                                <div class="row">
                                                    @foreach($categoryProducts as $product)
                                                        @php
                                                            $currentStock = $product->has_stock ? $product->getCurrentStock(auth()->user()->restaurant_id) : null;
                                                            $isOutOfStock = $currentStock !== null && $currentStock <= 0;
                                                            $isLowStock = $currentStock !== null && $currentStock > 0 && $currentStock <= $product->stock_minimum;
                                                        @endphp
                                                        <div class="col-12 col-md-6 mb-2 product-item" data-name="{{ strtolower($product->name) }}" data-category-name="{{ strtolower($categoryName) }}">
                                                            <div class="d-flex justify-content-between align-items-start border rounded p-2 {{ $isOutOfStock ? 'border-danger bg-light' : ($isLowStock ? 'border-warning bg-light' : '') }}">
                                                                <div class="me-2 flex-grow-1">
                                                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                                        <strong class="fs-6">{{ $product->name }}</strong>
                                                                        @if($isOutOfStock)
                                                                            <span class="badge bg-danger" title="Sin stock disponible">
                                                                                <i class="bi bi-x-circle-fill"></i> Sin Stock
                                                                            </span>
                                                                        @elseif($isLowStock)
                                                                            <span class="badge bg-warning text-dark" title="Stock bajo">
                                                                                <i class="bi bi-exclamation-circle-fill"></i> Stock Bajo
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                    @if($product->description)
                                                                        <div class="text-muted small mb-1">{{ $product->description }}</div>
                                                                    @endif
                                                                    @if($product->has_stock && $currentStock !== null)
                                                                        <div class="small mb-2">
                                                                            <span class="badge bg-{{ $isOutOfStock ? 'danger' : ($isLowStock ? 'warning' : 'success') }}">
                                                                                Stock: {{ $currentStock }}
                                                                                @if($product->stock_minimum > 0)
                                                                                    (Mín: {{ $product->stock_minimum }})
                                                                                @endif
                                                                            </span>
                                                                        </div>
                                                                    @endif
                                                                    <div class="fs-5 fw-bold text-primary">${{ number_format($product->price, 2) }}</div>
                                                                </div>
                                                                <div class="ms-2">
                                                                    <button type="button"
                                                                            class="btn btn-primary {{ $isOutOfStock ? 'disabled' : '' }}"
                                                                            style="min-width: 48px; min-height: 48px;"
                                                                            onclick="addModalItem({{ $product->id }}, '{{ addslashes($product->name) }}', {{ (float) $product->price }}, {{ $currentStock ?? 'null' }}, {{ $product->stock_minimum ?? 0 }})"
                                                                            {{ $isOutOfStock ? 'disabled title="Producto sin stock"' : '' }}>
                                                                        <i class="bi bi-plus-lg"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="sticky-top" style="top: 70px;">
                                <h6 class="mb-3"><i class="bi bi-receipt"></i> Pedido</h6>

                                <div class="mb-3">
                                    <label class="form-label">Observaciones (opcional)</label>
                                    <textarea class="form-control" id="newOrderObservations" rows="3" placeholder="Ej: sin sal, alergias, etc."></textarea>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="sendToKitchen" checked style="min-width: 48px; min-height: 24px;">
                                    <label class="form-check-label ms-2" for="sendToKitchen" style="font-size: 0.875rem;">Enviar a cocina al confirmar</label>
                                </div>

                                <div id="modalItemsEmpty" class="text-muted text-center py-3">No hay items en el pedido.</div>
                                <div id="modalItemsList" class="mb-3"></div>

                                <div class="border-top pt-3 pb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <strong class="fs-5">Total:</strong> <span id="modalTotal" class="fs-4 fw-bold text-primary">$0.00</span>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100" id="confirmOrderBtn" disabled style="min-height: 52px; font-size: 1.125rem; font-weight: 700;">
                                        <i class="bi bi-check-circle"></i> Confirmar Pedido
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
function openChangeStatusModal(tableId, currentStatus, capacity) {
    const modal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
    const form = document.getElementById('changeStatusForm');
    const statusSelect = document.getElementById('status');
    const guestsInput = document.getElementById('guests_count');
    const guestsContainer = document.getElementById('guestsCountContainer');
    const waiterContainer = document.getElementById('waiterContainer');
    const waiterSelect = document.getElementById('waiter_id');
    const tableIdInput = document.getElementById('tableId');
    const maxCapacitySpan = document.getElementById('maxCapacity');
    const statusHelp = document.getElementById('statusHelp');
    
    // Configurar formulario
    form.action = `/tables/${tableId}/status`;
    tableIdInput.value = tableId;
    maxCapacitySpan.textContent = capacity;
    guestsInput.max = capacity;
    
    // Limpiar event listeners anteriores clonando el select
    const newStatusSelect = statusSelect.cloneNode(true);
    statusSelect.parentNode.replaceChild(newStatusSelect, statusSelect);
    const newStatusSelectRef = document.getElementById('status');
    
    // Función para mostrar/ocultar campos según el estado
    function updateFieldsForStatus(status) {
        if (status === 'OCUPADA') {
            guestsContainer.style.display = 'block';
            guestsInput.required = true;
            if (!guestsInput.value || guestsInput.value === '0') {
                guestsInput.value = 1;
            }
            waiterContainer.style.display = 'block';
            waiterSelect.required = true;
        } else if (status === 'LIBRE') {
            guestsContainer.style.display = 'none';
            guestsInput.required = false;
            guestsInput.value = 0;
            waiterContainer.style.display = 'none';
            waiterSelect.required = false;
            waiterSelect.value = '';
        } else if (status === 'RESERVADA') {
            guestsContainer.style.display = 'block';
            guestsInput.required = true;
            if (!guestsInput.value || guestsInput.value === '0') {
                guestsInput.value = 1;
            }
            waiterContainer.style.display = 'none';
            waiterSelect.required = false;
            waiterSelect.value = '';
        } else {
            guestsContainer.style.display = 'none';
            guestsInput.required = false;
            guestsInput.value = 0;
            waiterContainer.style.display = 'none';
            waiterSelect.required = false;
            waiterSelect.value = '';
        }
    }
    
    // Configurar opciones según el estado actual
    newStatusSelectRef.innerHTML = '';
    
    if (currentStatus === 'LIBRE') {
        // Si está LIBRE, solo puede cambiar a OCUPADA o RESERVADA
        newStatusSelectRef.innerHTML = `
            <option value="OCUPADA">Ocupada</option>
            <option value="RESERVADA">Reservada</option>
        `;
        statusHelp.textContent = 'Una mesa libre solo puede marcarse como Ocupada o Reservada.';
        guestsContainer.style.display = 'block';
        guestsInput.required = true;
        guestsInput.value = 1;
        waiterContainer.style.display = 'none';
        waiterSelect.required = false;
        waiterSelect.value = '';
    } else if (currentStatus === 'OCUPADA') {
        // Si está OCUPADA, puede cambiar a LIBRE o CERRADA
        newStatusSelectRef.innerHTML = `
            <option value="LIBRE">Libre</option>
            <option value="CERRADA">Cerrada</option>
        `;
        statusHelp.textContent = 'Para cerrar la mesa y generar el recibo, usa el botón "Cerrar Mesa" en lugar de cambiar el estado.';
        guestsContainer.style.display = 'none';
        guestsInput.required = false;
        guestsInput.value = 0;
        waiterContainer.style.display = 'none';
        waiterSelect.required = false;
        waiterSelect.value = '';
    } else if (currentStatus === 'RESERVADA') {
        // Si está RESERVADA, puede cambiar a OCUPADA o LIBRE
        newStatusSelectRef.innerHTML = `
            <option value="LIBRE">Libre</option>
            <option value="OCUPADA">Ocupada</option>
        `;
        statusHelp.textContent = 'Una mesa reservada puede marcarse como Ocupada o Libre.';
        guestsContainer.style.display = 'block';
        guestsInput.required = true;
        guestsInput.value = 1;
        waiterContainer.style.display = 'none';
        waiterSelect.required = false;
        waiterSelect.value = '';
    } else {
        // Cualquier otro estado, permitir todos los cambios
        newStatusSelectRef.innerHTML = `
            <option value="LIBRE">Libre</option>
            <option value="OCUPADA">Ocupada</option>
            <option value="RESERVADA">Reservada</option>
            <option value="CERRADA">Cerrada</option>
        `;
        statusHelp.textContent = '';
        guestsContainer.style.display = 'block';
        guestsInput.required = true;
        guestsInput.value = 1;
        waiterContainer.style.display = 'none';
        waiterSelect.required = false;
        waiterSelect.value = '';
    }
    
    // Actualizar cuando cambie el estado seleccionado
    newStatusSelectRef.addEventListener('change', function() {
        updateFieldsForStatus(this.value);
    });
    
    // Verificar el estado inicial después de configurar las opciones
    setTimeout(() => {
        const initialValue = newStatusSelectRef.value;
        updateFieldsForStatus(initialValue);
    }, 50);
    
    // También verificar cuando el modal se muestra completamente
    const modalElement = document.getElementById('changeStatusModal');
    const handleShown = function() {
        const currentValue = newStatusSelectRef.value;
        updateFieldsForStatus(currentValue);
        modalElement.removeEventListener('shown.bs.modal', handleShown);
    };
    modalElement.addEventListener('shown.bs.modal', handleShown);
    
    modal.show();
}

function editTable(tableId) {
    // TODO: Implementar edición de mesa
    Swal.fire({
        icon: 'info',
        title: 'Editar Mesa',
        text: 'Funcionalidad de edición de mesa ' + tableId + ' en desarrollo',
        confirmButtonColor: '#1e8081',
        confirmButtonText: 'Entendido'
    });
}

function confirmCloseTable(tableId) {
    Swal.fire({
        icon: 'question',
        title: '¿Cerrar Mesa?',
        text: 'Se cerrarán todos los pedidos activos y se generará el recibo consolidado.',
        showCancelButton: true,
        confirmButtonColor: '#1e8081',
        cancelButtonColor: '#7b7d84',
        confirmButtonText: 'Sí, cerrar mesa',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('closeTableForm' + tableId).submit();
        }
    });
}

// ===== Modal Nuevo Pedido =====
let newOrderModal;
let modalItems = [];
let modalItemCounter = 0;

function openNewOrderModal(tableId, tableLabel) {
    if (!newOrderModal) {
        newOrderModal = new bootstrap.Modal(document.getElementById('newOrderModal'));
    }

    document.getElementById('newOrderTableId').value = tableId;
    document.getElementById('newOrderTableLabel').textContent = tableLabel;
    document.getElementById('newOrderTableLabelMobile').textContent = tableLabel;
    document.getElementById('newOrderObservations').value = '';
    document.getElementById('sendToKitchen').checked = true;
    document.getElementById('productSearch').value = '';

    modalItems = [];
    modalItemCounter = 0;
    renderModalItems();
    filterProducts('');

    newOrderModal.show();
}

function addModalItem(productId, name, price, currentStock, stockMinimum) {
    // Validar stock si aplica
    if (currentStock !== null && currentStock !== undefined) {
        const existing = modalItems.find(i => i.product_id === productId);
        const requestedQty = existing ? existing.quantity + 1 : 1;
        
        if (currentStock <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Sin Stock',
                text: `El producto "${name}" no tiene stock disponible.`,
                confirmButtonColor: '#c94a2d',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        
        if (requestedQty > currentStock) {
            Swal.fire({
                icon: 'warning',
                title: 'Stock Insuficiente',
                html: `El producto "${name}" tiene stock limitado.<br><strong>Disponible: ${currentStock}</strong><br>Solicitado: ${requestedQty}`,
                confirmButtonColor: '#ffc107',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        
        if (currentStock <= stockMinimum && stockMinimum > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Stock Bajo',
                html: `El producto "${name}" tiene stock bajo.<br><strong>Disponible: ${currentStock}</strong><br>Mínimo recomendado: ${stockMinimum}`,
                confirmButtonColor: '#ffc107',
                confirmButtonText: 'Continuar',
                showCancelButton: true,
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    addItemToModal(productId, name, price, existing);
                }
            });
            return;
        }
    }
    
    addItemToModal(productId, name, price, modalItems.find(i => i.product_id === productId));
}

function addItemToModal(productId, name, price, existing) {
    if (existing) {
        existing.quantity += 1;
    } else {
        modalItemCounter++;
        modalItems.push({
            id: modalItemCounter,
            product_id: productId,
            name,
            price,
            quantity: 1,
            observations: ''
        });
    }
    renderModalItems();
}

function removeModalItem(itemId) {
    modalItems = modalItems.filter(i => i.id !== itemId);
    renderModalItems();
}

function updateModalQty(itemId, qty) {
    const item = modalItems.find(i => i.id === itemId);
    if (!item) return;
    item.quantity = Math.max(1, parseInt(qty || '1', 10));
    renderModalItems();
}

function updateModalObs(itemId, obs) {
    const item = modalItems.find(i => i.id === itemId);
    if (!item) return;
    item.observations = obs || '';
}

function renderModalItems() {
    const list = document.getElementById('modalItemsList');
    const empty = document.getElementById('modalItemsEmpty');
    const totalEl = document.getElementById('modalTotal');
    const btn = document.getElementById('confirmOrderBtn');

    if (modalItems.length === 0) {
        list.innerHTML = '';
        empty.style.display = 'block';
        totalEl.textContent = '$0.00';
        btn.disabled = true;
        return;
    }

    empty.style.display = 'none';
    btn.disabled = false;

    let total = 0;
    list.innerHTML = modalItems.map(item => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        return `
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="flex-grow-1">
                        <strong class="d-block mb-1">${item.name}</strong>
                        <div class="text-muted small mb-2">$${item.price.toFixed(2)} c/u</div>
                    </div>
                    <button type="button" class="btn btn-outline-danger ms-2" onclick="removeModalItem(${item.id})" style="min-width: 44px; min-height: 44px;">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="small fw-semibold">Cantidad</label>
                        <input type="number" class="form-control" min="1" value="${item.quantity}"
                               onchange="updateModalQty(${item.id}, this.value)" style="min-height: 44px;">
                    </div>
                    <div class="col-6">
                        <label class="small fw-semibold">Subtotal</label>
                        <div class="form-control bg-light" style="min-height: 44px; display: flex; align-items: center;">
                            <strong class="text-primary">$${subtotal.toFixed(2)}</strong>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <label class="small fw-semibold">Observaciones (opcional)</label>
                    <input type="text" class="form-control" value="${item.observations || ''}"
                           oninput="updateModalObs(${item.id}, this.value)" placeholder="Ej: sin sal..." style="min-height: 44px;">
                </div>
            </div>
        `;
    }).join('');

    totalEl.textContent = `$${total.toFixed(2)}`;
}

function filterProducts(term) {
    const t = (term || '').toLowerCase().trim();
    
    document.querySelectorAll('.category-section-modal').forEach(section => {
        let hasVisibleProducts = false;
        
        section.querySelectorAll('.product-item').forEach(el => {
            const name = el.getAttribute('data-name') || '';
            const categoryName = el.getAttribute('data-category-name') || '';
            
            if (!t || name.includes(t) || categoryName.includes(t)) {
                el.style.display = '';
                hasVisibleProducts = true;
            } else {
                el.style.display = 'none';
            }
        });
        
        // Mostrar/ocultar sección según si tiene productos visibles
        section.style.display = hasVisibleProducts ? 'block' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Búsqueda de mesas
    const tableSearch = document.getElementById('tableSearch');
    if (tableSearch) {
        tableSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            document.querySelectorAll('.table-item').forEach(item => {
                const tableNumber = item.dataset.tableNumber || '';
                const sectorName = item.dataset.sectorName || '';
                
                if (!searchTerm || tableNumber.includes(searchTerm) || sectorName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Ocultar secciones de sector si no tienen mesas visibles
            document.querySelectorAll('.card.mb-4').forEach(card => {
                const visibleTables = card.querySelectorAll('.table-item[style="display: block;"], .table-item:not([style*="display: none"])').length;
                if (searchTerm && visibleTables === 0) {
                    card.style.display = 'none';
                } else {
                    card.style.display = 'block';
                }
            });
        });
    }
    
    const search = document.getElementById('productSearch');
    if (search) {
        search.addEventListener('input', (e) => filterProducts(e.target.value));
    }

    const form = document.getElementById('newOrderForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (modalItems.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pedido vacío',
                    text: 'Agregá al menos un producto.',
                    confirmButtonColor: '#1e8081'
                });
                return;
            }

            const tableId = document.getElementById('newOrderTableId').value;
            const payload = {
                observations: document.getElementById('newOrderObservations').value || null,
                send_to_kitchen: document.getElementById('sendToKitchen').checked,
                items: modalItems.map(i => ({
                    product_id: i.product_id,
                    quantity: i.quantity,
                    observations: i.observations || null,
                }))
            };

            try {
                const res = await fetch(`/tables/${tableId}/orders`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo crear el pedido');
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Pedido creado',
                    html: `
                        <p>Pedido <strong>${data.order_number}</strong> creado exitosamente.</p>
                        <p class="text-muted small">${data.message || 'La comanda de cocina se ha impreso automáticamente.'}</p>
                        ${data.kitchen_ticket_url ? `
                            <div class="mt-3">
                                <a href="${data.kitchen_ticket_url}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-printer"></i> Ver Ticket Cocina
                                </a>
                            </div>
                        ` : ''}
                    `,
                    confirmButtonColor: '#1e8081',
                    confirmButtonText: 'Entendido',
                    showCancelButton: data.kitchen_ticket_url ? true : false,
                    cancelButtonText: 'Ver Ticket',
                    cancelButtonColor: '#007bff'
                }).then((result) => {
                    if (result.isDismissed && result.dismiss === Swal.DismissReason.cancel && data.kitchen_ticket_url) {
                        window.open(data.kitchen_ticket_url, '_blank');
                    }
                });

                newOrderModal.hide();
                
                // Recargar la página para actualizar la lista de pedidos
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: err.message || 'No se pudo crear el pedido',
                    confirmButtonColor: '#c94a2d',
                });
            }
        });
    }
    
    // MÓDULO 3: Sistema de notificaciones para pedidos listos
    let lastNotificationCheck = null;
    let notifiedOrders = new Set();
    
    async function checkReadyOrders() {
        try {
            const response = await fetch('/api/notifications/ready-orders', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) return;
            
            const data = await response.json();
            if (data.success && data.orders) {
                data.orders.forEach(order => {
                    // Solo notificar si no se ha notificado antes
                    if (!notifiedOrders.has(order.id)) {
                        notifiedOrders.add(order.id);
                        
                        // Mostrar notificación toast
                        Swal.fire({
                            icon: 'success',
                            title: '🍽️ Pedido Listo',
                            html: `Pedido <strong>#${order.number}</strong> listo en <strong>Mesa ${order.table_number}</strong>`,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: true,
                            confirmButtonText: 'Ver Pedidos',
                            confirmButtonColor: '#1e8081',
                            timer: 10000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer);
                                toast.addEventListener('mouseleave', Swal.resumeTimer);
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Redirigir a ver pedidos de la mesa
                                window.location.href = `/tables/${order.table_id || ''}/orders`;
                            }
                        });
                    }
                });
            }
        } catch (error) {
            console.error('Error checking ready orders:', error);
        }
    }
    
    // Iniciar polling cada 10 segundos si el usuario es MOZO
    @if(auth()->user()->role === 'MOZO')
        if (typeof checkReadyOrders === 'function') {
            // Primera verificación después de 2 segundos
            setTimeout(checkReadyOrders, 2000);
            // Luego cada 10 segundos
            setInterval(checkReadyOrders, 10000);
        }
    @endif
});
</script>
@endsection

