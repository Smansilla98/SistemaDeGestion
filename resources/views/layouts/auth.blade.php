<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') - Sistema de Gestión de Restaurante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @php
        // Obtener configuraciones visuales del restaurante (si hay un restaurante por defecto o usar valores por defecto)
        // En el login no hay usuario autenticado, así que usamos valores por defecto o el primer restaurante
        $restaurant = \App\Models\Restaurant::first();
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
        
        // Función para convertir hex a rgba con transparencia
        function hexToRgba($hex, $alpha = 0.1) {
            $hex = str_replace('#', '', $hex);
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return "rgba($r, $g, $b, $alpha)";
        }
        
        // Calcular versiones con transparencia
        $primaryRgba10 = hexToRgba($colors['primary'], 0.1);
        $primaryRgba30 = hexToRgba($colors['primary'], 0.3);
        $secondaryRgba10 = hexToRgba($colors['secondary'], 0.1);
        $secondaryRgba30 = hexToRgba($colors['secondary'], 0.3);
        $accentRgba10 = hexToRgba($colors['accent'], 0.1);
        
        // Cargar fuentes de Google Fonts
        $primaryFont = str_replace(' ', '+', $fonts['primary']);
        $secondaryFont = str_replace(' ', '+', $fonts['secondary']);
        
        // Logo
        $logo = $settings['logo'] ?? null;
    @endphp
    
    @if($primaryFont !== $secondaryFont)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ $primaryFont }}:wght@300;400;500;600;700&family={{ $secondaryFont }}:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @else
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ $primaryFont }}:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @endif
    
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
            --mosaic-card-bg: #ffffff;
            --mosaic-text-primary: #262c3b;
            --mosaic-text-secondary: #7b7d84;
            --mosaic-border: #cfcecd;
            
            /* Colores con transparencia */
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
            background: var(--mosaic-bg) !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .auth-card {
            background: var(--mosaic-card-bg);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: none;
            overflow: hidden;
            position: relative;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary));
        }

        .auth-card .card-body {
            padding: 3rem;
        }

        .auth-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--conurbania-primary-10), var(--conurbania-secondary-10));
            border-radius: 20px;
            padding: 1rem;
        }

        .auth-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .auth-logo i {
            font-size: 3rem;
            color: var(--conurbania-primary);
        }

        .auth-title {
            font-family: var(--font-primary);
            font-weight: 700;
            background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .auth-subtitle {
            color: var(--mosaic-text-secondary);
            font-family: var(--font-secondary);
        }

        .form-label {
            font-weight: 600;
            color: var(--mosaic-text-primary);
            font-family: var(--font-primary);
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            border: 2px solid var(--mosaic-border);
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            font-family: var(--font-secondary);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--conurbania-primary);
            box-shadow: 0 0 0 3px var(--conurbania-primary-10);
            outline: none;
        }

        .form-check-input:checked {
            background-color: var(--conurbania-primary);
            border-color: var(--conurbania-primary);
        }

        .form-check-input:focus {
            border-color: var(--conurbania-primary);
            box-shadow: 0 0 0 3px var(--conurbania-primary-10);
        }

        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            font-family: var(--font-primary);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-primary,
        button.btn-primary {
            background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary)) !important;
            color: white !important;
        }

        .btn-primary:hover,
        button.btn-primary:hover {
            background: linear-gradient(135deg, var(--conurbania-secondary), var(--conurbania-primary)) !important;
            color: white !important;
        }

        .alert {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 1.25rem 1.5rem;
        }

        .alert-danger {
            background: linear-gradient(135deg, var(--conurbania-accent-10), rgba(230, 126, 81, 0.1));
            color: var(--conurbania-danger);
            border-left: 4px solid var(--conurbania-danger);
        }

        .text-muted {
            color: var(--mosaic-text-secondary) !important;
            font-family: var(--font-secondary);
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }

        .border-top {
            border-color: var(--mosaic-border) !important;
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: var(--conurbania-danger);
        }

        @media (max-width: 768px) {
            .auth-card .card-body {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-6">
                <div class="card auth-card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="auth-logo">
                                @if($logo && Storage::disk('public')->exists($logo))
                                    <img src="{{ Storage::url($logo) }}" alt="Logo">
                                @else
                                    <img src="{{ asset('logo.png') }}" alt="Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <i class="bi bi-cup-hot" style="display: none;"></i>
                                @endif
                            </div>
                            <h2 class="auth-title">Sistema de Gestión</h2>
                            <p class="auth-subtitle">Inicia sesión para continuar</p>
                        </div>

                        @if($errors->any())
                            <div class="alert alert-danger mb-4">
                                @foreach($errors->all() as $error)
                                    <div><i class="bi bi-exclamation-circle"></i> {{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Integrar SweetAlert2 para mensajes de error
        @if($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Error de Validación',
                html: `{!! implode('<br>', $errors->all()) !!}`,
                confirmButtonColor: 'var(--conurbania-danger)',
            });
        @endif
    </script>
</body>
</html>

