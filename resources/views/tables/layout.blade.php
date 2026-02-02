@extends('layouts.app')

@section('title', 'Layout de Mesas')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-diagram-3"></i> Layout de Mesas</h1>
            <p class="text-muted">Organiza las mesas visualmente</p>
        </div>
        <div>
            <select id="sectorSelect" class="form-select d-inline-block" style="width: auto;" onchange="loadSector(this.value)">
                <option value="">Seleccionar sector</option>
                @foreach($sectors as $sector)
                <option value="{{ $sector->id }}" {{ $selectedSector && $selectedSector->id === $sector->id ? 'selected' : '' }}>
                    {{ $sector->name }}
                </option>
                @endforeach
            </select>
            @if($selectedSector)
            <button type="button" class="btn btn-primary ms-2" onclick="saveLayout()">
                <i class="bi bi-save"></i> Guardar Layout
            </button>
            <button type="button" class="btn btn-secondary ms-2" onclick="toggleEditMode()">
                <i class="bi bi-pencil"></i> <span id="editModeText">Modo Edición</span>
            </button>
            @endif
        </div>
    </div>
</div>

@if($selectedSector)
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Sector: {{ $selectedSector->name }}</h5>
    </div>
    <div class="card-body">
        @php
            $sectorNameLower = strtolower($selectedSector->name ?? '');
            $isSalon = str_contains($sectorNameLower, 'salon') || str_contains($sectorNameLower, 'salón');
            $fixtures = is_array($selectedSector->layout_config) ? ($selectedSector->layout_config['fixtures'] ?? []) : [];
            $stageX = $fixtures['stage']['x'] ?? 380;
            $stageY = $fixtures['stage']['y'] ?? 30;
            
            // Obtener subsectores con sus items
            $subsectors = $selectedSector->subsectors ?? collect();
        @endphp
        <div id="layoutCanvas" style="position: relative; width: 100%; min-height: 600px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; overflow: hidden;">
            @foreach($subsectors as $subsector)
                @php
                    $subsectorConfig = is_array($subsector->layout_config) ? $subsector->layout_config : [];
                    $subsectorX = $subsectorConfig['x'] ?? 12;
                    $subsectorY = $subsectorConfig['y'] ?? 12;
                @endphp
                <!-- Subsector: {{ $subsector->name }} -->
                <div class="subsector-area" 
                     data-subsector-id="{{ $subsector->id }}"
                     data-initial-x="{{ $subsectorX }}"
                     data-initial-y="{{ $subsectorY }}"
                     style="position: absolute;
                            left: {{ $subsectorX }}px;
                            top: {{ $subsectorY }}px;
                            width: 140px;
                            min-height: 100px;
                            background: #ffffff;
                            border: 2px solid rgba(0,0,0,0.15);
                            border-radius: 12px;
                            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
                            z-index: 5;
                            display: flex;
                            flex-direction: column;
                            padding: 10px;">
                    <div class="subsector-title" style="font-weight: 800; color: #262c3b; text-align: center; border-bottom: 1px solid rgba(0,0,0,0.08); padding-bottom: 8px; margin-bottom: 10px;">
                        <i class="bi bi-grid-3x3-gap"></i> {{ $subsector->name }}
                        @if($subsector->capacity)
                        <small class="text-muted d-block">{{ $subsector->capacity }} lugares</small>
                        @endif
                    </div>
                    <div class="subsector-items" style="display: flex; flex-direction: column; gap: 10px; justify-content: flex-start; align-items: center; flex: 1;">
                        @foreach($subsector->items as $item)
                        <div class="subsector-item" 
                             data-item-id="{{ $item->id }}"
                             data-item-status="{{ $item->status }}"
                             onclick="openSubsectorItemModal({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ $item->status }}', {{ $subsector->id }})"
                             style="width: 44px;
                                    height: 44px;
                                    border-radius: 50%;
                                    background: {{ $item->status === 'LIBRE' ? 'linear-gradient(135deg, #28a745, #20c997)' : ($item->status === 'OCUPADA' ? 'linear-gradient(135deg, #ffc107, #fd7e14)' : 'linear-gradient(135deg, #6c757d, #495057)') }};
                                    color: #fff;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-weight: 800;
                                    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
                                    user-select: none;
                                    cursor: pointer;
                                    transition: transform 0.2s;">
                            {{ $item->position }}
                        </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if($isSalon)
                <!-- Escenario (draggable) -->
                <div class="fixture-item fixture-stage"
                     data-fixture-id="stage"
                     data-initial-x="{{ $stageX }}"
                     data-initial-y="{{ $stageY }}"
                     style="left: {{ $stageX }}px; top: {{ $stageY }}px;">
                    <div class="fixture-title">
                        <i class="bi bi-music-note-beamed"></i> Escenario
                    </div>
                    <div class="fixture-subtitle">Arrastrable</div>
                </div>
            @endif
            @foreach($tables as $table)
            <div class="table-item" 
                 data-table-id="{{ $table->id }}"
                 data-table-status="{{ $table->status }}"
                 data-table-capacity="{{ $table->capacity }}"
                 data-initial-x="{{ $table->position_x ?? 50 }}"
                 data-initial-y="{{ $table->position_y ?? 50 }}"
                 style="position: absolute; 
                        left: {{ $table->position_x ?? 50 }}px; 
                        top: {{ $table->position_y ?? 50 }}px;
                        width: 80px;
                        height: 80px;
                        background: {{ $table->status === 'LIBRE' ? '#28a745' : ($table->status === 'OCUPADA' ? '#ffc107' : '#6c757d') }};
                        border: 2px solid #000;
                        border-radius: 8px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        cursor: move;
                        color: white;
                        font-weight: bold;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                        z-index: 10;
                        user-select: none;">
                <div style="font-size: 12px;">{{ $table->number }}</div>
                <div style="font-size: 10px;">{{ $table->capacity }}p</div>
                <div style="font-size: 9px; margin-top: 2px;" class="table-status-text">{{ $table->status }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@else
<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Selecciona un sector para ver y editar su layout.
</div>
@endif

@if($selectedSector && in_array(auth()->user()->role, ['ADMIN', 'MOZO']))
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

@push('styles')
<style>
.table-item {
    transition: transform 0.1s;
}
.table-item:hover {
    transform: scale(1.05);
    z-index: 20 !important;
}
.table-item.dragging {
    opacity: 0.7;
    z-index: 1000 !important;
}

/* Subsectores */
.subsector-area {
    transition: transform 0.1s;
}
.subsector-area:hover {
    transform: scale(1.02);
    z-index: 20 !important;
}
.subsector-item {
    transition: transform 0.2s;
}
.subsector-item:hover {
    transform: scale(1.1);
    z-index: 30 !important;
}

/* Fixture: Escenario */
.fixture-item {
    position: absolute;
    width: 220px;
    height: 90px;
    background: linear-gradient(135deg, rgba(38, 44, 59, 0.95), rgba(34, 86, 94, 0.95));
    border: 2px solid rgba(0,0,0,0.25);
    border-radius: 14px;
    color: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: move;
    box-shadow: 0 10px 25px rgba(0,0,0,0.18);
    z-index: 6;
    user-select: none;
}
.fixture-item:hover {
    transform: scale(1.02);
}
.fixture-item.dragging {
    opacity: 0.85;
    z-index: 1000 !important;
}
.fixture-title {
    font-weight: 900;
    letter-spacing: 0.5px;
}
.fixture-subtitle {
    font-size: 12px;
    opacity: 0.85;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
<script>
let editMode = true; // Modo edición activado por defecto
let draggedElement = null;

function toggleEditMode() {
    editMode = !editMode;
    const text = document.getElementById('editModeText');
    text.textContent = editMode ? 'Modo Visualización' : 'Modo Edición';
    
    const items = document.querySelectorAll('.table-item, .fixture-item');
    items.forEach(table => {
        if (editMode) {
            table.style.cursor = 'move';
        } else {
            table.style.cursor = 'pointer';
        }
    });
    
    if (editMode) {
        initDragAndDrop();
    } else {
        destroyDragAndDrop();
    }
}

function initDragAndDrop() {
    interact('.table-item').draggable({
        listeners: {
            start(event) {
                event.target.classList.add('dragging');
                draggedElement = event.target;
            },
            move(event) {
                const target = event.target;
                const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;
                
                target.style.transform = `translate(${x}px, ${y}px)`;
                target.setAttribute('data-x', x);
                target.setAttribute('data-y', y);
            },
            end(event) {
                event.target.classList.remove('dragging');
            }
        },
        modifiers: [
            interact.modifiers.restrictRect({
                restriction: 'parent',
                endOnly: true
            })
        ],
        inertia: false
    });

    // Drag para elementos fijos (escenario)
    interact('.fixture-item').draggable({
        listeners: {
            start(event) {
                event.target.classList.add('dragging');
                draggedElement = event.target;
            },
            move(event) {
                const target = event.target;
                const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

                target.style.transform = `translate(${x}px, ${y}px)`;
                target.setAttribute('data-x', x);
                target.setAttribute('data-y', y);
            },
            end(event) {
                event.target.classList.remove('dragging');
            }
        },
        modifiers: [
            interact.modifiers.restrictRect({
                restriction: 'parent',
                endOnly: true
            })
        ],
        inertia: false
    });
}

function destroyDragAndDrop() {
    interact('.table-item').unset();
    interact('.fixture-item').unset();
}

function loadSector(sectorId) {
    if (sectorId) {
        window.location.href = `{{ route('tables.layout') }}/${sectorId}`;
    } else {
        window.location.href = '{{ route('tables.layout') }}';
    }
}

function saveLayout() {
    if (!{{ $selectedSector ? 'true' : 'false' }}) {
        Swal.fire({
            icon: 'warning',
            title: 'Sector Requerido',
            text: 'Debes seleccionar un sector primero',
            confirmButtonColor: '#1e8081',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    const tables = [];
    const fixtures = [];
    const canvas = document.getElementById('layoutCanvas');
    
    document.querySelectorAll('.table-item').forEach(item => {
        const tableId = item.getAttribute('data-table-id');
        
        // Obtener posición inicial desde el atributo style
        const initialLeft = parseFloat(item.style.left) || parseFloat(item.getAttribute('data-initial-x')) || 0;
        const initialTop = parseFloat(item.style.top) || parseFloat(item.getAttribute('data-initial-y')) || 0;
        
        // Obtener offset del drag (data-x y data-y)
        const offsetX = parseFloat(item.getAttribute('data-x')) || 0;
        const offsetY = parseFloat(item.getAttribute('data-y')) || 0;
        
        // Calcular posición final
        const positionX = Math.max(0, Math.round(initialLeft + offsetX));
        const positionY = Math.max(0, Math.round(initialTop + offsetY));
        
        tables.push({
            id: tableId,
            position_x: positionX,
            position_y: positionY
        });
    });

    // Guardar fixtures (ej: escenario)
    document.querySelectorAll('.fixture-item').forEach(item => {
        const fixtureId = item.getAttribute('data-fixture-id');
        const initialLeft = parseFloat(item.style.left) || parseFloat(item.getAttribute('data-initial-x')) || 0;
        const initialTop = parseFloat(item.style.top) || parseFloat(item.getAttribute('data-initial-y')) || 0;
        const offsetX = parseFloat(item.getAttribute('data-x')) || 0;
        const offsetY = parseFloat(item.getAttribute('data-y')) || 0;
        const positionX = Math.max(0, Math.round(initialLeft + offsetX));
        const positionY = Math.max(0, Math.round(initialTop + offsetY));

        fixtures.push({
            id: fixtureId,
            position_x: positionX,
            position_y: positionY
        });
    });
    
    fetch('{{ route('tables.update-layout') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            sector_id: {{ $selectedSector->id ?? 'null' }},
            tables: tables,
            fixtures: fixtures
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Layout guardado exitosamente',
                confirmButtonColor: '#1e8081',
                confirmButtonText: 'Entendido',
                timer: 1500,
                timerProgressBar: true
            });
            // Actualizar posiciones absolutas y resetear transformaciones
            document.querySelectorAll('.table-item').forEach(item => {
                const offsetX = parseFloat(item.getAttribute('data-x')) || 0;
                const offsetY = parseFloat(item.getAttribute('data-y')) || 0;
                const currentLeft = parseFloat(item.style.left) || 0;
                const currentTop = parseFloat(item.style.top) || 0;
                
                // Nueva posición absoluta
                const newLeft = currentLeft + offsetX;
                const newTop = currentTop + offsetY;
                
                item.style.left = newLeft + 'px';
                item.style.top = newTop + 'px';
                item.style.transform = '';
                item.setAttribute('data-initial-x', newLeft);
                item.setAttribute('data-initial-y', newTop);
                item.removeAttribute('data-x');
                item.removeAttribute('data-y');
            });

            // Reset fixtures
            document.querySelectorAll('.fixture-item').forEach(item => {
                const offsetX = parseFloat(item.getAttribute('data-x')) || 0;
                const offsetY = parseFloat(item.getAttribute('data-y')) || 0;
                const currentLeft = parseFloat(item.style.left) || 0;
                const currentTop = parseFloat(item.style.top) || 0;
                const newLeft = currentLeft + offsetX;
                const newTop = currentTop + offsetY;

                item.style.left = newLeft + 'px';
                item.style.top = newTop + 'px';
                item.style.transform = '';
                item.setAttribute('data-initial-x', newLeft);
                item.setAttribute('data-initial-y', newTop);
                item.removeAttribute('data-x');
                item.removeAttribute('data-y');
            });
            
            // Recargar la página después de un breve delay para mostrar los cambios
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al guardar: ' + (data.message || 'Error desconocido'),
                confirmButtonColor: '#c94a2d',
                confirmButtonText: 'Entendido'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al guardar el layout',
            confirmButtonColor: '#c94a2d',
            confirmButtonText: 'Entendido'
        });
    });
}

// Inicializar drag and drop si hay mesas
document.addEventListener('DOMContentLoaded', function() {
    const tables = document.querySelectorAll('.table-item');
    const fixtures = document.querySelectorAll('.fixture-item');
    
    if (tables.length > 0 || fixtures.length > 0) {
        // Activar modo edición por defecto si hay mesas
        editMode = true;
        document.getElementById('editModeText').textContent = 'Modo Visualización';
        initDragAndDrop();
    }
    
    // Hacer las mesas clickeables para cambiar estado (clic derecho o doble clic)
    tables.forEach(item => {
        // Clic derecho para cambiar estado
        item.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            if (!this.classList.contains('dragging')) {
                const tableId = this.getAttribute('data-table-id');
                const currentStatus = this.getAttribute('data-table-status');
                const capacity = this.getAttribute('data-table-capacity');
                openChangeStatusModal(tableId, currentStatus, capacity);
            }
        });
        
        // Doble clic para cambiar estado
        item.addEventListener('dblclick', function(e) {
            if (!editMode && !this.classList.contains('dragging')) {
                const tableId = this.getAttribute('data-table-id');
                const currentStatus = this.getAttribute('data-table-status');
                const capacity = this.getAttribute('data-table-capacity');
                openChangeStatusModal(tableId, currentStatus, capacity);
            }
        });
    });
});

// Función para abrir modal de cambio de estado
function openChangeStatusModal(tableId, currentStatus, capacity) {
    const modal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
    const form = document.getElementById('changeStatusForm');
    const statusSelect = document.getElementById('status');
    const waiterContainer = document.getElementById('waiterContainer');
    const waiterSelect = document.getElementById('waiter_id');
    const guestsContainer = document.getElementById('guestsCountContainer');
    const guestsInput = document.getElementById('guests_count');
    const statusHelp = document.getElementById('statusHelp');
    const maxCapacitySpan = document.getElementById('maxCapacity');
    const tableIdInput = document.getElementById('tableId');
    
    // Configurar formulario
    form.action = `/tables/${tableId}/status`;
    tableIdInput.value = tableId;
    maxCapacitySpan.textContent = capacity;
    
    // Configurar opciones según estado actual
    if (currentStatus === 'LIBRE') {
        statusSelect.innerHTML = `
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
        statusSelect.innerHTML = `
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
        statusSelect.innerHTML = `
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
        statusSelect.innerHTML = `
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
    statusSelect.addEventListener('change', function() {
        updateFieldsForStatus(this.value);
    });
    
    // Función para actualizar campos según estado
    function updateFieldsForStatus(status) {
        if (status === 'OCUPADA') {
            waiterContainer.style.display = 'block';
            waiterSelect.required = true;
            guestsContainer.style.display = 'block';
            guestsInput.required = true;
        } else {
            waiterContainer.style.display = 'none';
            waiterSelect.required = false;
            if (status === 'LIBRE' || status === 'CERRADA') {
                guestsContainer.style.display = 'none';
                guestsInput.required = false;
            } else {
                guestsContainer.style.display = 'block';
                guestsInput.required = true;
            }
        }
    }
    
    // Verificar el estado inicial
    setTimeout(() => {
        updateFieldsForStatus(statusSelect.value);
    }, 50);
    
    modal.show();
}

// Manejar el submit del formulario de cambio de estado
document.getElementById('changeStatusForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
            return;
        }
        return response.json().catch(() => ({}));
    })
    .then(data => {
        if (data.success || data.message) {
            // Cerrar el modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('changeStatusModal'));
            modal.hide();
            
            // Actualizar el color y estado de la mesa en el layout
            const tableItem = document.querySelector(`[data-table-id="${formData.get('table_id')}"]`);
            if (tableItem) {
                const newStatus = formData.get('status');
                tableItem.setAttribute('data-table-status', newStatus);
                
                // Actualizar color según estado
                let newColor = '#6c757d'; // CERRADA por defecto
                if (newStatus === 'LIBRE') {
                    newColor = '#28a745';
                } else if (newStatus === 'OCUPADA') {
                    newColor = '#ffc107';
                } else if (newStatus === 'RESERVADA') {
                    newColor = '#17a2b8';
                }
                
                tableItem.style.background = newColor;
                const statusText = tableItem.querySelector('.table-status-text');
                if (statusText) {
                    statusText.textContent = newStatus;
                }
            }
            
            // Mostrar mensaje de éxito
            Swal.fire({
                icon: 'success',
                title: 'Estado Actualizado',
                text: 'El estado de la mesa se ha actualizado correctamente.',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            throw new Error(data.message || 'Error al actualizar el estado');
        }
    })
    .catch(error => {
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
        
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Ocurrió un error al actualizar el estado. Por favor, intenta nuevamente.',
        });
    });
});
</script>
@endpush
@endsection

