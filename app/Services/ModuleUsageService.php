<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CashMovement;
use App\Models\CashRegisterSession;
use App\Models\Category;
use App\Models\DiscountType;
use App\Models\Event;
use App\Models\FixedExpense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Printer;
use App\Models\Product;
use App\Models\RecurringActivity;
use App\Models\Sector;
use App\Models\StockMovement;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ModuleUsageService
{
    /** @var array<string, string> model_type (FQCN) => module key */
    private const AUDIT_MODEL_MAP = [
        Product::class => 'products',
        Category::class => 'categories',
        Sector::class => 'sectors',
        User::class => 'users',
        Order::class => 'orders',
        StockMovement::class => 'stock',
    ];

    /**
     * @return array{
     *     modules: list<array<string, mixed>>,
     *     totals: array<string, int|float>,
     *     period: array{from: ?string, to: ?string},
     *     top_users: list<array<string, mixed>>
     * }
     */
    public function getSummary(?int $restaurantId, ?Carbon $from, ?Carbon $to): array
    {
        $moduleDefs = config('permissions.modules', []);
        $auditCounts = $this->auditCountsByModule($restaurantId, $from, $to);
        $modules = [];

        foreach ($moduleDefs as $def) {
            $key = $def['key'];
            $sources = $this->sourcesForModule($key, $restaurantId, $from, $to, $auditCounts);
            $total = (int) array_sum($sources);

            $modules[] = [
                'key' => $key,
                'label' => $def['label'],
                'total' => $total,
                'used' => $total > 0,
                'sources' => $sources,
            ];
        }

        usort($modules, fn (array $a, array $b) => $b['total'] <=> $a['total']);

        $usedCount = count(array_filter($modules, fn (array $m) => $m['used']));
        $totalOps = (int) array_sum(array_column($modules, 'total'));

        return [
            'modules' => $modules,
            'totals' => [
                'modules_defined' => count($moduleDefs),
                'modules_used' => $usedCount,
                'modules_unused' => count($moduleDefs) - $usedCount,
                'total_operations' => $totalOps,
                'usage_rate' => count($moduleDefs) > 0
                    ? round(($usedCount / count($moduleDefs)) * 100, 1)
                    : 0,
            ],
            'period' => [
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
            'top_users' => $this->topUsersByActivity($restaurantId, $from, $to),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function sourcesForModule(
        string $key,
        ?int $restaurantId,
        ?Carbon $from,
        ?Carbon $to,
        array $auditCounts
    ): array {
        return match ($key) {
            'dashboard' => [
                'accesos (logins)' => $this->countQuery(
                    User::query()->whereNotNull('last_login_at'),
                    $restaurantId,
                    $from,
                    $to,
                    'restaurant_id',
                    'last_login_at'
                ),
            ],
            'tables' => [
                'mesas' => $this->countQuery(Table::query(), $restaurantId, $from, $to),
                'sesiones de mesa' => $this->countQuery(TableSession::query(), $restaurantId, $from, $to),
                'auditoría' => $auditCounts['tables'] ?? 0,
            ],
            'orders' => [
                'pedidos' => $this->countQuery(Order::query(), $restaurantId, $from, $to),
            ],
            'kitchen' => [
                'cambios de estado (cocina)' => $this->countKitchenActivity($restaurantId, $from, $to),
            ],
            'cash-register' => [
                'movimientos de caja' => $this->countQuery(CashMovement::query(), $restaurantId, $from, $to),
                'sesiones de caja' => $this->countQuery(CashRegisterSession::query(), $restaurantId, $from, $to),
                'pagos' => $this->countQuery(Payment::query(), $restaurantId, $from, $to),
            ],
            'discount-types' => [
                'descuentos' => $this->countQuery(DiscountType::query(), $restaurantId, $from, $to),
                'auditoría' => $auditCounts['discount-types'] ?? 0,
            ],
            'sectors' => [
                'sectores' => $this->countQuery(Sector::query(), $restaurantId, $from, $to),
                'auditoría' => $auditCounts['sectors'] ?? 0,
            ],
            'categories' => [
                'categorías' => $this->countQuery(Category::query(), $restaurantId, $from, $to),
                'auditoría' => $auditCounts['categories'] ?? 0,
            ],
            'products' => [
                'productos' => $this->countQuery(Product::query(), $restaurantId, $from, $to),
                'auditoría' => $auditCounts['products'] ?? 0,
            ],
            'stock' => [
                'movimientos de stock' => $this->countStockMovements($restaurantId, $from, $to, excludeMozo: true),
            ],
            'stock_mozo' => [
                'ingresos mozo' => $this->countMozoStockEntries($restaurantId, $from, $to),
            ],
            'users' => [
                'usuarios' => $this->countUsers($restaurantId, $from, $to),
                'auditoría' => $auditCounts['users'] ?? 0,
            ],
            'printers' => [
                'impresoras' => $this->countQuery(Printer::query(), $restaurantId, $from, $to),
            ],
            'events' => [
                'eventos' => $this->countQuery(Event::query(), $restaurantId, $from, $to),
            ],
            'recurring-activities' => [
                'actividades' => $this->countQuery(RecurringActivity::query(), $restaurantId, $from, $to),
            ],
            'fixed-expenses' => [
                'gastos fijos' => $this->countQuery(FixedExpense::query(), $restaurantId, $from, $to),
            ],
            'reports' => [
                'exportaciones / consultas' => $this->countReportActivity($restaurantId, $from, $to),
            ],
            'configuration' => [
                'permisos configurados' => $this->countPermissionChanges($restaurantId, $from, $to),
            ],
            'tutorials' => [
                'tutoriales PDF' => $this->countTutorialFiles(),
            ],
            default => [],
        };
    }

    /**
     * @return array<string, int>
     */
    private function auditCountsByModule(?int $restaurantId, ?Carbon $from, ?Carbon $to): array
    {
        $query = AuditLog::query()->select(['model_type', 'action', 'changes']);
        $this->applyScope($query, $restaurantId, $from, $to, 'restaurant_id', 'created_at');

        $counts = [];
        foreach ($query->cursor() as $row) {
            $moduleKey = $this->resolveAuditModule($row->model_type, $row->action, $row->changes);
            if ($moduleKey === null) {
                continue;
            }
            $counts[$moduleKey] = ($counts[$moduleKey] ?? 0) + 1;
        }

        return $counts;
    }

    private function resolveAuditModule(?string $modelType, string $action, mixed $changes): ?string
    {
        if ($action === 'ORDER_STATUS_CHANGED') {
            return null;
        }

        if ($action === 'STOCK_MOVEMENT_CREATED') {
            $decoded = is_array($changes) ? $changes : json_decode((string) $changes, true);
            if (is_array($decoded) && ($decoded['channel'] ?? null) === 'mozo_insumo') {
                return 'stock_mozo';
            }

            return 'stock';
        }

        if ($modelType && isset(self::AUDIT_MODEL_MAP[$modelType])) {
            return self::AUDIT_MODEL_MAP[$modelType];
        }

        return null;
    }

    private function countKitchenActivity(?int $restaurantId, ?Carbon $from, ?Carbon $to): int
    {
        $query = AuditLog::query()->where('action', 'ORDER_STATUS_CHANGED');
        $this->applyScope($query, $restaurantId, $from, $to, 'restaurant_id', 'created_at');

        $kitchenStatuses = ['ENVIADO', 'EN_PREPARACION', 'LISTO'];

        return $query->get()->filter(function (AuditLog $log) use ($kitchenStatuses) {
            $changes = $log->changes ?? [];
            $newStatus = $changes['status']['new'] ?? null;

            return in_array($newStatus, $kitchenStatuses, true);
        })->count();
    }

    private function countStockMovements(
        ?int $restaurantId,
        ?Carbon $from,
        ?Carbon $to,
        bool $excludeMozo = false
    ): int {
        $query = StockMovement::query();

        if ($excludeMozo) {
            $query->where(function (Builder $q) {
                $q->whereNull('reason')
                    ->orWhere('reason', 'not like', '%mozo%');
            });
        }

        return $this->countQuery($query, $restaurantId, $from, $to);
    }

    private function countMozoStockEntries(?int $restaurantId, ?Carbon $from, ?Carbon $to): int
    {
        $fromMovements = $this->countQuery(
            StockMovement::query()
                ->where('type', StockMovement::TYPE_ENTRADA)
                ->where('reason', 'like', '%mozo%'),
            $restaurantId,
            $from,
            $to
        );

        $auditQuery = AuditLog::query()->where('action', 'STOCK_MOVEMENT_CREATED');
        $this->applyScope($auditQuery, $restaurantId, $from, $to, 'restaurant_id', 'created_at');

        $fromAudit = $auditQuery->get()->filter(function (AuditLog $log) {
            $changes = $log->changes ?? [];

            return ($changes['channel'] ?? null) === 'mozo_insumo';
        })->count();

        return max($fromMovements, $fromAudit);
    }

    private function countUsers(?int $restaurantId, ?Carbon $from, ?Carbon $to): int
    {
        $query = User::query()->where('role', '!=', User::ROLE_SUPERADMIN);

        return $this->countQuery($query, $restaurantId, $from, $to);
    }

    private function countReportActivity(?int $restaurantId, ?Carbon $from, ?Carbon $to): int
    {
        // Proxy: pedidos cerrados consultados en reportes de ventas/productos.
        $query = Order::query()->where('status', 'CERRADO');

        return $this->countQuery($query, $restaurantId, $from, $to);
    }

    private function countPermissionChanges(?int $restaurantId, ?Carbon $from, ?Carbon $to): int
    {
        $roleQuery = DB::table('role_permissions');
        $userQuery = DB::table('user_permissions');

        if ($restaurantId !== null) {
            $userIds = User::query()
                ->where('restaurant_id', $restaurantId)
                ->pluck('id');
            $userQuery->whereIn('user_id', $userIds);
        }

        if ($from !== null) {
            $roleQuery->whereDate('updated_at', '>=', $from);
            $userQuery->whereDate('updated_at', '>=', $from);
        }

        if ($to !== null) {
            $roleQuery->whereDate('updated_at', '<=', $to);
            $userQuery->whereDate('updated_at', '<=', $to);
        }

        return (int) $roleQuery->count() + (int) $userQuery->count();
    }

    private function countTutorialFiles(): int
    {
        $path = storage_path('app/tutorials');

        if (! is_dir($path)) {
            return 0;
        }

        return count(File::glob($path.'/*.pdf') ?: []);
    }

    /**
     * @return list<array{name: string, role: string, operations: int}>
     */
    private function topUsersByActivity(?int $restaurantId, ?Carbon $from, ?Carbon $to): array
    {
        $auditQuery = AuditLog::query()
            ->select('user_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(10);

        $this->applyScope($auditQuery, $restaurantId, $from, $to, 'restaurant_id', 'created_at');

        $rows = $auditQuery->get();
        if ($rows->isEmpty()) {
            return [];
        }

        $users = User::query()
            ->whereIn('id', $rows->pluck('user_id'))
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($rows as $row) {
            $user = $users->get($row->user_id);
            if ($user === null || $user->isSuperAdmin()) {
                continue;
            }
            $result[] = [
                'name' => $user->name,
                'role' => $user->role,
                'operations' => (int) $row->total,
            ];
        }

        return $result;
    }

    private function countQuery(
        Builder $query,
        ?int $restaurantId,
        ?Carbon $from,
        ?Carbon $to,
        string $restaurantColumn = 'restaurant_id',
        string $dateColumn = 'created_at'
    ): int {
        $this->applyScope($query, $restaurantId, $from, $to, $restaurantColumn, $dateColumn);

        return (int) $query->count();
    }

    private function applyScope(
        Builder $query,
        ?int $restaurantId,
        ?Carbon $from,
        ?Carbon $to,
        string $restaurantColumn = 'restaurant_id',
        string $dateColumn = 'created_at'
    ): void {
        if ($restaurantId !== null) {
            $query->where($restaurantColumn, $restaurantId);
        }

        if ($from !== null) {
            $query->whereDate($dateColumn, '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate($dateColumn, '<=', $to);
        }
    }
}
