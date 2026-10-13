@extends('layouts.auth')

@section('title', 'Elegir sucursal')
@section('subtitle', '¿En qué sucursal vas a trabajar?')

@section('content')
    @error('restaurant_id')
        <div class="alert alert-danger py-2 small">{{ $message }}</div>
    @enderror

    <form action="{{ route('branch.store') }}" method="POST" class="d-grid gap-2">
        @csrf
        @foreach($branches as $branch)
            <button type="submit" name="restaurant_id" value="{{ $branch->id }}"
                    class="btn text-start p-3 border rounded-3 {{ $currentId === (int) $branch->id ? 'border-primary bg-light' : 'bg-white' }}">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-shop fs-3 text-secondary"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">
                            {{ $branch->name }}
                            @if($branch->parent_id === null)
                                <span class="badge bg-secondary ms-1">Casa matriz</span>
                            @endif
                        </div>
                        @if($branch->address)
                            <div class="small text-muted">{{ $branch->address }}</div>
                        @endif
                    </div>
                    @if($currentId === (int) $branch->id)
                        <span class="badge bg-primary">Actual</span>
                    @else
                        <i class="bi bi-chevron-right text-muted"></i>
                    @endif
                </div>
            </button>
        @endforeach
    </form>

    <form action="{{ route('logout') }}" method="POST" class="mt-3 text-center">
        @csrf
        <button type="submit" class="btn btn-link btn-sm text-muted">Cerrar sesión</button>
    </form>
@endsection
