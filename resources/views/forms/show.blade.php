@extends('layouts.app')

@section('title', $source->name)

@section('content')
@php
    $actions = $canManage ? '<a href="'.route('forms.edit', $source).'" class="btn btn-outline-light"><i class="bi bi-gear"></i> Configurar</a>' : null;
@endphp
@include('community._head', ['icon' => 'bi-ui-checks', 'title' => $source->name, 'back' => route('forms.index'),
    'subtitle' => $source->targetLabel().($source->workshop ? ' · '.$source->workshop->name : '').' · '.($source->last_synced_at ? 'última importación '.$source->last_synced_at->diffForHumans() : 'sin importar todavía'),
    'actions' => $actions])

@if($source->last_error)<div class="alert alert-danger">{{ $source->last_error }}</div>@endif

<div class="row g-4">
    <div class="col-xl-5">
        @if($canManage)
        <div class="card mb-4"><div class="card-header"><h6 class="mb-0">Traer respuestas</h6></div><div class="card-body">
            <form method="POST" action="{{ route('forms.sync', $source) }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap">@csrf
                @if($source->sheet_url)
                    <button class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> Sincronizar con Google ahora</button>
                @else
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.ods,.csv" required>
                    <button class="btn btn-primary">Subir respuestas actualizadas</button>
                @endif
            </form>
            <div class="small text-muted mt-2">Las respuestas ya importadas no se repiten. Las que quedaron «para revisar» se vuelven a intentar.
                @if($pendingCount)<br><strong>Respuestas descargadas sin importar: {{ $pendingCount }}.</strong>@endif</div>
        </div></div>

        <div class="card"><div class="card-header"><h6 class="mb-0">¿Qué columna es cada dato?</h6></div><div class="card-body">
            @if(empty($source->headers))
                <p class="text-muted mb-0">Todavía no se leyó la planilla.</p>
            @else
            <form method="POST" action="{{ route('forms.mapping', $source) }}" class="row g-2">@csrf
                @foreach($fields as $key => $label)
                    @continue($source->target !== 'AGENDA' && in_array($key, ['date', 'time'], true))
                    @continue($source->target !== 'ALUMNOS' && $key === 'workshop')
                    @continue(in_array($source->target, ['ALUMNOS', 'CONTACTOS'], true) && $key === 'title')
                    @continue($source->target === 'AGENDA' && in_array($key, ['last_name', 'email', 'phone', 'document', 'birth_date', 'workshop', 'consent'], true))
                    <div class="col-5 small d-flex align-items-center">{{ $label }}@if(in_array($key, $required, true))<span class="text-danger ms-1">*</span>@endif</div>
                    <div class="col-7"><select name="mapping[{{ $key }}]" class="form-select form-select-sm">
                        <option value="">— no está en el formulario —</option>
                        @foreach($source->headers as $h)<option value="{{ $h }}" @selected(($source->mapping[$key] ?? null) === $h)>{{ \Illuminate\Support\Str::limit($h, 60) }}</option>@endforeach
                    </select></div>
                @endforeach
                <div class="col-12 mt-2"><button class="btn btn-primary w-100"><i class="bi bi-download"></i> Guardar e importar</button></div>
            </form>
            @endif
        </div></div>
        @endif
    </div>

    <div class="col-xl-7">
        @if($preview)
        <div class="card mb-4"><div class="card-header"><h6 class="mb-0">Últimas respuestas en la planilla</h6></div>
            <div class="table-responsive" style="max-height: 260px"><table class="table table-sm mb-0 small">
                <thead><tr>@foreach(array_keys($preview[0]) as $h)<th class="text-nowrap">{{ \Illuminate\Support\Str::limit($h, 28) }}</th>@endforeach</tr></thead>
                <tbody>@foreach($preview as $r)<tr>@foreach($r as $v)<td class="text-nowrap">{{ \Illuminate\Support\Str::limit($v, 40) }}</td>@endforeach</tr>@endforeach</tbody>
            </table></div>
        </div>
        @endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"><h6 class="mb-0">Respuestas procesadas</h6>
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('forms.show', $source) }}" class="btn btn-{{ request('estado') ? 'outline-secondary' : 'secondary' }}">Todas</a>
                    <a href="{{ route('forms.show', [$source, 'estado' => 'IMPORTADA']) }}" class="btn btn-{{ request('estado') === 'IMPORTADA' ? 'success' : 'outline-success' }}">Importadas ({{ $counts['IMPORTADA'] ?? 0 }})</a>
                    <a href="{{ route('forms.show', [$source, 'estado' => 'ERROR']) }}" class="btn btn-{{ request('estado') === 'ERROR' ? 'danger' : 'outline-danger' }}">Para revisar ({{ $counts['ERROR'] ?? 0 }})</a>
                    <a href="{{ route('forms.show', [$source, 'estado' => 'OMITIDA']) }}" class="btn btn-{{ request('estado') === 'OMITIDA' ? 'secondary' : 'outline-secondary' }}">Omitidas ({{ $counts['OMITIDA'] ?? 0 }})</a>
                </div>
            </div>
            <ul class="list-group list-group-flush">
            @forelse($rows as $r)
                @php $name = trim(($r->data[$source->mapping['name'] ?? ''] ?? '').' '.($r->data[$source->mapping['last_name'] ?? ''] ?? '')) ?: ($r->data[$source->mapping['title'] ?? ''] ?? 'Respuesta'); @endphp
                <li class="list-group-item small">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $name }}</strong>
                        <span class="badge bg-{{ ['IMPORTADA' => 'success', 'ERROR' => 'danger', 'OMITIDA' => 'secondary'][$r->status] ?? 'secondary' }}">{{ $r->status === 'ERROR' ? 'Para revisar' : ucfirst(strtolower($r->status)) }}</span>
                    </div>
                    <div class="text-muted">{{ $r->message }}</div>
                    @if($r->contact && $canManage)<a href="{{ route('contacts.edit', $r->contact) }}">Ver contacto</a>@endif
                </li>
            @empty
                <li class="list-group-item text-muted text-center py-4">Todavía no se importó ninguna respuesta.</li>
            @endforelse
            </ul>
            <div class="card-footer">{{ $rows->links() }}</div>
        </div>

        @if($canManage)
        <form method="POST" action="{{ route('forms.destroy', $source) }}" class="mt-3" onsubmit="return confirm('¿Quitar este formulario? Lo ya importado se conserva.')">@csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger">Quitar formulario</button></form>
        @endif
    </div>
</div>
@endsection
