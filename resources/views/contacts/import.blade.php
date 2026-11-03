@extends('layouts.app')

@section('title', 'Importar contactos')

@section('content')
@include('community._head', ['icon' => 'bi-upload', 'title' => 'Importar contactos', 'back' => route('contacts.index')])
<div class="card" style="max-width: 720px"><div class="card-body">
    <p>Subí un archivo CSV (se puede exportar desde una planilla) con estas columnas en la primera fila:</p>
    <p><code>{{ implode(', ', $columns) }}</code></p>
    <ul class="small text-muted">
        <li>Sólo <strong>nombre</strong> es obligatoria. <strong>tipo</strong>: alumno, profesor, artista, emprendedor, cliente, proveedor, participante o publico.</li>
        <li><strong>etiquetas</strong> separadas por «|». <strong>acepta_difusion</strong>: «si» si la persona aceptó recibir novedades.</li>
        <li>Si ya existe alguien con el mismo email o teléfono, se actualiza en lugar de duplicarse.</li>
    </ul>
    <form method="POST" action="{{ route('contacts.import.store') }}" enctype="multipart/form-data" class="d-flex gap-2">
        @csrf
        <input type="file" name="file" accept=".csv,text/csv" class="form-control" required>
        <button class="btn btn-primary">Importar</button>
    </form>
</div></div>
@endsection
