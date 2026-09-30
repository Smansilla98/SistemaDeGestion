<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\ModuleLicenseService;
use Carbon\Carbon;

/**
 * Presupuesto de lista por módulo. No tiene cliente asignado.
 */
final class CommercialQuote
{
    public function __construct(
        private readonly ModuleLicenseService $licenses,
    ) {}

    /**
     * @param  list<string>|null  $only  Claves a incluir. Null = todas las ofrecidas.
     * @return array<string, mixed>
     */
    public function present(?array $only = null): array
    {
        $lines = $this->lines($only);

        $license = (int) array_sum(array_column($lines, 'license_usd'));
        $setup = (int) array_sum(array_column($lines, 'setup_usd'));
        $count = count($lines);
        $issued = Carbon::now();
        $until = $issued->copy()->addDays(30);

        return [
            'product' => 'Al Toque',
            'tagline' => 'Gestión gastronómica simple.',
            'modules' => 'Comandas · Mesas · Cocina · Caja · Stock',
            'from' => 'Santiago Mansilla — Desarrollador Fullstack',
            'issued_label' => $issued->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'valid_label' => $until->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'lines' => $lines,
            'count' => $count,
            'license_usd' => $license,
            'setup_usd' => $setup,
            'offer_usd' => $license + $setup,
            'hosting_usd' => $this->hostingUsd($count),
            'hosting_label' => $this->hostingLabel($count),
            'bundles' => $this->bundles(),
            'catalog' => $this->lines(null),
        ];
    }

    /**
     * @param  list<string>|null  $only
     * @return list<array{key: string, label: string, description: string, license_usd: int, setup_usd: int, offer_usd: int}>
     */
    private function lines(?array $only): array
    {
        $lines = [];
        foreach ($this->licenses->offered() as $key => $module) {
            if ($only !== null && ! in_array($key, $only, true)) {
                continue;
            }
            $license = (int) ($module['license_usd'] ?? 0);
            $setup = (int) ($module['setup_usd'] ?? 0);
            $lines[] = [
                'key' => $key,
                'label' => $module['label'],
                'description' => $module['description'],
                'license_usd' => $license,
                'setup_usd' => $setup,
                'offer_usd' => $license + $setup,
            ];
        }

        return $lines;
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    public function sanitizeKeys(array $keys): array
    {
        $allowed = array_keys($this->licenses->offered());

        return array_values(array_filter(
            $keys,
            fn (string $key): bool => in_array($key, $allowed, true),
        ));
    }

    public function hostingUsd(int $activeModules): int
    {
        if ($activeModules <= 0) {
            return 0;
        }
        if ($activeModules <= 2) {
            return 90;
        }
        if ($activeModules <= 4) {
            return 140;
        }

        return 220;
    }

    /**
     * @return list<array{name: string, keys: list<string>, total_usd: int}>
     */
    private function bundles(): array
    {
        $defs = [
            ['name' => 'A — Arranque', 'keys' => ['orders', 'catalog', 'cash']],
            ['name' => 'B — Salón', 'keys' => ['tables', 'orders', 'kitchen', 'catalog', 'cash']],
            ['name' => 'C — Suite', 'keys' => array_keys($this->licenses->offered())],
        ];

        $priced = [];
        foreach ($this->licenses->offered() as $key => $module) {
            $priced[$key] = (int) ($module['license_usd'] ?? 0) + (int) ($module['setup_usd'] ?? 0);
        }

        $bundles = [];
        foreach ($defs as $def) {
            $total = 0;
            foreach ($def['keys'] as $key) {
                $total += $priced[$key] ?? 0;
            }
            $bundles[] = [
                'name' => $def['name'],
                'keys' => $def['keys'],
                'total_usd' => $total,
            ];
        }

        return $bundles;
    }

    private function hostingLabel(int $activeModules): string
    {
        if ($activeModules <= 0) {
            return 'Sin módulos';
        }
        if ($activeModules <= 2) {
            return 'Escalón 1 · hasta 2 módulos';
        }
        if ($activeModules <= 4) {
            return 'Escalón 2 · 3 o 4 módulos';
        }

        return 'Escalón 3 · 5 o más módulos';
    }
}
