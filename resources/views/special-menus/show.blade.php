@extends('layouts.app')

@section('title', $menu->name)

@php
    $inMenu = $menu->items->keyBy('product_id');
    $money = fn ($v) => '$'.number_format((float) $v, 2, ',', '.');
@endphp

@section('content')
@include('community._head', ['icon' => 'bi-stars', 'title' => $menu->name, 'back' => route('special-menus.index'),
    'subtitle' => $menu->starts_on->format('d/m/Y').($menu->ends_on->ne($menu->starts_on) ? ' al '.$menu->ends_on->format('d/m/Y') : '').' · '.($menu->exclusive ? 'sólo se venden estos productos' : 'la carta sigue completa; estos usan el precio del menú')])

<div class="row g-4">
    <div class="col-xl-7">
        @if($canEdit)
        <form method="POST" action="{{ route('special-menus.items', $menu) }}" class="card">@csrf
            <div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">Productos del menú</h5><span class="badge bg-secondary">{{ $menu->items->count() }}</span></div>
            <div class="table-responsive" style="max-height: 620px"><table class="table table-sm mb-0 align-middle">
                <thead class="sticky-top"><tr><th></th><th>Producto</th><th class="text-end">Carta</th><th style="width: 120px">Precio menú</th><th style="width: 100px">Previsto</th></tr></thead>
                <tbody>
                @forelse($products as $category => $list)
                    <tr class="table-light"><td colspan="5" class="small fw-semibold">{{ $category }}</td></tr>
                    @foreach($list as $p)
                    @php $it = $inMenu->get($p->id); @endphp
                    <tr>
                        <td><input type="checkbox" class="form-check-input" name="include[{{ $p->id }}]" value="1" @checked($it)></td>
                        <td>{{ $p->name }}</td>
                        <td class="text-end small text-muted">{{ $money($p->price) }}</td>
                        <td><input type="number" step="0.01" min="0" name="price[{{ $p->id }}]" class="form-control form-control-sm" value="{{ $it?->price }}" placeholder="igual"></td>
                        <td><input type="number" min="0" name="planned[{{ $p->id }}]" class="form-control form-control-sm" value="{{ $it?->planned_quantity }}" placeholder="—"></td>
                    </tr>
                    @endforeach
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No hay productos activos en la carta.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div class="card-footer d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <span class="small text-muted">Precio vacío = precio de carta. «Previsto»: cuántos pensás vender, para calcular el stock.</span>
                <button class="btn btn-primary btn-sm">Guardar productos</button>
            </div>
        </form>
        @else
        <div class="card"><div class="card-header"><h5 class="mb-0">Productos del menú</h5></div><ul class="list-group list-group-flush">
            @forelse($menu->items as $it)
            <li class="list-group-item d-flex justify-content-between"><span>{{ $it->product?->name }}</span><span>{{ $money($it->price ?? $it->product?->price) }}@if($it->planned_quantity) · previsto {{ $it->planned_quantity }}@endif</span></li>
            @empty<li class="list-group-item text-muted">Sin productos.</li>@endforelse
        </ul></div>
        @endif
    </div>

    <div class="col-xl-5">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Stock para lo previsto</h5></div>
            <div class="table-responsive"><table class="table table-sm mb-0 small align-middle">
                <thead><tr><th>Insumo / producto</th><th class="text-end">Hace falta</th><th class="text-end">Hay</th><th class="text-end">Falta</th></tr></thead>
                <tbody>
                @forelse($plan as $r)
                <tr>
                    <td>{{ $r['product']->name }}<div class="text-muted">para {{ implode(', ', array_unique($r['for'])) }}</div></td>
                    <td class="text-end">{{ $r['needed'] }} {{ $r['unit'] }}</td>
                    <td class="text-end">{{ $r['available'] }} {{ $r['unit'] }}</td>
                    <td class="text-end fw-semibold {{ $r['missing'] > 0 ? 'text-danger' : 'text-success' }}">{{ $r['missing'] > 0 ? $r['missing'].' '.$r['unit'] : 'Alcanza' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Cargá cantidades previstas para ver qué stock hace falta. Se calcula con las recetas (insumos) o con el stock del producto.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            @if($plan->where('missing', '>', 0)->isNotEmpty())
            <div class="card-footer small"><i class="bi bi-cart"></i> Para comprar: {{ $plan->where('missing', '>', 0)->map(fn ($r) => $r['product']->name.' ('.$r['missing'].' '.$r['unit'].')')->implode(', ') }}. <a href="{{ route('stock.index') }}">Ir a stock</a></div>
            @endif
        </div>

        @if($canEdit)
        <div class="card"><div class="card-header"><h6 class="mb-0">Datos del menú</h6></div><div class="card-body">
            <form method="POST" action="{{ route('special-menus.update', $menu) }}" class="row g-2">@csrf @method('PUT')
                <div class="col-12"><input name="name" class="form-control" value="{{ old('name', $menu->name) }}" required></div>
                <div class="col-6"><label class="form-label small">Desde</label><input type="date" name="starts_on" class="form-control" value="{{ $menu->starts_on->format('Y-m-d') }}" required></div>
                <div class="col-6"><label class="form-label small">Hasta</label><input type="date" name="ends_on" class="form-control" value="{{ $menu->ends_on->format('Y-m-d') }}" required></div>
                <div class="col-12"><textarea name="notes" class="form-control" rows="2" placeholder="Notas">{{ $menu->notes }}</textarea></div>
                <div class="col-12 form-check ms-1"><input type="checkbox" class="form-check-input" name="exclusive" value="1" id="exclusive" @checked($menu->exclusive)><label class="form-check-label small" for="exclusive">Sólo se venden los productos del menú</label></div>
                <div class="col-12 form-check ms-1"><input type="checkbox" class="form-check-input" name="in_agenda" value="1" id="in_agenda" @checked($menu->agenda_notice_id)><label class="form-check-label small" for="in_agenda">Avisar en la agenda</label></div>
                <div class="col-12 d-flex justify-content-between"><button class="btn btn-primary btn-sm">Guardar</button></div>
            </form>
            <form method="POST" action="{{ route('special-menus.destroy', $menu) }}" class="text-end mt-2" onsubmit="return confirm('¿Eliminar el menú?')">@csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm">Eliminar</button></form>
        </div></div>
        @endif
    </div>
</div>
@endsection
