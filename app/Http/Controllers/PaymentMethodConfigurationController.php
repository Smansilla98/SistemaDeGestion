<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethodConfiguration;
use App\Services\PaymentMethodConfigurationService;
use Illuminate\Http\Request;

class PaymentMethodConfigurationController extends Controller
{
    public function __construct(
        private PaymentMethodConfigurationService $configurations,
    ) {
        $this->middleware('role:ADMIN,GERENTE');
    }

    /**
     * Configuración → Medios de cobro. Muestra los 5 tipos siempre (aunque
     * el restaurante todavía no haya guardado nada para alguno), con la fila
     * ya guardada si existe o un objeto "en blanco" del mismo tipo si no.
     */
    public function index()
    {
        $restaurantId = auth()->user()->restaurant_id;
        $existing = $this->configurations->all($restaurantId)->keyBy('type');
        $labels = PaymentMethodConfiguration::defaultLabels();

        $rows = collect(PaymentMethodConfiguration::types())->map(
            fn (string $type) => $existing->get($type) ?? new PaymentMethodConfiguration([
                'restaurant_id' => $restaurantId,
                'type' => $type,
                'label' => $labels[$type],
                'is_active' => true,
                'sort_order' => 0,
            ])
        );

        return view('configuration.payment-methods', ['rows' => $rows]);
    }

    public function update(Request $request, string $type)
    {
        if (! in_array($type, PaymentMethodConfiguration::types(), true)) {
            abort(404);
        }

        $validated = $request->validate([
            'label' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
            'alias' => 'nullable|string|max:255',
            'cvu' => 'nullable|string|max:30',
            'cbu' => 'nullable|string|max:30',
            'account_holder' => 'nullable|string|max:255',
            'cuit' => 'nullable|string|max:15',
            'instructions' => 'nullable|string|max:1000',
            'qr_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_qr_image' => 'sometimes|boolean',
        ]);

        $restaurantId = auth()->user()->restaurant_id;
        $validated['type'] = $type;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['label'] = $validated['label'] ?: PaymentMethodConfiguration::defaultLabels()[$type];

        $config = $this->configurations->upsert($restaurantId, $validated);

        if ($request->hasFile('qr_image')) {
            $this->configurations->setQrImage($config, $request->file('qr_image'));
        }

        return back()->with('success', $config->label.' actualizado');
    }

    public function disable(PaymentMethodConfiguration $config)
    {
        abort_unless((int) $config->restaurant_id === (int) auth()->user()->restaurant_id, 404);

        $this->configurations->disable($config);

        return back()->with('success', $config->label.' desactivado');
    }
}
