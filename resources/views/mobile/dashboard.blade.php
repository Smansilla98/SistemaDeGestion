@extends('layouts.mobile')

@section('title', 'Dashboard Mobile')

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <h2 class="h4 mb-1">Hola, {{ $user->name }}</h2>
        <p class="text-muted mb-0 small">Rol: {{ $rol }}</p>
    </div>

    <div class="row g-3">
        <div class="col-6">
            <a href="{{ route('m.pedidos.index') }}" class="text-decoration-none">
                <div class="card bg-dark border-0 text-white h-100">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center" style="min-height: 90px;">
                        <i class="bi bi-clipboard-check mb-2" style="font-size: 1.6rem;"></i>
                        <span class="fw-semibold">Pedidos</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6">
            <a href="{{ route('tables.layout') }}" class="text-decoration-none">
                <div class="card bg-dark border-0 text-white h-100">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center" style="min-height: 90px;">
                        <i class="bi bi-grid-3x3-gap mb-2" style="font-size: 1.6rem;"></i>
                        <span class="fw-semibold">Mesas</span>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

