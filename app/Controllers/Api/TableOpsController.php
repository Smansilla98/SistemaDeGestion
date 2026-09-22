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
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

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
            return ApiResponse::error($this->safeErrorMessage($e, 'occupy'), 422, 'OCCUPY_ERROR');
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
            return ApiResponse::error($this->safeErrorMessage($e, 'transfer'), 422, 'TRANSFER_ERROR');
        }

        return ApiResponse::success([
            'from' => $from->fresh()->toArray(),
            'to' => $to->fresh()->toArray(),
        ], 200, 'Mesa transferida');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Table::class);

        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $validated = $request->validate([
            'sector_id' => 'required|integer|exists:sectors,id',
            'number' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'position_x' => 'nullable|integer|min:0',
            'position_y' => 'nullable|integer|min:0',
        ]);

        $sectorOk = \App\Models\Sector::query()
            ->where('restaurant_id', $restaurantId)
            ->where('id', $validated['sector_id'])
            ->exists();
        if (! $sectorOk) {
            return ApiResponse::error('Sector inválido', 422, 'INVALID_SECTOR');
        }

        $table = Table::create([
            ...$validated,
            'restaurant_id' => $restaurantId,
            'status' => Table::STATUS_LIBRE,
            'position_x' => $validated['position_x'] ?? 50,
            'position_y' => $validated['position_y'] ?? 50,
        ]);

        return ApiResponse::success($table->fresh(['sector'])->toArray(), 201, 'Mesa creada');
    }

    public function update(Request $request, int $id): JsonResponse
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
            'sector_id' => 'sometimes|required|integer|exists:sectors,id',
            'number' => 'sometimes|required|string|max:255',
            'capacity' => 'sometimes|required|integer|min:1',
            'position_x' => 'nullable|integer|min:0',
            'position_y' => 'nullable|integer|min:0',
            'status' => 'sometimes|required|in:'.implode(',', Table::getStatuses()),
        ]);

        if (isset($validated['sector_id'])) {
            $sectorOk = \App\Models\Sector::query()
                ->where('restaurant_id', $restaurantId)
                ->where('id', $validated['sector_id'])
                ->exists();
            if (! $sectorOk) {
                return ApiResponse::error('Sector inválido', 422, 'INVALID_SECTOR');
            }
        }

        $table->update($validated);

        return ApiResponse::success($table->fresh(['sector'])->toArray(), 200, 'Mesa actualizada');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $table = Table::query()->where('restaurant_id', $restaurantId)->find($id);
        if ($table === null) {
            return ApiResponse::error('Mesa no encontrada', 404, 'NOT_FOUND');
        }

        Gate::authorize('delete', $table);

        if ($table->current_order_id) {
            return ApiResponse::error('No se puede eliminar una mesa con pedido activo', 422, 'HAS_ORDER');
        }
        if ($table->current_session_id) {
            $session = TableSession::find($table->current_session_id);
            if ($session && $session->isOpen()) {
                return ApiResponse::error('No se puede eliminar una mesa con sesión abierta', 422, 'HAS_SESSION');
            }
        }

        $table->delete();

        return ApiResponse::success(null, 200, 'Mesa eliminada');
    }

    public function reserve(Request $request, int $id): JsonResponse
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
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_time' => 'required',
            'number_of_guests' => 'required|integer|min:1|max:'.$table->capacity,
        ]);

        if ($table->status !== Table::STATUS_LIBRE) {
            return ApiResponse::error('La mesa no está disponible para reservar', 422, 'NOT_AVAILABLE');
        }

        // Paridad web: marca RESERVADA (sin tabla reservations aún).
        $table->update(['status' => Table::STATUS_RESERVADA]);

        return ApiResponse::success([
            'table' => $table->fresh(['sector'])->toArray(),
            'reservation' => $validated,
        ], 200, 'Mesa reservada');
    }

    public function updateLayout(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $role = (string) ($request->user()?->role ?? '');
        if (! in_array($role, ['SUPERADMIN', 'ADMIN', 'GERENTE', 'MOZO', 'ENCARGADO'], true)) {
            return ApiResponse::error('Sin permiso para editar layout', 403, 'FORBIDDEN');
        }

        $validated = $request->validate([
            'sector_id' => 'required|integer|exists:sectors,id',
            'tables' => 'required|array',
            'tables.*.id' => 'required|integer|exists:tables,id',
            'tables.*.position_x' => 'required|integer|min:0',
            'tables.*.position_y' => 'required|integer|min:0',
            'fixtures' => 'nullable|array',
            'fixtures.*.id' => 'required_with:fixtures|string|max:50',
            'fixtures.*.position_x' => 'required_with:fixtures|integer|min:0',
            'fixtures.*.position_y' => 'required_with:fixtures|integer|min:0',
        ]);

        $sector = \App\Models\Sector::query()
            ->where('restaurant_id', $restaurantId)
            ->find($validated['sector_id']);
        if ($sector === null) {
            return ApiResponse::error('Sector no encontrado', 404, 'NOT_FOUND');
        }

        foreach ($validated['tables'] as $tableData) {
            $table = Table::query()
                ->where('restaurant_id', $restaurantId)
                ->find($tableData['id']);
            if ($table === null) {
                continue;
            }
            Gate::authorize('update', $table);
            $table->update([
                'position_x' => $tableData['position_x'],
                'position_y' => $tableData['position_y'],
            ]);
        }

        if (! empty($validated['fixtures'])) {
            $layoutConfig = is_array($sector->layout_config) ? $sector->layout_config : [];
            $layoutConfig['fixtures'] = $layoutConfig['fixtures'] ?? [];
            foreach ($validated['fixtures'] as $fixture) {
                $layoutConfig['fixtures'][$fixture['id']] = [
                    'x' => (int) $fixture['position_x'],
                    'y' => (int) $fixture['position_y'],
                ];
            }
            $sector->update(['layout_config' => $layoutConfig]);
        }

        return ApiResponse::success(null, 200, 'Layout actualizado');
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
            return ApiResponse::error($this->safeErrorMessage($e, 'pay'), 422, 'PAY_ERROR');
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

    /**
     * Mensaje seguro para mostrarle al usuario ante una excepción.
     *
     * QueryException hereda de RuntimeException, así que un catch(\Throwable)
     * ingenuo termina devolviendo SQL crudo (nombre de tabla, constraint,
     * hasta valores) al cliente cuando algo como un unique index falla — antes
     * pasaba con "Ocupar mesa". Solo las excepciones de negocio que este
     * código tira a propósito (RuntimeException simple, con un mensaje en
     * español pensado para mostrarse) llegan tal cual; cualquier otra cosa
     * (QueryException, Error, lo que sea) se loguea completa y al usuario le
     * llega un mensaje genérico.
     */
    private function safeErrorMessage(\Throwable $e, string $context): string
    {
        $isSafeToShow = ! ($e instanceof QueryException) && $e instanceof \RuntimeException;

        if (! $isSafeToShow) {
            Log::error("Error inesperado en {$context}", [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return 'Ocurrió un error inesperado. Reintentá en un momento o avisá a soporte.';
        }

        return $e->getMessage();
    }
}
