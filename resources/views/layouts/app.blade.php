<!DOCTYPE html>
<html lang="es">
<head>
    @php
        // Definir $colors y $fonts PRIMERO para que estén disponibles en todo el layout (evitar "Undefined variable $colors")
        $restaurant = auth()->check() ? \App\Models\Restaurant::find(auth()->user()->restaurant_id) : null;
        $settings = $restaurant?->settings ?? [];
        $colors = $settings['colors'] ?? [
            'primary' => '#1e8081',
            'secondary' => '#22565e',
            'accent' => '#c94a2d',
        ];
        $fonts = $settings['fonts'] ?? [
            'primary' => 'Inter',
            'secondary' => 'Roboto',
        ];
        function hexToRgba($hex, $alpha = 0.1) {
            $hex = str_replace('#', '', $hex);
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return "rgba($r, $g, $b, $alpha)";
        }
        $primaryRgba10 = hexToRgba($colors['primary'], 0.1);
        $primaryRgba30 = hexToRgba($colors['primary'], 0.3);
        $secondaryRgba10 = hexToRgba($colors['secondary'], 0.1);
        $secondaryRgba30 = hexToRgba($colors['secondary'], 0.3);
        $accentRgba10 = hexToRgba($colors['accent'], 0.1);
        $primaryFont = str_replace(' ', '+', $fonts['primary']);
        $secondaryFont = str_replace(' ', '+', $fonts['secondary']);
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="restaurant-id" content="{{ auth()->check() ? auth()->user()->restaurant_id : '' }}">
    <meta name="route-name" content="{{ \Illuminate\Support\Facades\Route::currentRouteName() }}">
    <meta name="user-role" content="{{ auth()->check() ? auth()->user()->role : '' }}">
    <title>@yield('title', 'Sistema de Gestión de Restaurante')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="{{ $colors['primary'] }}">
    <link rel="icon" type="image/png" href="{{ asset('icons/icon-192.png') }}">
    <link rel="apple-touch-icon" type="image/png" href="{{ asset('icons/icon-192.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    @if($primaryFont !== $secondaryFont)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ $primaryFont }}:wght@300;400;500;600;700&family={{ $secondaryFont }}:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @else
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ $primaryFont }}:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @endif
    
    @stack('styles')
    {{-- Variables de tema dinámicas (colores/fuentes del restaurante). El resto del CSS vive en resources/css/components/*.css vía Vite. --}}
    <style>
        :root {
            --conurbania-primary: {{ $colors['primary'] }};
            --conurbania-secondary: {{ $colors['secondary'] }};
            --conurbania-accent: {{ $colors['accent'] }};
            --conurbania-dark: #262c3b;
            --conurbania-medium: #7b7d84;
            --conurbania-light: #cfcecd;
            --conurbania-success: {{ $colors['primary'] }};
            --conurbania-success-end: {{ $colors['secondary'] }};
            --conurbania-warning: #7b7d84;
            --conurbania-warning-end: {{ $colors['secondary'] }};
            --conurbania-info: {{ $colors['primary'] }};
            --conurbania-info-end: {{ $colors['secondary'] }};
            --conurbania-danger: {{ $colors['accent'] }};
            --conurbania-danger-end: #e67e51;
            --mosaic-bg: linear-gradient(135deg, {{ $colors['primary'] }} 0%, {{ $colors['secondary'] }} 50%, #262c3b 100%);
            --mosaic-sidebar-bg: linear-gradient(180deg, #262c3b 0%, {{ $colors['secondary'] }} 50%, {{ $colors['primary'] }} 100%);
            --mosaic-card-bg: #ffffff;
            --mosaic-text-primary: #262c3b;
            --mosaic-text-secondary: #7b7d84;
            --mosaic-border: #cfcecd;
            --conurbania-primary-10: {{ $primaryRgba10 }};
            --conurbania-primary-30: {{ $primaryRgba30 }};
            --conurbania-secondary-10: {{ $secondaryRgba10 }};
            --conurbania-secondary-30: {{ $secondaryRgba30 }};
            --conurbania-accent-10: {{ $accentRgba10 }};
            --font-primary: '{{ $fonts['primary'] }}', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --font-secondary: '{{ $fonts['secondary'] }}', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
    </style>
</head>
<body class="conurbania-app">
    <div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="novaSidebar">
        <div class="sb-logo">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                <img src="{{ asset('logo.png') }}" alt="Conurbania" style="max-height: 42px; width: auto; display: block;">
            </a>
        </div>

        <nav class="sb-nav">
            @php
                $perm = auth()->check() ? app(\App\Services\PermissionService::class) : null;
                $navUser = auth()->user();
                $canDashboard = $perm && $perm->allowed($navUser, 'dashboard.view');
                $canTables = ($perm && $perm->allowed($navUser, 'tables.view'))
                    || (($navUser->role ?? null) === \App\Models\User::ROLE_ENCARGADO);
                $canOrders = $perm && $perm->allowed($navUser, 'orders.view');
                $canKitchen = $perm && $perm->allowed($navUser, 'kitchen.view');
                $canCashRegister = $perm && $perm->allowed($navUser, 'cash-register.view');
                $canDiscountTypes = $perm && $perm->allowed($navUser, 'discount-types.view');
                $canSectors = $perm && $perm->allowed($navUser, 'sectors.view');
                $canCategories = $perm && $perm->allowed($navUser, 'categories.view');
                $canProducts = $perm && $perm->allowed($navUser, 'products.view');
                $canStock = $perm && $perm->allowed($navUser, 'stock.view');
                $canStockMozoIns = $perm && $perm->allowed($navUser, 'stock_mozo.create')
                    && (! $canStock || ($navUser->role ?? '') === \App\Models\User::ROLE_MOZO);
                $canUsers = $perm && $perm->allowed($navUser, 'users.view');
                $canPrinters = $perm && $perm->allowed($navUser, 'printers.view');
                $canEvents = $perm && $perm->allowed($navUser, 'events.view');
                $canRecurring = $perm && $perm->allowed($navUser, 'recurring-activities.view');
                $canFixedExpenses = $perm && $perm->allowed($navUser, 'fixed-expenses.view');
                $canReports = $perm && $perm->allowed($navUser, 'reports.view');
                $canConfiguration = $perm && $perm->allowed($navUser, 'configuration.view');
                $canTutorials = $perm && $perm->allowed($navUser, 'tutorials.view');
                $navTablesCount = $canTables && $navUser && $navUser->restaurant_id ? \App\Models\Table::where('restaurant_id', $navUser->restaurant_id)->count() : 0;
                $navPendingOrdersCount = $canOrders && $navUser && $navUser->restaurant_id ? \App\Models\Order::where('restaurant_id', $navUser->restaurant_id)->whereIn('status', ['ABIERTO', 'ENVIADO', 'EN_PREPARACION', 'LISTO'])->count() : 0;
            @endphp

            @if(auth()->check())
            <div class="sb-label">Operaciones</div>
            @if($canDashboard || $canTables || $canOrders || $canKitchen || $canCashRegister || $canDiscountTypes)
            @if($canDashboard)
            <a href="{{ route('dashboard') }}" class="sb-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-3x3-gap sb-ico"></i>
                <span>Página Principal</span>
            </a>
            @endif
            @if($canTables)
            <a href="{{ route('tables.index') }}" class="sb-link {{ request()->routeIs('tables.*') ? 'active' : '' }}">
                <i class="bi bi-table sb-ico"></i>
                <span>Mesas</span>
                @if($navTablesCount > 0)<span class="sb-badge sb-bt">{{ $navTablesCount }}</span>@endif
            </a>
            @endif
            @if($canOrders)
            <a href="{{ route('orders.index') }}" class="sb-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                <i class="bi bi-receipt sb-ico"></i>
                <span>Pedidos</span>
                @if($navPendingOrdersCount > 0)<span class="sb-badge sb-ba">{{ $navPendingOrdersCount }}</span>@endif
            </a>
            @endif
            @if($canKitchen)
            {{--
            <a href="{{ route('kitchen.index') }}" class="sb-link {{ request()->routeIs('kitchen.*') ? 'active' : '' }}">
                <i class="bi bi-egg-fried sb-ico"></i>
                <span>Cocina</span>
            </a>
            --}}
            @endif
            @if($canCashRegister)
            <a href="{{ route('cash-register.index') }}" class="sb-link {{ request()->routeIs('cash-register.*') ? 'active' : '' }}">
                <i class="bi bi-plus-square sb-ico"></i>
                <span>Caja</span>
            </a>
            @endif
            @if($canDiscountTypes)
            <a href="{{ route('discount-types.index') }}" class="sb-link {{ request()->routeIs('discount-types.*') ? 'active' : '' }}">
                <i class="bi bi-percent sb-ico"></i>
                <span>Descuentos</span>
            </a>
            @endif
            @endif

            @if($canSectors || $canCategories || $canProducts || $canStock || $canStockMozoIns || $canUsers || $canPrinters)
            <div class="sb-div"></div>
            <div class="sb-label">Restaurante</div>
            @if($canSectors)
            <a href="{{ route('sectors.index') }}" class="sb-link {{ request()->routeIs('sectors.*') ? 'active' : '' }}">
                <i class="bi bi-building sb-ico"></i>
                <span>Sectores</span>
            </a>
            @endif
            @if($canCategories)
            <a href="{{ route('categories.index') }}" class="sb-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <i class="bi bi-grid-3x3 sb-ico"></i>
                <span>Categorías</span>
            </a>
            @endif
            @if($canProducts)
            <a href="{{ route('products.index') }}" class="sb-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                <i class="bi bi-tag sb-ico"></i>
                <span>Productos</span>
            </a>
            @endif
            @if($canStockMozoIns)
            <a href="{{ route('stock.mozo-insumos.create') }}" class="sb-link {{ request()->routeIs('stock.mozo-insumos.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam sb-ico"></i>
                <span>Insumos</span>
            </a>
            @endif
            @if($canStock)
            <a href="{{ route('stock.index') }}" class="sb-link {{ request()->routeIs('stock.index') || request()->routeIs('stock.movements') || request()->routeIs('stock.create-movement') || request()->routeIs('stock.store-movement') ? 'active' : '' }}">
                <i class="bi bi-bag sb-ico"></i>
                <span>Stock</span>
            </a>
            @endif
            @if($canUsers)
            <a href="{{ route('users.index') }}" class="sb-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-people sb-ico"></i>
                <span>Usuarios</span>
            </a>
            @endif
            @if($canPrinters)
            {{--
            <a href="{{ route('printers.index') }}" class="sb-link {{ request()->routeIs('printers.*') ? 'active' : '' }}">
                <i class="bi bi-printer sb-ico"></i>
                <span>Impresoras</span>
            </a>
            --}}
            @endif
            @endif

            @if($canEvents || $canRecurring)
            <div class="sb-div"></div>
            <div class="sb-label">Planificación</div>
            @if($canEvents)
            <a href="{{ route('events.index') }}" class="sb-link {{ request()->routeIs('events.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-event sb-ico"></i>
                <span>Eventos</span>
            </a>
            @endif
            @if($canRecurring)
            <a href="{{ route('recurring-activities.index') }}" class="sb-link {{ request()->routeIs('recurring-activities.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-repeat sb-ico"></i>
                <span>Actividades Recurrentes</span>
            </a>
            @endif
            @endif

            @if($canFixedExpenses || $canReports)
            <div class="sb-div"></div>
            <div class="sb-label">Finanzas</div>
            @if($canFixedExpenses)
            <a href="{{ route('fixed-expenses.index') }}" class="sb-link {{ request()->routeIs('fixed-expenses.*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack sb-ico"></i>
                <span>Gastos Fijos</span>
            </a>
            @endif
            @if($canReports)
            <a href="{{ route('reports.index') }}" class="sb-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-graph-up sb-ico"></i>
                <span>Reportes</span>
            </a>
            @endif
            @endif

            @if($canConfiguration || $canTutorials || ($navUser && $navUser->isAdminLevel()))
            <div class="sb-div"></div>
            @if($canConfiguration)
            {{--
            <a href="{{ route('configuration.index') }}" class="sb-link {{ request()->routeIs('configuration.*') ? 'active' : '' }}">
                <i class="bi bi-gear sb-ico"></i>
                <span>Configuración</span>
            </a>
            --}}
            @endif
            @if($canTutorials)
            {{--
            <a href="{{ route('tutorials.index') }}" class="sb-link {{ request()->routeIs('tutorials.*') ? 'active' : '' }}">
                <i class="bi bi-journal-bookmark sb-ico"></i>
                <span>Tutoriales</span>
            </a>
            --}}
            @endif
            @if($navUser && $navUser->isAdminLevel())
            {{--
            <a href="{{ route('permissions.index') }}" class="sb-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock sb-ico"></i>
                <span>Permisos</span>
            </a>
            --}}
            @endif
            @if($navUser && $navUser->isSuperAdmin())
            <a href="{{ route('module-usage.index') }}" class="sb-link {{ request()->routeIs('module-usage.*') ? 'active' : '' }}">
                <i class="bi bi-speedometer2 sb-ico"></i>
                <span>Monitor de módulos</span>
            </a>
            @endif
            @endif
            @endif
        </nav>

        @auth
        @php
            $roleLabels = ['SUPERADMIN' => 'Superadmin', 'ADMIN' => 'Administrador', 'GERENTE' => 'Gerente', 'SUPERVISOR' => 'Supervisor', 'MANAGER' => 'Manager', 'CAJERO' => 'Cajero', 'COCINA' => 'Cocina', 'MOZO' => 'Mozo', 'VENDEDOR' => 'Vendedor', 'ENCARGADO' => 'Encargado'];
            $sidebarRoleLabel = $roleLabels[auth()->user()->role ?? ''] ?? auth()->user()->role;
        @endphp
        <div class="sb-footer">
            <div class="sb-user">
                <div class="sb-av">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                <div>
                    <div class="sb-un">{{ auth()->user()->name }}</div>
                    <div class="sb-ur">{{ $sidebarRoleLabel }}</div>
                </div>
                <a href="#" class="sb-lo" onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();" title="Cerrar sesión">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M10 8H3M6 5l-3 3 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 4V3a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1h-3a1 1 0 0 1-1-1v-1" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                </a>
            </div>
            <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
        @endauth
    </aside>

    <div class="nova-overlay" id="novaOverlay" onclick="toggleSidebar()"></div>

    <div class="main">
        <header class="topbar">
            <button type="button" class="btn btn-g btn-sm d-md-none me-2" id="novaSidebarToggle" onclick="toggleSidebar()" aria-label="Abrir menú de navegación" aria-controls="novaSidebar" aria-expanded="false">
                <i class="bi bi-list"></i>
            </button>
            <span class="tb-title">@yield('title', 'Sistema de Gestión')</span>
            <div class="tb-sp"></div>
            @auth
            @php
                $headerUnreadCount = auth()->user()->unreadNotifications()->count();
                $headerNotifications = auth()->user()->unreadNotifications()->latest()->limit(5)->get();
            @endphp
            @if($headerUnreadCount > 0)
            <a href="{{ route('notifications.index') }}" class="btn btn-g btn-sm position-relative" title="Notificaciones">
                <i class="bi bi-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 9px;">{{ $headerUnreadCount > 99 ? '99+' : $headerUnreadCount }}</span>
            </a>
            @endif
            <span class="tb-badge">{{ strtoupper(auth()->user()->role) }}</span>
            <div class="dropdown flex-shrink-0">
                <a href="#" class="tb-user dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="tb-uav">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                    <span class="tb-user-label text-truncate">{{ auth()->user()->name }}</span>
                    <i class="bi bi-chevron-down small tb-user-chevron"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><span class="dropdown-item-text small text-muted">Rol: {{ auth()->user()->role }}</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
            @endauth
        </header>

        <div class="page">
            @yield('content')
        </div>
    </div>
    </div>
    </div><!-- /.layout -->

    <!-- Service worker PWA -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js').catch(function (e) {
                    console.error('SW registration failed', e);
                });
            });
        }
    </script>

    <!-- Scripts para feedback visual mejorado -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar toasts para mensajes flash
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: '{{ session('success') }}',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                    background: '#e6ffed',
                    color: '#1e8081',
                    iconColor: '#1e8081',
                });
            @endif

            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '{{ session('error') }}',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                    background: '#ffe6e6',
                    color: '#c94a2d',
                    iconColor: '#c94a2d',
                });
            @endif

            @if(session('warning'))
                Swal.fire({
                    icon: 'warning',
                    title: 'Advertencia',
                    text: '{{ session('warning') }}',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                    background: '#fff3cd',
                    color: '#856404',
                    iconColor: '#ffc107',
                });
            @endif

            @if(session('info'))
                Swal.fire({
                    icon: 'info',
                    title: 'Información',
                    text: '{{ session('info') }}',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                    background: '#d1ecf1',
                    color: '#0c5460',
                    iconColor: '#17a2b8',
                });
            @endif

            @if(isset($errors) && $errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Validación',
                    html: '<ul style="text-align: left; margin: 1rem 0;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
                    confirmButtonColor: '#c94a2d',
                    confirmButtonText: 'Entendido',
                    width: '500px',
                });
            @endif
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    {{-- drag-drop + notifications se cargan vía Vite (resources/js/app.js). public/js/* está deprecado. --}}
    
    <!-- UI/UX: Sistema de Toasts y Confirmaciones Mejorado -->
    <script>
        // Helper global para toasts
        window.showToast = function(type, title, message, duration = 4000) {
            const configs = {
                success: {
                    icon: 'success',
                    iconColor: '#1e8081',
                    background: '#e6ffed',
                    color: '#1e8081'
                },
                error: {
                    icon: 'error',
                    iconColor: '#c94a2d',
                    background: '#ffe6e6',
                    color: '#c94a2d'
                },
                warning: {
                    icon: 'warning',
                    iconColor: '#ffc107',
                    background: '#fff3cd',
                    color: '#856404'
                },
                info: {
                    icon: 'info',
                    iconColor: '#17a2b8',
                    background: '#d1ecf1',
                    color: '#0c5460'
                }
            };
            
            const config = configs[type] || configs.info;
            
            Swal.fire({
                icon: config.icon,
                title: title,
                text: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: duration,
                timerProgressBar: true,
                background: config.background,
                color: config.color,
                iconColor: config.iconColor,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
        };
        
        // Helper global para confirmaciones
        window.showConfirm = function(title, message, confirmText = 'Sí', cancelText = 'Cancelar', confirmColor = '#1e8081') {
            return Swal.fire({
                icon: 'question',
                title: title,
                text: message,
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#7b7d84',
                confirmButtonText: confirmText,
                cancelButtonText: cancelText
            });
        };
        
        // Helper para mostrar spinner de carga
        window.showLoading = function(message = 'Cargando...') {
            Swal.fire({
                title: message,
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        };
        
        // Helper para ocultar loading
        window.hideLoading = function() {
            Swal.close();
        };
    </script>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('novaSidebar');
            const overlay = document.getElementById('novaOverlay');
            const toggle = document.getElementById('novaSidebarToggle');
            const isOpen = sidebar.classList.toggle('show');
            overlay.classList.toggle('show', isOpen);
            if (toggle) {
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                toggle.setAttribute('aria-label', isOpen ? 'Cerrar menú de navegación' : 'Abrir menú de navegación');
            }
            if (isOpen) {
                const firstLink = sidebar.querySelector('.sb-link, a');
                if (firstLink) firstLink.focus();
            } else if (toggle) {
                toggle.focus();
            }
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

        function toggleNavGroup(groupId) {
            const items = document.getElementById(groupId + '-items');
            const icon = document.getElementById(groupId + '-icon');
            const header = icon.closest('.nova-nav-group-header');
            
            if (items && icon && header) {
                items.classList.toggle('show');
                header.classList.toggle('active');
                icon.classList.toggle('bi-chevron-down');
                icon.classList.toggle('bi-chevron-up');
            }
        }

        // Cerrar menús al hacer clic fuera
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('userDropdownMenu');
            const headerMenu = document.getElementById('headerDropdownMenu');
            const userMenuButton = event.target.closest('.nova-user-menu');
            const headerMenuButton = event.target.closest('.nova-header-dropdown-toggle');

            if (userMenu && !userMenuButton && !userMenu.contains(event.target)) {
                userMenu.classList.remove('show');
            }

            if (headerMenu && !headerMenuButton && !headerMenu.contains(event.target)) {
                headerMenu.classList.remove('show');
            }
        });

        // Cerrar sidebar en móvil al hacer clic en un enlace
        document.querySelectorAll('.nova-sidebar .nova-nav-item, .nova-sidebar .nova-nav-subitem').forEach(item => {
            item.addEventListener('click', function() {
                if (window.matchMedia('(max-width: 768px)').matches && document.getElementById('novaSidebar').classList.contains('show')) {
                    setTimeout(toggleSidebar, 150);
                }
            });
        });
    </script>
    <script>
        // ─────────────────────────────────────────────────────────────────────
        // Tutorial interactivo (overlay + tooltips), dinámico por route.
        // ─────────────────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            const routeMeta = document.querySelector('meta[name="route-name"]');
            if (!routeMeta) return;

            const routeName = routeMeta.getAttribute('content');
            if (!routeName) return;

            const params = new URLSearchParams(window.location.search);
            const forceStart = params.get('tutorial') === '1';
            const disable = params.get('tutorial') === '0';
            if (disable) return;

            const storageBase = `interactiveTutorial:${routeName}`;
            const completedKey = `${storageBase}:completed`;
            const stepKey = `${storageBase}:step`;

            const completed = localStorage.getItem(completedKey) === '1';
            const progressStep = parseInt(localStorage.getItem(stepKey) || '0', 10);

            // Si ya lo completó y no pedimos forzar, no mostramos nada.
            if (!forceStart && completed) return;

            fetch(`/tutorials/interactive/steps?route=${encodeURIComponent(routeName)}`)
                .then(r => r.ok ? r.json() : Promise.reject(r))
                .then(payload => {
                    const steps = payload && payload.steps ? payload.steps : [];
                    if (!steps.length) return;
                    const startIndex = forceStart ? 0 : Math.min(progressStep, steps.length - 1);
                    runInteractiveTutorial(steps, startIndex, storageBase);
                })
                .catch(() => {});
        });

        function runInteractiveTutorial(steps, startIndex, storageBase) {
            const completedKey = `${storageBase}:completed`;
            const stepKey = `${storageBase}:step`;

            const overlay = document.createElement('div');
            overlay.className = 'it-overlay';

            const tooltip = document.createElement('div');
            tooltip.className = 'it-tooltip';

            document.body.appendChild(overlay);
            document.body.appendChild(tooltip);

            const state = { i: startIndex, targetEl: null };

            function setProgress(i, completed) {
                localStorage.setItem(stepKey, String(i));
                localStorage.setItem(completedKey, completed ? '1' : '0');
            }

            function clamp(v, min, max) {
                return Math.max(min, Math.min(max, v));
            }

            function positionTooltip(rect, placement) {
                const padding = 12;
                const ttRect = tooltip.getBoundingClientRect();
                const ttW = ttRect.width || 320;
                const ttH = ttRect.height || 140;

                let top = rect.top;
                let left = rect.left;

                if (placement === 'bottom') {
                    top = rect.bottom + 12;
                    left = rect.left + (rect.width - ttW) / 2;
                } else if (placement === 'top') {
                    top = rect.top - ttH - 12;
                    left = rect.left + (rect.width - ttW) / 2;
                } else if (placement === 'right') {
                    top = rect.top + (rect.height - ttH) / 2;
                    left = rect.right + 12;
                } else if (placement === 'left') {
                    top = rect.top + (rect.height - ttH) / 2;
                    left = rect.left - ttW - 12;
                } else {
                    // default bottom
                    top = rect.bottom + 12;
                    left = rect.left + (rect.width - ttW) / 2;
                }

                left = clamp(left, padding, window.innerWidth - ttW - padding);
                top = clamp(top, padding, window.innerHeight - ttH - padding);

                tooltip.style.left = `${left}px`;
                tooltip.style.top = `${top}px`;
            }

            function highlight(el) {
                document.querySelectorAll('.it-highlight').forEach(x => x.classList.remove('it-highlight'));
                if (el) el.classList.add('it-highlight');
            }

            function findTarget(selector) {
                try {
                    return document.querySelector(selector);
                } catch (e) {
                    return null;
                }
            }

            function renderStep(i) {
                if (i < 0 || i >= steps.length) return;

                const step = steps[i];
                const target = findTarget(step.target);

                if (!target) {
                    // Si no existe el target (por permisos/responsive), saltamos.
                    setTimeout(() => {
                        state.i++;
                        if (state.i >= steps.length) {
                            setProgress(steps.length - 1, true);
                            teardown();
                            return;
                        }
                        renderStep(state.i);
                    }, 250);
                    return;
                }

                state.targetEl = target;
                highlight(target);
                const rect = target.getBoundingClientRect();

                tooltip.innerHTML = `
                    <h3>${step.title || ''}</h3>
                    <p>${step.text || ''}</p>
                    <div class="it-actions">
                        <button type="button" class="it-btn it-btn-secondary" data-dir="prev" ${i === 0 ? 'style="visibility:hidden"' : ''}>Anterior</button>
                        <div style="flex:1"></div>
                        <button type="button" class="it-btn it-btn-next" data-dir="next">${(step.next === null || step.next === undefined) ? 'Finalizar' : 'Siguiente'}</button>
                    </div>
                `;

                // Reposicionar luego de renderizar contenido
                requestAnimationFrame(() => positionTooltip(rect, step.placement || 'bottom'));
                setProgress(i, false);
            }

            function teardown() {
                document.querySelectorAll('.it-highlight').forEach(x => x.classList.remove('it-highlight'));
                overlay.remove();
                tooltip.remove();
            }

            overlay.addEventListener('click', function () {
                // Click sobre overlay = cerrar (guarda progreso y permite reanudar)
                setProgress(state.i, false);
                teardown();
            });

            tooltip.addEventListener('click', function (e) {
                const btn = e.target.closest('button[data-dir]');
                if (!btn) return;
                const dir = btn.getAttribute('data-dir');

                if (dir === 'prev') {
                    highlight(null);
                    state.i = Math.max(0, state.i - 1);
                    renderStep(state.i);
                    return;
                }

                // next
                const step = steps[state.i];
                const next = step.next === null || step.next === undefined ? null : step.next;
                if (next === null) {
                    setProgress(state.i, true);
                    teardown();
                    return;
                }
                highlight(null);
                state.i = next;
                renderStep(state.i);
            });

            renderStep(state.i);

            // Recalcular posición al redimensionar
            window.addEventListener('resize', function () {
                if (state.targetEl) positionTooltip(state.targetEl.getBoundingClientRect(), (steps[state.i] && steps[state.i].placement) ? steps[state.i].placement : 'bottom');
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
