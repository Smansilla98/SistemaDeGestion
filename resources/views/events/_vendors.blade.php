{{-- Quién vende qué en el evento: la casa (con su stock) y los puestos que dejan un porcentaje. --}}
@php
    $canVendors = app(\App\Services\PermissionService::class)->allowed(auth()->user(), 'events.update');
    $vendors = $event->vendors()->with('entrepreneur')->get();
    $categories = \App\Models\EventVendor::CATEGORIES;
    $house = $event->house_sells ?? [];
    $vMoney = fn ($c) => \App\Services\Finance\ActivityFinanceService::money((int) $c);
    $vPct = fn ($p) => rtrim(rtrim(number_format((float) $p, 2, ',', ''), '0'), ',').'%';
    $vEntrepreneurs = $canVendors ? \App\Models\Entrepreneur::query()->where('restaurant_id', $event->restaurant_id)->where('is_active', true)->orderBy('business_name')->get(['id', 'business_name']) : collect();
    $totalCommission = $vendors->sum(fn ($v) => $v->commissionCents());
    $totalCollected = $vendors->sum('collected_cents');
@endphp
@if($canVendors || $vendors->isNotEmpty() || $house !== [])
<div class="card mt-4" id="puestos">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-shop"></i> Quién vende qué</h5>
        <div class="small text-muted">Lo que vende la casa sale de su stock. Los puestos venden lo suyo y le dejan un porcentaje al espacio: no se carga stock de eso.</div>
    </div>
    <div class="card-body border-bottom">
        @if($canVendors)
        <form method="POST" action="{{ route('events.house-sells', $event) }}" class="d-flex flex-wrap align-items-center gap-3">
            @csrf @method('PUT')
            <strong>La casa vende:</strong>
            @foreach($categories as $key => $label)
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="house_sells[]" value="{{ $key }}" id="house_{{ $key }}" @checked(in_array($key, $house, true))>
                    <label class="form-check-label" for="house_{{ $key }}">{{ $label }}</label>
                </div>
            @endforeach
            <button class="btn btn-sm btn-outline-primary">Guardar</button>
        </form>
        @else
            <strong>La casa vende:</strong> {{ collect($house)->map(fn ($k) => $categories[$k] ?? $k)->implode(', ') ?: '—' }}
        @endif
    </div>
    <div class="table-responsive"><table class="table mb-0 align-middle small">
        <thead><tr><th>Puesto</th><th>Rubro</th><th class="text-end">% para el espacio</th><th style="min-width: 190px">Vendió</th><th class="text-end">Le toca al espacio</th><th class="text-end">Cobrado</th><th class="text-end">Falta cobrar</th>@if($canVendors)<th></th>@endif</tr></thead>
        <tbody>
        @forelse($vendors as $v)
        <tr>
            <td>
                @if($v->entrepreneur_id)<a href="{{ route('market.entrepreneurs.show', $v->entrepreneur_id) }}">{{ $v->name }}</a>@else{{ $v->name }}@endif
                @if($v->notes)<div class="text-muted">{{ $v->notes }}</div>@endif
            </td>
            <td>{{ $categories[$v->category] ?? $v->category }}</td>
            <td class="text-end">{{ $vPct($v->percent) }}</td>
            <td>
                @if($canVendors)
                <form method="POST" action="{{ route('events.vendors.update', [$event, $v]) }}" class="d-flex gap-1">@csrf @method('PUT')
                    <input type="hidden" name="entrepreneur_id" value="{{ $v->entrepreneur_id }}"><input type="hidden" name="name" value="{{ $v->name }}">
                    <input type="hidden" name="category" value="{{ $v->category }}"><input type="hidden" name="percent" value="{{ $v->percent }}"><input type="hidden" name="notes" value="{{ $v->notes }}">
                    <input name="sales" class="form-control form-control-sm" placeholder="$ total vendido" value="{{ $v->sales_cents !== null ? number_format($v->sales_cents / 100, 2, ',', '.') : '' }}" aria-label="Total vendido por {{ $v->name }}">
                    <button class="btn btn-sm btn-outline-secondary" title="Guardar"><i class="bi bi-check2"></i></button>
                </form>
                @else
                    {{ $v->sales_cents !== null ? $vMoney($v->sales_cents) : '—' }}
                @endif
            </td>
            <td class="text-end">{{ $v->sales_cents !== null ? $vMoney($v->commissionCents()) : '—' }}</td>
            <td class="text-end">{{ $vMoney($v->collected_cents) }}</td>
            <td class="text-end fw-bold">{{ $vMoney($v->pendingCents()) }}</td>
            @if($canVendors)<td class="text-end text-nowrap">
                @if($v->pendingCents() > 0)
                <form method="POST" action="{{ route('events.vendors.collect', [$event, $v]) }}" class="d-inline" onsubmit="return confirm('¿Registrar que cobraste {{ $vMoney($v->pendingCents()) }} de {{ $v->name }}?')">@csrf
                    <button class="btn btn-sm btn-success"><i class="bi bi-cash-coin"></i> Cobrar</button></form>
                @endif
                @if($v->collected_cents === 0)
                <form method="POST" action="{{ route('events.vendors.destroy', [$event, $v]) }}" class="d-inline" onsubmit="return confirm('¿Quitar el puesto?')">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-link text-danger p-0 ms-1" aria-label="Quitar {{ $v->name }}"><i class="bi bi-trash"></i></button></form>
                @endif
            </td>@endif
        </tr>
        @empty
        <tr><td colspan="8" class="text-muted text-center py-3">Sin puestos. Sumá a los emprendedores que vienen a vender (comida típica, parrilla…) con el porcentaje que arreglaste.</td></tr>
        @endforelse
        </tbody>
        @if($vendors->isNotEmpty())
        <tfoot><tr class="fw-bold"><td colspan="4">Total de los puestos</td><td class="text-end">{{ $vMoney($totalCommission) }}</td><td class="text-end">{{ $vMoney($totalCollected) }}</td><td class="text-end">{{ $vMoney(max(0, $totalCommission - $totalCollected)) }}</td>@if($canVendors)<td></td>@endif</tr></tfoot>
        @endif
    </table></div>
    @if($canVendors)
    <div class="card-body border-top">
        <form method="POST" action="{{ route('events.vendors.store', $event) }}" class="row g-2">@csrf
            <div class="col-md-3"><select name="entrepreneur_id" class="form-select" aria-label="Emprendedor"><option value="">Emprendedor…</option>@foreach($vEntrepreneurs as $e)<option value="{{ $e->id }}">{{ $e->business_name }}</option>@endforeach</select></div>
            <div class="col-md-3"><input name="name" class="form-control" placeholder="…o nombre (ej. Parrillero Juan)" aria-label="Nombre del puesto"></div>
            <div class="col-md-2"><select name="category" class="form-select" aria-label="Rubro">@foreach($categories as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2"><div class="input-group"><input type="number" name="percent" step="0.01" min="0" max="100" class="form-control" placeholder="%" required aria-label="Porcentaje para el espacio"><span class="input-group-text">%</span></div></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Agregar puesto</button></div>
        </form>
        <div class="small text-muted mt-1">Al terminar el evento anotá cuánto vendió cada puesto y tocá «Cobrar»: la comisión queda como ingreso del evento.</div>
    </div>
    @endif
</div>
@endif
