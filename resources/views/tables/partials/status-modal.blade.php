{{--
    Cambio de estado de mesa compartido por Mesas y el Plano.
    Uso: window.openChangeStatusModal(tableId, estadoActual, capacidad, estadoSugerido).
    Al guardar, el servidor vuelve a la pantalla desde la que se abrió (return_to).
    Requiere $waiters.
--}}
<style>
    #changeStatusModal .modal-content { border-radius: 18px; border: 0; }
    .st-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: .5rem; }
    .st-options input { position: absolute; opacity: 0; pointer-events: none; }
    .st-options label { border: 2px solid #e2e5e7; border-radius: 12px; padding: .7rem; text-align: center; font-weight: 800; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: .3rem; }
    .st-options input:checked + label { border-color: var(--conurbania-primary); background: var(--conurbania-primary-10); }
    .st-options input:focus-visible + label { outline: 3px solid var(--conurbania-primary-30); }
</style>
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusTitle">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="changeStatusForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="changeStatusTitle">Estado de la mesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="st-options mb-2" id="statusOptions"></div>
                    <p class="small text-muted mb-3" id="statusHelp"></p>
                    <div class="mb-3" id="waiterContainer" style="display: none;">
                        <label for="waiter_id" class="form-label">Mozo que atiende <span class="text-danger">*</span></label>
                        <select class="form-select" id="waiter_id" name="waiter_id">
                            <option value="">Elegí un mozo…</option>
                            @foreach($waiters as $waiter)
                            <option value="{{ $waiter->id }}" @selected($waiter->id === auth()->id())>{{ $waiter->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1" id="guestsCountContainer">
                        <label for="guests_count" class="form-label">Personas</label>
                        <input type="number" class="form-control" id="guests_count" name="guests_count" min="1" value="1">
                        <small class="text-muted">Hasta <span id="maxCapacity">0</span></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const INFO = {
        LIBRE: { label: 'Libre', icon: 'bi-circle', help: 'Libera la mesa. Si tenía un pedido abierto, cobralo antes desde «Cobrar y cerrar».' },
        OCUPADA: { label: 'Ocupada', icon: 'bi-people-fill', help: 'Sienta a los clientes y asigna el mozo que los atiende.' },
        RESERVADA: { label: 'Reservada', icon: 'bi-calendar-check', help: 'La mesa queda apartada; al llegar los clientes, pasala a Ocupada.' },
        CERRADA: { label: 'Cerrada', icon: 'bi-slash-circle', help: 'La mesa no se usa por ahora.' },
    };
    const ALLOWED = { LIBRE: ['OCUPADA', 'RESERVADA'], OCUPADA: ['LIBRE', 'CERRADA'], RESERVADA: ['OCUPADA', 'LIBRE'], CERRADA: ['LIBRE', 'OCUPADA', 'RESERVADA'] };

    window.openChangeStatusModal = function (tableId, currentStatus, capacity, target) {
        const modalEl = document.getElementById('changeStatusModal');
        const form = document.getElementById('changeStatusForm');
        const guests = document.getElementById('guests_count');
        form.action = `/tables/${tableId}/status`;
        document.getElementById('maxCapacity').textContent = `${capacity} ${Number(capacity) === 1 ? 'lugar' : 'lugares'}`;
        guests.max = capacity; guests.value = 1;
        const options = ALLOWED[currentStatus] || Object.keys(INFO);
        const chosen = options.includes(target) ? target : options[0];
        document.getElementById('statusOptions').innerHTML = options.map((s) => `
            <div><input type="radio" name="status" id="st_${s}" value="${s}" ${s === chosen ? 'checked' : ''}>
            <label for="st_${s}"><i class="bi ${INFO[s].icon}"></i>${INFO[s].label}</label></div>`).join('');
        const sync = () => {
            const s = form.querySelector('input[name="status"]:checked')?.value;
            const needsGuests = s === 'OCUPADA' || s === 'RESERVADA';
            document.getElementById('guestsCountContainer').style.display = needsGuests ? '' : 'none';
            guests.required = needsGuests;
            if (!needsGuests) guests.value = 0; else if (!Number(guests.value)) guests.value = 1;
            const needsWaiter = s === 'OCUPADA' && currentStatus !== 'OCUPADA';
            document.getElementById('waiterContainer').style.display = needsWaiter ? '' : 'none';
            document.getElementById('waiter_id').required = needsWaiter;
            document.getElementById('statusHelp').textContent = INFO[s]?.help || '';
        };
        form.querySelectorAll('input[name="status"]').forEach((r) => r.addEventListener('change', sync));
        sync();
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    };

    document.addEventListener('click', (e) => {
        const b = e.target.closest('.js-status');
        if (b) window.openChangeStatusModal(b.dataset.tableId, b.dataset.status, Number(b.dataset.capacity), b.dataset.targetStatus);
    });
})();
</script>
