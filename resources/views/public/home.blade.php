@extends('public.layout')

@section('styles')
        .hero { background: #000; color: #fff; padding: 72px 0 84px; text-align: center; position: relative; overflow: hidden; }
        .hero__logo { width: min(520px, 86%); height: auto; margin: 0 auto 18px; display: block; }
        .hero p { font-size: clamp(1.05rem, 2.2vw, 1.3rem); font-weight: 300; color: rgba(255,255,255,.82); max-width: 34em; margin: 0 auto 30px; }
        .hero .actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

        .ws-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; }
        .ws { border: 2px solid var(--ink); border-radius: 18px; padding: 22px; display: flex; flex-direction: column; gap: 10px; background: #fff; transition: transform .15s, box-shadow .15s; }
        .ws:hover { transform: translateY(-3px); box-shadow: 8px 8px 0 var(--ink); }
        .ws h3 { margin: 0; font-size: 1.3rem; font-weight: 900; }
        .ws__meta { display: flex; flex-wrap: wrap; gap: 6px; }
        .ws__rows { list-style: none; margin: 0; padding: 0; display: grid; gap: 4px; color: var(--muted); font-size: .94rem; }
        .ws__rows i { width: 18px; display: inline-block; color: var(--ink); }
        .ws p { margin: 0; color: var(--muted); font-size: .95rem; }
        .ws .btn { margin-top: auto; align-self: flex-start; padding: 10px 18px; }
        .empty { border: 2px dashed var(--line); border-radius: 18px; padding: 32px; text-align: center; color: var(--muted); }

        .agenda { list-style: none; margin: 0; padding: 0; display: grid; gap: 12px; }
        .agenda li { display: grid; grid-template-columns: 76px 1fr; gap: 18px; align-items: start; padding: 14px 0; border-bottom: 1px solid var(--line); }
        .date { background: #000; color: #fff; border-radius: 14px; text-align: center; padding: 8px 4px; line-height: 1.1; }
        .date b { display: block; font-size: 1.7rem; font-weight: 900; }
        .date span { font-size: .72rem; text-transform: uppercase; letter-spacing: .1em; }
        .agenda h3 { margin: 0 0 4px; font-size: 1.12rem; }
        .agenda p { margin: 6px 0 0; color: var(--muted); }
        .dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 4px; vertical-align: middle; }

        .space { background: var(--soft); }
        .space-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .space-grid article { background: #fff; border-radius: 18px; padding: 22px; }
        .space-grid i { font-size: 1.6rem; }
        .space-grid h3 { margin: 10px 0 4px; font-size: 1.08rem; }
        .space-grid p { margin: 0; color: var(--muted); font-size: .94rem; }
        .about { font-size: 1.15rem; max-width: 46em; margin: 0 0 28px; }

        .contact { background: #000; color: #fff; }
        .contact .kicker { color: rgba(255,255,255,.6); }
        .contact-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
        .contact-grid a, .contact-grid div { display: flex; gap: 12px; align-items: flex-start; padding: 18px; border: 1px solid rgba(255,255,255,.2); border-radius: 16px; text-decoration: none; }
        .contact-grid a:hover { border-color: #fff; }
        .contact-grid i { font-size: 1.4rem; }
        .contact-grid small { display: block; color: rgba(255,255,255,.6); }

        @media (max-width: 900px) { .space-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) { .space-grid { grid-template-columns: 1fr; } .hero .btn { width: 100%; } }
@endsection

@section('content')
    <section class="hero">
        <div class="wrap">
            <img class="hero__logo" src="{{ asset(config('app.brand.logo')) }}" alt="{{ \App\Support\Branding::name() }} Espacio Cultural">
            <p>{{ $info['about'] }}</p>
            <div class="actions">
                <a href="#talleres" class="btn btn--light"><i class="bi bi-easel"></i> Ver talleres</a>
                <a href="#agenda" class="btn btn--light btn--ghost" style="color:#fff;border-color:#fff"><i class="bi bi-calendar-event"></i> Próximas fechas</a>
            </div>
        </div>
    </section>

    <section id="talleres" class="block">
        <div class="wrap">
            <p class="kicker">Inscripción abierta</p>
            <h2>Talleres</h2>
            @if($workshops->isEmpty())
                <div class="empty">Pronto publicamos los talleres de la temporada. Seguinos para enterarte primero.</div>
            @else
                <div class="ws-grid">
                    @foreach($workshops as $w)
                        @php $left = $w->capacity === null ? null : max(0, $w->capacity - $w->active_count); @endphp
                        <article class="ws">
                            <div class="ws__meta">
                                @if($w->category)<span class="chip"><span class="dot" style="background: {{ $w->category->color ?: '#000' }}"></span>{{ $w->category->name }}</span>@endif
                                @if($w->status === 'EN_CURSO')<span class="chip">En curso</span>@endif
                                @if($left === 0)<span class="chip chip--dark">Lista de espera</span>@elseif($left !== null && $left <= 3)<span class="chip chip--dark">Últimos {{ $left }} lugares</span>@endif
                            </div>
                            <h3>{{ $w->name }}</h3>
                            <ul class="ws__rows">
                                @foreach($w->schedules as $s)<li><i class="bi bi-clock"></i> {{ $s->label() }}</li>@endforeach
                                @if($w->teacher)<li><i class="bi bi-person"></i> Con {{ $w->teacher->alias ?: $w->teacher->name }}</li>@endif
                                @if($w->starts_on)<li><i class="bi bi-calendar3"></i> Desde el {{ $w->starts_on->format('d/m') }}</li>@endif
                                <li><i class="bi bi-cash"></i> {{ $w->fee_cents ? \App\Services\Finance\ActivityFinanceService::money((int) $w->fee_cents).' por mes' : 'Gratuito' }}</li>
                            </ul>
                            @if($w->description)<p>{{ \Illuminate\Support\Str::limit($w->description, 140) }}</p>@endif
                            <a href="{{ route('public.workshop', $w) }}" class="btn">{{ $left === 0 ? 'Anotarme en lista de espera' : 'Inscribirme' }} <i class="bi bi-arrow-right"></i></a>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section id="agenda" class="block" style="padding-top: 0">
        <div class="wrap">
            <p class="kicker">Agenda</p>
            <h2>Próximas fechas</h2>
            @if($upcoming->isEmpty())
                <div class="empty">Estamos armando la agenda. Muy pronto, nuevas fechas.</div>
            @else
                <ul class="agenda">
                    @foreach($upcoming as $item)
                        <li>
                            <div class="date"><b>{{ $item['date']->format('d') }}</b><span>{{ $item['date']->locale('es')->isoFormat('MMM') }}</span></div>
                            <div>
                                <div class="ws__meta">
                                    <span class="chip">{{ $item['kind'] }}</span>
                                    @if($item['category'])<span class="chip"><span class="dot" style="background: {{ $item['color'] ?: '#000' }}"></span>{{ $item['category'] }}</span>@endif
                                </div>
                                <h3 style="margin-top: 6px">{{ $item['title'] }}</h3>
                                <small>{{ ucfirst($item['date']->locale('es')->isoFormat('dddd D [de] MMMM')) }}@if($item['time']) · {{ $item['time'] }} h @endif</small>
                                @if($item['description'])<p>{{ \Illuminate\Support\Str::limit($item['description'], 180) }}</p>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <section id="espacio" class="block space">
        <div class="wrap">
            <p class="kicker">El espacio</p>
            <h2>Mucho más que talleres</h2>
            <div class="space-grid">
                <article><i class="bi bi-easel"></i><h3>Talleres</h3><p>Música, teatro, danza, artes y oficios, con grupos reducidos y profes del barrio.</p></article>
                <article><i class="bi bi-music-note-beamed"></i><h3>Shows y eventos</h3><p>Fechas en vivo, varietés, presentaciones de fin de taller y propuestas para toda la familia.</p></article>
                <article><i class="bi bi-shop-window"></i><h3>Feria</h3><p>Emprendedores y productores locales con sus productos todo el año.</p></article>
                <article><i class="bi bi-cup-hot"></i><h3>Barra</h3><p>Algo rico para tomar y picar antes, durante y después de cada actividad.</p></article>
            </div>
            <p class="about" style="margin-top: 28px">¿Querés dar un taller, tocar o sumarte a la feria? Escribinos y armamos algo juntos.</p>
        </div>
    </section>

    <section id="contacto" class="block contact">
        <div class="wrap">
            <p class="kicker">Contacto</p>
            <h2>Vení a {{ \App\Support\Branding::name() }}</h2>
            <div class="contact-grid">
                @if($info['address'])
                    <a href="{{ $info['maps_url'] }}" target="_blank" rel="noopener"><i class="bi bi-geo-alt"></i><span>{{ $info['address'] }}<small>Cómo llegar</small></span></a>
                @endif
                @if($info['hours'])
                    <div><i class="bi bi-clock"></i><span>{{ $info['hours'] }}<small>Horarios</small></span></div>
                @endif
                @if($info['whatsapp_url'])
                    <a href="{{ $info['whatsapp_url'] }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i><span>WhatsApp<small>Consultas e inscripciones</small></span></a>
                @elseif($info['phone'])
                    <a href="tel:{{ $info['phone'] }}"><i class="bi bi-telephone"></i><span>{{ $info['phone'] }}<small>Teléfono</small></span></a>
                @endif
                @if($info['instagram_url'])
                    <a href="{{ $info['instagram_url'] }}" target="_blank" rel="noopener"><i class="bi bi-instagram"></i><span>{{ '@'.$info['instagram'] }}<small>Novedades y fotos</small></span></a>
                @endif
                @if($info['email'])
                    <a href="mailto:{{ $info['email'] }}"><i class="bi bi-envelope"></i><span>{{ $info['email'] }}<small>Email</small></span></a>
                @endif
                @if(! $info['address'] && ! $info['hours'] && ! $info['whatsapp_url'] && ! $info['phone'] && ! $info['instagram_url'] && ! $info['email'])
                    <div><i class="bi bi-chat-heart"></i><span>Muy pronto vas a encontrar acá cómo llegar y cómo escribirnos.<small>Mientras tanto, acercate a cualquier actividad.</small></span></div>
                @endif
            </div>
        </div>
    </section>
@endsection
