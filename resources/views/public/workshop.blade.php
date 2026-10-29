@extends('public.layout')

@section('title', $workshop->name.' — '.\App\Support\Branding::name())
@section('description', \Illuminate\Support\Str::limit($workshop->description ?: 'Taller en '.\App\Support\Branding::name().'. Inscribite online.', 160))

@section('styles')
        .ws-head { background: #000; color: #fff; padding: 48px 0; }
        .ws-head a.back { color: rgba(255,255,255,.7); text-decoration: none; font-size: .92rem; }
        .ws-head h1 { font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 900; margin: 12px 0 8px; letter-spacing: -.02em; }
        .ws-head .chip { background: transparent; color: #fff; border-color: rgba(255,255,255,.4); }
        .ws-body { display: grid; grid-template-columns: 1.1fr .9fr; gap: 40px; padding: 48px 0 72px; align-items: start; }
        .facts { list-style: none; margin: 0 0 24px; padding: 0; display: grid; gap: 10px; }
        .facts li { display: flex; gap: 12px; font-size: 1.02rem; }
        .facts i { font-size: 1.2rem; }
        .desc { white-space: pre-line; color: #333; font-size: 1.04rem; }
        .form-card { border: 2px solid var(--ink); border-radius: 20px; padding: 26px; position: sticky; top: 84px; background: #fff; }
        .form-card h2 { margin: 0 0 4px; font-size: 1.4rem; font-weight: 900; }
        .form-card > p { margin: 0 0 18px; color: var(--muted); }
        label { display: block; font-weight: 500; margin: 0 0 6px; }
        .field { margin-bottom: 14px; }
        input[type=text], input[type=tel], input[type=email], textarea { width: 100%; font: inherit; padding: 12px 14px; border: 1.5px solid #cfcfcf; border-radius: 12px; background: #fff; }
        input:focus, textarea:focus { outline: none; border-color: var(--ink); box-shadow: 0 0 0 3px rgba(0,0,0,.08); }
        .check { display: flex; gap: 10px; align-items: flex-start; font-weight: 400; color: var(--muted); font-size: .92rem; }
        .check input { margin-top: 4px; }
        .hp { position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden; }
        .err { color: var(--err); font-size: .88rem; margin-top: 4px; }
        .form-card .btn { width: 100%; margin-top: 6px; }
        @media (max-width: 860px) { .ws-body { grid-template-columns: 1fr; } .form-card { position: static; } }
@endsection

@section('content')
    <section class="ws-head">
        <div class="wrap">
            <a href="{{ route('home') }}#talleres" class="back"><i class="bi bi-arrow-left"></i> Todos los talleres</a>
            <h1>{{ $workshop->name }}</h1>
            <div class="ws__meta" style="display:flex;gap:6px;flex-wrap:wrap">
                @if($workshop->category)<span class="chip">{{ $workshop->category->name }}</span>@endif
                <span class="chip">{{ $workshop->status === 'EN_CURSO' ? 'En curso' : 'Inscripción abierta' }}</span>
            </div>
        </div>
    </section>

    <div class="wrap">
        <div class="ws-body">
            <div>
                <ul class="facts">
                    @foreach($workshop->schedules as $s)<li><i class="bi bi-clock"></i> {{ $s->label() }} h</li>@endforeach
                    @if($workshop->teacher)<li><i class="bi bi-person"></i> Con {{ $workshop->teacher->alias ?: $workshop->teacher->name }}</li>@endif
                    @if($workshop->starts_on)<li><i class="bi bi-calendar3"></i> Desde el {{ $workshop->starts_on->locale('es')->isoFormat('D [de] MMMM') }}@if($workshop->ends_on) hasta el {{ $workshop->ends_on->locale('es')->isoFormat('D [de] MMMM') }}@endif</li>@endif
                    @if($workshop->room)<li><i class="bi bi-door-open"></i> {{ $workshop->room }}</li>@endif
                    <li><i class="bi bi-cash"></i> {{ $workshop->fee_cents ? \App\Services\Finance\ActivityFinanceService::money((int) $workshop->fee_cents).' por mes' : 'Gratuito' }}</li>
                    @if($spotsLeft !== null)<li><i class="bi bi-people"></i> {{ $spotsLeft > 0 ? "Quedan {$spotsLeft} lugares" : 'Cupo completo: te anotamos en lista de espera' }}</li>@endif
                </ul>
                @if($workshop->description)<div class="desc">{{ $workshop->description }}</div>@endif
            </div>

            <aside class="form-card" id="inscripcion">
                @if(session('enrolled'))
                    <div class="alert alert--ok"><i class="bi bi-check-circle-fill"></i><span>{{ session('enrolled') }}</span></div>
                    <a href="{{ route('home') }}#talleres" class="btn btn--ghost">Ver otros talleres</a>
                @else
                    <h2>{{ $spotsLeft === 0 ? 'Anotate en lista de espera' : 'Inscribite' }}</h2>
                    <p>Dejanos tus datos y te contactamos para confirmar.</p>
                    <form method="POST" action="{{ route('public.enroll', $workshop) }}">
                        @csrf
                        <div class="hp" aria-hidden="true"><label for="website">No completar</label><input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></div>
                        <div class="field">
                            <label for="name">Nombre y apellido *</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required minlength="3" maxlength="120" autocomplete="name">
                            @error('name')<div class="err">{{ $message }}</div>@enderror
                        </div>
                        <div class="field">
                            <label for="phone">Teléfono / WhatsApp *</label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required maxlength="40" autocomplete="tel">
                            @error('phone')<div class="err">{{ $message }}</div>@enderror
                        </div>
                        <div class="field">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="150" autocomplete="email">
                            @error('email')<div class="err">{{ $message }}</div>@enderror
                        </div>
                        <div class="field">
                            <label for="note">¿Algo que debamos saber?</label>
                            <textarea id="note" name="note" rows="3" maxlength="500" placeholder="Experiencia previa, edad, consultas…">{{ old('note') }}</textarea>
                        </div>
                        <div class="field">
                            <label class="check"><input type="checkbox" name="accepts_marketing" value="1" @checked(old('accepts_marketing'))> Quiero recibir novedades de {{ \App\Support\Branding::name() }} (talleres, shows y feria).</label>
                        </div>
                        <button type="submit" class="btn">{{ $spotsLeft === 0 ? 'Anotarme en lista de espera' : 'Enviar inscripción' }}</button>
                    </form>
                @endif
            </aside>
        </div>
    </div>
@endsection
