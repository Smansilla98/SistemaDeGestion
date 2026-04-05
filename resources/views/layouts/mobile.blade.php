<!DOCTYPE html>
<html lang="es">
<head>
    @php
        // Definir $colors PRIMERO para que estén disponibles en todo el layout (evitar "Undefined variable $colors")
        $user = auth()->user();
        $role = $user?->role;
        $restaurant = $user ? \App\Models\Restaurant::find($user->restaurant_id) : null;
        $settings = $restaurant?->settings ?? [];
        $colors = $settings['colors'] ?? [
            'primary' => '#1e8081',
            'secondary' => '#22565e',
            'accent' => '#c94a2d',
        ];
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Gestión Mobile')</title>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="{{ $colors['primary'] }}">
    <link rel="icon" type="image/png" href="{{ asset('icons/icon-192.png') }}">
    <link rel="apple-touch-icon" type="image/png" href="{{ asset('icons/icon-192.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    @stack('styles')
    @livewireStyles
    <style>
        body {
            background-color: #0f172a;
            color: #f9fafb;
        }
        .mobile-main {
            padding: 0.75rem;
            padding-bottom: 4.5rem; /* espacio navbar inferior */
        }
        .mobile-bottom-nav {
            height: 64px;
            border-top: 1px solid rgba(148, 163, 184, 0.4);
            background-color: #020617;
        }
        .mobile-bottom-nav .nav-link {
            font-size: 0.75rem;
            color: #9ca3af;
        }
        .mobile-bottom-nav .nav-link.active {
            color: {{ $colors['primary'] }};
        }
        .mobile-bottom-nav .nav-link i {
            font-size: 1.2rem;
        }
        .mobile-header {
            background: linear-gradient(90deg, {{ $colors['primary'] }} 0%, {{ $colors['secondary'] }} 100%);
        }
        .toast-mobile {
            min-width: 260px;
        }
        .connection-banner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1080;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <header class="mobile-header text-white py-2 px-3 d-flex justify-content-between align-items-center">
        <div>
            <div class="fw-semibold">{{ $user?->name }}</div>
            <div class="small text-white-50">{{ $role ?? 'Usuario' }}</div>
        </div>
        <form action="{{ route('logout') }}" method="POST" class="ms-2">
            @csrf
            <button type="submit" class="btn btn-sm btn-light d-flex align-items-center" style="min-height: 40px;">
                <i class="bi bi-box-arrow-right me-1"></i> Salir
            </button>
        </form>
    </header>

    <!-- Banner de conexión -->
    <div id="connectionBanner" class="connection-banner d-none">
        <div class="alert alert-warning text-center mb-0 py-1 small">
            <i class="bi bi-wifi-off me-1"></i>
            Sin conexión — los cambios se guardarán al recuperar señal
        </div>
    </div>

    <!-- Toasts -->
    @if(session('success') || session('error'))
        <div class="position-fixed top-0 start-50 translate-middle-x mt-3 z-3">
            <div class="toast show toast-mobile {{ session('success') ? 'bg-success text-white' : 'bg-danger text-white' }}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
                <div class="d-flex">
                    <div class="toast-body">
                        {{ session('success') ?? session('error') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    @endif

    <main class="mobile-main flex-grow-1">
        @yield('content')
    </main>

    @php
        $navConfig = [
            'ADMIN' => [
                ['label' => 'Inicio', 'icon' => 'bi-house-door', 'route' => 'm.dashboard', 'pattern' => 'm.dashboard'],
                ['label' => 'Pedidos', 'icon' => 'bi-clipboard-check', 'route' => 'm.pedidos.index', 'pattern' => 'm.pedidos.*'],
                ['label' => 'Mesas', 'icon' => 'bi-grid-3x3-gap', 'route' => 'm.pedidos.index', 'pattern' => 'm.pedidos.*'],
                ['label' => 'Stock', 'icon' => 'bi-boxes', 'route' => 'stock.index', 'pattern' => 'stock.*'],
                ['label' => 'Caja', 'icon' => 'bi-cash-coin', 'route' => 'm.caja.resumen', 'pattern' => 'm.caja.*'],
                ['label' => 'Reportes', 'icon' => 'bi-graph-up', 'route' => 'reports.index', 'pattern' => 'reports.*'],
            ],
            'MOZO' => [
                ['label' => 'Inicio', 'icon' => 'bi-house-door', 'route' => 'm.dashboard', 'pattern' => 'm.dashboard'],
                ['label' => 'Mesas', 'icon' => 'bi-grid-3x3-gap', 'route' => 'm.pedidos.index', 'pattern' => 'm.pedidos.*'],
                ['label' => 'Pedidos', 'icon' => 'bi-clipboard-check', 'route' => 'orders.index', 'pattern' => 'orders.*'],
                ['label' => 'Stock', 'icon' => 'bi-boxes', 'route' => 'stock.index', 'pattern' => 'stock.*'],
            ],
            'CAJERO' => [
                ['label' => 'Caja', 'icon' => 'bi-cash-coin', 'route' => 'm.caja.resumen', 'pattern' => 'm.caja.*'],
                ['label' => 'Pedidos', 'icon' => 'bi-clipboard-check', 'route' => 'orders.index', 'pattern' => 'orders.*'],
                ['label' => 'Reportes', 'icon' => 'bi-graph-up', 'route' => 'reports.index', 'pattern' => 'reports.*'],
            ],
            'COCINA' => [
                ['label' => 'Cocina', 'icon' => 'bi-egg-fried', 'route' => 'kitchen.index', 'pattern' => 'kitchen.*'],
                ['label' => 'Pedidos', 'icon' => 'bi-clipboard-check', 'route' => 'orders.index', 'pattern' => 'orders.*'],
            ],
            'default' => [
                ['label' => 'Inicio', 'icon' => 'bi-house-door', 'route' => 'm.dashboard', 'pattern' => 'm.dashboard'],
                ['label' => 'Pedidos', 'icon' => 'bi-clipboard-check', 'route' => 'm.pedidos.index', 'pattern' => 'm.pedidos.*'],
            ],
        ];
        $navItems = $navConfig[$role ?? ''] ?? $navConfig['default'];
        if ($user && ($role ?? '') === 'MOZO') {
            if (! app(\App\Services\PermissionService::class)->allowed($user, 'stock.view')) {
                $navItems = array_values(array_filter($navItems, fn ($i) => ($i['route'] ?? '') !== 'stock.index'));
            }
        }
    @endphp

    <nav class="mobile-bottom-nav fixed-bottom">
        <div class="container-fluid h-100">
            <div class="d-flex justify-content-around align-items-center h-100">
                @foreach($navItems as $item)
                    @php
                        $isActive = isset($item['pattern']) && request()->routeIs($item['pattern']);
                    @endphp
                    <a href="{{ route($item['route']) }}" class="nav-link d-flex flex-column align-items-center {{ $isActive ? 'active' : '' }}">
                        <i class="bi {{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
    <script>
        // Auto-dismiss toasts
        document.querySelectorAll('.toast').forEach(function (toastEl) {
            const t = new bootstrap.Toast(toastEl, { delay: 3000 });
            t.show();
        });

        // Indicador de conexión
        const banner = document.getElementById('connectionBanner');
        function updateConnectionStatus() {
            if (!navigator.onLine) {
                banner.classList.remove('d-none');
            } else {
                banner.classList.add('d-none');
            }
        }
        window.addEventListener('online', updateConnectionStatus);
        window.addEventListener('offline', updateConnectionStatus);
        updateConnectionStatus();

        // Service Worker para mobile layout
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js').catch(function (e) {
                    console.error('SW registration failed', e);
                });
            });
        }
    </script>
</body>
</html>

