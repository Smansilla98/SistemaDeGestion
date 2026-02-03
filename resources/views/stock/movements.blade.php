@extends('layouts.app')

@section('title', 'Movimientos de Stock')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('stock.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-list-ul"></i> Movimientos de Stock</h1>
                <p class="text-muted">Historial de movimientos de inventario</p>
            </div>
            <a href="{{ route('stock.create-movement') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Registrar Movimiento
            </a>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <form method="GET" action="{{ route('stock.movements') }}" class="row g-3">
            <div class="col-md-3">
                <select name="product_id" class="form-select">
                    <option value="">Todos los productos</option>
                    @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                        {{ $product->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Stock Anterior</th>
                        <th>Stock Nuevo</th>
                        <th>Proveedor/Costo</th>
                        <th>Motivo</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                        <td><strong>{{ $movement->product->name }}</strong></td>
                        <td>
                            <span class="badge bg-{{ $movement->type === 'ENTRADA' ? 'success' : ($movement->type === 'SALIDA' ? 'danger' : 'warning') }}">
                                {{ $movement->type }}
                            </span>
                        </td>
                        <td>{{ $movement->quantity }}</td>
                        <td>{{ $movement->previous_stock }}</td>
                        <td><strong>{{ $movement->new_stock }}</strong></td>
                        <td>
                            @if($movement->purchase)
                                <div>
                                    <strong>{{ $movement->purchase->supplier->name }}</strong><br>
                                    <small class="text-muted">
                                        Costo: ${{ number_format($movement->purchase->unit_cost, 2) }} c/u<br>
                                        Total: ${{ number_format($movement->purchase->total_cost, 2) }}<br>
                                        Fecha compra: {{ $movement->purchase->purchase_date->format('d/m/Y') }}
                                    </small>
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $movement->reason ?? '-' }}</td>
                        <td>{{ $movement->user->name }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">No hay movimientos registrados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $movements->links() }}
        </div>
    </div>
</div>
@endsection

