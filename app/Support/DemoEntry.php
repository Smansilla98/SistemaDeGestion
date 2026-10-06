<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * Elección de entrada en el demo de main: ver todo el producto
 * o recorrerlo solo con los módulos elegidos en esta sesión.
 */
final class DemoEntry
{
    public const DEMO = 'demo';

    public const CUSTOM = 'custom';

    public static function decides(?User $user): bool
    {
        // Solo el demo pide elegir entrada; en una instalación de cliente se apaga con
        // DEMO_ENTRY_ENABLED=false (por defecto sigue encendido, como hasta ahora).
        if ($user === null || ! config('app.demo_entry', true)) {
            return false;
        }

        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true);
    }

    public static function mode(): ?string
    {
        $request = request();
        if ($request === null || ! $request->hasSession()) {
            return null;
        }

        $mode = $request->session()->get('entry_mode');

        return in_array($mode, [self::DEMO, self::CUSTOM], true) ? $mode : null;
    }

    public static function enterDemo(): void
    {
        session([
            'entry_mode' => self::DEMO,
        ]);
        session()->forget('preview_modules');
    }

    /**
     * @param  array<string, bool>  $modules
     */
    public static function enterCustom(array $modules): void
    {
        session([
            'entry_mode' => self::CUSTOM,
            'preview_modules' => $modules,
        ]);
    }

    public static function allows(string $key): bool
    {
        $request = request();
        if ($request === null || ! $request->hasSession()) {
            return false;
        }

        $map = $request->session()->get('preview_modules', []);

        return is_array($map) && (bool) ($map[$key] ?? false);
    }

    public static function homeRoute(User $user): string
    {
        return match ($user->role) {
            User::ROLE_MOZO => 'tables.index',
            User::ROLE_COCINA => 'kitchen.index',
            User::ROLE_CAJERO => 'cash-register.index',
            default => 'dashboard',
        };
    }
}
