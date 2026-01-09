@extends('layouts.app')

@section('title', 'Control de Stock')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1><i class="bi bi-inboxes"></i> Control de Stock</h1>
        <p class="text-muted">Gestión de inventario y alertas</p>
    </div>
</div>

@if(count($lowStockAlerts) > 0)
<div class="alert alert-warning mb-4">
    <h5><i class="bi bi-exclamation-triangle"></i> Alertas de Stock Bajo</h5>
    <ul class="mb-0">
        @foreach($lowStockAlerts as $alert)
        <li>
            <strong>{{ $alert['product_name'] }}</strong>: 
            Stock actual: {{ $alert['current_stock'] }} | 
            Mínimo: {{ $alert['minimum_stock'] }}
        </li>
        @endforeach
    </ul>
</div>
@endif

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Productos con Stock</h5>
        <a href="{{ route('stock.movements') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-list-ul"></i> Ver Movimientos
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Stock Actual</th>
                        <th>Stock Mínimo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr class="{{ $product->is_low_stock ? 'table-warning' : '' }}">
                        <td><strong>{{ $product->name }}</strong></td>
                        <td>{{ $product->category->name }}</td>
                        <td>
                            <span class="badge bg-{{ $product->current_stock <= $product->stock_minimum ? 'danger' : 'success' }}">
                                {{ $product->current_stock }}
                            </span>
                        </td>
                        <td>{{ $product->stock_minimum }}</td>
                        <td>
                            @if($product->is_low_stock)
                            <span class="badge bg-warning">Stock Bajo</span>
                            @else
                            <span class="badge bg-success">Normal</span>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#movementModal{{ $product->id }}">
                                <i class="bi bi-plus-circle"></i> Movimiento
                            </button>
                        </td>
                    </tr>

                    <!-- Modal para movimiento -->
                    <div class="modal fade" id="movementModal{{ $product->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('stock.store-movement') }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Registrar Movimiento - {{ $product->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        
                                        <div class="mb-3">
                                            <label for="type" class="form-label">Tipo de Movimiento</label>
                                            <select class="form-select" id="type" name="type" required>
                                                <option value="ENTRADA">Entrada</option>
                                                <option value="SALIDA">Salida</option>
                                                <option value="AJUSTE">Ajuste</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="quantity" class="form-label">Cantidad</label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" required min="1">
                                        </div>

                                        <div class="mb-3">
                                            <label for="reason" class="form-label">Motivo</label>
                                            <input type="text" class="form-control" id="reason" name="reason">
                                        </div>

                                        <div class="mb-3">
                                            <label for="reference" class="form-label">Referencia</label>
                                            <input type="text" class="form-control" id="reference" name="reference">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-primary">Registrar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No hay productos con stock configurado</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

