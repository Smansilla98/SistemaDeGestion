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
            --mosaic-primary: #667eea;
            --mosaic-secondary: #764ba2;
            --mosaic-success: #11998e;
            --mosaic-success-end: #38ef7d;
            --mosaic-warning: #f093fb;
            --mosaic-warning-end: #f5576c;
            --mosaic-info: #4facfe;
            --mosaic-info-end: #00f2fe;
            --mosaic-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --mosaic-sidebar-bg: linear-gradient(180deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            --mosaic-card-bg: #ffffff;
            --mosaic-text-primary: #1a202c;
            --mosaic-text-secondary: #718096;
            --mosaic-border: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--mosaic-bg);
            color: var(--mosaic-text-primary);
            overflow-x: hidden;
        }

        /* Sidebar Mosaic Style */
        .nova-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: var(--mosaic-sidebar-bg);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            overflow-x: hidden;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
        }

        .nova-sidebar.collapsed {
            transform: translateX(-100%);
        }

        @media (max-width: 768px) {
            .nova-sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            .nova-sidebar.show {
                transform: translateX(0);
            }
        }

        .nova-sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }

        .nova-sidebar-header .logo {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nova-sidebar-header .logo i {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
        }

        .nova-sidebar-nav {
            padding: 1.5rem 0;
        }

        .nova-nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid transparent;
            margin: 0.25rem 0.75rem;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }

        .nova-nav-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }

        .nova-nav-item:hover::before {
            left: 100%;
        }

        .nova-nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: #667eea;
            transform: translateX(5px);
        }

        .nova-nav-item.active {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
            color: white;
            border-left-color: #667eea;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .nova-nav-item i {
            font-size: 1.25rem;
            width: 24px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .nova-nav-item:hover i,
        .nova-nav-item.active i {
            transform: scale(1.2);
        }

        .nova-sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
        }

        .nova-user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.05);
        }

        .nova-user-menu:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .nova-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .nova-user-info {
            flex: 1;
            min-width: 0;
        }

        .nova-user-name {
            font-size: 0.875rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nova-user-role {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Header Mosaic Style */
        .nova-header {
            position: fixed;
            top: 0;
            left: 280px;
            right: 0;
            height: 70px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            z-index: 999;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 768px) {
            .nova-header {
                left: 0;
            }
        }

        .nova-header-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nova-sidebar-toggle {
            display: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            font-size: 1.25rem;
            color: white;
            cursor: pointer;
            padding: 0.75rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .nova-sidebar-toggle:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .nova-sidebar-toggle {
                display: block;
            }
        }

        .nova-header-title {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            background: white;
            border: 2px solid var(--mosaic-border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .nova-header-dropdown-toggle:hover {
            border-color: var(--mosaic-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .nova-header-dropdown-menu {
            position: absolute;
            top: calc(100% + 0.75rem);
            right: 0;
            min-width: 220px;
            background: white;
            border: 1px solid var(--mosaic-border);
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            padding: 0.75rem;
            display: none;
            z-index: 1000;
        }

        .nova-header-dropdown-menu.show {
            display: block;
            animation: fadeInDown 0.3s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .nova-dropdown-item {
            display: block;
            padding: 0.875rem 1rem;
            color: var(--mosaic-text-primary);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .nova-dropdown-item:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            color: var(--mosaic-primary);
            transform: translateX(5px);
        }

        /* Main Content Mosaic Style */
        .nova-main {
            margin-left: 280px;
            margin-top: 70px;
            padding: 2rem;
            min-height: calc(100vh - 70px);
        }

        @media (max-width: 768px) {
            .nova-main {
                margin-left: 0;
                padding: 1rem;
            }
        }

        /* Global Mosaic Cards */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-bottom: 2px solid var(--mosaic-border);
            padding: 1.5rem;
            font-weight: 700;
            border-radius: 20px 20px 0 0;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Buttons Mosaic Style */
        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #fa709a, #fee140);
            color: white;
        }

        .btn-outline-primary {
            border: 2px solid var(--mosaic-primary);
            color: var(--mosaic-primary);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
        }

        /* Alerts Mosaic Style */
        .alert {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 1.25rem 1.5rem;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(17, 153, 142, 0.1), rgba(56, 239, 125, 0.1));
            color: #22543d;
            border-left: 4px solid #11998e;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(250, 112, 154, 0.1), rgba(254, 225, 64, 0.1));
            color: #742a2a;
            border-left: 4px solid #fa709a;
        }

        .alert-warning {
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.1), rgba(245, 87, 108, 0.1));
            color: #744210;
            border-left: 4px solid #f093fb;
        }

        .alert-info {
            background: linear-gradient(135deg, rgba(79, 172, 254, 0.1), rgba(0, 242, 254, 0.1));
            color: #2c5282;
            border-left: 4px solid #4facfe;
        }

        /* Tables Mosaic Style */
        .table {
            border-radius: 15px;
            overflow: hidden;
        }

        .table thead th {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            color: var(--mosaic-text-primary);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            padding: 1rem;
            border: none;
        }

        .table tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--mosaic-border);
            color: var(--mosaic-text-primary);
        }

        .table tbody tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        /* Badges Mosaic Style */
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        /* Forms Mosaic Style */
        .form-control,
        .form-select {
            border-radius: 12px;
            border: 2px solid var(--mosaic-border);
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--mosaic-primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Overlay para móvil */
        .nova-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 999;
        }

        .nova-overlay.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Scrollbar personalizado */
        .nova-sidebar::-webkit-scrollbar {
            width: 8px;
        }

        .nova-sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .nova-sidebar::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 4px;
        }

        .nova-sidebar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
        }

        /* Modal Mosaic Style */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-bottom: 2px solid var(--mosaic-border);
            border-radius: 20px 20px 0 0;
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 2px solid var(--mosaic-border);
            border-radius: 0 0 20px 20px;
            padding: 1.5rem;
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
            <h5 class="nova-header-title mb-0">@yield('title', 'Sistema de Gestión')</h5>
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
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
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
