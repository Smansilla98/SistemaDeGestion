<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CycleOrder;
use App\Models\OrderCycle;
use App\Models\Restaurant;
use App\Services\Community\ContactService;
use App\Services\Market\OrderCycleService;
use App\Support\PublicSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Nodo de pedidos en la web pública: el vecino arma su pedido de la oferta del ciclo,
 * sin cuenta. Lo recibe la gestión como cualquier otro pedido del ciclo.
 */
class PublicNodoController extends Controller
{
    public function show(int $cycle, OrderCycleService $cycles): View
    {
        [$org, $model] = $this->publicCycle($cycle);
        $offer = $model->offer()->with('product.entrepreneur')->get()
            ->filter(fn ($o) => $o->product?->is_active)
            ->sortBy(fn ($o) => ($o->product->category ?? '').$o->product->name)->values();

        return view('public.nodo', [
            'org' => $org,
            'info' => PublicSite::info($org),
            'cycle' => $model,
            'offer' => $offer,
            'taken' => $cycles->takenQuantities($model),
        ]);
    }

    public function store(Request $request, int $cycle, ContactService $contacts, OrderCycleService $cycles): RedirectResponse
    {
        [$org, $model] = $this->publicCycle($cycle);

        // Campo trampa: las personas no lo ven, los bots lo completan.
        if (filled($request->input('website'))) {
            return redirect()->route('public.nodo', $model->id)->with('ordered', 'Recibimos tu pedido.');
        }

        $data = $request->validate([
            'name' => 'required|string|min:3|max:120',
            'phone' => 'required|string|min:6|max:40',
            'email' => 'nullable|email|max:150',
            'note' => 'nullable|string|max:500',
            'items' => 'required|array',
            'items.*' => 'nullable|numeric|min:0|max:10000',
            'accepts_marketing' => 'nullable|boolean',
        ], [], ['name' => 'nombre y apellido', 'phone' => 'teléfono', 'note' => 'comentario', 'items' => 'pedido']);

        $contact = $contacts->findOrCreate((int) $org->id, $data['name'], $data['email'] ?? null, $data['phone'],
            'CLIENTE', (bool) ($data['accepts_marketing'] ?? false));

        // Desde la web no se pisa un pedido existente: cualquiera podría escribir el teléfono de otra persona.
        if (CycleOrder::query()->where('order_cycle_id', $model->id)->where('contact_id', $contact->id)->exists()) {
            throw ValidationException::withMessages(['phone' => 'Ya tenemos un pedido con ese teléfono en este ciclo. Si querés cambiarlo, escribinos.']);
        }

        try {
            $order = $cycles->placeOrder($model, $contact, $data['items'], false, $data['note'] ?? null);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['items' => $e->validator->errors()->first()]);
        }
        $cycles->notifyNewPublicOrder($model, $order, $contact->name);

        return redirect()->route('public.nodo', $model->id)->with('ordered', sprintf(
            '¡Gracias, %s! Recibimos tu pedido por %s. Lo retirás el %s.',
            $contact->name, \App\Services\Finance\ActivityFinanceService::money((int) $order->total_cents),
            $model->delivery_on->locale('es')->isoFormat('dddd D [de] MMMM'),
        ));
    }

    /** @return array{0: Restaurant, 1: OrderCycle} */
    private function publicCycle(int $id): array
    {
        $org = PublicSite::organization();
        $cycle = $org ? OrderCycle::withoutGlobalScopes()->where('restaurant_id', $org->id)->find($id) : null;
        abort_unless($org && $cycle && $cycle->acceptsOrders() && $cycle->offer()->exists(), 404);

        return [$org, $cycle];
    }
}
