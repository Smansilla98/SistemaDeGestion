<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\StockReset;
use App\Services\StockResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockResetController extends Controller
{
    public function __construct(private StockResetService $resets) {}

    public function index(Request $request): View
    {
        $restaurantId = (int) $request->user()->restaurant_id;

        return view('stock.reset', [
            'preview' => $this->resets->preview($restaurantId),
            'history' => StockReset::query()
                ->where('restaurant_id', $restaurantId)
                ->with('user:id,name')
                ->latest()
                ->limit(10)
                ->get(),
            'confirmation' => StockResetService::CONFIRMATION,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'counts' => ['nullable', 'array'],
            'counts.*' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'notes' => ['nullable', 'string', 'max:500'],
            'confirmation' => ['required', Rule::in([StockResetService::CONFIRMATION])],
        ], [
            'confirmation.in' => 'Escribí "'.StockResetService::CONFIRMATION.'" para confirmar.',
            'confirmation.required' => 'Escribí "'.StockResetService::CONFIRMATION.'" para confirmar.',
        ]);

        try {
            $reset = $this->resets->execute(
                (int) $request->user()->restaurant_id,
                $request->user(),
                $validated['counts'] ?? [],
                $validated['notes'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('stock.reset.index')->with(
            'success',
            "Puesta a punto #{$reset->id} lista: {$reset->orders_cancelled} pedido(s) frenados, "
            ."{$reset->tables_freed} mesa(s) liberadas y {$reset->products_adjusted} stock(s) ajustados."
        );
    }
}
