<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Table;
use App\Services\DashboardStatsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardStatsService $dashboardStats
    ) {}

    /**
     * Mostrar dashboard principal
     */
    public function index()
    {
        $restaurantId = auth()->user()->restaurant_id;
        $today = Carbon::today();

        $stats = $this->dashboardStats->operational($restaurantId);

        $stats['ventas_hoy'] = Order::where('restaurant_id', $restaurantId)
            ->where('status', 'CERRADO')
            ->whereDate('created_at', $today)
            ->sum('total');

        $stats['today_orders'] = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $today)
            ->count();

        // Pedidos recientes (sin cache para datos en tiempo real)
        $recentOrders = Order::where('restaurant_id', $restaurantId)
            ->with(['table', 'user'])
            ->whereIn('status', ['ABIERTO', 'ENVIADO', 'EN_PREPARACION', 'LISTO'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Top productos del día (sin caché para datos en tiempo real)
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.restaurant_id', $restaurantId)
            ->where('orders.status', 'CERRADO')
            ->whereDate('orders.created_at', $today)
            ->select('products.name', DB::raw('SUM(order_items.quantity) as total_quantity'))
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get();

        // Productos con stock bajo (sin cache para datos en tiempo real)
        $lowStockProducts = Product::where('restaurant_id', $restaurantId)
            ->where('has_stock', true)
            ->where('is_active', true)
            ->with('category')
            ->get()
            ->filter(function ($product) use ($restaurantId) {
                $currentStock = $product->getCurrentStock($restaurantId);

                return $currentStock <= $product->stock_minimum && $currentStock > 0;
            })
            ->sortBy(function ($product) use ($restaurantId) {
                return $product->getCurrentStock($restaurantId);
            })
            ->take(10);

        // Productos sin stock
        $outOfStockProducts = Product::where('restaurant_id', $restaurantId)
            ->where('has_stock', true)
            ->where('is_active', true)
            ->with('category')
            ->get()
            ->filter(function ($product) use ($restaurantId) {
                return $product->getCurrentStock($restaurantId) <= 0;
            })
            ->take(10);

        // MÓDULO 5: Ventas por mozo del día (sin caché para datos en tiempo real)
        $salesByWaiter = Payment::where('payments.restaurant_id', $restaurantId)
            ->whereDate('payments.created_at', $today)
            ->join('table_sessions', 'payments.table_session_id', '=', 'table_sessions.id')
            ->join('users', 'table_sessions.waiter_id', '=', 'users.id')
            ->select('users.name', 'users.id', DB::raw('SUM(payments.amount) as total_sales'), DB::raw('COUNT(DISTINCT payments.id) as payment_count'))
            ->groupBy('users.id', 'users.name')
            ->orderBy('total_sales', 'desc')
            ->get();

        // MÓDULO 5: Mesas activas con información de sesión (orden 0-9, 10-19, etc.)
        $activeTables = Table::sortByNumericGroup(
            Table::where('restaurant_id', $restaurantId)
                ->where('status', 'OCUPADA')
                ->with(['currentSession.waiter', 'sector'])
                ->get()
        );

        // MÓDULO 5: Ingresos del día por método de pago (sin caché para datos en tiempo real)
        $incomeByMethod = Payment::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $today)
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->orderBy('total', 'desc')
            ->get();

        $recentEnrollments = $this->recentEnrollments();
        $culture = $this->culture();
        $agendaWeek = $this->agendaWeek();
        $showBar = $this->showsBar();

        return view('dashboard', compact(
            'recentEnrollments',
            'culture',
            'agendaWeek',
            'showBar',
            'stats',
            'recentOrders',
            'topProducts',
            'lowStockProducts',
            'outOfStockProducts',
            'salesByWaiter',
            'activeTables',
            'incomeByMethod'
        ));
    }

    /**
     * Resumen del espacio cultural: talleres, inscriptos, lista de espera y eventos próximos.
     *
     * @return array{workshops: int, enrolled: int, waitlist: int, upcoming_events: int}|null
     */
    private function culture(): ?array
    {
        $user = auth()->user();
        $orgId = $user?->restaurant_id;
        $licenses = app(\App\Services\ModuleLicenseService::class);
        $perm = app(\App\Services\PermissionService::class);
        if ($orgId === null || ! $licenses->enabledForUser($user, 'workshops') || ! $perm->allowed($user, 'workshops.view')) {
            return null;
        }

        $workshops = \App\Models\Workshop::query()->where('restaurant_id', $orgId)->whereIn('status', ['ABIERTO', 'EN_CURSO']);
        if ($user->role === \App\Models\User::ROLE_PROFESOR) {
            $workshops->whereHas('teacher', fn ($t) => $t->where('user_id', $user->id));
        }
        $ids = $workshops->pluck('id');
        $enrollments = \App\Models\WorkshopEnrollment::query()->whereIn('workshop_id', $ids);

        $canEvents = $licenses->enabledForUser($user, 'events') && $perm->allowed($user, 'events.view');

        return [
            'workshops' => $ids->count(),
            'enrolled' => (clone $enrollments)->where('status', \App\Models\WorkshopEnrollment::ACTIVA)->count(),
            'waitlist' => (clone $enrollments)->where('status', \App\Models\WorkshopEnrollment::EN_ESPERA)->count(),
            'upcoming_events' => $canEvents ? \App\Models\Event::query()->where('restaurant_id', $orgId)
                ->whereBetween('date', [today()->addDay(), today()->addDays(14)])->where('status', '!=', \App\Models\Event::STATUS_CANCELADO)->count() : 0,
        ];
    }

    /**
     * Agenda de los próximos 7 días (clases, eventos, etapas, actividades, entregas y avisos): lo primero del inicio.
     *
     * @return \Illuminate\Support\Collection<array-key, mixed>|null fecha (Y-m-d) => items
     */
    private function agendaWeek(): ?\Illuminate\Support\Collection
    {
        $user = auth()->user();
        $orgId = $user?->restaurant_id;
        if ($orgId === null || ! app(\App\Services\ModuleLicenseService::class)->enabledForUser($user, 'workshops')
            || ! app(\App\Services\PermissionService::class)->allowed($user, 'agenda.view')) {
            return null;
        }

        $from = \Carbon\CarbonImmutable::today();

        return app(\App\Services\Community\AgendaService::class)->between($user, (int) $orgId, $from, $from->addDays(6)->endOfDay());
    }

    /** La barra (mesas, pedidos, caja) se muestra sólo si el módulo está activo y la persona lo usa. */
    private function showsBar(): bool
    {
        $user = auth()->user();
        $licenses = app(\App\Services\ModuleLicenseService::class);
        $perm = app(\App\Services\PermissionService::class);

        return ($licenses->enabledForUser($user, 'tables') && $perm->allowed($user, 'tables.view'))
            || ($licenses->enabledForUser($user, 'orders') && $perm->allowed($user, 'orders.view'));
    }

    /**
     * Últimas inscripciones a talleres (quién, qué taller, cuándo) para el inicio de un centro cultural.
     * El profe ve sólo las de sus talleres.
     */
    private function recentEnrollments(): \Illuminate\Support\Collection
    {
        $user = auth()->user();
        $orgId = $user?->restaurant_id;
        if ($orgId === null
            || ! app(\App\Services\ModuleLicenseService::class)->enabledForUser($user, 'workshops')
            || ! app(\App\Services\PermissionService::class)->allowed($user, 'workshops.view')) {
            return collect();
        }

        return \App\Models\WorkshopEnrollment::query()
            ->with(['contact', 'workshop'])
            ->whereIn('status', [\App\Models\WorkshopEnrollment::ACTIVA, \App\Models\WorkshopEnrollment::EN_ESPERA])
            ->whereHas('workshop', function ($q) use ($orgId, $user) {
                $q->where('restaurant_id', $orgId);
                if ($user->role === \App\Models\User::ROLE_PROFESOR) {
                    $q->whereHas('teacher', fn ($t) => $t->where('user_id', $user->id));
                }
            })
            ->latest('enrolled_at')->latest('id')->limit(8)->get();
    }
}
