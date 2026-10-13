@extends('layouts.app')

@section('title', 'Sucursales')

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-shop"></i> Sucursales del grupo</h5>
                <span class="badge bg-secondary">{{ $branches->count() }}</span>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Cada sucursal tiene sus propias mesas, pedidos, caja y stock. Los administradores eligen sucursal al iniciar sesión;
                    el resto del personal entra directo a la suya.
                </p>

                @foreach($branches as $branch)
                    <form action="{{ route('branches.update', $branch) }}" method="POST" class="border rounded-3 p-3 mb-3">
                        @csrf
                        @method('PUT')
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                @if($branch->parent_id === null)
                                    <span class="badge bg-secondary">Casa matriz</span>
                                @endif
                                @if((int) $branch->id === (int) $current->id)
                                    <span class="badge bg-primary">Operando acá</span>
                                @endif
                                @unless($branch->is_active)
                                    <span class="badge bg-danger">Inactiva</span>
                                @endunless
                            </div>
                            <span class="small text-muted">{{ $branch->users_count }} usuario(s)</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Nombre</label>
                                <input type="text" name="name" class="form-control form-control-sm" value="{{ $branch->name }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Dirección</label>
                                <input type="text" name="address" class="form-control form-control-sm" value="{{ $branch->address }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Teléfono</label>
                                <input type="text" name="phone" class="form-control form-control-sm" value="{{ $branch->phone }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end justify-content-between">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" class="form-check-input" name="is_active" value="1" id="active-{{ $branch->id }}" {{ $branch->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="active-{{ $branch->id }}">Activa</label>
                                </div>
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar"><i class="bi bi-check-lg"></i></button>
                            </div>
                        </div>
                    </form>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nueva sucursal</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('branches.create') }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small" for="branch-name">Nombre</label>
                        <input type="text" id="branch-name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small" for="branch-address">Dirección</label>
                        <input type="text" id="branch-address" name="address" class="form-control" value="{{ old('address') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small" for="branch-phone">Teléfono</label>
                        <input type="text" id="branch-phone" name="phone" class="form-control" value="{{ old('phone') }}">
                    </div>
                    <div class="form-check mb-3">
                        <input type="hidden" name="copy_catalog" value="0">
                        <input type="checkbox" class="form-check-input" id="copy_catalog" name="copy_catalog" value="1" checked>
                        <label class="form-check-label small" for="copy_catalog">
                            Copiar la carta de <strong>{{ $current->name }}</strong> (categorías, productos, variantes y recetas)
                        </label>
                    </div>
                    <p class="text-muted small">El stock no se copia: la sucursal arranca en cero y se carga con una puesta a punto.</p>
                    <button type="submit" class="btn btn-primary w-100">Crear sucursal</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
