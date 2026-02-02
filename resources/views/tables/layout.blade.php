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
        @endphp
        <div id="layoutCanvas" style="position: relative; width: 100%; min-height: 600px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; overflow: hidden;">
            @if($isSalon)
                <!-- Barra fija (4 lugares) - lado izquierdo -->
                <div class="bar-area">
                    <div class="bar-title">
                        <i class="bi bi-cup-straw"></i> Barra
                        <small class="text-muted d-block">4 lugares</small>
                    </div>
                    <div class="bar-seats">
                        @for($i = 1; $i <= 4; $i++)
                            <div class="bar-seat" title="Lugar {{ $i }}">{{ $i }}</div>
                        @endfor
                    </div>
                </div>

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
                <div style="font-size: 9px; margin-top: 2px;">{{ $table->status }}</div>
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

/* Barra fija (salón) */
.bar-area {
    position: absolute;
    left: 12px;
    top: 12px;
    bottom: 12px;
    width: 140px;
    background: #ffffff;
    border: 2px solid rgba(0,0,0,0.15);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    z-index: 5;
    display: flex;
    flex-direction: column;
    padding: 10px;
}
.bar-title {
    font-weight: 800;
    color: #262c3b;
    text-align: center;
    border-bottom: 1px solid rgba(0,0,0,0.08);
    padding-bottom: 8px;
    margin-bottom: 10px;
}
.bar-seats {
    display: flex;
    flex-direction: column;
    gap: 10px;
    justify-content: flex-start;
    align-items: center;
    flex: 1;
}
.bar-seat {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e8081, #22565e);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    box-shadow: 0 6px 16px rgba(30, 128, 129, 0.25);
    user-select: none;
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
    
    // Hacer las mesas clickeables para ver detalles (solo en modo visualización)
    tables.forEach(item => {
        item.addEventListener('click', function(e) {
            if (!editMode && !this.classList.contains('dragging')) {
                const tableId = this.getAttribute('data-table-id');
                window.location.href = `{{ route('orders.create') }}/${tableId}`;
            }
        });
    });
});
</script>
@endpush
@endsection

