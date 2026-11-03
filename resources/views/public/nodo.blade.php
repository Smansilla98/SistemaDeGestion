@extends('public.layout')

@section('title', $cycle->name.' — '.\App\Support\Branding::name())
@section('description', 'Hacé tu pedido del nodo de '.\App\Support\Branding::name().' hasta el '.$cycle->closes_at->format('d/m').'.')

@section('styles')
        .ws-head { background: #000; color: #fff; padding: 48px 0; }
        .ws-head a.back { color: rgba(255,255,255,.7); text-decoration: none; font-size: .92rem; }
        .ws-head h1 { font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 900; margin: 12px 0 8px; letter-spacing: -.02em; }
        .ws-head .chip { background: transparent; color: #fff; border-color: rgba(255,255,255,.4); }
        .ws-body { display: grid; grid-template-columns: 1.2fr .8fr; gap: 40px; padding: 48px 0 72px; align-items: start; }
        .offer { list-style: none; margin: 0; padding: 0; border-top: 1px solid #e5e5e5; }
        .offer li { display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: center; padding: 14px 0; border-bottom: 1px solid #e5e5e5; }
        .offer h3 { margin: 0; font-size: 1.05rem; font-weight: 700; }
        .offer small { color: var(--muted); }
        .offer .qty { display: flex; align-items: center; gap: 8px; }
        .offer input[type=number] { width: 84px; font: inherit; padding: 10px 12px; border: 1.5px solid #cfcfcf; border-radius: 12px; text-align: right; }
        .offer .cat { padding: 18px 0 6px; font-size: .78rem; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); font-weight: 700; border-bottom: 0; display: block; }
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
        .total { display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 900; margin: 6px 0 14px; }
        .form-card .btn { width: 100%; margin-top: 6px; }
        @media (max-width: 860px) { .ws-body { grid-template-columns: 1fr; } .form-card { position: static; } }
@endsection

@section('content')
    @php $money = fn (int $c) => \App\Services\Finance\ActivityFinanceService::money($c); @endphp
    <section class="ws-head">
        <div class="wrap">
            <a href="{{ route('home') }}" class="back"><i class="bi bi-arrow-left"></i> Volver</a>
            <h1>{{ $cycle->name }}</h1>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <span class="chip"><i class="bi bi-clock"></i> Pedidos hasta el {{ $cycle->closes_at->locale('es')->isoFormat('dddd D [de] MMMM, H:mm') }} h</span>
                <span class="chip"><i class="bi bi-bag"></i> Retiro el {{ $cycle->delivery_on->locale('es')->isoFormat('dddd D [de] MMMM') }}</span>
            </div>
        </div>
    </section>

    <div class="wrap">
        @if(session('ordered'))
            <div style="padding: 40px 0 72px">
                <div class="alert alert--ok"><i class="bi bi-check-circle-fill"></i><span>{{ session('ordered') }}</span></div>
                <a href="{{ route('home') }}" class="btn btn--ghost">Volver al inicio</a>
            </div>
        @else
        <form method="POST" action="{{ route('public.nodo.order', $cycle->id) }}" class="ws-body" id="nodoForm">
            @csrf
            <div>
                @if($cycle->notes)<p class="desc" style="white-space:pre-line;margin-top:0">{{ $cycle->notes }}</p>@endif
                @error('items')<div class="err" style="margin-bottom:12px">{{ $message }}</div>@enderror
                <ul class="offer">
                    @foreach($offer->groupBy(fn ($o) => $o->product->category ?: 'Productos') as $category => $items)
                        <li class="cat">{{ $category }}</li>
                        @foreach($items as $o)
                            @php
                                $left = $o->max_quantity !== null ? max(0, $o->max_quantity - ($taken[$o->market_product_id] ?? 0)) : null;
                            @endphp
                            <li>
                                <div>
                                    <h3>{{ $o->product->name }}</h3>
                                    <small>{{ $money((int) $o->price_cents) }} por {{ $o->product->unit }}@if($o->product->entrepreneur) · {{ $o->product->entrepreneur->business_name }}@endif
                                        @if($left !== null) · {{ $left > 0 ? 'quedan '.\App\Services\Market\MarketStockService::qty((float) $left) : 'agotado' }}@endif</small>
                                </div>
                                <div class="qty">
                                    <input type="number" id="q{{ $o->market_product_id }}" aria-label="Cantidad de {{ $o->product->name }}" name="items[{{ $o->market_product_id }}]" min="0" step="any"
                                           @if($left !== null) max="{{ $left }}" @endif @disabled($left !== null && $left <= 0)
                                           value="{{ old('items.'.$o->market_product_id) }}" data-price="{{ (int) $o->price_cents }}" inputmode="decimal" placeholder="0">
                                </div>
                            </li>
                        @endforeach
                    @endforeach
                </ul>
            </div>

            <aside class="form-card">
                <h2>Tu pedido</h2>
                <p>Completá las cantidades y tus datos. Pagás al retirar.</p>
                <div class="total"><span>Total</span><span id="nodoTotal">$ 0</span></div>
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
                    <label for="note">Comentario</label>
                    <textarea id="note" name="note" rows="2" maxlength="500" placeholder="Alguna aclaración para el retiro…">{{ old('note') }}</textarea>
                </div>
                <div class="field">
                    <label class="check"><input type="checkbox" name="accepts_marketing" value="1" @checked(old('accepts_marketing'))> Quiero recibir novedades de {{ \App\Support\Branding::name() }}.</label>
                </div>
                <button type="submit" class="btn">Enviar pedido</button>
            </aside>
        </form>
        <script>
            (function () {
                const form = document.getElementById('nodoForm');
                const out = document.getElementById('nodoTotal');
                const fmt = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 2 });
                const sum = function () {
                    let cents = 0;
                    form.querySelectorAll('input[data-price]').forEach(function (i) { cents += (parseFloat(i.value.replace(',', '.')) || 0) * parseInt(i.dataset.price, 10); });
                    out.textContent = fmt.format(Math.round(cents) / 100);
                };
                form.addEventListener('input', sum);
                sum();
            })();
        </script>
        @endif
    </div>
@endsection
