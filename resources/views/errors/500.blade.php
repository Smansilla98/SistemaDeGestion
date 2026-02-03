@extends('layouts.app')

@section('title', 'Error del servidor')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center align-items-center" style="min-height: 70vh;">
        <div class="col-md-6 text-center">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="bi bi-exclamation-circle" style="font-size: 5rem; color: var(--conurbania-danger);"></i>
                    </div>
                    <h1 class="display-4 fw-bold mb-3" style="color: var(--conurbania-danger);">500</h1>
                    <h2 class="h4 mb-4">Error del servidor</h2>
                    <p class="text-muted mb-4">
                        @if(isset($message))
                            {{ $message }}
                        @else
                            Ha ocurrido un error inesperado. Por favor, intenta nuevamente más tarde.
                        @endif
                    </p>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            <i class="bi bi-house"></i> Volver al inicio
                        </a>
                        <button onclick="window.location.reload()" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Recargar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

