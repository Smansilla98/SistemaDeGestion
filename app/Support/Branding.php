<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Branding
{
    public static function name(): string
    {
        return (string) config('app.brand.name', config('app.name', 'Patuka'));
    }

    public static function tagline(): string
    {
        return (string) config('app.brand.tagline', 'Espacio cultural');
    }

    public static function modulesLine(): string
    {
        return (string) config('app.brand.modules', 'Talleres · Agenda · Comunidad · Feria · Barra');
    }

    /**
     * Paleta visible: negro y blanco de Patuka. Los colores de marcas
     * anteriores guardados en la configuración se leen como esta base.
     *
     * @param  array<string, mixed>|null  $settings
     * @return array{primary: string, secondary: string, accent: string}
     */
    public static function palette(?array $settings = null): array
    {
        $defaults = ['primary' => '#111111', 'secondary' => '#000000', 'accent' => '#5c5c5c'];
        $legacy = [
            '#1e8081' => '#111111',
            '#22565e' => '#000000',
            '#1d9e75' => '#111111',
            '#155240' => '#000000',
            '#082822' => '#000000',
            '#d06a1f' => '#111111',
            '#6b3a1e' => '#000000',
            '#24160f' => '#000000',
            '#4e8d99' => '#111111',
            '#2a5c68' => '#000000',
            '#16343b' => '#000000',
            '#c94a2d' => '#5c5c5c',
        ];
        $stored = is_array($settings['colors'] ?? null) ? $settings['colors'] : [];
        $colors = [];
        foreach ($defaults as $key => $default) {
            $value = (string) ($stored[$key] ?? $default);
            $colors[$key] = $legacy[strtolower($value)] ?? $value;
        }

        return $colors;
    }

    /**
     * URL pública del logo (para HTML/navegador).
     * Prioridad: logo cargado en Configuración → logo de Patuka (APP_LOGO / APP_LOGO_LIGHT) → null.
     * $onLight elige la versión negra, para fondos claros.
     */
    public static function logoUrl(?array $settings = null, bool $onLight = false): ?string
    {
        $settingsLogo = $settings['logo'] ?? null;
        if (is_string($settingsLogo) && $settingsLogo !== '' && Storage::disk('public')->exists($settingsLogo)) {
            return Storage::url($settingsLogo);
        }

        $configured = config($onLight ? 'app.brand.logo_light' : 'app.brand.logo');
        if (is_string($configured) && $configured !== '' && is_file(public_path($configured))) {
            return asset($configured);
        }

        return null;
    }

    /**
     * Ruta absoluta en disco (para tickets/impresiones con public_path).
     * Las impresiones van sobre papel blanco: se usa el logo para fondo claro.
     */
    public static function logoPath(?array $settings = null): ?string
    {
        $settingsLogo = $settings['logo'] ?? null;
        if (is_string($settingsLogo) && $settingsLogo !== '' && Storage::disk('public')->exists($settingsLogo)) {
            return Storage::disk('public')->path($settingsLogo);
        }

        $configured = config('app.brand.logo_light');
        if (is_string($configured) && $configured !== '' && is_file(public_path($configured))) {
            return public_path($configured);
        }

        return null;
    }

    public static function hasLogo(?array $settings = null): bool
    {
        return self::logoUrl($settings) !== null;
    }
}
