@extends('layouts.app')

@section('title', 'Recibir consignación')

@section('content')
@include('community._head', ['icon' => 'bi-box-arrow-in-down', 'title' => 'Recibir mercadería en consignación', 'back' => route('market.consignments.index')])

@if($entrepreneurs->isEmpty())
<div class="alert alert-info">Primero cargá un emprendedor y sus productos.</div>
@else
<form method="GET" class="mb-3 d-flex gap-2" style="max-width: 520px">
    <select name="emprendedor" class="form-select" onchange="this.form.submit()">
        <option value="">Elegí el emprendedor</option>
        @foreach($entrepreneurs as $e)<option value="{{ $e->id }}" @selected($selected === $e->id)>{{ $e->business_name }}</option>@endforeach
    </select>
</form>
@php $current = $entrepreneurs->firstWhere('id', $selected); @endphp
@if($current)
<form method="POST" action="{{ route('market.consignments.store') }}">
    @csrf
    <input type="hidden" name="entrepreneur_id" value="{{ $current->id }}">
    <div class="card mb-3"><div class="card-body row g-3">
        <div class="col-md-3"><label class="form-label">Fecha de recepción</label><input type="date" name="received_on" class="form-control" value="{{ today()->toDateString() }}" required></div>
        <div class="col-md-3"><label class="form-label">Comisión del espacio (%)</label><input type="number" step="0.01" min="0" max="100" name="commission_percent" class="form-control" value="{{ old('commission_percent', 30) }}" required></div>
        <div class="col-md-6"><label class="form-label">Notas</label><input name="notes" class="form-control"></div>
    </div></div>
    <div class="card mb-3"><div class="table-responsive"><table class="table mb-0 align-middle">
        <thead><tr><th>Producto</th><th style="width: 160px">Cantidad</th><th style="width: 180px">Precio de venta</th></tr></thead>
        <tbody>
        @forelse($current->products as $i => $p)
        <tr><td>{{ $p->name }} <span class="small text-muted">({{ $p->unit }})</span><input type="hidden" name="items[{{ $i }}][market_product_id]" value="{{ $p->id }}"></td>
            <td><input type="number" step="0.001" min="0" name="items[{{ $i }}][quantity]" class="form-control" placeholder="0"></td>
            <td><input name="items[{{ $i }}][price]" class="form-control" placeholder="{{ number_format($p->price_cents / 100, 2, ',', '.') }}"></td></tr>
        @empty
        <tr><td colspan="3" class="text-muted">Este emprendedor no tiene productos activos. Cargalos en «Productos y stock».</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    @if($current->products->isNotEmpty())<button class="btn btn-primary">Registrar consignación</button>@endif
</form>
@endif
@endif
@endsection
