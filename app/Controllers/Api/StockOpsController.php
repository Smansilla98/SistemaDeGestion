<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class StockOpsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $user = $request->user();
        $query = Product::query()
            ->where('restaurant_id', $restaurantId)
            ->where('has_stock', true)
            ->where('is_active', true);

        if ($user?->role === User::ROLE_MOZO) {
            $query->products();
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->string('search').'%');
        }

        $products = $query->orderBy('name')->limit(200)->get();

        $stockMap = Stock::query()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('product_id', $products->pluck('id'))
            ->get()
            ->keyBy('product_id');

        $rows = $products->map(function (Product $product) use ($stockMap) {
            $qty = (int) ($stockMap->get($product->id)?->quantity ?? 0);
            $min = (int) ($product->stock_minimum ?? 0);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type,
                'current_stock' => $qty,
                'stock_minimum' => $min,
                'is_low_stock' => $qty <= $min,
                'unit' => $product->unit ?? null,
            ];
        })->values()->all();

        return ApiResponse::success($rows);
    }

    public function movements(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $query = StockMovement::query()
            ->where('restaurant_id', $restaurantId)
            ->with(['product:id,name,type', 'user:id,name']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        if ($request->user()?->role === User::ROLE_MOZO) {
            $query->whereHas('product', fn ($q) => $q->where('type', 'PRODUCT'));
        }

        $movements = $query->orderByDesc('created_at')->limit(100)->get();

        $rows = $movements->map(fn (StockMovement $m) => [
            'id' => $m->id,
            'type' => $m->type,
            'quantity' => $m->quantity,
            'previous_stock' => $m->previous_stock,
            'new_stock' => $m->new_stock,
            'reason' => $m->reason,
            'reference' => $m->reference,
            'product_id' => $m->product_id,
            'product' => $m->product?->name,
            'user' => $m->user?->name,
            'created_at' => optional($m->created_at)->toIso8601String(),
        ])->values()->all();

        return ApiResponse::success($rows);
    }

    public function storeMovement(Request $request, StockService $stock): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'type' => 'required|in:ENTRADA,SALIDA,AJUSTE',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        if ((int) $product->restaurant_id !== $restaurantId) {
            return ApiResponse::error('Producto de otro restaurante', 403, 'FORBIDDEN');
        }

        $role = $request->user()?->role;
        if ($role === User::ROLE_MOZO) {
            if (! $product->isProduct()) {
                throw ValidationException::withMessages([
                    'product_id' => ['Los mozos solo pueden mover stock de productos a la venta.'],
                ]);
            }
            if ($validated['type'] === 'AJUSTE') {
                throw ValidationException::withMessages([
                    'type' => ['Los mozos solo pueden registrar entradas o salidas.'],
                ]);
            }
        }

        try {
            $movement = $stock->recordMovement([
                'restaurant_id' => $restaurantId,
                'product_id' => $validated['product_id'],
                'user_id' => (int) $request->user()->id,
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? null,
                'reference' => $validated['reference'] ?? 'mobile',
            ]);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'STOCK_MOVEMENT_ERROR');
        }

        return ApiResponse::success($movement->load(['product:id,name', 'user:id,name'])->toArray(), 201, 'Movimiento registrado');
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
