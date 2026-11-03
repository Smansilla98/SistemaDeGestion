{{-- Vista mensual de la agenda: grilla de lunes a domingo. Clic en un día vacío crea un evento con esa fecha. --}}
@php
    $gridStart = $from->startOfWeek();
    $gridEnd = $to->endOfWeek()->startOfDay();
    $todayKey = today()->toDateString();
    $maxPerDay = 4;
@endphp
<div class="card agenda-month">
    <div class="table-responsive">
        <table class="table table-bordered mb-0 agenda-grid">
            <thead><tr>@foreach(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $d)<th class="text-center small text-uppercase">{{ $d }}</th>@endforeach</tr></thead>
            <tbody>
            @for($week = $gridStart; $week->lte($gridEnd); $week = $week->addWeek())
                <tr>
                @for($i = 0; $i < 7; $i++)
                    @php
                        $day = $week->addDays($i);
                        $key = $day->toDateString();
                        $inMonth = $day->month === $from->month;
                        $items = $days->get($key, collect());
                        $createUrl = $canCreateEvent && $inMonth ? route('events.create', ['date' => $key]) : null;
                    @endphp
                    <td class="agenda-day {{ $inMonth ? '' : 'is-out' }} {{ $key === $todayKey ? 'is-today' : '' }} {{ $createUrl ? 'can-create' : '' }}"
                        @if($createUrl) data-create-url="{{ $createUrl }}" title="Crear un evento el {{ $day->format('d/m') }}" @endif>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="agenda-day-n">{{ $day->day }}</span>
                            @if($createUrl)<a href="{{ $createUrl }}" class="agenda-day-add" aria-label="Crear evento el {{ $day->format('d/m') }}"><i class="bi bi-plus-lg"></i></a>@endif
                        </div>
                        @foreach($items->take($maxPerDay) as $it)
                            @php $tag = $it['url'] ? 'a' : 'div'; @endphp
                            <{{ $tag }} @if($it['url']) href="{{ $it['url'] }}" @endif class="agenda-chip kind-{{ \Illuminate\Support\Str::slug($it['type']) }} {{ $it['cancelled'] ? 'is-cancelled' : '' }}"
                                title="{{ $it['kind'] }}: {{ $it['title'] }}{{ $it['time'] ? ' · '.$it['time'] : '' }}{{ $it['detail'] ? ' · '.$it['detail'] : '' }}"
                                @if($it['color']) style="border-left-color: {{ $it['color'] }}" @endif>
                                @if($it['time'])<span class="agenda-chip-time">{{ $it['time'] }}</span>@endif {{ \Illuminate\Support\Str::limit($it['title'], 22) }}
                            </{{ $tag }}>
                        @endforeach
                        <a class="agenda-day-week" href="{{ route('agenda.index', ['vista' => 'semana', 'desde' => $key] + $keep) }}" aria-label="Ver la semana del {{ $day->format('d/m') }}"></a>
                        @if($items->count() > $maxPerDay)
                            <a class="agenda-more" href="{{ route('agenda.index', ['vista' => 'semana', 'desde' => $key] + $keep) }}">+{{ $items->count() - $maxPerDay }} más</a>
                        @endif
                    </td>
                @endfor
                </tr>
            @endfor
            </tbody>
        </table>
    </div>
</div>

@push('styles')
<style>
.agenda-grid { table-layout: fixed; width: 100%; }
.agenda-grid th { background: #f4f4f4; }
.agenda-grid td.agenda-day { vertical-align: top !important; height: 118px; padding: 6px !important; overflow: hidden; }
.agenda-day.is-out { background: #fafafa; color: #adb5bd; }
.agenda-day.is-today { box-shadow: inset 0 0 0 2px #111; }
.agenda-day.can-create { cursor: pointer; }
.agenda-day.can-create:hover { background: #fafafa; }
.agenda-day-n { font-weight: 700; font-size: .9rem; }
.agenda-day-add { opacity: 0; color: #6c757d; text-decoration: none; padding: 0 4px; border-radius: 6px; }
.agenda-day.can-create:hover .agenda-day-add, .agenda-day-add:focus { opacity: 1; }
.agenda-day-add:hover { background: #111; color: #fff; }
@media (hover: none) { .agenda-day-add { opacity: .7; } }
.agenda-chip { display: block; font-size: .72rem; line-height: 1.25; margin-top: 3px; padding: 2px 5px; border-radius: 4px; max-width: 100%;
    background: #f1f1f1; color: #111 !important; border-left: 3px solid #111; text-decoration: none; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
.agenda-chip:hover { background: #e6e6e6; }
.agenda-chip.kind-evento { background: #111; color: #fff !important; }
.agenda-chip.kind-etapa { background: #3a3a3a; color: #fff !important; }
.agenda-chip.kind-aviso { background: #fff4d6; }
.agenda-chip.kind-entrega { background: #e7f3ec; }
.agenda-chip.is-cancelled { text-decoration: line-through; opacity: .55; }
.agenda-chip-time { font-weight: 700; }
.agenda-more { display: block; font-size: .72rem; margin-top: 3px; }
.agenda-day-week { display: none; }
/* Celular: siete columnas no entran con texto; cada actividad es un punto y el día abre esa semana. */
@media (max-width: 767.98px) {
    .agenda-grid th { font-size: .62rem; padding: 4px 0 !important; }
    .agenda-grid td.agenda-day { height: 58px; padding: 3px !important; }
    .agenda-day-n { font-size: .78rem; }
    .agenda-day-add, .agenda-more { display: none; }
    .agenda-chip { display: inline-block; width: 7px; height: 7px; padding: 0; margin: 1px; border-radius: 50%; border: 0; font-size: 0; vertical-align: middle; }
    .agenda-chip.kind-clase, .agenda-chip.kind-actividad { background: #6c757d; }
    .agenda-chip.kind-aviso { background: #e0a800; }
    .agenda-chip.kind-entrega { background: #2f9e5a; }
    .agenda-day-week { display: block; position: absolute; inset: 0; }
    .agenda-grid td.agenda-day { position: relative; }
}
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.agenda-day[data-create-url]').forEach(function (cell) {
    cell.addEventListener('click', function (e) {
        if (e.target.closest('a, button')) return;
        window.location.href = cell.dataset.createUrl;
    });
});
</script>
@endpush
