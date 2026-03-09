@extends('layouts.mobile')

@section('title', 'Pedidos Mobile')

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <h2 class="h4 mb-1">Toma de pedidos</h2>
        <p class="text-muted mb-0 small">Usá esta pantalla para cargar pedidos de forma rápida.</p>
    </div>

    <livewire:mobile.toma-pedido />
</div>
@endsection

