<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Branding
{
    public static function name(): string
    {
        return (string) config('app.brand.name', config('app.name', 'Al Toque'));
    }

    public static function tagline(): string
    {
        return (string) config('app.brand.tagline', 'Gestión gastronómica simple.');
    }

    public static function modulesLine(): string
    {
        return (string) config('app.brand.modules', 'Comandas · Mesas · Cocina · Caja · Stock');
    }

    /**
     * Paleta visible. Los verdes de la marca anterior se leen como la base de Al Toque.
     *
     * @param  array<string, mixed>|null  $settings
     * @return array{primary: string, secondary: string, accent: string}
     */
    public static function palette(?array $settings = null): array
    {
        $legacy = [
            '#1e8081' => '#d06a1f',
            '#22565e' => '#6b3a1e',
            '#1d9e75' => '#d06a1f',
            '#155240' => '#6b3a1e',
            '#082822' => '#24160f',
        ];
        $stored = is_array($settings['colors'] ?? null) ? $settings['colors'] : [];
        $colors = [
            'primary' => (string) ($stored['primary'] ?? '#d06a1f'),
            'secondary' => (string) ($stored['secondary'] ?? '#6b3a1e'),
            'accent' => (string) ($stored['accent'] ?? '#c94a2d'),
        ];
        foreach (['primary', 'secondary'] as $key) {
            $needle = strtolower($colors[$key]);
            if (isset($legacy[$needle])) {
                $colors[$key] = $legacy[$needle];
            }
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
