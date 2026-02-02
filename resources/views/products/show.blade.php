@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('products.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-box-seam"></i> {{ $product->name }}</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Información del Producto</h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">Categoría:</dt>
                    <dd class="col-sm-9">{{ $product->category->name }}</dd>

                    <dt class="col-sm-3">Precio:</dt>
                    <dd class="col-sm-9"><strong>${{ number_format($product->price, 2) }}</strong></dd>

                    @if($product->description)
                    <dt class="col-sm-3">Descripción:</dt>
                    <dd class="col-sm-9">{{ $product->description }}</dd>
                    @endif

                    <dt class="col-sm-3">Maneja Stock:</dt>
                    <dd class="col-sm-9">
                        <span class="badge bg-{{ $product->has_stock ? 'success' : 'secondary' }}">
                            {{ $product->has_stock ? 'Sí' : 'No' }}
                        </span>
                    </dd>

                    @if($product->has_stock)
                    <dt class="col-sm-3">Stock Actual:</dt>
                    <dd class="col-sm-9">
                        <span class="badge bg-{{ $product->getCurrentStock(auth()->user()->restaurant_id) <= $product->stock_minimum ? 'danger' : 'success' }}">
                            {{ $product->getCurrentStock(auth()->user()->restaurant_id) }} unidades
                        </span>
                    </dd>

                    <dt class="col-sm-3">Stock Mínimo:</dt>
                    <dd class="col-sm-9">{{ $product->stock_minimum }} unidades</dd>
                    @endif

                    <dt class="col-sm-3">Estado:</dt>
                    <dd class="col-sm-9">
                        <span class="badge bg-{{ $product->is_active ? 'success' : 'secondary' }}">
                            {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </dd>
                </dl>

                <div class="mt-3">
                    @can('update', $product)
                    <a href="{{ route('products.edit', $product) }}" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    @endcan
                    @can('delete', $product)
                    <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline" data-confirm-delete>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

