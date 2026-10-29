<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Restaurant;

/**
 * Web pública del espacio: qué organización muestra y sus datos de contacto.
 */
final class PublicSite
{
    /** Talleres que se publican y aceptan inscripción desde la web. */
    public const OPEN_STATUSES = ['ABIERTO', 'EN_CURSO'];

    /**
     * La organización de la web: PUBLIC_ORG_ID, si no el primer espacio cultural activo, si no la primera.
     */
    public static function organization(): ?Restaurant
    {
        $id = config('app.brand.public_org_id');
        if ($id) {
            return Restaurant::query()->whereKey((int) $id)->first();
        }

        return Restaurant::query()->where('is_active', true)->where('kind', 'ESPACIO_CULTURAL')->orderBy('id')->first()
            ?? Restaurant::query()->orderBy('id')->first();
    }

    /**
     * Datos de contacto y textos editables en Configuración → Web pública.
     *
     * @return array{about: string, hours: string, instagram: ?string, instagram_url: ?string, whatsapp: ?string, whatsapp_url: ?string, maps_url: ?string, address: ?string, phone: ?string, email: ?string}
     */
    public static function info(?Restaurant $org): array
    {
        $settings = is_array($org?->settings) ? $org->settings : [];
        $public = is_array($settings['public'] ?? null) ? $settings['public'] : [];

        $instagram = ltrim(trim((string) ($public['instagram'] ?? '')), '@') ?: null;
        $whatsapp = preg_replace('/\D+/', '', (string) ($public['whatsapp'] ?? '')) ?: null;
        $address = $org?->address ?: null;

        return [
            'about' => (string) ($public['about'] ?? 'Un espacio para aprender, crear y encontrarnos: talleres, shows, feria y barra.'),
            'hours' => (string) ($public['hours'] ?? ''),
            'instagram' => $instagram,
            'instagram_url' => $instagram ? 'https://instagram.com/'.rawurlencode($instagram) : null,
            'whatsapp' => $whatsapp,
            'whatsapp_url' => $whatsapp ? 'https://wa.me/'.$whatsapp : null,
            'maps_url' => $address ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($address) : null,
            'address' => $address,
            'phone' => $org?->phone ?: null,
            'email' => $org?->email ?: null,
        ];
    }
}
