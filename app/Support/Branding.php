<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Branding
{
    public static function name(): string
    {
        return (string) config('app.brand.name', config('app.name', 'Sistema de Gestión'));
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
