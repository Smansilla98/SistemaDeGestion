@extends('layouts.app')

@section('title', $entrepreneur->business_name)

@php $money = fn ($c) => \App\Services\Finance\ActivityFinanceService::money((int) $c); @endphp

@section('content')
@include('community._head', ['icon' => 'bi-shop-window', 'title' => $entrepreneur->business_name, 'back' => route('market.entrepreneurs.index'),
    'subtitle' => trim(($entrepreneur->contact?->name ?? '').($entrepreneur->payment_alias ? ' · alias '.$entrepreneur->payment_alias : ''), ' ·'),
    'actions' => ($canEdit ? '<a href="'.route('market.entrepreneurs.edit', $entrepreneur).'" class="btn btn-outline-light"><i class="bi bi-pencil"></i> Editar</a> <a href="'.route('market.consignments.create', ['emprendedor' => $entrepreneur->id]).'" class="btn btn-primary"><i class="bi bi-box-arrow-in-down"></i> Recibir consignación</a>' : '')])

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Productos</h5></div>
            <div class="table-responsive"><table class="table mb-0 align-middle">
                <thead><tr><th>Producto</th><th>Precio</th><th>Stock</th></tr></thead>
                <tbody>
                @forelse($products as $p)
                <tr><td><a href="{{ route('market.products.show', $p) }}">{{ $p->name }}</a> <span class="small text-muted">{{ $p->category }}</span></td>
                    <td>{{ $money($p->price_cents) }} / {{ $p->unit }}</td>
                    <td class="{{ $p->isLow() ? 'text-danger fw-bold' : '' }}">{{ \App\Services\Market\MarketStockService::qty($p->stock_quantity) }}</td></tr>
                @empty
                <tr><td colspan="3" class="text-muted text-center py-4">Sin productos. Cargalos desde «Productos y stock».</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Consignaciones</h5></div>
            <ul class="list-group list-group-flush">
            @forelse($consignments as $row)
                <a href="{{ route('market.consignments.show', $row['c']) }}" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span>#{{ $row['c']->id }} · {{ $row['c']->received_on->format('d/m/Y') }} <span class="badge bg-{{ $row['c']->status === 'ABIERTA' ? 'primary' : 'secondary' }}">{{ $row['c']->status }}</span></span>
                    <span class="small">{{ \App\Services\Market\MarketStockService::qty($row['b']['pending_qty']) }} u. en local · se le debe {{ $money($row['b']['owed_cents']) }}</span>
                </a>
            @empty
                <li class="list-group-item text-muted">Sin consignaciones.</li>
            @endforelse
            </ul>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-4"><div class="card-body">
            @if($entrepreneur->description)<p>{{ $entrepreneur->description }}</p>@endif
            @php $brand = $entrepreneur->brand ?? []; @endphp
            @if(! empty($brand['tagline']))<p class="fst-italic">«{{ $brand['tagline'] }}»</p>@endif
            <div class="d-flex gap-2 mb-3">@foreach(($brand['colors'] ?? []) as $c)<div class="text-center small"><div style="width:42px;height:42px;border-radius:8px;background:{{ $c }};border:1px solid #0002"></div>{{ $c }}</div>@endforeach</div>
            @if($canDocs)
            <form method="POST" action="{{ route('market.entrepreneurs.brand-manual', $entrepreneur) }}">@csrf
                <button class="btn btn-primary"><i class="bi bi-file-earmark-pdf"></i> Generar manual de marca (PDF)</button></form>
            <div class="small text-muted mt-1">Si hay un documento «Logo» del emprendedor (PNG/JPG), se incluye.</div>
            @endif
        </div></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between"><h5 class="mb-0">Documentos</h5>
                @if($canDocs)<a href="{{ route('documents.create', ['emprendedor' => $entrepreneur->id]) }}" class="btn btn-sm btn-outline-primary">Subir</a>@endif</div>
            <ul class="list-group list-group-flush">
            @forelse($documents as $d)
                <a href="{{ route('documents.show', $d) }}" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span>{{ $d->title }}</span><span class="small text-muted">{{ \App\Models\Document::CATEGORIES[$d->category] ?? $d->category }} · v{{ $d->versions->max('version') }}</span>
                </a>
            @empty
                <li class="list-group-item text-muted">Sin documentos.</li>
            @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
