@extends('layouts.app')

@section('title', 'Gestión de Mesas')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-table"></i> Gestión de Mesas</h1>
            <p class="text-muted">Administra las mesas del restaurante</p>
        </div>
        <div>
            @can('create', App\Models\Table::class)
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTableModal">
                <i class="bi bi-plus-circle"></i> Nueva Mesa
            </button>
            @endcan
            <a href="{{ route('tables.layout') }}" class="btn btn-outline-primary">
                <i class="bi bi-diagram-3"></i> Layout Visual
            </a>
        </div>
    </div>
</div>

@foreach($sectors as $sector)
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-door-open"></i> {{ $sector->name }}
            @if($sector->description)
                <small class="text-muted">- {{ $sector->description }}</small>
            @endif
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            @forelse($sector->tables as $table)
            <div class="col-md-3 mb-3">
                <div class="card h-100 border-{{ $table->status === 'LIBRE' ? 'success' : ($table->status === 'OCUPADA' ? 'warning' : 'secondary') }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">{{ $table->number }}</h5>
                            @can('update', $table)
                            <button type="button" 
                                    class="badge bg-{{ $table->status === 'LIBRE' ? 'success' : ($table->status === 'OCUPADA' ? 'warning' : 'secondary') }} border-0"
                                    style="cursor: pointer;"
                                    onclick="openChangeStatusModal({{ $table->id }}, '{{ $table->status }}', {{ $table->capacity }})">
                                {{ $table->status }}
                            </button>
                            @else
                            <span class="badge bg-{{ $table->status === 'LIBRE' ? 'success' : ($table->status === 'OCUPADA' ? 'warning' : 'secondary') }}">
                                {{ $table->status }}
                            </span>
                            @endcan
                        </div>
                        <p class="text-muted mb-2">
                            <i class="bi bi-people"></i> Capacidad: {{ $table->capacity }} personas
                        </p>
                        @if($table->current_order_id && $table->currentOrder)
                        <p class="mb-2">
                            <a href="{{ route('orders.show', $table->currentOrder) }}" class="btn btn-sm btn-outline-primary">
                                Ver Pedido
                            </a>
                        </p>
                        @endif
                        <div class="d-flex gap-2 flex-wrap">
                            @if($table->status === 'LIBRE')
                            {{-- Mesa LIBRE: Solo puede reservar o cambiar estado a OCUPADA --}}
                            <a href="{{ route('tables.reserve', $table) }}" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-calendar-check"></i> Reservar
                            </a>
                            @can('update', $table)
                            <button type="button" 
                                    class="btn btn-sm btn-primary"
                                    onclick="openChangeStatusModal({{ $table->id }}, '{{ $table->status }}', {{ $table->capacity }})">
                                <i class="bi bi-check-circle"></i> Marcar Ocupada
                            </button>
                            @endcan
                            @elseif($table->status === 'OCUPADA')
                            {{-- Mesa OCUPADA: Puede tomar pedidos o cerrar mesa --}}
                            @if(in_array(auth()->user()->role, ['ADMIN', 'MOZO']))
                            <a href="{{ route('orders.create', ['tableId' => $table->id]) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-circle"></i> Nuevo Pedido
                            </a>
                            @endif
                            @if($table->current_order_id && $table->currentOrder)
                            <a href="{{ route('orders.show', $table->currentOrder) }}" class="btn btn-sm btn-warning">
                                <i class="bi bi-eye"></i> Ver Pedido
                            </a>
                            @endif
                            @can('update', $table)
                            <form action="{{ route('tables.close', $table) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('¿Está seguro de cerrar esta mesa? Se cerrarán todos los pedidos activos y se generará el recibo consolidado.')">
                                    <i class="bi bi-check-circle"></i> Cerrar Mesa
                                </button>
                            </form>
                            @endcan
                            @elseif($table->status === 'RESERVADA')
                            {{-- Mesa RESERVADA: Puede cambiar estado --}}
                            @can('update', $table)
                            <button type="button" 
                                    class="btn btn-sm btn-primary"
                                    onclick="openChangeStatusModal({{ $table->id }}, '{{ $table->status }}', {{ $table->capacity }})">
                                <i class="bi bi-check-circle"></i> Cambiar Estado
                            </button>
                            @endcan
                            @endif
                            @can('update', $table)
                            <a href="{{ route('tables.edit', $table) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <p class="text-muted text-center">No hay mesas en este sector</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endforeach

@can('create', App\Models\Table::class)
<!-- Modal Crear Mesa -->
<div class="modal fade" id="createTableModal" tabindex="-1">
    <div class="modal-dialog">
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
    <div class="modal-dialog">
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
                    <div class="mb-3" id="guestsCountContainer">
                        <label for="guests_count" class="form-label">Cantidad de Personas</label>
                        <input type="number" class="form-control" id="guests_count" name="guests_count" min="1" value="1" required>
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

<script>
function openChangeStatusModal(tableId, currentStatus, capacity) {
    const modal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
    const form = document.getElementById('changeStatusForm');
    const statusSelect = document.getElementById('status');
    const guestsInput = document.getElementById('guests_count');
    const guestsContainer = document.getElementById('guestsCountContainer');
    const tableIdInput = document.getElementById('tableId');
    const maxCapacitySpan = document.getElementById('maxCapacity');
    const statusHelp = document.getElementById('statusHelp');
    
    // Configurar formulario
    form.action = `/tables/${tableId}/status`;
    tableIdInput.value = tableId;
    maxCapacitySpan.textContent = capacity;
    guestsInput.max = capacity;
    
    // Limpiar event listeners anteriores
    const newStatusSelect = statusSelect.cloneNode(true);
    statusSelect.parentNode.replaceChild(newStatusSelect, statusSelect);
    const newStatusSelectRef = document.getElementById('status');
    
    // Configurar opciones según el estado actual
    function updateStatusOptions() {
        // Limpiar opciones
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
        }
        
        // Actualizar cuando cambie el estado seleccionado
        newStatusSelectRef.addEventListener('change', function() {
            if (this.value === 'LIBRE') {
                guestsContainer.style.display = 'none';
                guestsInput.required = false;
                guestsInput.value = 0;
            } else if (this.value === 'OCUPADA' || this.value === 'RESERVADA') {
                guestsContainer.style.display = 'block';
                guestsInput.required = true;
                guestsInput.value = 1;
            } else {
                guestsContainer.style.display = 'none';
                guestsInput.required = false;
                guestsInput.value = 0;
            }
        });
    }
    
    updateStatusOptions();
    modal.show();
}

function editTable(tableId) {
    // TODO: Implementar edición de mesa
    alert('Editar mesa ' + tableId);
}
</script>
@endsection

