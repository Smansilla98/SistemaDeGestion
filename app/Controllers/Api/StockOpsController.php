<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\Core\Rbac\RbacChecker;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
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

        $type = strtoupper((string) $request->input('type', ''));
        $canMozoInsumo = RbacChecker::roleHasAny($user?->role, ['stock_mozo.create', 'stock.write']);

        if ($type === 'INSUMO') {
            if ($user?->role === User::ROLE_MOZO && ! $canMozoInsumo) {
                return ApiResponse::error('No tenés permiso para ver insumos', 403, 'FORBIDDEN');
            }
            $query->insumos();
        } elseif ($user?->role === User::ROLE_MOZO) {
            $query->products();
        } elseif ($type === 'PRODUCT') {
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
            ->with(['product:id,name,type', 'user:id,name', 'purchase.supplier:id,name']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }

        if ($request->user()?->role === User::ROLE_MOZO) {
            $canMozoInsumo = RbacChecker::roleHasAny($request->user()?->role, ['stock_mozo.create', 'stock.write']);
            if ($canMozoInsumo) {
                $query->whereHas('product', fn ($q) => $q->whereIn('type', ['PRODUCT', 'INSUMO']));
            } else {
                $query->whereHas('product', fn ($q) => $q->where('type', 'PRODUCT'));
            }
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
            'purchase' => $m->purchase ? [
                'supplier_id' => $m->purchase->supplier_id,
                'supplier' => $m->purchase->supplier?->name,
                'unit_cost' => $m->purchase->unit_cost !== null ? (float) $m->purchase->unit_cost : null,
                'total_cost' => $m->purchase->total_cost !== null ? (float) $m->purchase->total_cost : null,
                'purchase_date' => optional($m->purchase->purchase_date)?->toDateString(),
                'invoice_number' => $m->purchase->invoice_number,
            ] : null,
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
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'new_supplier_name' => 'nullable|string|max:255',
            'unit_cost' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'invoice_number' => 'nullable|string|max:255',
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        if ((int) $product->restaurant_id !== $restaurantId) {
            return ApiResponse::error('Producto de otro restaurante', 403, 'FORBIDDEN');
        }

        $role = $request->user()?->role;
        $canMozoInsumo = RbacChecker::roleHasAny($role, ['stock_mozo.create', 'stock.write']);

        if ($role === User::ROLE_MOZO) {
            if ($product->isInsumo()) {
                if (! $canMozoInsumo || $validated['type'] !== 'ENTRADA') {
                    throw ValidationException::withMessages([
                        'product_id' => ['Solo podés registrar entradas de insumos con el permiso correspondiente.'],
                    ]);
                }
            } elseif (! $product->isProduct()) {
                throw ValidationException::withMessages([
                    'product_id' => ['Los mozos solo pueden mover stock de productos a la venta.'],
                ]);
            } elseif ($validated['type'] === 'AJUSTE') {
                throw ValidationException::withMessages([
                    'type' => ['Los mozos solo pueden registrar entradas o salidas.'],
                ]);
            }
        }

        $payload = [
            'restaurant_id' => $restaurantId,
            'product_id' => $validated['product_id'],
            'user_id' => (int) $request->user()->id,
            'type' => $validated['type'],
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'] ?? null,
            'reference' => $validated['reference'] ?? 'mobile',
        ];

        if ($validated['type'] === 'ENTRADA' && (
            ! empty($validated['supplier_id'])
            || ! empty($validated['new_supplier_name'])
            || isset($validated['unit_cost'])
        )) {
            $supplierId = $validated['supplier_id'] ?? null;
            if (! $supplierId && ! empty($validated['new_supplier_name'])) {
                $supplier = Supplier::query()->create([
                    'restaurant_id' => $restaurantId,
                    'name' => $validated['new_supplier_name'],
                    'is_active' => true,
                ]);
                $supplierId = $supplier->id;
            }
            if ($supplierId && isset($validated['unit_cost']) && ! empty($validated['purchase_date'])) {
                $payload['purchase_data'] = [
                    'supplier_id' => $supplierId,
                    'unit_cost' => $validated['unit_cost'],
                    'purchase_date' => $validated['purchase_date'],
                    'invoice_number' => $validated['invoice_number'] ?? null,
                ];
            }
        }

        try {
            $movement = $stock->recordMovement($payload);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'STOCK_MOVEMENT_ERROR');
        }

        return ApiResponse::success(
            $movement->load(['product:id,name', 'user:id,name', 'purchase.supplier:id,name'])->toArray(),
            201,
            'Movimiento registrado'
        );
    }

    /**
     * Listado de insumos para el flujo mozo (GET) / ingreso simple (POST).
     */
    public function mozoInsumos(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $products = Product::query()
            ->where('restaurant_id', $restaurantId)
            ->insumos()
            ->where('is_active', true)
            ->where('has_stock', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'type']);

        $stockMap = Stock::query()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('product_id', $products->pluck('id'))
            ->get()
            ->keyBy('product_id');

        $rows = $products->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'type' => $p->type,
            'unit' => $p->unit,
            'current_stock' => (int) ($stockMap->get($p->id)?->quantity ?? 0),
        ])->values()->all();

        return ApiResponse::success($rows);
    }

    public function storeMozoInsumo(Request $request, StockService $stock): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        if ((int) $product->restaurant_id !== $restaurantId) {
            return ApiResponse::error('Producto de otro restaurante', 403, 'FORBIDDEN');
        }
        if (! $product->isInsumo()) {
            return ApiResponse::error('Solo podés registrar ingresos de insumos', 422, 'NOT_INSUMO');
        }
        if (! $product->has_stock) {
            return ApiResponse::error('Este insumo no tiene control de stock activado', 422, 'NO_STOCK');
        }

        try {
            $movement = $stock->recordMovement([
                'restaurant_id' => $restaurantId,
                'product_id' => $product->id,
                'user_id' => (int) $request->user()->id,
                'type' => 'ENTRADA',
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? 'Ingreso de insumo (mozo)',
                'reference' => 'mobile_mozo_insumo',
            ]);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'STOCK_MOVEMENT_ERROR');
        }

        return ApiResponse::success(
            $movement->load(['product:id,name', 'user:id,name'])->toArray(),
            201,
            'Ingreso de insumo registrado'
        );
    }

    public function suppliers(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $rows = Supplier::query()
            ->where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return ApiResponse::success($rows->toArray());
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
