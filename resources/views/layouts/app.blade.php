<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="restaurant-id" content="{{ auth()->check() ? auth()->user()->restaurant_id : '' }}">
    <title>@yield('title', 'Sistema de Gestión de Restaurante')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    @stack('styles')
    <style>
        :root {
            --nova-sidebar-bg: #1d4ed8;
            --nova-sidebar-hover: #2563eb;
            --nova-sidebar-active: #3b82f6;
            --nova-header-bg: #ffffff;
            --nova-content-bg: #f8fafc;
            --nova-text-primary: #1e293b;
            --nova-text-secondary: #64748b;
            --nova-border: #e2e8f0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--nova-content-bg);
            color: var(--nova-text-primary);
        }

        /* Sidebar */
        .nova-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: linear-gradient(180deg, var(--nova-sidebar-bg) 0%, #1e40af 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .nova-sidebar.collapsed {
            transform: translateX(-100%);
        }

        @media (max-width: 768px) {
            .nova-sidebar {
                transform: translateX(-100%);
            }
            .nova-sidebar.show {
                transform: translateX(0);
            }
        }

        .nova-sidebar-header {
            padding: 1.5rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nova-sidebar-header .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nova-sidebar-nav {
            padding: 1rem 0;
        }

        .nova-nav-item {
            display: block;
            padding: 0.75rem 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nova-nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: rgba(255, 255, 255, 0.3);
        }

        .nova-nav-item.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: white;
            font-weight: 600;
        }

        .nova-nav-item i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .nova-sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nova-user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .nova-user-menu:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nova-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .nova-user-info {
            flex: 1;
            min-width: 0;
        }

        .nova-user-name {
            font-size: 0.875rem;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nova-user-role {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Header */
        .nova-header {
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            height: 64px;
            background: var(--nova-header-bg);
            border-bottom: 1px solid var(--nova-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            z-index: 999;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 768px) {
            .nova-header {
                left: 0;
            }
        }

        .nova-header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nova-sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--nova-text-primary);
            cursor: pointer;
            padding: 0.5rem;
        }

        @media (max-width: 768px) {
            .nova-sidebar-toggle {
                display: block;
            }
        }

        .nova-header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nova-header-dropdown {
            position: relative;
        }

        .nova-header-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: none;
            border: 1px solid var(--nova-border);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .nova-header-dropdown-toggle:hover {
            background-color: var(--nova-content-bg);
        }

        .nova-header-dropdown-menu {
            position: absolute;
            top: calc(100% + 0.5rem);
            right: 0;
            min-width: 200px;
            background: white;
            border: 1px solid var(--nova-border);
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 0.5rem;
            display: none;
        }

        .nova-header-dropdown-menu.show {
            display: block;
        }

        .nova-dropdown-item {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--nova-text-primary);
            text-decoration: none;
            border-radius: 0.375rem;
            transition: background-color 0.2s ease;
        }

        .nova-dropdown-item:hover {
            background-color: var(--nova-content-bg);
        }

        /* Main Content */
        .nova-main {
            margin-left: 260px;
            margin-top: 64px;
            padding: 2rem;
            min-height: calc(100vh - 64px);
        }

        @media (max-width: 768px) {
            .nova-main {
                margin-left: 0;
            }
        }

        /* Alerts */
        .nova-alert {
            border-radius: 0.5rem;
            border: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* Overlay para móvil */
        .nova-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .nova-overlay.show {
            display: block;
        }

        /* Scrollbar personalizado */
        .nova-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .nova-sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .nova-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .nova-sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="nova-sidebar" id="novaSidebar">
        <div class="nova-sidebar-header">
            <a href="{{ route('dashboard') }}" class="logo">
                <i class="bi bi-cup-hot"></i>
                <span>Restaurante</span>
            </a>
        </div>

        <nav class="nova-sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nova-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>

            @if(in_array(auth()->user()->role, ['ADMIN', 'MOZO']))
            <a href="{{ route('tables.index') }}" class="nova-nav-item {{ request()->routeIs('tables.*') ? 'active' : '' }}">
                <i class="bi bi-table"></i>
                <span>Mesas</span>
            </a>
            <a href="{{ route('orders.index') }}" class="nova-nav-item {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i>
                <span>Pedidos</span>
            </a>
            @endif

            @if(in_array(auth()->user()->role, ['ADMIN', 'COCINA']))
            <a href="{{ route('kitchen.index') }}" class="nova-nav-item {{ request()->routeIs('kitchen.*') ? 'active' : '' }}">
                <i class="bi bi-fire"></i>
                <span>Cocina</span>
            </a>
            @endif

            @if(in_array(auth()->user()->role, ['ADMIN', 'CAJERO']))
            <a href="{{ route('cash-register.index') }}" class="nova-nav-item {{ request()->routeIs('cash-register.*') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i>
                <span>Caja</span>
            </a>
            @endif

            @if(auth()->user()->role === 'ADMIN')
            <a href="{{ route('products.index') }}" class="nova-nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i>
                <span>Productos</span>
            </a>
            <a href="{{ route('stock.index') }}" class="nova-nav-item {{ request()->routeIs('stock.*') ? 'active' : '' }}">
                <i class="bi bi-inboxes"></i>
                <span>Stock</span>
            </a>
            <a href="{{ route('reports.index') }}" class="nova-nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-graph-up"></i>
                <span>Reportes</span>
            </a>
            @endif
        </nav>

        <div class="nova-sidebar-footer">
            <div class="nova-user-menu" onclick="toggleUserMenu()">
                <div class="nova-user-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="nova-user-info">
                    <div class="nova-user-name">{{ auth()->user()->name }}</div>
                    <div class="nova-user-role">{{ auth()->user()->role }}</div>
                </div>
                <i class="bi bi-chevron-up" id="userMenuIcon"></i>
            </div>
            <div class="nova-header-dropdown-menu" id="userDropdownMenu">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nova-dropdown-item w-100 text-start">
                        <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Overlay para móvil -->
    <div class="nova-overlay" id="novaOverlay" onclick="toggleSidebar()"></div>

    <!-- Header -->
    <header class="nova-header">
        <div class="nova-header-left">
            <button class="nova-sidebar-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <h5 class="mb-0 text-muted">@yield('title', 'Sistema de Gestión')</h5>
        </div>
        <div class="nova-header-right">
            <div class="nova-header-dropdown">
                <button class="nova-header-dropdown-toggle" onclick="toggleHeaderMenu()">
                    <i class="bi bi-person-circle"></i>
                    <span>{{ auth()->user()->name }}</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="nova-header-dropdown-menu" id="headerDropdownMenu">
                    <div class="nova-dropdown-item">
                        <small class="text-muted">Rol: {{ auth()->user()->role }}</small>
                    </div>
                    <hr class="my-1">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="nova-dropdown-item w-100 text-start">
                            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="nova-main">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show nova-alert" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show nova-alert" role="alert">
                <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show nova-alert" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <strong>Error:</strong>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ secure_asset('js/drag-drop.js') }}"></script>
    <script src="{{ secure_asset('js/notifications.js') }}"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('novaSidebar');
            const overlay = document.getElementById('novaOverlay');
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }

        function toggleUserMenu() {
            const menu = document.getElementById('userDropdownMenu');
            const icon = document.getElementById('userMenuIcon');
            menu.classList.toggle('show');
            icon.classList.toggle('bi-chevron-up');
            icon.classList.toggle('bi-chevron-down');
        }

        function toggleHeaderMenu() {
            const menu = document.getElementById('headerDropdownMenu');
            menu.classList.toggle('show');
        }

        // Cerrar menús al hacer clic fuera
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('userDropdownMenu');
            const headerMenu = document.getElementById('headerDropdownMenu');
            const userMenuButton = event.target.closest('.nova-user-menu');
            const headerMenuButton = event.target.closest('.nova-header-dropdown-toggle');

            if (!userMenuButton && !userMenu.contains(event.target)) {
                userMenu.classList.remove('show');
            }

            if (!headerMenuButton && !headerMenu.contains(event.target)) {
                headerMenu.classList.remove('show');
            }
        });

        // Cerrar sidebar en móvil al hacer clic en un enlace
        if (window.innerWidth <= 768) {
            document.querySelectorAll('.nova-nav-item').forEach(item => {
                item.addEventListener('click', () => {
                    setTimeout(() => {
                        toggleSidebar();
                    }, 100);
                });
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
