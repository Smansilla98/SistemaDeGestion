@php
    $brand = \App\Support\Branding::name();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $brand.' — Espacio cultural')</title>
    <meta name="description" content="@yield('description', $info['about'])">
    <meta name="theme-color" content="#000000">
    <meta property="og:title" content="@yield('title', $brand.' — Espacio cultural')">
    <meta property="og:description" content="@yield('description', $info['about'])">
    <meta property="og:image" content="{{ asset('icons/icon-512.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --ink: #0a0a0a; --paper: #ffffff; --soft: #f4f4f4; --line: #e3e3e3; --muted: #5e5e5e; --ok: #1b7a43; --warn: #8a5a00; --err: #b3261e; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; font-family: Roboto, system-ui, sans-serif; color: var(--ink); background: var(--paper); line-height: 1.5; }
        a { color: inherit; }
        img { max-width: 100%; }
        .wrap { max-width: 1120px; margin: 0 auto; padding: 0 20px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 22px; border-radius: 999px; font: inherit; font-weight: 700; text-decoration: none; border: 2px solid var(--ink); background: var(--ink); color: #fff; cursor: pointer; transition: background .15s, color .15s; }
        .btn:hover { background: #fff; color: var(--ink); }
        .btn--light { background: #fff; color: var(--ink); border-color: #fff; }
        .btn--light:hover { background: transparent; color: #fff; }
        .btn--ghost { background: transparent; color: var(--ink); }
        .btn--ghost:hover { background: var(--ink); color: #fff; }

        .nav { position: sticky; top: 0; z-index: 20; background: #000; color: #fff; }
        .nav .wrap { display: flex; align-items: center; gap: 24px; height: 64px; }
        .nav__brand img { height: 44px; width: auto; display: block; }
        .nav__links { display: flex; gap: 22px; margin-left: auto; }
        .nav__links a { text-decoration: none; color: rgba(255,255,255,.75); font-weight: 500; }
        .nav__links a:hover { color: #fff; }
        .nav__team { color: rgba(255,255,255,.6); text-decoration: none; font-size: .9rem; border: 1px solid rgba(255,255,255,.3); padding: 6px 14px; border-radius: 999px; }
        .nav__team:hover { color: #fff; border-color: #fff; }

        .kicker { text-transform: uppercase; letter-spacing: .14em; font-size: .78rem; font-weight: 700; color: var(--muted); margin: 0 0 8px; }
        section.block { padding: 64px 0; scroll-margin-top: 64px; }
        section.block h2 { font-size: clamp(1.7rem, 3.5vw, 2.4rem); font-weight: 900; letter-spacing: -.01em; margin: 0 0 28px; }
        .alert { border-radius: 14px; padding: 14px 18px; margin: 0 0 20px; display: flex; gap: 10px; align-items: flex-start; }
        .alert--ok { background: #e9f5ee; color: var(--ok); }
        .alert--err { background: #fbeceb; color: var(--err); }
        .chip { display: inline-block; font-size: .75rem; font-weight: 700; padding: 3px 10px; border-radius: 999px; border: 1px solid var(--line); background: #fff; }
        .chip--dark { background: var(--ink); color: #fff; border-color: var(--ink); }

        footer { background: #000; color: rgba(255,255,255,.7); padding: 40px 0; font-size: .92rem; }
        footer .wrap { display: flex; flex-wrap: wrap; gap: 24px; justify-content: space-between; align-items: center; }
        footer img { height: 54px; width: auto; }
        footer a { color: #fff; }
        footer nav { display: flex; gap: 16px; flex-wrap: wrap; }

        @media (max-width: 720px) {
            .nav__links { display: none; }
            .nav__team { margin-left: auto; }
            section.block { padding: 44px 0; }
            .wrap { padding: 0 16px; }
        }
        @yield('styles')
    </style>
</head>
<body>
<header class="nav">
    <div class="wrap">
        <a href="{{ route('home') }}" class="nav__brand" aria-label="{{ $brand }}">
            <img src="{{ asset(config('app.brand.logo')) }}" alt="{{ $brand }} Espacio Cultural">
        </a>
        <nav class="nav__links" aria-label="Secciones">
            <a href="{{ route('home') }}#talleres">Talleres</a>
            <a href="{{ route('home') }}#agenda">Agenda</a>
            <a href="{{ route('home') }}#espacio">El espacio</a>
            <a href="{{ route('home') }}#contacto">Contacto</a>
        </nav>
        <a href="{{ route('login') }}" class="nav__team"><i class="bi bi-person"></i> Equipo</a>
    </div>
</header>

<main>
    @yield('content')
</main>

<footer>
    <div class="wrap">
        <img src="{{ asset(config('app.brand.logo')) }}" alt="{{ $brand }}">
        <nav>
            @if($info['instagram_url'])<a href="{{ $info['instagram_url'] }}" target="_blank" rel="noopener"><i class="bi bi-instagram"></i> {{ '@'.$info['instagram'] }}</a>@endif
            @if($info['whatsapp_url'])<a href="{{ $info['whatsapp_url'] }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> WhatsApp</a>@endif
            <a href="{{ route('privacy') }}">Privacidad</a>
        </nav>
        <span>© {{ date('Y') }} {{ $brand }} · Espacio cultural</span>
    </div>
</footer>
</body>
</html>
