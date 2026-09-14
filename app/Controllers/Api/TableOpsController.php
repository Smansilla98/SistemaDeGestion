<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\Models\DiscountType;
use App\Models\Order;
use App\Models\Table;
use App\Models\TableSession;
use App\Services\OrderService;
use App\Services\TableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class TableOpsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Table::class);

        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $query = Table::query()
            ->where('restaurant_id', $restaurantId)
            ->with(['sector:id,name', 'currentSession.waiter:id,name']);

        if ($request->filled('sector_id')) {
            $query->where('sector_id', $request->integer('sector_id'));
        }

        $tables = $query->orderBy('number')->get()->map(fn (Table $t) => [
            'id' => $t->id,
            'number' => $t->number,
            'status' => $t->status,
            'capacity' => $t->capacity,
            'sector' => $t->sector?->name,
            'sector_id' => $t->sector_id,
            'current_session_id' => $t->current_session_id,
            'current_order_id' => $t->current_order_id,
            'waiter' => $t->currentSession?->waiter?->name,
            'position_x' => (int) ($t->position_x ?? 50),
            'position_y' => (int) ($t->position_y ?? 50),
        ]);

        return ApiResponse::success($tables->values()->all());
    }

    public function layout(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Table::class);

        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $sectorId = $request->filled('sector_id') ? $request->integer('sector_id') : null;

        $sectors = \App\Models\Sector::query()
            ->where('restaurant_id', $restaurantId)
            ->where('type', \App\Models\Sector::TYPE_SECTOR)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'layout_config']);

        if ($sectorId === null && $sectors->isNotEmpty()) {
            $sectorId = (int) $sectors->first()->id;
        }

        $sector = $sectors->firstWhere('id', $sectorId);

        $tables = Table::query()
            ->where('restaurant_id', $restaurantId)
            ->when($sectorId, fn ($q) => $q->where('sector_id', $sectorId))
            ->with(['currentSession.waiter:id,name'])
            ->orderBy('number')
            ->get()
            ->map(fn (Table $t) => [
                'id' => $t->id,
                'number' => $t->number,
                'status' => $t->status,
                'capacity' => $t->capacity,
                'position_x' => (int) ($t->position_x ?? 50),
                'position_y' => (int) ($t->position_y ?? 50),
                'waiter' => $t->currentSession?->waiter?->name,
                'current_order_id' => $t->current_order_id,
            ]);

        $fixtures = is_array($sector?->layout_config) ? ($sector->layout_config['fixtures'] ?? []) : [];

        return ApiResponse::success([
            'sector_id' => $sectorId,
            'sectors' => $sectors->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->all(),
            'tables' => $tables->values()->all(),
            'fixtures' => $fixtures,
            'canvas' => ['width' => 720, 'height' => 520],
        ]);
    }

    public function show(Request $request, int $id, TableService $tables): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $table = Table::query()
            ->where('restaurant_id', $restaurantId)
            ->with(['sector', 'currentSession.waiter:id,name'])
            ->find($id);

        if ($table === null) {
            return ApiResponse::error('Mesa no encontrada', 404, 'NOT_FOUND');
        }

        $this->authorize('view', $table);

        $orders = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('table_id', $table->id)
            ->when($table->current_session_id, fn ($q) => $q->where('table_session_id', $table->current_session_id))
            ->whereNotIn('status', ['CERRADO', 'CANCELADO'])
            ->with(['items.product', 'user:id,name'])
            ->orderByDesc('created_at')
            ->get();

        $receipt = null;
        try {
            $receipt = $tables->getConsolidatedReceiptData($table);
        } catch (\Throwable) {
            // sin recibo si no hay sesión
        }

        return ApiResponse::success([
            'table' => [
                'id' => $table->id,
                'number' => $table->number,
                'status' => $table->status,
                'capacity' => $table->capacity,
                'sector' => $table->sector?->name,
                'sector_id' => $table->sector_id,
                'current_session_id' => $table->current_session_id,
                'current_order_id' => $table->current_order_id,
                'waiter' => $table->currentSession?->waiter?->name,
            ],
            'orders' => $orders->toArray(),
            'receipt' => $receipt,
        ]);
    }

    public function occupy(Request $request, int $id, OrderService $orders): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $table = Table::query()->where('restaurant_id', $restaurantId)->find($id);
        if ($table === null) {
            return ApiResponse::error('Mesa no encontrada', 404, 'NOT_FOUND');
        }

        Gate::authorize('update', $table);

        try {
            $orders->ensureTableReadyForOrder($table, (int) $request->user()->id);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'OCCUPY_ERROR');
        }

        return ApiResponse::success($table->fresh(['sector', 'currentSession.waiter'])->toArray(), 200, 'Mesa ocupada');
    }

    public function free(Request $request, int $id): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $table = Table::query()->where('restaurant_id', $restaurantId)->find($id);
        if ($table === null) {
            return ApiResponse::error('Mesa no encontrada', 404, 'NOT_FOUND');
        }

        Gate::authorize('update', $table);

        $openOrders = Order::query()
            ->where('table_id', $table->id)
            ->whereNotIn('status', ['CERRADO', 'CANCELADO'])
            ->count();

        if ($openOrders > 0) {
            return ApiResponse::error('La mesa tiene pedidos abiertos. Cobrálos o anulalos antes de liberar.', 422, 'HAS_OPEN_ORDERS');
        }

        DB::transaction(function () use ($table) {
            if ($table->current_session_id) {
                DB::table('table_sessions')
                    ->where('id', $table->current_session_id)
                    ->update([
                        'ended_at' => now(),
                        'status' => TableSession::STATUS_CERRADA,
                        'updated_at' => now(),
                    ]);
            }
            $table->update([
                'status' => Table::STATUS_LIBRE,
                'current_order_id' => null,
                'current_session_id' => null,
            ]);
        });

        return ApiResponse::success($table->fresh()->toArray(), 200, 'Mesa liberada');
    }

    public function transfer(Request $request, int $id): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $validated = $request->validate([
            'target_table_id' => ['required', 'integer', 'different:'.$id],
        ]);

        $from = Table::query()->where('restaurant_id', $restaurantId)->find($id);
        $to = Table::query()->where('restaurant_id', $restaurantId)->find($validated['target_table_id']);

        if ($from === null || $to === null) {
            return ApiResponse::error('Mesa no encontrada', 404, 'NOT_FOUND');
        }

        Gate::authorize('update', $from);
        Gate::authorize('update', $to);

        if ($from->status !== Table::STATUS_OCUPADA) {
            return ApiResponse::error('La mesa origen debe estar ocupada', 422, 'INVALID_SOURCE');
        }
        if ($to->status !== Table::STATUS_LIBRE) {
            return ApiResponse::error('La mesa destino debe estar libre', 422, 'INVALID_TARGET');
        }

        try {
            DB::transaction(function () use ($from, $to, $request) {
                $sessionId = $from->current_session_id;
                if ($sessionId) {
                    DB::table('table_sessions')->where('id', $sessionId)->update([
                        'table_id' => $to->id,
                        'updated_at' => now(),
                    ]);
                } else {
                    $session = TableSession::create([
                        'restaurant_id' => $from->restaurant_id,
                        'table_id' => $to->id,
                        'waiter_id' => $request->user()->id,
                        'opened_by_user_id' => $request->user()->id,
                        'started_at' => now(),
                        'status' => TableSession::STATUS_ABIERTA,
                    ]);
                    $sessionId = $session->id;
                }

                Order::query()
                    ->where('table_id', $from->id)
                    ->whereNotIn('status', ['CERRADO', 'CANCELADO'])
                    ->update([
                        'table_id' => $to->id,
                        'table_session_id' => $sessionId,
                    ]);

                $to->update([
                    'status' => Table::STATUS_OCUPADA,
                    'current_session_id' => $sessionId,
                    'current_order_id' => $from->current_order_id,
                ]);

                $from->update([
                    'status' => Table::STATUS_LIBRE,
                    'current_session_id' => null,
                    'current_order_id' => null,
                ]);
            });
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'TRANSFER_ERROR');
        }

        return ApiResponse::success([
            'from' => $from->fresh()->toArray(),
            'to' => $to->fresh()->toArray(),
        ], 200, 'Mesa transferida');
    }

    public function pay(Request $request, int $id, TableService $tables): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $table = Table::query()->where('restaurant_id', $restaurantId)->find($id);
        if ($table === null) {
            return ApiResponse::error('Mesa no encontrada', 404, 'NOT_FOUND');
        }

        Gate::authorize('update', $table);

        $validated = $request->validate([
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method' => ['required', 'string'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'discount_type_id' => ['nullable', 'integer'],
        ]);

        try {
            $result = $tables->processTablePayment($table, $validated, (int) $request->user()->id);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'PAY_ERROR');
        }

        if (! ($result['success'] ?? false)) {
            return ApiResponse::error($result['message'] ?? 'No se pudo cobrar', 422, 'PAY_ERROR');
        }

        unset($result['redirect'], $result['flash']);

        return ApiResponse::success($result, 200, 'Mesa cobrada');
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
