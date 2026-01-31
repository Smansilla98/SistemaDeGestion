@extends('layouts.app')

@section('title', 'KDS - Kitchen Display System')

@section('content')
<style>
    .kds-container {
        background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
        min-height: calc(100vh - 70px);
        padding: 2rem;
    }

    .kds-header {
        background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary));
        color: white;
        padding: 1.5rem 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(30, 128, 129, 0.3);
    }

    .kds-header h1 {
        margin: 0;
        font-size: 2rem;
        font-weight: 700;
    }

    .kds-filters {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .kds-filter-btn {
        padding: 0.75rem 1.5rem;
        border: 2px solid var(--conurbania-primary);
        background: white;
        color: var(--conurbania-primary);
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .kds-filter-btn.active {
        background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary));
        color: white;
        border-color: transparent;
        box-shadow: 0 4px 15px rgba(30, 128, 129, 0.3);
    }

    .kds-columns {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .kds-column {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        min-height: 600px;
    }

    .kds-column-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
        border-bottom: 3px solid;
    }

    .kds-column-header.pending {
        border-color: #ffc107;
        color: #856404;
    }

    .kds-column-header.preparing {
        border-color: #fd7e14;
        color: #843505;
    }

    .kds-column-header.ready {
        border-color: #28a745;
        color: #155724;
    }

    .kds-column-header h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .kds-badge {
        background: rgba(0, 0, 0, 0.1);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .kds-order-card {
        background: white;
        border: 3px solid;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .kds-order-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 6px;
        height: 100%;
        background: currentColor;
    }

    .kds-order-card.pending {
        border-color: #ffc107;
        color: #856404;
    }

    .kds-order-card.pending.new {
        animation: pulse 2s infinite;
        border-color: #ff9800;
        box-shadow: 0 0 20px rgba(255, 152, 0, 0.5);
    }

    .kds-order-card.preparing {
        border-color: #fd7e14;
        color: #843505;
    }

    .kds-order-card.ready {
        border-color: #28a745;
        color: #155724;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }

    .kds-order-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid rgba(0, 0, 0, 0.1);
    }

    .kds-order-info h4 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .kds-order-meta {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        text-align: right;
    }

    .kds-time-badge {
        background: rgba(0, 0, 0, 0.1);
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .kds-time-badge.urgent {
        background: #dc3545;
        color: white;
        animation: blink 1s infinite;
    }

    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    .kds-items-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .kds-item {
        background: rgba(0, 0, 0, 0.03);
        border-radius: 12px;
        padding: 1rem;
        border-left: 4px solid;
        transition: all 0.3s ease;
    }

    .kds-item.pending {
        border-left-color: #ffc107;
    }

    .kds-item.preparing {
        border-left-color: #fd7e14;
    }

    .kds-item.ready {
        border-left-color: #28a745;
    }

    .kds-item.entregado {
        border-left-color: #6c757d;
        opacity: 0.6;
    }

    .kds-item-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 0.5rem;
    }

    .kds-item-name {
        font-weight: 700;
        font-size: 1.1rem;
    }

    .kds-item-qty {
        background: rgba(0, 0, 0, 0.1);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-weight: 700;
        font-size: 1rem;
    }

    .kds-item-obs {
        font-size: 0.9rem;
        color: #6c757d;
        font-style: italic;
        margin-top: 0.5rem;
    }

    .kds-item-status {
        margin-top: 0.75rem;
    }

    .kds-status-select {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .kds-status-select.pending {
        border-color: #ffc107;
        background: rgba(255, 193, 7, 0.1);
    }

    .kds-status-select.preparing {
        border-color: #fd7e14;
        background: rgba(253, 126, 20, 0.1);
    }

    .kds-status-select.ready {
        border-color: #28a745;
        background: rgba(40, 167, 69, 0.1);
    }

    .kds-empty {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }

    .kds-empty i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }

    .kds-auto-refresh {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        gap: 1rem;
        z-index: 100;
    }

    .kds-auto-refresh-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #28a745;
        animation: pulse-dot 2s infinite;
    }

    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.2); }
    }

    @media (max-width: 768px) {
        .kds-columns {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="kds-container">
    <div class="kds-header">
        <h1><i class="bi bi-fire"></i> Kitchen Display System (KDS)</h1>
        <p class="mb-0" style="opacity: 0.9;">Gestión de pedidos en tiempo real</p>
    </div>

    <div class="kds-filters">
        <button class="kds-filter-btn active" data-filter="all" onclick="filterKDS('all')">
            <i class="bi bi-list-ul"></i> Todos
        </button>
        <button class="kds-filter-btn" data-filter="pending" onclick="filterKDS('pending')">
            <i class="bi bi-clock"></i> Pendientes
        </button>
        <button class="kds-filter-btn" data-filter="preparing" onclick="filterKDS('preparing')">
            <i class="bi bi-gear"></i> En Preparación
        </button>
        <button class="kds-filter-btn" data-filter="ready" onclick="filterKDS('ready')">
            <i class="bi bi-check-circle"></i> Listos
        </button>
    </div>

    <div class="kds-columns">
        <!-- Columna: Pendientes -->
        <div class="kds-column" data-column="pending">
            <div class="kds-column-header pending">
                <h3><i class="bi bi-clock-history"></i> Pendientes</h3>
                <span class="kds-badge" id="pending-count">0</span>
            </div>
            <div class="kds-orders-container" id="pending-orders">
                @if(isset($orders['ENVIADO']) && $orders['ENVIADO']->count() > 0)
                    @foreach($orders['ENVIADO']->sortBy('sent_at') as $order)
                        @include('kitchen.partials.order-card', ['order' => $order, 'status' => 'pending'])
                    @endforeach
                @else
                    <div class="kds-empty">
                        <i class="bi bi-inbox"></i>
                        <p>No hay pedidos pendientes</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Columna: En Preparación -->
        <div class="kds-column" data-column="preparing">
            <div class="kds-column-header preparing">
                <h3><i class="bi bi-gear-fill"></i> En Preparación</h3>
                <span class="kds-badge" id="preparing-count">0</span>
            </div>
            <div class="kds-orders-container" id="preparing-orders">
                @if(isset($orders['EN_PREPARACION']) && $orders['EN_PREPARACION']->count() > 0)
                    @foreach($orders['EN_PREPARACION']->sortBy('sent_at') as $order)
                        @include('kitchen.partials.order-card', ['order' => $order, 'status' => 'preparing'])
                    @endforeach
                @else
                    <div class="kds-empty">
                        <i class="bi bi-inbox"></i>
                        <p>No hay pedidos en preparación</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Columna: Listos -->
        <div class="kds-column" data-column="ready">
            <div class="kds-column-header ready">
                <h3><i class="bi bi-check-circle-fill"></i> Listos</h3>
                <span class="kds-badge" id="ready-count">0</span>
            </div>
            <div class="kds-orders-container" id="ready-orders">
                @if(isset($orders['LISTO']) && $orders['LISTO']->count() > 0)
                    @foreach($orders['LISTO']->sortBy('sent_at') as $order)
                        @include('kitchen.partials.order-card', ['order' => $order, 'status' => 'ready'])
                    @endforeach
                @else
                    <div class="kds-empty">
                        <i class="bi bi-inbox"></i>
                        <p>No hay pedidos listos</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="kds-auto-refresh">
        <div class="kds-auto-refresh-indicator"></div>
        <span>Actualización automática activa</span>
    </div>
</div>

@push('scripts')
<script>
let lastUpdateTime = Date.now();
let autoRefreshInterval;

// Calcular tiempo transcurrido
function getElapsedTime(sentAt) {
    if (!sentAt) return '0 min';
    const sent = new Date(sentAt);
    const now = new Date();
    const diff = Math.floor((now - sent) / 60000); // minutos
    if (diff < 1) return '< 1 min';
    if (diff < 60) return `${diff} min`;
    const hours = Math.floor(diff / 60);
    const mins = diff % 60;
    return `${hours}h ${mins}m`;
}

// Actualizar contadores
function updateCounters() {
    const pending = document.querySelectorAll('[data-column="pending"] .kds-order-card').length;
    const preparing = document.querySelectorAll('[data-column="preparing"] .kds-order-card').length;
    const ready = document.querySelectorAll('[data-column="ready"] .kds-order-card').length;
    
    document.getElementById('pending-count').textContent = pending;
    document.getElementById('preparing-count').textContent = preparing;
    document.getElementById('ready-count').textContent = ready;
}

// Filtrar por estado
function filterKDS(filter) {
    document.querySelectorAll('.kds-filter-btn').forEach(btn => btn.classList.remove('active'));
    event.target.closest('.kds-filter-btn').classList.add('active');
    
    document.querySelectorAll('.kds-column').forEach(col => {
        if (filter === 'all') {
            col.style.display = '';
        } else {
            col.style.display = col.dataset.column === filter ? '' : 'none';
        }
    });
}

// Actualizar estado de item
document.addEventListener('change', async function(e) {
    if (!e.target.classList.contains('kds-status-select')) return;
    
    const select = e.target;
    const form = select.closest('form');
    const itemId = form.dataset.itemId;
    const newStatus = select.value;
    const itemName = form.dataset.itemName || 'Item';
    const tableName = form.dataset.tableName || 'Mesa';
    
    // Deshabilitar select mientras se procesa
    select.disabled = true;
    
    try {
        const res = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({ status: newStatus }).toString(),
        });

        if (!res.ok) {
            throw new Error('No se pudo actualizar el estado');
        }

        const data = await res.json();
        
        // Actualizar clase del item
        const itemCard = select.closest('.kds-item');
        itemCard.className = `kds-item ${newStatus.toLowerCase()}`;
        select.className = `kds-status-select ${newStatus.toLowerCase()}`;

        // Mostrar toast según estado
        if (newStatus === 'ENTREGADO') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `✅ ${itemName} entregado en Mesa ${tableName}`,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                background: '#e6ffed',
                color: '#1e8081',
                iconColor: '#1e8081',
            });
            
            // Ocultar item entregado con animación
            setTimeout(() => {
                itemCard.style.opacity = '0';
                itemCard.style.transform = 'translateX(-20px)';
                setTimeout(() => itemCard.remove(), 300);
            }, 1000);
        } else {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `Estado actualizado: ${itemName}`,
                text: `Nuevo estado: ${newStatus}`,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                background: '#e6ffed',
                color: '#1e8081',
                iconColor: '#1e8081',
            });
        }
        
        updateCounters();
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo actualizar el estado del item.',
            confirmButtonColor: '#c94a2d',
        });
        // Revertir selección
        select.value = select.dataset.previousValue;
    } finally {
        select.disabled = false;
    }
});

// Auto-refresh cada 30 segundos (solo si no hay interacción reciente)
function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        const timeSinceLastUpdate = Date.now() - lastUpdateTime;
        if (timeSinceLastUpdate > 25000) { // 25 segundos sin interacción
            location.reload();
        }
    }, 30000);
}

// Marcar interacción
document.addEventListener('mousemove', () => lastUpdateTime = Date.now());
document.addEventListener('click', () => lastUpdateTime = Date.now());
document.addEventListener('keypress', () => lastUpdateTime = Date.now());

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    updateCounters();
    startAutoRefresh();
    
    // Actualizar tiempos transcurridos cada minuto
    setInterval(() => {
        document.querySelectorAll('.kds-time-badge').forEach(badge => {
            const sentAt = badge.dataset.sentAt;
            if (sentAt) {
                badge.textContent = getElapsedTime(sentAt);
                const minutes = Math.floor((new Date() - new Date(sentAt)) / 60000);
                if (minutes > 15) {
                    badge.classList.add('urgent');
                }
            }
        });
    }, 60000);
    
    // Marcar pedidos nuevos (menos de 2 minutos)
    document.querySelectorAll('.kds-order-card').forEach(card => {
        const sentAt = card.dataset.sentAt;
        if (sentAt) {
            const minutes = Math.floor((new Date() - new Date(sentAt)) / 60000);
            if (minutes < 2) {
                card.classList.add('new');
            }
        }
    });
});
</script>
@endpush
@endsection
