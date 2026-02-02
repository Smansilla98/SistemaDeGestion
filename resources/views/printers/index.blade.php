@extends('layouts.app')

@section('title', 'Impresoras')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-printer"></i> Impresoras</h1>
            <p class="text-muted">Configuración de impresoras térmicas</p>
        </div>
        @can('create', App\Models\Printer::class)
        <a href="{{ route('printers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nueva Impresora
        </a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Conexión</th>
                        <th>Dirección/Ruta</th>
                        <th>Ancho Papel</th>
                        <th>Auto Imprimir</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($printers as $printer)
                    <tr>
                        <td><strong>{{ $printer->name }}</strong></td>
                        <td>
                            <span class="badge bg-info">
                                @if($printer->type === 'kitchen') Cocina
                                @elseif($printer->type === 'bar') Barra
                                @elseif($printer->type === 'cashier') Cajero
                                @else Factura
                                @endif
                            </span>
                        </td>
                        <td>{{ ucfirst($printer->connection_type) }}</td>
                        <td>
                            @if($printer->connection_type === 'network')
                            {{ $printer->ip_address }}:{{ $printer->port }}
                            @elseif($printer->connection_type === 'file')
                            {{ $printer->path ?? 'No configurado' }}
                            @else
                            USB
                            @endif
                        </td>
                        <td>{{ $printer->paper_width }}mm</td>
                        <td>
                            @if($printer->auto_print)
                            <span class="badge bg-success">Sí</span>
                            @else
                            <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td>
                            @if($printer->is_active)
                            <span class="badge bg-success">Activa</span>
                            @else
                            <span class="badge bg-secondary">Inactiva</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                @can('update', $printer)
                                <a href="{{ route('printers.edit', $printer) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('printers.test', $printer) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary" title="Probar Impresora">
                                        <i class="bi bi-printer"></i>
                                    </button>
                                </form>
                                @endcan
                                @can('delete', $printer)
                                <form action="{{ route('printers.destroy', $printer) }}" method="POST" class="d-inline" id="deletePrinterForm{{ $printer->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeletePrinter({{ $printer->id }}, '{{ $printer->name }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            No hay impresoras configuradas
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDeletePrinter(printerId, printerName) {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar Impresora?',
        html: `¿Estás seguro de eliminar la impresora <strong>${printerName}</strong>?<br><small class="text-muted">Esta acción no se puede deshacer.</small>`,
        showCancelButton: true,
        confirmButtonColor: '#c94a2d',
        cancelButtonColor: '#7b7d84',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deletePrinterForm' + printerId).submit();
        }
    });
}
</script>
@endpush
@endsection

