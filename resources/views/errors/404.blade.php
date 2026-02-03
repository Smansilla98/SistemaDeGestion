@extends('layouts.app')

@section('title', 'Página no encontrada')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center align-items-center" style="min-height: 70vh;">
        <div class="col-md-6 text-center">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="bi bi-exclamation-triangle" style="font-size: 5rem; color: var(--conurbania-warning);"></i>
                    </div>
                    <h1 class="display-4 fw-bold mb-3" style="color: var(--conurbania-primary);">404</h1>
                    <h2 class="h4 mb-4">Página no encontrada</h2>
                    <p class="text-muted mb-4">
                        La página que estás buscando no existe o ha sido movida.
                    </p>
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-house"></i> Volver al inicio
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

