<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Restaurant;
use App\Models\User;
use App\Support\DemoEntry;

/**
 * Licencia de módulos por restaurante (venta por separado).
 * Ausencia de mapa = todo habilitado, para no cortar locales ya en uso.
 */
final class ModuleLicenseService
{
    /**
     * @return array<string, array{label: string, description: string, offered?: bool, web: list<string>, api: list<string>}>
     */
    public function catalog(): array
    {
        /** @var array<string, array{label: string, description: string, offered?: bool, web: list<string>, api: list<string>}> $catalog */
        $catalog = config('sellable_modules', []);

        return $catalog;
    }

    /**
     * Módulos que se pueden ofrecer a un restaurante (venta de comida).
     *
     * @return array<string, array{label: string, description: string, offered?: bool, web: list<string>, api: list<string>}>
     */
    public function offered(): array
    {
        return array_filter(
            $this->catalog(),
            fn (array $module): bool => ($module['offered'] ?? true) === true,
        );
    }

    public function enabledForUser(?User $user, string $key): bool
    {
        if ($user === null) {
            return false;
        }

        if (DemoEntry::mode() === DemoEntry::DEMO) {
            return true;
        }

        if (DemoEntry::mode() === DemoEntry::CUSTOM) {
            return DemoEntry::allows($key);
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->isEnabled($user->restaurant_id, $key);
    }

    public function isEnabled(?int $restaurantId, string $key): bool
    {
        if (! array_key_exists($key, $this->catalog())) {
            return true;
        }

        if ($restaurantId === null) {
            return true;
        }

        $map = $this->storedMap($restaurantId);

        if ($map === null || ! array_key_exists($key, $map)) {
            return true;
        }

        return (bool) $map[$key];
    }

    /**
     * @return array<string, bool>
     */
    public function mapFor(?int $restaurantId): array
    {
        $stored = $restaurantId !== null ? $this->storedMap($restaurantId) : null;
        $map = [];

        foreach (array_keys($this->catalog()) as $key) {
            $map[$key] = $stored === null || ! array_key_exists($key, $stored)
                ? true
                : (bool) $stored[$key];
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Restaurant $restaurant, array $input): void
    {
        $map = [];
        foreach (array_keys($this->catalog()) as $key) {
            $map[$key] = filter_var($input[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $settings = $restaurant->settings ?? [];
        $settings['licensed_modules'] = $map;
        $restaurant->settings = $settings;
        $restaurant->save();
    }

    public function moduleForWebRoute(?string $routeName): ?string
    {
        if ($routeName === null || $routeName === '') {
            return null;
        }

        foreach ($this->catalog() as $key => $module) {
            foreach ($module['web'] as $prefix) {
                if ($routeName === rtrim($prefix, '.') || str_starts_with($routeName, $prefix)) {
                    return $key;
                }
            }
        }

        return null;
    }

    public function moduleForApiPath(string $path): ?string
    {
        $path = trim($path, '/');
        if (str_starts_with($path, 'api/')) {
            $path = substr($path, 4);
        }

        $bestKey = null;
        $bestLength = -1;

        foreach ($this->catalog() as $key => $module) {
            foreach ($module['api'] as $prefix) {
                if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                    $length = strlen($prefix);
                    if ($length > $bestLength) {
                        $bestKey = $key;
                        $bestLength = $length;
                    }
                }
            }
        }

        return $bestKey;
    }

    /**
     * @return array<string, bool>|null
     */
    private function storedMap(int $restaurantId): ?array
    {
        $settings = Restaurant::query()->whereKey($restaurantId)->value('settings');
        if (is_string($settings)) {
            $settings = json_decode($settings, true);
        }
        if (! is_array($settings) || ! isset($settings['licensed_modules']) || ! is_array($settings['licensed_modules'])) {
            return null;
        }

        return $settings['licensed_modules'];
    }
}
