@extends('layouts.app')

@section('title', 'Manual de uso')

@push('styles')
<style>
    .mu-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 1rem; margin-bottom: 1.1rem; }
    .mu-head h1 { color: #fff; font-weight: 800; font-size: clamp(1.6rem, 3vw, 2.3rem); margin: 0; }
    .mu-head p { color: rgba(255,255,255,.85); margin: .25rem 0 0; max-width: 46em; }
    .mu-btn { border: 0; border-radius: 12px; font-weight: 700; padding: .6rem 1rem; background: rgba(255,255,255,.95); color: var(--conurbania-dark); display: inline-flex; align-items: center; gap: .4rem; }

    .mu-panel { background: #fff; border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,.12); padding: 1rem 1.1rem; margin-bottom: 1.1rem; }
    .mu-roles { display: flex; gap: .45rem; overflow-x: auto; scrollbar-width: thin; padding-bottom: .2rem; }
    .mu-role { flex: 0 0 auto; display: inline-flex; align-items: center; gap: .4rem; border: 1.5px solid #e2e5e7; border-radius: 999px; padding: .45rem .9rem; font-weight: 700; font-size: .9rem; color: #33403f; text-decoration: none; white-space: nowrap; }
    .mu-role.is-active { background: var(--conurbania-primary); border-color: var(--conurbania-primary); color: #fff; }
    .mu-role .mu-you { font-size: .68rem; font-weight: 800; text-transform: uppercase; background: var(--conurbania-primary-10); color: var(--conurbania-secondary); border-radius: 999px; padding: .1rem .45rem; }
    .mu-role.is-active .mu-you { background: rgba(255,255,255,.25); color: #fff; }
    .mu-search { position: relative; margin-top: .85rem; }
    .mu-search i { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: #8a9397; }
    .mu-search input { width: 100%; border: 1.5px solid #e2e5e7; border-radius: 12px; padding: .65rem .9rem .65rem 2.4rem; font-size: 1rem; }
    .mu-search input:focus { outline: none; border-color: var(--conurbania-primary); box-shadow: 0 0 0 3px var(--conurbania-primary-10); }

    .mu-layout { display: grid; grid-template-columns: 250px 1fr; gap: 1.1rem; align-items: start; }
    .mu-toc { position: sticky; top: 1rem; background: #fff; border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,.12); padding: .8rem; max-height: calc(100vh - 2rem); overflow-y: auto; }
    .mu-toc p { font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #7b7d84; margin: .3rem .5rem .5rem; }
    .mu-toc a { display: flex; gap: .55rem; align-items: center; padding: .45rem .55rem; border-radius: 9px; color: #33403f; text-decoration: none; font-weight: 600; font-size: .9rem; }
    .mu-toc a:hover { background: #f2f4f5; }
    .mu-toc a i { color: var(--conurbania-secondary); }

    .mu-hero { background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary)); color: #fff; border-radius: 18px; padding: 1.25rem 1.3rem; margin-bottom: 1.1rem; box-shadow: 0 10px 30px rgba(0,0,0,.15); }
    .mu-hero h2 { font-size: 1.35rem; font-weight: 800; margin: 0 0 .3rem; display: flex; align-items: center; gap: .5rem; }
    .mu-hero > p { margin: 0 0 1rem; opacity: .95; }
    .mu-day { list-style: none; counter-reset: dia; margin: 0; padding: 0; display: grid; gap: .5rem; }
    .mu-day li { counter-increment: dia; display: flex; gap: .7rem; align-items: flex-start; background: rgba(255,255,255,.12); border-radius: 12px; padding: .6rem .75rem; line-height: 1.45; }
    .mu-day li::before { content: counter(dia); flex: 0 0 26px; height: 26px; border-radius: 50%; background: #fff; color: var(--conurbania-secondary); font-weight: 800; display: grid; place-items: center; font-size: .85rem; }

    .mu-sec { background: #fff; border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,.12); padding: 1.1rem 1.2rem; margin-bottom: 1rem; scroll-margin-top: 1rem; }
    .mu-sec-head { display: flex; justify-content: space-between; align-items: center; gap: .75rem; margin-bottom: .6rem; flex-wrap: wrap; }
    .mu-sec-head h3 { font-size: 1.12rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: .55rem; color: #1a2326; }
    .mu-sec-head h3 i { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; background: var(--conurbania-primary-10); color: var(--conurbania-secondary); font-size: 1.05rem; }
    .mu-go { border: 1.5px solid #e2e5e7; border-radius: 10px; padding: .35rem .75rem; font-weight: 700; font-size: .85rem; color: var(--conurbania-secondary); text-decoration: none; white-space: nowrap; }
    .mu-go:hover { border-color: var(--conurbania-primary); }
    .mu-intro { color: #5d676b; margin: 0 0 .6rem; }
    .mu-steps { margin: 0; padding-left: 1.3rem; display: grid; gap: .45rem; line-height: 1.5; color: #26302f; }
    .mu-steps li::marker { font-weight: 800; color: var(--conurbania-secondary); }
    .mu-tips { margin-top: .8rem; display: grid; gap: .4rem; }
    .mu-tip { display: flex; gap: .55rem; background: #fff8e6; border: 1px solid #f4dfa8; border-radius: 10px; padding: .55rem .7rem; font-size: .92rem; color: #5b4510; }
    .mu-tip i { color: #c48a0c; }

    .mu-faq details { border-bottom: 1px solid #eceef0; padding: .75rem 0; }
    .mu-faq details:last-child { border-bottom: 0; }
    .mu-faq summary { font-weight: 700; cursor: pointer; list-style: none; display: flex; justify-content: space-between; gap: .75rem; }
    .mu-faq summary::after { content: "+"; font-weight: 800; color: var(--conurbania-secondary); }
    .mu-faq details[open] summary::after { content: "−"; }
    .mu-faq details p { margin: .5rem 0 0; color: #4b5557; line-height: 1.55; }
    .mu-nores { display: none; text-align: center; color: #fff; padding: 1.5rem; }
    mark.mu-hit { background: #ffe58a; padding: 0 .1em; border-radius: 3px; }

    @media (max-width: 991.98px) {
        .mu-layout { grid-template-columns: 1fr; }
        .mu-toc { position: static; max-height: none; }
        .mu-toc nav { display: flex; gap: .35rem; overflow-x: auto; }
        .mu-toc nav a { flex: 0 0 auto; border: 1.5px solid #e2e5e7; }
    }
    @media print {
        .sb, .tb, header, nav, .mu-head .mu-btn, .mu-panel, .mu-toc, .alert { display: none !important; }
        .mu-layout { display: block; }
        .mu-sec, .mu-hero { box-shadow: none; border: 1px solid #ddd; break-inside: avoid; }
        .mu-hero { color: #000; background: #fff; }
        .mu-day li { background: #f5f5f5; }
        .mu-faq details { display: block; }
        body { background: #fff !important; }
    }
</style>
@endpush

@section('content')
<div class="mu">
    <div class="mu-head">
        <div>
            <h1><i class="bi bi-question-circle"></i> Manual de uso</h1>
            <p>Paso a paso de lo que hace cada rol en el sistema. Abre con el tuyo; arriba podés ver el de los demás.</p>
        </div>
        <button type="button" class="mu-btn" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
    </div>

    <div class="mu-panel">
        <nav class="mu-roles" aria-label="Manual por rol">
            @foreach($roles as $key => $r)
            <a href="{{ route('manual', strtolower($key)) }}" class="mu-role {{ $key === $role ? 'is-active' : '' }}" @if($key === $role) aria-current="page" @endif>
                <i class="bi {{ $r['icon'] }}"></i> {{ $r['label'] }}
                @if($key === $ownRole)<span class="mu-you">Tu rol</span>@endif
            </a>
            @endforeach
        </nav>
        <label class="mu-search d-block mb-0">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="muSearch" placeholder="Buscar en el manual: cobrar, reservar, stock…" autocomplete="off" aria-label="Buscar en el manual">
        </label>
    </div>

    <div class="mu-layout">
        <aside class="mu-toc" aria-label="Índice">
            <p>En este manual</p>
            <nav>
                <a href="#tu-turno"><i class="bi bi-sun"></i> {{ $isOwn ? 'Tu turno' : 'Un turno' }}</a>
                @foreach($sections as $section)
                <a href="#{{ $section['key'] }}" data-toc="{{ $section['key'] }}"><i class="bi {{ $section['icon'] }}"></i> {{ $section['title'] }}</a>
                @endforeach
                @if(count($faqs))<a href="#preguntas"><i class="bi bi-chat-dots"></i> Preguntas frecuentes</a>@endif
            </nav>
        </aside>

        <div>
            <section class="mu-hero" id="tu-turno">
                <h2><i class="bi {{ $meta['icon'] }}"></i> {{ $isOwn ? 'Sos '.$meta['label'] : 'Rol: '.$meta['label'] }}</h2>
                <p>{{ $meta['summary'] }}</p>
                <ol class="mu-day">
                    @foreach($meta['day'] as $step)
                    <li>{{ $step }}</li>
                    @endforeach
                </ol>
            </section>

            @foreach($sections as $section)
            <section class="mu-sec" id="{{ $section['key'] }}" data-section>
                <div class="mu-sec-head">
                    <h3><i class="bi {{ $section['icon'] }}" aria-hidden="true"></i> {{ $section['title'] }}</h3>
                    @if($section['url'])
                    <a href="{{ $section['url'] }}" class="mu-go">Ir a la pantalla <i class="bi bi-arrow-right"></i></a>
                    @endif
                </div>
                @if(!empty($section['intro']))<p class="mu-intro">{{ $section['intro'] }}</p>@endif
                @if(!empty($section['steps']))
                <ol class="mu-steps">
                    @foreach($section['steps'] as $step)
                    <li>{{ $step }}</li>
                    @endforeach
                </ol>
                @endif
                @if(!empty($section['tips']))
                <div class="mu-tips">
                    @foreach($section['tips'] as $tip)
                    <div class="mu-tip"><i class="bi bi-lightbulb" aria-hidden="true"></i><span>{{ $tip }}</span></div>
                    @endforeach
                </div>
                @endif
            </section>
            @endforeach

            @if(count($faqs))
            <section class="mu-sec mu-faq" id="preguntas" data-section>
                <div class="mu-sec-head"><h3><i class="bi bi-chat-dots" aria-hidden="true"></i> Preguntas frecuentes</h3></div>
                @foreach($faqs as $faq)
                <details data-faq>
                    <summary>{{ $faq['q'] }}</summary>
                    <p>{{ $faq['a'] }}</p>
                </details>
                @endforeach
            </section>
            @endif
            <p class="mu-nores" id="muNoResults"><i class="bi bi-search"></i> No encontramos nada con esa palabra. Probá con otra o revisá el manual de otro rol.</p>
        </div>
    </div>
</div>

<script>
(function () {
    const input = document.getElementById('muSearch');
    const norm = (s) => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    const sections = [...document.querySelectorAll('[data-section]')];
    sections.forEach((s) => { s.dataset.text = norm(s.textContent); });

    function clearMarks(root) {
        root.querySelectorAll('mark.mu-hit').forEach((m) => m.replaceWith(document.createTextNode(m.textContent)));
        root.normalize();
    }
    function mark(root, q) {
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        const hits = [];
        while (walker.nextNode()) {
            const node = walker.currentNode;
            const i = norm(node.nodeValue).indexOf(q);
            if (i >= 0 && node.parentElement.tagName !== 'MARK') hits.push([node, i]);
        }
        hits.forEach(([node, i]) => {
            const m = document.createElement('mark');
            m.className = 'mu-hit';
            const after = node.splitText(i);
            after.splitText(q.length);
            m.textContent = after.nodeValue;
            after.replaceWith(m);
        });
    }

    input.addEventListener('input', () => {
        const q = norm(input.value.trim());
        let shown = 0;
        sections.forEach((s) => {
            clearMarks(s);
            if (s.matches('.mu-faq')) {
                let any = false;
                s.querySelectorAll('[data-faq]').forEach((d) => {
                    const ok = !q || norm(d.textContent).includes(q);
                    d.hidden = !ok; d.open = !!q && ok; any = any || ok;
                });
                s.hidden = !any;
            } else {
                s.hidden = !!q && !s.dataset.text.includes(q);
            }
            if (!s.hidden) { shown++; if (q.length > 1) mark(s, q); }
            const toc = document.querySelector(`[data-toc="${s.id}"]`);
            if (toc) toc.hidden = s.hidden;
        });
        document.getElementById('tu-turno').hidden = !!q;
        document.getElementById('muNoResults').style.display = shown ? 'none' : 'block';
    });
})();
</script>
@endsection
