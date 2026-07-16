{{--
  Layout mobile ConurbaniaBa V2.
  Se mantiene separado de layouts/app.blade.php a propósito:
  - Bottom navigation + safe-area (iOS notch)
  - Flujo PWA / detect.mobile distinto al sidebar desktop
  - Menos peso en vistas de salón (mozo) vs shell admin completo
  Comparte tokens Vite (app.css + mobile.css) con la web.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    @php
        $user = auth()->user();
        $role = $user?->role;
        $restaurant = $user ? \App\Models\Restaurant::find($user->restaurant_id) : null;
        $settings = $restaurant?->settings ?? [];
        $colors = $settings['colors'] ?? [
            'primary' => '#1d9e75',
            'secondary' => '#155240',
            'accent' => '#c94a2d',
        ];
        $roleLabels = [
            'SUPERADMIN' => 'Superadmin',
            'ADMIN' => 'Administrador',
            'GERENTE' => 'Gerente',
            'SUPERVISOR' => 'Supervisor',
            'CAJERO' => 'Cajero',
            'COCINA' => 'Cocina',
            'MOZO' => 'Mozo',
            'ENCARGADO' => 'Encargado',
        ];
        $roleLabel = $roleLabels[$role ?? ''] ?? ($role ?? 'Usuario');
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="restaurant-id" content="{{ $user?->restaurant_id }}">
    <meta name="theme-color" content="{{ $colors['primary'] }}">
    <title>@yield('title', 'Gestión Mobile')</title>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" type="image/png" href="{{ asset('icons/icon-192.png') }}">
    <link rel="apple-touch-icon" type="image/png" href="{{ asset('icons/icon-192.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @livewireStyles
</head>
<body class="conurbania-app m-app d-flex flex-column min-vh-100">
    <header class="m-header">
        <div class="m-header-row">
            <div>
                <div class="m-hello">Hola,</div>
                <div class="m-name">{{ $user?->name }}</div>
                <div class="m-meta">{{ $roleLabel }}@if($restaurant) · {{ $restaurant->name }}@endif</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @hasSection('header-actions')
                    @yield('header-actions')
                @else
                    <a href="{{ route('notifications.index') }}" class="m-icon-btn" aria-label="Notificaciones">
                        <i class="bi bi-bell" aria-hidden="true"></i>
                    </a>
                @endif
                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="m-icon-btn" aria-label="Cerrar sesión">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div id="connectionBanner" class="m-connection-banner d-none">
        <div class="alert alert-warning text-center mb-0 py-1 small rounded-0">
            <i class="bi bi-wifi-off me-1" aria-hidden="true"></i>
            Sin conexión — los cambios se guardarán al recuperar señal
        </div>
    </div>

    @if(session('success') || session('error'))
        <div class="position-fixed top-0 start-50 translate-middle-x mt-3 z-3" style="padding-top: env(safe-area-inset-top);">
            <div class="toast show toast-mobile {{ session('success') ? 'bg-success text-white' : 'bg-danger text-white' }}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        {{ session('success') ?? session('error') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                </div>
            </div>
        </div>
    @endif

    <main class="m-main flex-grow-1">
        @yield('content')
    </main>

    @php
        $navConfig = [
            'ADMIN' => [
                ['label' => 'Inicio', 'icon' => 'bi-house-door', 'route' => 'm.dashboard', 'pattern' => 'm.dashboard'],
                ['label' => 'Mesas', 'icon' => 'bi-grid-3x3-gap', 'route' => 'm.pedidos.index', 'pattern' => 'm.pedidos.*'],
                ['label' => 'Pedidos', 'icon' => 'bi-receipt', 'route' => 'm.pedidos.index', 'pattern' => 'm.pedidos.*'],
                ['label' => 'Caja', 'icon' => 'bi-cash-coin', 'route' => 'm.caja.resumen', 'pattern' => 'm.caja.*'],
            ],
            'SUPERADMIN' => [
                ['label' => 'Inicio', 'icon' => 'bi-house-door', 'route' => 'm.dashboard', 'pattern' => 'm.dashboard'],
                ['label' => 'Mesas', 'icon' => 'bi-grid-3x3-gap', 'route' => 'm.pedidos.index', 'pattern' => 'm.pedidos.*'],
                ['label' => 'Pedidos', 'icon' => 'bi-receipt', 'route' => 'm.pedidos.index', 'pattern' => 'm.pedidos.*'],
                ['label' => 'Caja', 'icon' => 'bi-cash-coin', 'route' => 'm.caja.resumen', 'pattern' => 'm.caja.*'],
            ],
            'MOZO' => [
                ['label' => 'Inicio', 'icon' => 'bi-house-door', 'route' => 'm.dashboard', 'pattern' => 'm.dashboard'],
                ['label' => 'Mesas', 'icon' => 'bi-grid-3x3-gap', 'route' => 'm.pedidos.index', 'pattern' => 'm.pedidos.*'],
                ['label' => 'Pedidos', 'icon' => 'bi-receipt', 'route' => 'orders.index', 'pattern' => 'orders.*'],
                ['label' => 'Stock', 'icon' => 'bi-boxes', 'route' => 'stock.index', 'pattern' => 'stock.*'],
            ],
            'CAJERO' => [
                ['label' => 'Inicio', 'icon' => 'bi-house-door', 'route' => 'm.dashboard', 'pattern' => 'm.dashboard'],
                ['label' => 'Caja', 'icon' => 'bi-cash-coin', 'route' => 'm.caja.resumen', 'pattern' => 'm.caja.*'],
                ['label' => 'Pedidos', 'icon' => 'bi-receipt', 'route' => 'orders.index', 'pattern' => 'orders.*'],
                ['label' => 'Reportes', 'icon' => 'bi-graph-up', 'route' => 'reports.index', 'pattern' => 'reports.*'],
            ],
            'COCINA' => [
                ['label' => 'Cocina', 'icon' => 'bi-egg-fried', 'route' => 'kitchen.index', 'pattern' => 'kitchen.*'],
                ['label' => 'Pedidos', 'icon' => 'bi-receipt', 'route' => 'orders.index', 'pattern' => 'orders.*'],
            ],
            'default' => [
                ['label' => 'Inicio', 'icon' => 'bi-house-door', 'route' => 'm.dashboard', 'pattern' => 'm.dashboard'],
                ['label' => 'Pedidos', 'icon' => 'bi-receipt', 'route' => 'm.pedidos.index', 'pattern' => 'm.pedidos.*'],
            ],
        ];
        $navItems = $navConfig[$role ?? ''] ?? $navConfig['default'];
        if ($user && ($role ?? '') === 'MOZO') {
            if (! app(\App\Services\PermissionService::class)->allowed($user, 'stock.view')) {
                $navItems = array_values(array_filter($navItems, fn ($i) => ($i['route'] ?? '') !== 'stock.index'));
            }
        }
    @endphp

    <nav class="m-bottom-nav" aria-label="Navegación principal">
        @foreach($navItems as $item)
            @php
                $isActive = isset($item['pattern']) && request()->routeIs($item['pattern']);
            @endphp
            <a href="{{ route($item['route']) }}" class="{{ $isActive ? 'active' : '' }}" @if($isActive) aria-current="page" @endif>
                <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @livewireScripts
    <script>
        document.querySelectorAll('.toast').forEach(function (toastEl) {
            const t = new bootstrap.Toast(toastEl, { delay: 3000 });
            t.show();
        });

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

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js').catch(function (e) {
                    console.error('SW registration failed', e);
                });
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
