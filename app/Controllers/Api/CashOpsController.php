<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Models\Order;
use App\Models\User;
use App\Services\CashRegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CashOpsController extends Controller
{
    public function registers(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $query = CashRegister::query()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('name');

        // Operación diaria: solo activas. Gestión (?all=1): todas.
        if (! $request->boolean('all')) {
            $query->where('is_active', true);
        }

        $rows = $query->get(['id', 'name', 'is_active']);

        return ApiResponse::success($rows->toArray());
    }

    public function storeRegister(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $register = CashRegister::query()->create([
            'restaurant_id' => $restaurantId,
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return ApiResponse::success($register->only(['id', 'name', 'is_active']), 201, 'Caja creada');
    }

    public function updateRegister(Request $request, int $id): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $register = CashRegister::query()
            ->where('restaurant_id', $restaurantId)
            ->find($id);

        if ($register === null) {
            return ApiResponse::error('Caja no encontrada', 404, 'NOT_FOUND');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $register->update($validated);

        return ApiResponse::success($register->fresh()->only(['id', 'name', 'is_active']), 200, 'Caja actualizada');
    }

    public function destroyRegister(Request $request, int $id): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $register = CashRegister::query()
            ->where('restaurant_id', $restaurantId)
            ->find($id);

        if ($register === null) {
            return ApiResponse::error('Caja no encontrada', 404, 'NOT_FOUND');
        }

        if ($register->sessions()->where('status', CashRegisterSession::STATUS_ABIERTA)->exists()) {
            return ApiResponse::error('No se puede eliminar una caja con sesiones abiertas', 422, 'HAS_OPEN_SESSION');
        }

        if ($register->sessions()->exists()) {
            return ApiResponse::error(
                'No se puede eliminar una caja con sesiones históricas. Desactivala en su lugar.',
                422,
                'HAS_SESSIONS'
            );
        }

        $register->delete();

        return ApiResponse::success(null, 200, 'Caja eliminada');
    }

    public function currentSession(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $session = CashRegisterSession::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', CashRegisterSession::STATUS_ABIERTA)
            ->with(['cashRegister:id,name', 'user:id,name'])
            ->orderByDesc('opened_at')
            ->first();

        return ApiResponse::success($session?->toArray());
    }

    public function open(Request $request, int $registerId, CashRegisterService $cash): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $register = CashRegister::query()
            ->where('restaurant_id', $restaurantId)
            ->find($registerId);

        if ($register === null) {
            return ApiResponse::error('Caja no encontrada', 404, 'NOT_FOUND');
        }

        $validated = $request->validate([
            'initial_amount' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $session = $cash->openSession([
                'restaurant_id' => $restaurantId,
                'cash_register_id' => $register->id,
                'user_id' => (int) $request->user()->id,
                'initial_amount' => $validated['initial_amount'],
            ]);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'OPEN_CASH_ERROR');
        }

        return ApiResponse::success($session->toArray(), 201, 'Caja abierta');
    }

    public function summary(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $openSessions = CashRegisterSession::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', CashRegisterSession::STATUS_ABIERTA)
            ->with(['cashRegister:id,name', 'user:id,name'])
            ->orderByDesc('opened_at')
            ->get();

        $openSessionsPayload = $openSessions->map(fn (CashRegisterSession $s) => [
            'id' => $s->id,
            'status' => $s->status,
            'register' => $s->cashRegister?->name,
            'cash_register_id' => $s->cash_register_id,
            'user' => $s->user?->name,
            'initial_amount' => (float) $s->initial_amount,
            'opened_at' => optional($s->opened_at)->toIso8601String(),
        ])->values()->all();

        $session = null;
        if ($request->filled('session_id')) {
            $session = $openSessions->firstWhere('id', $request->integer('session_id'));
            if ($session === null) {
                $session = CashRegisterSession::query()
                    ->where('restaurant_id', $restaurantId)
                    ->where('status', CashRegisterSession::STATUS_ABIERTA)
                    ->where('id', $request->integer('session_id'))
                    ->with(['cashRegister:id,name', 'user:id,name'])
                    ->first();
            }
        } else {
            $session = $openSessions->first();
        }

        if ($session === null) {
            return ApiResponse::success([
                'session' => null,
                'sales_total' => 0,
                'payments_count' => 0,
                'expected_amount' => 0,
                'open_sessions' => $openSessionsPayload,
            ]);
        }

        $payments = $session->payments();
        $sales = (float) (clone $payments)->sum('amount');
        $count = (clone $payments)->count();
        $ingresos = (float) $session->cashMovements()->where('type', 'INGRESO')->sum('amount');
        $egresos = (float) $session->cashMovements()->where('type', 'EGRESO')->sum('amount');
        $expected = (float) $session->initial_amount + $sales + $ingresos - $egresos;

        return ApiResponse::success([
            'session' => $session->load('cashRegister:id,name')->toArray(),
            'sales_total' => $sales,
            'payments_count' => $count,
            'expected_amount' => $expected,
            'open_sessions' => $openSessionsPayload,
        ]);
    }

    public function close(Request $request, CashRegisterService $cash): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $validated = $request->validate([
            'final_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'session_id' => ['nullable', 'integer'],
        ]);

        $session = $this->resolveOpenSession($restaurantId, $validated['session_id'] ?? null);
        if ($session === null) {
            return ApiResponse::error('No hay sesión de caja abierta', 422, 'NO_OPEN_SESSION');
        }

        try {
            $closed = $cash->closeSession($session, $validated);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'CLOSE_CASH_ERROR');
        }

        return ApiResponse::success($closed->toArray(), 200, 'Caja cerrada');
    }

    public function sessions(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $rows = CashRegisterSession::query()
            ->where('restaurant_id', $restaurantId)
            ->with(['cashRegister:id,name', 'user:id,name'])
            ->orderByDesc('opened_at')
            ->limit(40)
            ->get()
            ->map(fn (CashRegisterSession $s) => [
                'id' => $s->id,
                'status' => $s->status,
                'register' => $s->cashRegister?->name,
                'user' => $s->user?->name,
                'initial_amount' => (float) $s->initial_amount,
                'final_amount' => $s->final_amount !== null ? (float) $s->final_amount : null,
                'opened_at' => optional($s->opened_at)->toIso8601String(),
                'closed_at' => optional($s->closed_at)->toIso8601String(),
            ]);

        return ApiResponse::success($rows->values()->all());
    }

    public function sessionDetail(Request $request, int $sessionId): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $session = CashRegisterSession::query()
            ->where('restaurant_id', $restaurantId)
            ->with(['cashRegister:id,name', 'user:id,name', 'payments.order.table', 'cashMovements.user:id,name'])
            ->find($sessionId);

        if ($session === null) {
            return ApiResponse::error('Sesión no encontrada', 404, 'NOT_FOUND');
        }

        $sales = (float) $session->payments()->sum('amount');
        $ingresos = (float) $session->cashMovements()->where('type', 'INGRESO')->sum('amount');
        $egresos = (float) $session->cashMovements()->where('type', 'EGRESO')->sum('amount');
        $expected = (float) $session->initial_amount + $sales + $ingresos - $egresos;

        $user = $request->user();
        $orderIds = $session->payments()->whereNotNull('order_id')->pluck('order_id')->unique()->values();
        $salesDetail = Order::query()
            ->whereIn('id', $orderIds)
            ->with(['table:id,number', 'user:id,name', 'items.product:id,name,has_stock'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'total' => (float) $order->total,
                'customer_name' => $order->customer_name,
                'table' => $order->table?->number,
                'user' => $order->user?->name,
                'created_at' => optional($order->created_at)->toIso8601String(),
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product' => $item->product?->name,
                    'product_id' => $item->product_id,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) ($item->subtotal ?? ($item->unit_price * $item->quantity)),
                    'has_stock' => (bool) ($item->product?->has_stock ?? false),
                ])->values()->all(),
            ])->values()->all();

        return ApiResponse::success([
            'session' => $session->toArray(),
            'sales_total' => $sales,
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'expected_amount' => $expected,
            'payments' => $session->payments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => (float) $p->amount,
                'payment_method' => $p->payment_method,
                'order_number' => $p->order?->number,
                'table' => $p->order?->table?->number,
                'created_at' => optional($p->created_at)->toIso8601String(),
            ])->values()->all(),
            'movements' => $session->cashMovements->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'amount' => (float) $m->amount,
                'description' => $m->description,
                'reference' => $m->reference,
                'user_id' => $m->user_id,
                'can_delete' => $this->canDeleteMovement($user, $m, $session),
                'created_at' => optional($m->created_at)->toIso8601String(),
            ])->values()->all(),
            'sales_detail' => $salesDetail,
        ]);
    }

    public function storeMovement(Request $request, CashRegisterService $cash): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $validated = $request->validate([
            'type' => 'required|in:INGRESO,EGRESO',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
            'session_id' => 'nullable|integer',
        ]);

        $session = $this->resolveOpenSession($restaurantId, $validated['session_id'] ?? null);
        if ($session === null) {
            return ApiResponse::error('No hay sesión de caja abierta', 422, 'NO_OPEN_SESSION');
        }

        try {
            $movement = $cash->recordMovement([
                'restaurant_id' => $restaurantId,
                'cash_register_session_id' => $session->id,
                'user_id' => (int) $request->user()->id,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'reference' => $validated['reference'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'CASH_MOVEMENT_ERROR');
        }

        return ApiResponse::success($movement->toArray(), 201, 'Movimiento registrado');
    }

    public function destroyMovement(Request $request, int $id): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $movement = CashMovement::query()
            ->where('restaurant_id', $restaurantId)
            ->with('cashRegisterSession')
            ->find($id);

        if ($movement === null) {
            return ApiResponse::error('Movimiento no encontrado', 404, 'NOT_FOUND');
        }

        $session = $movement->cashRegisterSession;
        if (! $this->canDeleteMovement($request->user(), $movement, $session)) {
            return ApiResponse::error('No tenés permiso para eliminar este movimiento', 403, 'FORBIDDEN');
        }

        try {
            $movement->delete();
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'DELETE_MOVEMENT_ERROR');
        }

        return ApiResponse::success(null, 200, 'Movimiento eliminado');
    }

    private function resolveOpenSession(int $restaurantId, ?int $sessionId): ?CashRegisterSession
    {
        $query = CashRegisterSession::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', CashRegisterSession::STATUS_ABIERTA);

        if ($sessionId !== null) {
            return $query->where('id', $sessionId)->first();
        }

        return $query->orderByDesc('opened_at')->first();
    }

    private function canDeleteMovement(?User $user, CashMovement $movement, ?CashRegisterSession $session): bool
    {
        if ($user === null) {
            return false;
        }

        $role = $user->role ?? '';
        if (in_array($role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN, User::ROLE_GERENTE], true)) {
            return true;
        }

        if ($session === null || $session->status !== CashRegisterSession::STATUS_ABIERTA) {
            return false;
        }

        return (int) $movement->user_id === (int) $user->id;
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
