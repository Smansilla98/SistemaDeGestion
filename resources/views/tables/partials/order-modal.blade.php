{{--
    Toma de pedido (catálogo + carrito) compartida por Mesas y el Plano.
    Uso: window.TakeOrder.open(urlDelEndpoint, 'Mesa 5').
    Al confirmar dispara el evento `altoque:order-created` en document.
    Requiere $products (agrupados por categoría) y el partial variant-picker.
--}}
@php
    $poRestaurantId = auth()->user()->restaurant_id;
    $poCatalog = $products->map(function ($items, $categoryName) use ($poRestaurantId) {
        return [
            'name' => $categoryName ?: 'Sin categoría',
            'products' => $items->map(function ($product) use ($poRestaurantId) {
                $stock = $product->has_stock ? $product->getCurrentStock($poRestaurantId) : null;

                return [
                    'id' => (int) $product->id,
                    'name' => $product->name,
                    'price' => (float) $product->price,
                    'stock' => $stock,
                    'low' => $stock !== null && $stock > 0 && $stock <= (int) $product->stock_minimum,
                ];
            })->values(),
        ];
    })->values();
@endphp
<style>
    #newOrderModal .modal-content { border: 0; border-radius: 20px; overflow: hidden; }
    #newOrderModal .modal-header { border-bottom: 1px solid #eceef0; padding: .9rem 1.1rem; }
    #newOrderModal .modal-title { font-weight: 800; }
    #newOrderModal .modal-title small { font-weight: 700; color: var(--conurbania-primary); font-size: 1em; }
    #newOrderModal .modal-body { padding: 0; display: flex; min-height: 0; overflow: hidden; }
    .po-catalog { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; border-right: 1px solid #eceef0; min-height: 0; }
    .po-catalog-head { padding: .85rem 1rem .4rem; background: #fff; }
    .po-search { position: relative; display: block; margin: 0; }
    .po-search i { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: #8a9397; }
    .po-search input { width: 100%; border: 1.5px solid #e2e5e7; border-radius: 12px; padding: .65rem .9rem .65rem 2.4rem; font-size: 1rem; }
    .po-search input:focus { outline: none; border-color: var(--conurbania-primary); box-shadow: 0 0 0 3px var(--conurbania-primary-10); }
    .po-chips { display: flex; gap: .45rem; overflow-x: auto; padding: .15rem 0 .25rem; margin-top: .75rem; scrollbar-width: thin; }
    .po-chip { flex: 0 0 auto; border: 1.5px solid #e2e5e7; background: #fff; color: #33403f; border-radius: 999px; padding: .45rem .9rem; font-weight: 700; font-size: .88rem; white-space: nowrap; }
    .po-chip small { color: #8a9397; font-weight: 600; margin-left: .3rem; }
    .po-chip.is-active { background: var(--conurbania-primary); border-color: var(--conurbania-primary); color: #fff; }
    .po-chip.is-active small { color: rgba(255,255,255,.8); }
    .po-scroll { overflow-y: auto; padding: .25rem 1rem 1rem; flex: 1; min-height: 0; }
    .po-cat-title { font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #7b7d84; margin: .9rem 0 .5rem; }
    .po-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .55rem; }
    .po-prod { position: relative; text-align: left; border: 1.5px solid #e6e8ea; background: #fff; border-radius: 14px; padding: .7rem .75rem; min-height: 86px; display: flex; flex-direction: column; justify-content: space-between; gap: .35rem; transition: border-color .12s, transform .12s; }
    .po-prod:hover:not(:disabled) { border-color: var(--conurbania-primary); transform: translateY(-1px); }
    .po-prod:active:not(:disabled) { transform: scale(.98); }
    .po-prod strong { font-size: .92rem; line-height: 1.25; color: #1a2326; }
    .po-prod .po-price { font-weight: 800; color: var(--conurbania-secondary); }
    .po-prod .po-stock { font-size: .72rem; font-weight: 700; color: #94640a; }
    .po-prod:disabled { opacity: .5; background: #f7f7f8; cursor: not-allowed; }
    .po-prod:disabled .po-stock { color: var(--conurbania-accent); }
    .po-prod .po-qty { position: absolute; top: -8px; right: -6px; min-width: 26px; height: 26px; border-radius: 13px; background: var(--conurbania-primary); color: #fff; font-weight: 800; font-size: .8rem; display: none; place-items: center; padding: 0 .4rem; box-shadow: 0 3px 8px rgba(0,0,0,.18); }
    .po-prod.in-cart { border-color: var(--conurbania-primary); background: var(--conurbania-primary-10); }
    .po-prod.in-cart .po-qty { display: grid; }
    .po-cart { flex: 0 0 360px; display: flex; flex-direction: column; background: #fafbfb; min-height: 0; }
    .po-cart-head { padding: .9rem 1rem .5rem; font-weight: 800; display: flex; justify-content: space-between; align-items: center; }
    .po-cart-list { flex: 1; overflow-y: auto; padding: 0 1rem; min-height: 0; }
    .po-line { background: #fff; border: 1px solid #eceef0; border-radius: 12px; padding: .6rem .7rem; margin-bottom: .5rem; }
    .po-line-top { display: flex; justify-content: space-between; gap: .5rem; align-items: flex-start; }
    .po-line-top strong { font-size: .92rem; }
    .po-line-top span { font-weight: 800; white-space: nowrap; }
    .po-line-bottom { display: flex; align-items: center; justify-content: space-between; margin-top: .45rem; gap: .5rem; }
    .po-step { display: inline-flex; align-items: center; border: 1.5px solid #e2e5e7; border-radius: 10px; overflow: hidden; background: #fff; }
    .po-step button { width: 38px; height: 36px; border: 0; background: #fff; font-size: 1.1rem; font-weight: 800; color: #33403f; }
    .po-step button:hover { background: #f2f3f4; }
    .po-step output { min-width: 34px; text-align: center; font-weight: 800; }
    .po-note-btn { border: 0; background: none; color: var(--conurbania-secondary); font-weight: 700; font-size: .82rem; }
    .po-note { margin-top: .45rem; width: 100%; border: 1.5px solid #e2e5e7; border-radius: 9px; padding: .4rem .6rem; font-size: .88rem; }
    .po-empty { text-align: center; color: #8a9397; padding: 2rem 1rem; }
    .po-empty i { font-size: 2rem; display: block; margin-bottom: .4rem; opacity: .6; }
    .po-cart-foot { padding: .8rem 1rem 1rem; border-top: 1px solid #eceef0; background: #fff; }
    .po-cart-foot textarea { width: 100%; border: 1.5px solid #e2e5e7; border-radius: 10px; padding: .5rem .65rem; font-size: .9rem; resize: vertical; }
    .po-total { display: flex; justify-content: space-between; align-items: baseline; margin: .6rem 0; }
    .po-total strong { font-size: 1.5rem; }
    .po-confirm { width: 100%; min-height: 52px; border: 0; border-radius: 13px; font-weight: 800; font-size: 1.05rem; color: #fff; background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary)); }
    .po-confirm:disabled { opacity: .45; }
    .po-mobile-bar { display: none; }
    @media (min-width: 992px) {
        #newOrderModal .modal-content { height: min(86vh, 820px); }
    }
    @media (max-width: 991.98px) {
        .po-cart { position: fixed; inset: auto 0 0 0; max-height: 82vh; z-index: 1065; border-radius: 20px 20px 0 0; box-shadow: 0 -12px 40px rgba(0,0,0,.25); transform: translateY(105%); transition: transform .25s ease; }
        .po-cart.is-open { transform: none; }
        .po-catalog { border-right: 0; }
        .po-scroll { padding-bottom: 96px; }
        .po-mobile-bar { display: flex; position: fixed; left: 0; right: 0; bottom: 0; z-index: 1060; padding: .7rem 1rem calc(.7rem + env(safe-area-inset-bottom)); background: #fff; border-top: 1px solid #eceef0; gap: .6rem; }
        .po-mobile-bar button { min-height: 50px; border-radius: 13px; font-weight: 800; border: 0; }
        .po-mobile-bar .po-see { flex: 1; background: #f0f2f3; color: #1a2326; }
        .po-mobile-bar .po-go { flex: 1.3; color: #fff; background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary)); }
        .po-mobile-bar .po-go:disabled { opacity: .45; }
    }
    @media (max-width: 767.98px) {
        .po-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        #newOrderModal .modal-content { border-radius: 0; }
    }
</style>

<div class="modal fade" id="newOrderModal" tabindex="-1" aria-labelledby="newOrderTitle">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content">
            <form id="newOrderForm" class="d-flex flex-column h-100" style="min-height: 0;">
                <div class="modal-header">
                    <h5 class="modal-title" id="newOrderTitle">Nuevo pedido · <small id="newOrderTableLabel"></small></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="po-catalog">
                        <div class="po-catalog-head">
                            <label class="po-search">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <input type="search" id="productSearch" placeholder="Buscar producto…" autocomplete="off" aria-label="Buscar producto">
                            </label>
                            <div class="po-chips" id="poCats" role="group" aria-label="Categorías"></div>
                        </div>
                        <div class="po-scroll" id="poProducts"></div>
                    </div>
                    <aside class="po-cart" id="poCart" aria-label="Pedido">
                        <div class="po-cart-head">
                            <span><i class="bi bi-receipt"></i> Pedido</span>
                            <button type="button" class="btn-close d-lg-none" id="poCartClose" aria-label="Volver a los productos"></button>
                        </div>
                        <div class="po-cart-list" id="modalItemsList"></div>
                        <div class="po-cart-foot">
                            <textarea id="newOrderObservations" rows="2" placeholder="Nota para todo el pedido (opcional)"></textarea>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="sendToKitchen" checked>
                                <label class="form-check-label small fw-semibold" for="sendToKitchen">Enviar a cocina al confirmar</label>
                            </div>
                            <div class="po-total"><span class="text-muted fw-semibold">Total</span><strong id="modalTotal">$0,00</strong></div>
                            <button type="submit" class="po-confirm" id="confirmOrderBtn" disabled><i class="bi bi-check2-circle"></i> Confirmar pedido</button>
                        </div>
                    </aside>
                    <div class="po-mobile-bar">
                        <button type="button" class="po-see" id="poSeeCart"><i class="bi bi-bag"></i> <span id="poCount">0 ítems</span> · <span id="poBarTotal">$0,00</span></button>
                        <button type="submit" class="po-go" id="poBarConfirm" disabled>Confirmar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="application/json" id="poCatalog">@json($poCatalog)</script>
<script>
window.TakeOrder = (function () {
    const money = (n) => '$' + Number(n || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const $ = (id) => document.getElementById(id);
    const catalog = JSON.parse($('poCatalog').textContent || '[]');
    const productsById = {};
    catalog.forEach((c) => c.products.forEach((p) => { productsById[p.id] = p; }));

    let cart = [];
    let lineSeq = 0;
    let currentCat = 'all';
    let endpoint = null;
    let modal;
    let sending = false;
    const qtyInCart = (id) => cart.filter((l) => l.product_id === id).reduce((a, l) => a + l.quantity, 0);

    function renderCatalog() {
        const q = ($('productSearch').value || '').toLowerCase().trim();
        $('poCats').innerHTML = [`<button type="button" class="po-chip ${currentCat === 'all' ? 'is-active' : ''}" data-cat="all">Todo</button>`]
            .concat(catalog.map((c, i) => `<button type="button" class="po-chip ${currentCat === String(i) ? 'is-active' : ''}" data-cat="${i}">${esc(c.name)}<small>${c.products.length}</small></button>`)).join('');
        const html = catalog.map((c, i) => {
            if (currentCat !== 'all' && currentCat !== String(i)) return '';
            const items = c.products.filter((p) => !q || p.name.toLowerCase().includes(q) || c.name.toLowerCase().includes(q));
            if (!items.length) return '';
            return `<div class="po-cat-title">${esc(c.name)}</div><div class="po-grid">${items.map((p) => {
                const out = p.stock !== null && p.stock <= 0;
                const qty = qtyInCart(p.id);
                const stockTxt = p.stock === null ? '' : (out ? 'Sin stock' : (p.low ? `Quedan ${p.stock}` : ''));
                return `<button type="button" class="po-prod ${qty ? 'in-cart' : ''}" data-product="${p.id}" ${out ? 'disabled' : ''} aria-label="Sumar ${esc(p.name)}">
                    <span class="po-qty">${qty}</span>
                    <strong>${esc(p.name)}</strong>
                    <span class="d-flex justify-content-between align-items-end gap-1"><span class="po-price">${money(p.price)}</span>${stockTxt ? `<span class="po-stock">${stockTxt}</span>` : ''}</span>
                </button>`;
            }).join('')}</div>`;
        }).join('');
        $('poProducts').innerHTML = html || '<div class="po-empty"><i class="bi bi-search"></i>No hay productos con ese nombre.</div>';
    }

    function renderCart() {
        const total = cart.reduce((a, l) => a + l.price * l.quantity, 0);
        const count = cart.reduce((a, l) => a + l.quantity, 0);
        $('modalItemsList').innerHTML = cart.length ? cart.map((l) => `
            <div class="po-line" data-line="${l.id}">
                <div class="po-line-top"><strong>${esc(l.name)}</strong><span>${money(l.price * l.quantity)}</span></div>
                <div class="po-line-bottom">
                    <div class="po-step"><button type="button" data-dec aria-label="Quitar uno">−</button><output>${l.quantity}</output><button type="button" data-inc aria-label="Sumar uno">+</button></div>
                    <button type="button" class="po-note-btn" data-note><i class="bi bi-chat-left-text"></i> ${l.observations ? 'Editar nota' : 'Nota'}</button>
                </div>
                ${l.showNote || l.observations ? `<input type="text" class="po-note" data-note-input value="${esc(l.observations)}" placeholder="Ej: sin sal, bien cocido…">` : ''}
            </div>`).join('') : '<div class="po-empty"><i class="bi bi-bag"></i>Tocá un producto para sumarlo.</div>';
        $('modalTotal').textContent = money(total);
        $('poBarTotal').textContent = money(total);
        $('poCount').textContent = count === 1 ? '1 ítem' : `${count} ítems`;
        $('confirmOrderBtn').disabled = !cart.length || sending;
        $('poBarConfirm').disabled = !cart.length || sending;
        renderCatalog();
    }

    function stockAllows(productId) {
        const p = productsById[productId];
        if (!p || p.stock === null || qtyInCart(productId) + 1 <= p.stock) return true;
        Swal.fire({ icon: 'warning', title: 'Stock insuficiente', html: `Quedan <strong>${p.stock}</strong> de «${esc(p.name)}».`, confirmButtonColor: '#4e8d99' });
        return false;
    }

    async function addProduct(productId) {
        const p = productsById[productId];
        if (!p || !stockAllows(productId)) return;
        const choice = await AlToqueVariants.resolve(p.id, p.name, p.price);
        if (!choice) return;
        const variantId = choice.variantId || null;
        const line = cart.find((l) => l.product_id === p.id && l.variant_id === variantId);
        if (line) line.quantity++;
        else cart.push({ id: ++lineSeq, product_id: p.id, variant_id: variantId, name: choice.name, price: Number(choice.price), quantity: 1, observations: '' });
        renderCart();
    }

    $('poProducts').addEventListener('click', (e) => {
        const b = e.target.closest('[data-product]');
        if (b && !b.disabled) addProduct(Number(b.dataset.product));
    });
    $('poCats').addEventListener('click', (e) => {
        const b = e.target.closest('[data-cat]');
        if (b) { currentCat = b.dataset.cat; renderCatalog(); $('poProducts').scrollTop = 0; }
    });
    $('productSearch').addEventListener('input', () => { currentCat = 'all'; renderCatalog(); });
    $('modalItemsList').addEventListener('click', (e) => {
        const row = e.target.closest('[data-line]');
        const line = row && cart.find((l) => l.id === Number(row.dataset.line));
        if (!line) return;
        if (e.target.closest('[data-inc]')) {
            if (stockAllows(line.product_id)) { line.quantity++; renderCart(); }
        } else if (e.target.closest('[data-dec]')) {
            line.quantity--;
            if (line.quantity <= 0) cart = cart.filter((l) => l !== line);
            renderCart();
        } else if (e.target.closest('[data-note]')) {
            line.showNote = true; renderCart();
            document.querySelector(`[data-line="${line.id}"] [data-note-input]`)?.focus();
        }
    });
    $('modalItemsList').addEventListener('input', (e) => {
        const input = e.target.closest('[data-note-input]');
        const line = input && cart.find((l) => l.id === Number(input.closest('[data-line]').dataset.line));
        if (line) line.observations = input.value;
    });
    $('poSeeCart').addEventListener('click', () => $('poCart').classList.add('is-open'));
    $('poCartClose').addEventListener('click', () => $('poCart').classList.remove('is-open'));

    $('newOrderForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!cart.length || sending || !endpoint) return;
        sending = true;
        renderCart();
        const sendToKitchen = $('sendToKitchen').checked;
        const payload = {
            observations: $('newOrderObservations').value || null,
            send_to_kitchen: sendToKitchen,
            idempotency_key: (window.crypto && crypto.randomUUID) ? crypto.randomUUID().replace(/-/g, '').slice(0, 26) : String(Date.now()) + Math.random().toString(36).slice(2, 10),
            items: cart.map((l) => ({ product_id: l.product_id, variant_id: l.variant_id, quantity: l.quantity, observations: l.observations || null })),
        };

        // Sin 'noopener': con esa opción window.open devuelve null y la ventana
        // no se puede redirigir al ticket ni cerrar si el pedido falla.
        const printWin = sendToKitchen ? window.open('', 'kitchen_print', 'width=450,height=700') : null;
        try { if (printWin) printWin.opener = null; } catch (err) {}

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.message || 'No se pudo crear el pedido');

            if (data.kitchen_ticket_url && printWin && !printWin.closed) {
                printWin.location.href = data.kitchen_ticket_url;
                setTimeout(() => { try { if (!printWin.closed) printWin.close(); } catch (err) {} }, 3500);
            } else if (printWin && !printWin.closed) {
                printWin.close();
            }

            cart = [];
            modal.hide();
            document.dispatchEvent(new CustomEvent('altoque:order-created', { detail: data }));
            Swal.fire({
                icon: data.print_ok === false ? 'warning' : 'success',
                title: data.added_to_existing ? 'Ítems agregados' : 'Pedido enviado',
                html: `<p class="mb-1">Pedido <strong>${esc(data.order_number)}</strong></p>
                       <p class="text-muted small mb-2">${esc(data.message || '')}</p>
                       ${data.print_ok === false ? '<p class="text-warning small mb-2"><i class="bi bi-exclamation-triangle"></i> La impresora falló. Reimprimí desde el botón.</p>' : ''}
                       <div class="d-flex gap-2 justify-content-center flex-wrap">
                         ${data.kitchen_ticket_url ? `<a href="${esc(data.kitchen_ticket_url)}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i> Ticket de cocina</a>` : ''}
                         ${data.comanda_url ? `<a href="${esc(data.comanda_url)}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i class="bi bi-receipt"></i> Comanda</a>` : ''}
                       </div>`,
                confirmButtonColor: '#4e8d99',
                confirmButtonText: 'Listo',
                timer: data.print_ok === false ? undefined : 4000,
                timerProgressBar: true,
            });
        } catch (err) {
            try { if (printWin && !printWin.closed) printWin.close(); } catch (e2) {}
            Swal.fire({ icon: 'error', title: 'No se pudo enviar', text: err.message || 'No se pudo crear el pedido', confirmButtonColor: '#c94a2d' });
        } finally {
            sending = false;
            renderCart();
        }
    });

    return {
        open(url, label) {
            modal = modal || new bootstrap.Modal($('newOrderModal'));
            endpoint = url;
            $('newOrderTableLabel').textContent = label || '';
            $('newOrderObservations').value = '';
            $('sendToKitchen').checked = true;
            $('productSearch').value = '';
            cart = []; currentCat = 'all';
            $('poCart').classList.remove('is-open');
            renderCart();
            modal.show();
        },
    };
})();
</script>
