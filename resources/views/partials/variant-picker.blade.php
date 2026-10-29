{{--
    Selector de variantes para las pantallas de toma de pedidos.
    Un producto con variantes (ej. Pizza) no tiene precio propio: se vende eligiendo una
    (Napolitana, Jamón y morrones…). Expone window.AlToqueVariants.resolve().
--}}
@php
    $variantMap = \App\Models\ProductModifier::query()
        ->where('kind', \App\Models\ProductModifier::KIND_VARIANT)
        ->where('is_active', true)
        ->whereHas('product', fn ($q) => $q->where('restaurant_id', auth()->user()->restaurant_id)->where('is_active', true))
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get(['id', 'product_id', 'name', 'price'])
        ->groupBy('product_id')
        ->map(fn ($group) => $group->map(fn ($v) => [
            'id' => (int) $v->id,
            'name' => $v->name,
            'price' => (float) $v->price,
        ])->values());
@endphp
<script>
window.AlToqueVariants = (function () {
    const map = @json($variantMap);
    const money = (n) => '$' + Number(n).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function has(productId) {
        return Array.isArray(map[productId]) && map[productId].length > 0;
    }

    /**
     * Devuelve {variantId, name, price} listo para el carrito, o null si se canceló.
     * Sin variantes, resuelve de inmediato con el nombre y precio del producto.
     */
    async function resolve(productId, productName, productPrice) {
        if (!has(productId)) {
            return { variantId: null, name: productName, price: productPrice };
        }

        const options = {};
        map[productId].forEach((v) => { options[v.id] = `${v.name} — ${money(v.price)}`; });

        const result = await Swal.fire({
            title: productName,
            text: 'Elegí la variante',
            input: 'radio',
            inputOptions: options,
            inputValidator: (value) => (value ? undefined : 'Elegí una variante para continuar'),
            showCancelButton: true,
            confirmButtonText: 'Agregar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#111111',
            customClass: { input: 'at-variant-options' },
        });

        if (!result.isConfirmed) {
            return null;
        }

        const variant = map[productId].find((v) => String(v.id) === String(result.value));

        return { variantId: variant.id, name: `${productName} · ${variant.name}`, price: variant.price };
    }

    return { has, resolve, options: (productId) => map[productId] || [], money };
})();
</script>
<style>
    .at-variant-options { flex-direction: column; align-items: flex-start !important; gap: 10px; }
    .at-variant-options label { font-size: 1.05rem; }
</style>
