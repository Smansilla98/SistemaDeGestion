@extends('layouts.app')

@section('title', 'Categorías de actividades')

@section('content')
@include('community._head', ['icon' => 'bi-tags', 'title' => 'Categorías de actividades', 'back' => route('agenda.index'),
    'subtitle' => 'Clasificá talleres, eventos y avisos (Música, Teatro, Feria…) para filtrar la agenda. Los formularios también pueden crearlas.'])
<div class="row g-4">
    <div class="col-lg-7"><div class="card"><ul class="list-group list-group-flush">
        @forelse($categories as $row)
        <li class="list-group-item">
            <form method="POST" action="{{ route('agenda.categories.update', $row['c']) }}" class="d-flex gap-2 align-items-center">
                @csrf @method('PUT')
                <input type="color" name="color" value="{{ $row['c']->color }}" class="form-control form-control-color">
                <input name="name" value="{{ $row['c']->name }}" class="form-control" required>
                <span class="small text-muted text-nowrap">{{ $row['uses'] }} usos</span>
                <button class="btn btn-sm btn-outline-primary">Guardar</button>
            </form>
            <form method="POST" action="{{ route('agenda.categories.destroy', $row['c']) }}" class="mt-1 text-end" onsubmit="return confirm('¿Eliminar la categoría? Las actividades quedan sin categoría.')">@csrf @method('DELETE')
                <button class="btn btn-sm btn-link text-danger p-0">Eliminar</button></form>
        </li>
        @empty
        <li class="list-group-item text-muted text-center py-4">Todavía no hay categorías.</li>
        @endforelse
    </ul></div></div>
    <div class="col-lg-5"><div class="card"><div class="card-body">
        <h6>Nueva categoría</h6>
        <form method="POST" action="{{ route('agenda.categories.store') }}" class="d-flex gap-2">@csrf
            <input type="color" name="color" value="#8a5cc2" class="form-control form-control-color">
            <input name="name" class="form-control" placeholder="Ej. Música" required>
            <button class="btn btn-primary">Crear</button>
        </form>
    </div></div></div>
</div>
@endsection
