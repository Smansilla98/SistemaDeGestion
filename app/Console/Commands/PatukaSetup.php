<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ActivityCategory;
use App\Models\DistributionRule;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\ModuleLicenseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Deja la organización lista para operar como Patuka Espacio Cultural:
 * tipo de organización, módulos, marca en negro y blanco, categorías, reparto 70/30 y admin.
 * Se puede correr más de una vez: completa lo que falta y no borra nada.
 */
class PatukaSetup extends Command
{
    protected $signature = 'patuka:setup
        {--org= : ID de una organización existente para convertir en Patuka (si no, usa o crea "Patuka")}
        {--admin-user= : Usuario (para ingresar) del administrador; se crea si no existe}
        {--admin-password= : Contraseña del administrador nuevo (mínimo 8 caracteres)}
        {--admin-email= : Email del administrador nuevo (opcional)}
        {--first-run : Para el arranque del contenedor: si ya hay un espacio cultural configurado no lo toca (sólo crea el admin si falta)}';

    protected $description = 'Configura Patuka Espacio Cultural: módulos, marca, categorías, reparto y administrador';

    private const CATEGORIES = [
        'Música' => '#000000', 'Teatro' => '#3d3d3d', 'Danza' => '#5e5e5e', 'Artes visuales' => '#808080',
        'Oficios' => '#a8a8a8', 'Shows' => '#6b6b6b', 'Feria' => '#cccccc',
    ];

    public function handle(ModuleLicenseService $licenses): int
    {
        $password = $this->option('admin-password');
        if ($this->option('admin-user') && $password !== null && mb_strlen((string) $password) < 8) {
            $this->error('La contraseña del administrador tiene que tener al menos 8 caracteres.');

            return self::FAILURE;
        }

        $existing = $this->option('first-run') && ! $this->option('org')
            ? Restaurant::query()->where('kind', 'ESPACIO_CULTURAL')->orderBy('id')->first()
            : null;

        $org = $existing ?? DB::transaction(function () use ($licenses) {
            $org = $this->option('org')
                ? Restaurant::query()->findOrFail((int) $this->option('org'))
                : Restaurant::query()->firstOrCreate(['name' => 'Patuka'], ['is_active' => true]);

            $settings = is_array($org->settings) ? $org->settings : [];
            $settings['colors'] = ['primary' => '#111111', 'secondary' => '#000000', 'accent' => '#5c5c5c'];
            $settings['fonts'] = ['primary' => 'Roboto', 'secondary' => 'Roboto'];
            $settings['public'] = ($settings['public'] ?? []) + [
                'about' => 'Un espacio para aprender, crear y encontrarnos: talleres, shows, feria y barra.',
                'hours' => null, 'instagram' => null, 'whatsapp' => null,
            ];
            $org->forceFill(['kind' => 'ESPACIO_CULTURAL', 'is_active' => true, 'settings' => $settings])->save();

            // Todos los módulos: talleres, agenda, contactos, feria, finanzas y también barra/caja/stock.
            $licenses->save($org, array_fill_keys(array_keys($licenses->catalog()), true));

            foreach (self::CATEGORIES as $name => $color) {
                ActivityCategory::withoutGlobalScopes()->firstOrCreate(['restaurant_id' => $org->id, 'name' => $name], ['color' => $color]);
            }
            DistributionRule::withoutGlobalScopes()->firstOrCreate(['restaurant_id' => $org->id, 'name' => 'Talleres 70/30'], [
                'base' => 'NETO', 'parties' => [['label' => 'Docente', 'percent' => 70], ['label' => 'Espacio', 'percent' => 30]],
            ]);

            return $org;
        });

        $this->info($existing
            ? "Primera ejecución ya hecha: «{$org->name}» (#{$org->id}) no se modificó."
            : "Organización #{$org->id} «{$org->name}» lista como espacio cultural.");

        if ($username = $this->option('admin-user')) {
            if (User::query()->where('username', $username)->exists()) {
                $this->line("El usuario {$username} ya existe: no se cambió su contraseña ni su organización.");
            } elseif ($password === null) {
                $this->warn('Para crear el administrador pasá también --admin-password.');
            } else {
                User::query()->create(['name' => 'Administración Patuka', 'username' => $username,
                    'email' => $this->option('admin-email') ?: $username.'@patuka.local', 'password' => Hash::make((string) $password),
                    'role' => 'ADMIN', 'restaurant_id' => $org->id, 'is_active' => true]);
                $this->info("Administrador «{$username}» creado.");
            }
        }

        if (! config('app.brand.public_org_id') && $org->id !== \App\Support\PublicSite::organization()?->id) {
            $this->warn("La web pública muestra otra organización: agregá PUBLIC_ORG_ID={$org->id} al .env.");
        }
        $this->line('Completá dirección, horarios, Instagram y WhatsApp en Configuración → Web pública.');

        return self::SUCCESS;
    }
}
