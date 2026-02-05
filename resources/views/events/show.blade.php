@extends('layouts.app')

@section('title', 'Evento: ' . $event->name)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('events.index', ['year' => $event->date->year, 'month' => $event->date->month]) }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver al Calendario
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
            <i class="bi bi-calendar-event"></i> {{ $event->name }}
        </h1>
        <p class="text-muted">
            <i class="bi bi-calendar"></i> {{ $event->date->locale('es')->translatedFormat('l d/m/Y') }}
            @if($event->time)
                @php
                    $time = is_string($event->time) ? $event->time : \Carbon\Carbon::parse($event->time)->format('H:i');
                @endphp
                <i class="bi bi-clock ms-2"></i> {{ $time }}hs
            @endif
            | 
            <span class="badge bg-{{ 
                $event->status === 'FINALIZADO' ? 'success' : 
                ($event->status === 'CANCELADO' ? 'secondary' : 
                ($event->status === 'EN_CURSO' ? 'warning' : 'primary')) 
            }}">{{ $event->status }}</span>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Información del Evento</h5>
            </div>
            <div class="card-body">
                @if($event->description)
                <div class="mb-3">
                    <strong>Descripción:</strong>
                    <p>{{ $event->description }}</p>
                </div>
                @endif

                @if($event->expected_attendance)
                <div class="mb-3">
                    <strong>Asistencia Esperada:</strong> {{ $event->expected_attendance }} personas
                </div>
                @endif

                <div class="mb-3">
                    <strong>Creado por:</strong> {{ $event->creator->name }}
                </div>
            </div>
        </div>

        @if($event->products->count() > 0)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Productos Necesarios</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Stock Actual</th>
                                <th>Cantidad Necesaria</th>
                                <th>Estado</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($event->products as $product)
                            @php
                                $currentStock = $productStocks[$product->id] ?? 0;
                                $expectedQuantity = $product->pivot->expected_quantity ?? 0;
                                $hasEnoughStock = $currentStock >= $expectedQuantity;
                            @endphp
                            <tr class="{{ !$hasEnoughStock ? 'table-warning' : '' }}">
                                <td><strong>{{ $product->name }}</strong></td>
                                <td>
                                    <span class="badge bg-{{ $hasEnoughStock ? 'success' : 'danger' }}">
                                        {{ $currentStock }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $expectedQuantity }}</span>
                                </td>
                                <td>
                                    @if($hasEnoughStock)
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i> Stock suficiente
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="bi bi-exclamation-triangle"></i> Faltan {{ $expectedQuantity - $currentStock }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($product->pivot->notes)
                                        <small>{{ $product->pivot->notes }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body">
                <p class="text-muted text-center mb-0">No hay productos asociados a este evento</p>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Acciones</h5>
            </div>
            <div class="card-body">
                @can('update', $event)
                <a href="{{ route('events.edit', $event) }}" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-pencil"></i> Editar Evento
                </a>
                @endcan

                @can('delete', $event)
                <form action="{{ route('events.destroy', $event) }}" method="POST" 
                      onsubmit="return confirm('¿Estás seguro de eliminar este evento?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-trash"></i> Eliminar Evento
                    </button>
                </form>
                @endcan
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Fecha de creación:</strong><br>
                    {{ $event->created_at->format('d/m/Y H:i') }}
                </p>
                @if($event->updated_at != $event->created_at)
                <p class="mb-0">
                    <strong>Última actualización:</strong><br>
                    {{ $event->updated_at->format('d/m/Y H:i') }}
                </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

