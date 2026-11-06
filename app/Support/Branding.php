<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Branding
{
    public static function name(): string
    {
        return (string) config('app.brand.name', config('app.name', 'Conurbania'));
    }

    public static function tagline(): string
    {
        return (string) config('app.brand.tagline', 'Restaurante');
    }

    public static function modulesLine(): string
    {
        return (string) config('app.brand.modules', 'Comandas · Mesas · Cocina · Caja · Stock');
    }

    /**
     * Paleta visible: la de Conurbania, o la que el local guardó en Configuración.
     * Los azules de la demo (Al Toque) que hayan quedado guardados vuelven a la base de Conurbania.
     *
     * @param  array<string, mixed>|null  $settings
     * @return array{primary: string, secondary: string, accent: string}
     */
    public static function palette(?array $settings = null): array
    {
        $defaults = ['primary' => '#1e8081', 'secondary' => '#22565e', 'accent' => '#c94a2d'];
        $demo = [
            '#4e8d99' => '#1e8081',
            '#2a5c68' => '#22565e',
            '#16343b' => '#22565e',
        ];
        $stored = is_array($settings['colors'] ?? null) ? $settings['colors'] : [];
        $colors = [];
        foreach ($defaults as $key => $default) {
            $value = (string) ($stored[$key] ?? $default);
            $colors[$key] = $demo[strtolower($value)] ?? $value;
        }

        return $colors;
    }

    /**
     * URL pública del logo (para HTML/navegador).
     * Prioridad: logo del restaurante → APP_LOGO → null (fallback genérico en la vista).
     */
    public static function logoUrl(?array $settings = null): ?string
    {
        $settingsLogo = $settings['logo'] ?? null;
        if (is_string($settingsLogo) && $settingsLogo !== '' && Storage::disk('public')->exists($settingsLogo)) {
            return Storage::url($settingsLogo);
        }

        $configured = config('app.brand.logo');
        if (is_string($configured) && $configured !== '' && is_file(public_path($configured))) {
            return asset($configured);
        }

        return null;
    }

    /**
     * Ruta absoluta en disco (para tickets/impresiones con public_path).
     */
    public static function logoPath(?array $settings = null): ?string
    {
        $settingsLogo = $settings['logo'] ?? null;
        if (is_string($settingsLogo) && $settingsLogo !== '' && Storage::disk('public')->exists($settingsLogo)) {
            return Storage::disk('public')->path($settingsLogo);
        }

        $configured = config('app.brand.logo');
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
