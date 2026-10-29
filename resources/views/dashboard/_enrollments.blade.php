{{-- Últimas inscripciones a talleres: quién se anotó, a qué taller y cuándo. --}}
@if(($recentEnrollments ?? collect())->isNotEmpty())
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-person-plus"></i> Últimas inscripciones</h5>
        <a href="{{ route('workshops.index') }}" class="small text-dark fw-semibold">Ver talleres</a>
    </div>
    <ul class="list-group list-group-flush">
        @foreach($recentEnrollments as $e)
        <a href="{{ route('workshops.show', $e->workshop_id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>
                <strong>{{ $e->contact?->name }}</strong>
                <span class="text-muted">se anotó en</span> {{ $e->workshop?->name }}
                @if($e->status === 'EN_ESPERA')<span class="badge bg-warning text-dark">Lista de espera #{{ $e->waitlist_position }}</span>@endif
            </span>
            <span class="small text-muted" title="{{ $e->enrolled_at?->format('d/m/Y H:i') }}">
                {{ $e->enrolled_at?->format('d/m/Y H:i') }} · {{ $e->enrolled_at?->diffForHumans() }}
            </span>
        </a>
        @endforeach
    </ul>
</div>
@endif
