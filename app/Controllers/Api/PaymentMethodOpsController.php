<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\Models\PaymentMethodConfiguration;
use App\Services\PaymentMethodConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentMethodOpsController extends Controller
{
    public function index(Request $request, PaymentMethodConfigurationService $service): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        return ApiResponse::success($service->all($restaurantId)->values()->all());
    }

    /**
     * Medios activos para el POS (cobrar mesa/orden). Si el restaurante
     * todavía no configuró nada, devuelve los métodos clásicos activos
     * por defecto (ver PaymentMethodConfigurationService::active).
     */
    public function active(Request $request, PaymentMethodConfigurationService $service): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        return ApiResponse::success($service->active($restaurantId)->values()->all());
    }

    public function store(Request $request, PaymentMethodConfigurationService $service): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $validated = $this->validatePayload($request);
        $validated['label'] = $validated['label'] ?? PaymentMethodConfiguration::defaultLabels()[$validated['type']];

        $config = $service->upsert($restaurantId, $validated);

        return ApiResponse::success($config->fresh()->toArray(), 201, 'Medio de cobro guardado');
    }

    public function update(Request $request, int $id, PaymentMethodConfigurationService $service): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $config = PaymentMethodConfiguration::where('restaurant_id', $restaurantId)->find($id);
        if ($config === null) {
            return ApiResponse::error('Medio de cobro no encontrado', 404, 'NOT_FOUND');
        }

        $validated = $this->validatePayload($request, $config->type);
        $config = $service->upsert($restaurantId, $validated);

        return ApiResponse::success($config->fresh()->toArray(), 200, 'Medio de cobro actualizado');
    }

    /**
     * "Eliminar" un medio de cobro en realidad lo desactiva — nunca se borra
     * la fila (no rompe nada histórico porque Payment no la referencia, pero
     * el pedido explícito fue "desactivar en lugar de eliminar").
     */
    public function destroy(Request $request, int $id, PaymentMethodConfigurationService $service): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $config = PaymentMethodConfiguration::where('restaurant_id', $restaurantId)->find($id);
        if ($config === null) {
            return ApiResponse::error('Medio de cobro no encontrado', 404, 'NOT_FOUND');
        }

        $service->disable($config);

        return ApiResponse::success(null, 200, 'Medio de cobro desactivado');
    }

    public function uploadQrImage(Request $request, int $id, PaymentMethodConfigurationService $service): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $config = PaymentMethodConfiguration::where('restaurant_id', $restaurantId)->find($id);
        if ($config === null) {
            return ApiResponse::error('Medio de cobro no encontrado', 404, 'NOT_FOUND');
        }

        $request->validate([
            'qr_image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $config = $service->setQrImage($config, $request->file('qr_image'));

        return ApiResponse::success($config->toArray(), 200, 'QR actualizado');
    }

    private function validatePayload(Request $request, ?string $lockedType = null): array
    {
        $rules = [
            'label' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'alias' => ['nullable', 'string', 'max:255'],
            'cvu' => ['nullable', 'string', 'max:30'],
            'cbu' => ['nullable', 'string', 'max:30'],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'cuit' => ['nullable', 'string', 'max:15'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'remove_qr_image' => ['sometimes', 'boolean'],
        ];

        if ($lockedType === null) {
            $rules['type'] = ['required', 'string', 'in:'.implode(',', PaymentMethodConfiguration::types())];
        }

        $validated = $request->validate($rules);
        $validated['type'] = $lockedType ?? $validated['type'];

        return $validated;
    }

    private function requireRestaurantId(Request $request): int|JsonResponse
    {
        $rid = $request->user()?->restaurant_id;
        if (! $rid) {
            return ApiResponse::error('Usuario sin restaurante asignado', 403, 'NO_RESTAURANT');
        }

        return (int) $rid;
    }
}
