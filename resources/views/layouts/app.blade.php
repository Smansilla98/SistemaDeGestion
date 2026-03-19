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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="restaurant-id" content="{{ auth()->check() ? auth()->user()->restaurant_id : '' }}">
    <title>@yield('title', 'Sistema de Gestión de Restaurante')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="{{ $colors['primary'] }}">
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
    <style>
        :root {
            /* Paleta personalizable - Colores del restaurante */
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
            
            /* Colores con transparencia para usar en gradientes */
            --conurbania-primary-10: {{ $primaryRgba10 }};
            --conurbania-primary-30: {{ $primaryRgba30 }};
            --conurbania-secondary-10: {{ $secondaryRgba10 }};
            --conurbania-secondary-30: {{ $secondaryRgba30 }};
            --conurbania-accent-10: {{ $accentRgba10 }};
            
            /* Fuentes personalizables */
            --font-primary: '{{ $fonts['primary'] }}', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --font-secondary: '{{ $fonts['secondary'] }}', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-primary) !important;
            background: #f4f7f6 !important;
            color: var(--mosaic-text-primary) !important;
            overflow-x: hidden;
        }
        
        .font-secondary {
            font-family: var(--font-secondary);
        }

        /* Sidebar Mosaic Style */
        .nova-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: var(--mosaic-sidebar-bg) !important;
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
            padding: 1.5rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(0, 0, 0, 0.2);
        }

        .nova-sidebar-header .logo {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nova-sidebar-header .logo .sidebar-brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #22c55e;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .nova-sidebar-header .logo .sidebar-brand-text { display: flex; flex-direction: column; gap: 0; }
        .nova-sidebar-header .logo .sidebar-brand-name { font-size: 1.1rem; line-height: 1.2; }
        .nova-sidebar-header .logo .sidebar-brand-sub { font-size: 0.65rem; font-weight: 500; color: rgba(255,255,255,0.75); letter-spacing: 0.02em; margin-top: 1px; }

        .nova-sidebar-header .logo img {
            height: 40px;
            width: auto;
            max-width: 160px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .nova-sidebar-header .logo span {
            display: none;
        }

        .nova-nav-section-title {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.7);
            padding: 1rem 1.25rem 0.5rem;
            text-transform: uppercase;
        }

        .nova-nav-item .nav-item-badge {
            margin-left: auto;
            min-width: 1.5rem;
            height: 1.5rem;
            padding: 0 0.5rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .nova-nav-item .nav-item-badge.nav-badge-green { background: #22c55e; color: white; }
        .nova-nav-item .nav-item-badge.nav-badge-orange { background: #f97316; color: white; }

        .nova-sidebar-nav {
            padding: 1.5rem 0 5rem;
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
            border-left-color: var(--conurbania-primary);
            transform: translateX(5px);
        }

        .nova-nav-item.active {
            background: linear-gradient(135deg, var(--conurbania-primary-30), var(--conurbania-secondary-30));
            color: white;
            border-left-color: var(--conurbania-primary);
            font-weight: 600;
            box-shadow: 0 4px 15px var(--conurbania-primary-30);
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

        /* Nav Group Styles */
        .nova-nav-group {
            margin: 0.5rem 0.75rem;
        }

        .nova-nav-group-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            cursor: pointer;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.05);
        }

        .nova-nav-group-header:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nova-nav-group-header i:last-child {
            margin-left: auto;
            transition: transform 0.3s ease;
        }

        .nova-nav-group-header.active i:last-child {
            transform: rotate(180deg);
        }

        .nova-nav-group-items {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            padding-left: 1rem;
        }

        .nova-nav-group-items.show {
            max-height: 500px;
        }

        .nova-nav-subitem {
            margin-left: 1.5rem;
            padding-left: 2rem;
            font-size: 0.95rem;
        }

        .nova-sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(0, 0, 0, 0.15);
        }

        .nova-user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .nova-user-menu:hover {
            background: rgba(255, 255, 255, 0.06);
        }

        .nova-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #22c55e;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .nova-user-info {
            flex: 1;
            min-width: 0;
        }

        .nova-user-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nova-user-role {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.75);
        }

        .nova-user-exit {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            padding: 0.25rem;
            border-radius: 6px;
            transition: color 0.2s, background 0.2s;
        }
        .nova-user-exit:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
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
            background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary));
            border: none;
            font-size: 1.25rem;
            color: white;
            cursor: pointer;
            padding: 0.75rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px var(--conurbania-primary-30);
        }

        .nova-sidebar-toggle:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px var(--conurbania-primary-30);
        }

        @media (max-width: 768px) {
            .nova-sidebar-toggle {
                display: block;
            }
        }

        .nova-header-title {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nova-header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nova-header-role-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary));
            color: white;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .nova-header-role-badge i {
            font-size: 1rem;
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
            border-color: var(--conurbania-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px var(--conurbania-primary-10);
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
            background: linear-gradient(135deg, var(--conurbania-primary-10), var(--conurbania-secondary-10));
            color: var(--conurbania-primary);
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
            background: linear-gradient(135deg, var(--conurbania-primary-10), var(--conurbania-secondary-10));
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

        .btn-primary,
        button.btn-primary,
        a.btn-primary {
            background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary)) !important;
            color: white !important;
            border-color: var(--conurbania-primary) !important;
        }

        .btn-primary:hover,
        button.btn-primary:hover,
        a.btn-primary:hover {
            background: linear-gradient(135deg, var(--conurbania-secondary), var(--conurbania-primary)) !important;
            color: white !important;
        }

        .btn-success,
        button.btn-success,
        a.btn-success {
            background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary)) !important;
            color: white !important;
            border-color: var(--conurbania-primary) !important;
        }

        .btn-warning,
        button.btn-warning,
        a.btn-warning {
            background: linear-gradient(135deg, var(--conurbania-medium), var(--conurbania-secondary)) !important;
            color: white !important;
        }

        .btn-info,
        button.btn-info,
        a.btn-info {
            background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary)) !important;
            color: white !important;
            border-color: var(--conurbania-primary) !important;
        }

        .btn-danger,
        button.btn-danger,
        a.btn-danger {
            background: linear-gradient(135deg, var(--conurbania-danger), var(--conurbania-danger-end)) !important;
            color: white !important;
            border-color: var(--conurbania-danger) !important;
        }

        .btn-outline-primary {
            border: 2px solid var(--conurbania-primary);
            color: var(--conurbania-primary);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary));
            color: white;
            border-color: transparent;
        }

        /* Alerts Mosaic Style */
        .alert {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            padding: 1.25rem 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(25, 135, 84, 0.15), rgba(25, 135, 84, 0.1));
            background-color: rgba(212, 237, 218, 0.9) !important;
            color: #0f5132;
            border-left: 4px solid #198754;
            border: 1px solid rgba(25, 135, 84, 0.3);
        }

        .alert-success h5,
        .alert-success strong {
            color: #0f5132;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(220, 53, 69, 0.1));
            background-color: rgba(248, 215, 218, 0.95) !important;
            color: #842029;
            border-left: 4px solid #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .alert-danger h5,
        .alert-danger strong {
            color: #842029;
        }

        .alert-warning {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.2), rgba(255, 193, 7, 0.15));
            background-color: rgba(255, 243, 205, 0.95) !important;
            color: #664d03;
            border-left: 4px solid #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.4);
        }

        .alert-warning h5,
        .alert-warning strong {
            color: #664d03;
        }

        .alert-info {
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.15), rgba(13, 110, 253, 0.1));
            background-color: rgba(207, 226, 255, 0.9) !important;
            color: #084298;
            border-left: 4px solid #0d6efd;
            border: 1px solid rgba(13, 110, 253, 0.3);
        }

        .alert-info h5,
        .alert-info strong {
            color: #084298;
        }

        /* Tables Mosaic Style */
        .table {
            border-radius: 15px;
            overflow: hidden;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--conurbania-primary-10), var(--conurbania-secondary-10));
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
            background: linear-gradient(135deg, var(--conurbania-primary-10), var(--conurbania-secondary-10));
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
            border-color: var(--conurbania-primary);
            box-shadow: 0 0 0 3px var(--conurbania-primary-10);
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
            background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary));
            border-radius: 4px;
        }

        .nova-sidebar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--conurbania-secondary), var(--conurbania-primary));
        }

        /* Modal Mosaic Style */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--conurbania-primary-10), var(--conurbania-secondary-10));
            border-bottom: 2px solid var(--mosaic-border);
            border-radius: 20px 20px 0 0;
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 2px solid var(--mosaic-border);
            border-radius: 0 0 20px 20px;
            padding: 1.5rem;
        }

        /* ========== Optimización móvil y responsive ========== */
        html {
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
        }
        body {
            padding-left: env(safe-area-inset-left);
            padding-right: env(safe-area-inset-right);
            padding-bottom: env(safe-area-inset-bottom);
        }
        @media (max-width: 768px) {
            .nova-sidebar-toggle { min-width: 44px; min-height: 44px; padding: 0.6rem; }
            .nova-header { left: 0; padding: 0 0.75rem; height: 56px; }
            .nova-header-title { font-size: 1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 50vw; }
            .nova-header-role-badge { padding: 0.4rem 0.6rem; font-size: 0.75rem; }
            .nova-header-role-badge span { display: none; }
            .nova-header-dropdown-toggle span { max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .nova-main { margin-left: 0; margin-top: 56px; padding: 0.75rem; min-height: calc(100vh - 56px); padding-bottom: calc(1rem + env(safe-area-inset-bottom)); }
            .card { border-radius: 16px; }
            .card-header { padding: 1rem; font-size: 1rem; }
            .card-body { padding: 1rem; }
            .card:hover { transform: none; }
            .btn { min-height: 44px; padding: 0.65rem 1rem; font-size: 0.9375rem; }
            .btn-sm { min-height: 38px; padding: 0.5rem 0.75rem; }
            .table thead th, .table tbody td { padding: 0.65rem 0.5rem; font-size: 0.875rem; }
            .table tbody tr:hover { transform: none; }
            .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0 -0.75rem; padding: 0 0.75rem; }
            .form-control, .form-select { min-height: 44px; font-size: 16px; }
            .modal-dialog { margin: 0.5rem; max-width: calc(100% - 1rem); }
            .modal-content { border-radius: 16px; }
            .modal-header, .modal-footer { padding: 1rem; }
            .alert { padding: 1rem; }
            .nova-main h1, .nova-main .h1 { font-size: 1.5rem !important; }
            .nova-main h5, .nova-main .h5 { font-size: 1.125rem !important; }
            .page-title-responsive { font-size: clamp(1.25rem, 4vw, 2.5rem) !important; }
        }
        @media (max-width: 576px) {
            .nova-header-title { max-width: 40vw; }
            .nova-main { padding: 0.5rem; }
            .row.g-4 { --bs-gutter-x: 0.75rem; --bs-gutter-y: 0.75rem; }
            .d-flex.gap-2 { gap: 0.5rem !important; }
            .d-flex.gap-3 { gap: 0.5rem !important; }
        }
        @media (hover: none) and (pointer: coarse) {
            .card:hover { transform: none; }
            .btn:hover { transform: none; }
            .nova-nav-item:hover { transform: none; }
            .table tbody tr:hover { transform: none; }
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
                $canTables = $perm && $perm->allowed($navUser, 'tables.view');
                $canOrders = $perm && $perm->allowed($navUser, 'orders.view');
                $canKitchen = $perm && $perm->allowed($navUser, 'kitchen.view');
                $canCashRegister = $perm && $perm->allowed($navUser, 'cash-register.view');
                $canDiscountTypes = $perm && $perm->allowed($navUser, 'discount-types.view');
                $canSectors = $perm && $perm->allowed($navUser, 'sectors.view');
                $canCategories = $perm && $perm->allowed($navUser, 'categories.view');
                $canProducts = $perm && $perm->allowed($navUser, 'products.view');
                $canStock = $perm && $perm->allowed($navUser, 'stock.view');
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
            <!--<a href="{{ route('kitchen.index') }}" class="sb-link {{ request()->routeIs('kitchen.*') ? 'active' : '' }}">
                <i class="bi bi-egg-fried sb-ico"></i>
                <span>Cocina</span>
            </a>-->
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

            @if($canSectors || $canCategories || $canProducts || $canStock || $canUsers || $canPrinters)
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
            @if($canStock)
            <a href="{{ route('stock.index') }}" class="sb-link {{ request()->routeIs('stock.*') ? 'active' : '' }}">
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
            <!--<a href="{{ route('printers.index') }}" class="sb-link {{ request()->routeIs('printers.*') ? 'active' : '' }}">
                <i class="bi bi-printer sb-ico"></i>
                <span>Impresoras</span>
            </a>-->
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

            @if($canConfiguration || $canTutorials || ($navUser && $navUser->role === 'ADMIN'))
            <div class="sb-div"></div>
            @if($canConfiguration)
            <a href="{{ route('configuration.index') }}" class="sb-link {{ request()->routeIs('configuration.*') ? 'active' : '' }}">
                <i class="bi bi-gear sb-ico"></i>
                <span>Configuración</span>
            </a>
            @endif
            @if($canTutorials)
            <!--<a href="{{ route('tutorials.index') }}" class="sb-link {{ request()->routeIs('tutorials.*') ? 'active' : '' }}">
                <i class="bi bi-journal-bookmark sb-ico"></i>
                <span>Tutoriales</span>
            </a>-->
            @endif
            @if($navUser && $navUser->role === 'ADMIN')
            <!--<a href="{{ route('permissions.index') }}" class="sb-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock sb-ico"></i>
                <span>Permisos</span>
            </a>-->
            @endif
            @endif
            @endif
        </nav>

        @auth
        @php
            $roleLabels = ['ADMIN' => 'Administrador', 'GERENTE' => 'Gerente', 'SUPERVISOR' => 'Supervisor', 'MANAGER' => 'Manager', 'CAJERO' => 'Cajero', 'COCINA' => 'Cocina', 'MOZO' => 'Mozo', 'VENDEDOR' => 'Vendedor'];
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
            <button type="button" class="btn btn-g btn-sm d-md-none me-2" onclick="toggleSidebar()" aria-label="Menú">
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
            <div class="dropdown">
                <a href="#" class="tb-user dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="tb-uav">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                    {{ auth()->user()->name }}
                    <i class="bi bi-chevron-down small"></i>
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
    <script src="{{ secure_asset('js/drag-drop.js') }}"></script>
    <script src="{{ secure_asset('js/notifications.js') }}"></script>
    
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
    @stack('scripts')
</body>
</html>
