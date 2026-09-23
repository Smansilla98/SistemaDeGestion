<?php

namespace App\Services;

use App\Models\PaymentMethodConfiguration;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class PaymentMethodConfigurationService
{
    /**
     * Todos los medios configurados del restaurante (activos e inactivos),
     * para la pantalla de configuración.
     */
    public function all(int $restaurantId): Collection
    {
        return PaymentMethodConfiguration::where('restaurant_id', $restaurantId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Medios que el mozo puede ofrecer en el POS. Si el restaurante todavía
     * no configuró nada (instalación existente, o restaurante nuevo antes de
     * pasar por "Configuración → Medios de cobro"), se devuelven los 4
     * métodos clásicos activos por defecto — el mismo comportamiento que
     * tenía el sistema antes de esta funcionalidad, para no dejar a nadie
     * sin poder cobrar por no haber configurado todavía.
     */
    public function active(int $restaurantId): Collection
    {
        $configured = $this->all($restaurantId);
        if ($configured->isNotEmpty()) {
            return $configured->where('is_active', true)->values();
        }

        $labels = PaymentMethodConfiguration::defaultLabels();

        return collect([
            PaymentMethodConfiguration::TYPE_EFECTIVO,
            PaymentMethodConfiguration::TYPE_DEBITO,
            PaymentMethodConfiguration::TYPE_CREDITO,
            PaymentMethodConfiguration::TYPE_TRANSFERENCIA,
        ])->map(fn (string $type, int $i) => new PaymentMethodConfiguration([
            'restaurant_id' => $restaurantId,
            'type' => $type,
            'label' => $labels[$type],
            'is_active' => true,
            'sort_order' => $i,
        ]));
    }

    public function upsert(int $restaurantId, array $data): PaymentMethodConfiguration
    {
        $config = PaymentMethodConfiguration::updateOrCreate(
            ['restaurant_id' => $restaurantId, 'type' => $data['type']],
            collect($data)->except(['type', 'restaurant_id', 'qr_image'])->all()
        );

        if (! empty($data['remove_qr_image']) && $config->qr_image_path) {
            Storage::disk('public')->delete($config->qr_image_path);
            $config->update(['qr_image_path' => null]);
        }

        return $config;
    }

    public function setQrImage(PaymentMethodConfiguration $config, \Illuminate\Http\UploadedFile $file): PaymentMethodConfiguration
    {
        if ($config->qr_image_path) {
            Storage::disk('public')->delete($config->qr_image_path);
        }

        $path = $file->store('payment-qr', 'public');
        $config->update(['qr_image_path' => $path]);

        return $config->fresh();
    }

    public function disable(PaymentMethodConfiguration $config): PaymentMethodConfiguration
    {
        $config->update(['is_active' => false]);

        return $config;
    }

    public function ensureBelongsTo(PaymentMethodConfiguration $config, Restaurant|int $restaurant): void
    {
        $restaurantId = $restaurant instanceof Restaurant ? $restaurant->id : $restaurant;
        abort_unless((int) $config->restaurant_id === (int) $restaurantId, 404);
    }
}
