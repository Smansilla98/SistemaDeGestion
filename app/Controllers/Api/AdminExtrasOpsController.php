<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\Models\Event;
use App\Models\FixedExpense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RecurringActivity;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AdminExtrasOpsController extends Controller
{
    public function reportsSales(Request $request): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $from = $request->date('date_from') ?? Carbon::today()->subDays(30);
        $to = $request->date('date_to') ?? Carbon::today();

        $orders = Order::where('restaurant_id', $rid)
            ->where('status', 'CERRADO')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        $totalSales = (float) (clone $orders)->sum('total');
        $totalOrders = (clone $orders)->count();

        $salesByDay = (clone $orders)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r) => [
                'day' => (string) $r->day,
                'total' => (float) $r->total,
                'count' => (int) $r->count,
            ])
            ->all();

        $salesByMethod = Payment::where('restaurant_id', $rid)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($r) => [
                'payment_method' => (string) $r->payment_method,
                'total' => (float) $r->total,
            ])
            ->all();

        return ApiResponse::success([
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'sales_by_day' => $salesByDay,
            'sales_by_method' => $salesByMethod,
        ]);
    }

    public function reportsProducts(Request $request): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        [$from, $to] = $this->dateRange($request);

        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.restaurant_id', $rid)
            ->where('orders.status', 'CERRADO')
            ->whereBetween('orders.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'name' => (string) $r->name,
                'total_quantity' => (int) $r->total_quantity,
                'total_revenue' => (float) $r->total_revenue,
            ])
            ->all();

        return ApiResponse::success([
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'top_products' => $topProducts,
        ]);
    }

    public function reportsStaff(Request $request): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        [$from, $to] = $this->dateRange($request);

        $salesByStaff = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('payments', 'orders.id', '=', 'payments.order_id')
            ->where('orders.restaurant_id', $rid)
            ->where('orders.status', 'CERRADO')
            ->whereBetween('orders.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('SUM(payments.amount) as total_sales')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_sales')
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'name' => (string) $r->name,
                'total_orders' => (int) $r->total_orders,
                'total_sales' => (float) $r->total_sales,
            ])
            ->all();

        return ApiResponse::success([
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'sales_by_staff' => $salesByStaff,
        ]);
    }

    public function exportSalesExcel(Request $request): mixed
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }
        [$from, $to] = $this->dateRange($request);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SalesExport($from->toDateString(), $to->toDateString(), $rid),
            'ventas-'.$from->toDateString().'-'.$to->toDateString().'.xlsx'
        );
    }

    public function exportSalesPdf(Request $request): mixed
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }
        [$from, $to] = $this->dateRange($request);
        $dateFrom = $from->toDateString();
        $dateTo = $to->toDateString();

        $salesByDay = Payment::where('restaurant_id', $rid)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        $salesByMethod = Payment::where('restaurant_id', $rid)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();
        $totalSales = (float) Payment::where('restaurant_id', $rid)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->sum('amount');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.sales-pdf', compact(
            'salesByDay',
            'salesByMethod',
            'totalSales',
            'dateFrom',
            'dateTo'
        ));

        return $pdf->download('ventas-'.$dateFrom.'.pdf');
    }

    public function exportProductsExcel(Request $request): mixed
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }
        [$from, $to] = $this->dateRange($request);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\TopProductsExport($from->toDateString(), $to->toDateString(), $rid),
            'productos-'.$from->toDateString().'.xlsx'
        );
    }

    public function exportStaffExcel(Request $request): mixed
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }
        [$from, $to] = $this->dateRange($request);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StaffExport($from->toDateString(), $to->toDateString(), $rid),
            'mozos-'.$from->toDateString().'.xlsx'
        );
    }

    public function events(Request $request): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        return ApiResponse::success(
            Event::where('restaurant_id', $rid)->orderByDesc('date')->limit(50)->get()->toArray()
        );
    }

    public function storeEvent(Request $request): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'date' => 'required|date',
            'time' => 'nullable|string|max:20',
            'expected_attendance' => 'nullable|integer|min:0',
            'status' => 'sometimes|in:PROGRAMADO,EN_CURSO,FINALIZADO,CANCELADO',
        ]);

        $row = Event::create([
            'restaurant_id' => $rid,
            'created_by' => $request->user()->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'date' => $data['date'],
            'time' => $data['time'] ?? null,
            'expected_attendance' => $data['expected_attendance'] ?? null,
            'status' => $data['status'] ?? 'PROGRAMADO',
        ]);

        return ApiResponse::success($row->toArray(), 201);
    }

    public function updateEvent(Request $request, int $id): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $row = Event::where('restaurant_id', $rid)->find($id);
        if (! $row) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'date' => 'sometimes|date',
            'time' => 'nullable|string|max:20',
            'expected_attendance' => 'nullable|integer|min:0',
            'status' => 'sometimes|in:PROGRAMADO,EN_CURSO,FINALIZADO,CANCELADO',
        ]);
        $row->update($data);

        return ApiResponse::success($row->fresh()->toArray());
    }

    public function destroyEvent(Request $request, int $id): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $row = Event::where('restaurant_id', $rid)->find($id);
        if (! $row) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }
        $row->delete();

        return ApiResponse::success(null, 200, 'Eliminado');
    }

    public function recurring(Request $request): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        return ApiResponse::success(
            RecurringActivity::where('restaurant_id', $rid)->orderBy('name')->get()->toArray()
        );
    }

    public function storeRecurring(Request $request): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'day_of_week' => 'required|in:MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY,SUNDAY',
            'start_time' => 'required|string|max:20',
            'end_time' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        $row = RecurringActivity::create([
            'restaurant_id' => $rid,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success($row->toArray(), 201);
    }

    public function updateRecurring(Request $request, int $id): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $row = RecurringActivity::where('restaurant_id', $rid)->find($id);
        if (! $row) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'day_of_week' => 'sometimes|in:MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY,SUNDAY',
            'start_time' => 'sometimes|string|max:20',
            'end_time' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);
        $row->update($data);

        return ApiResponse::success($row->fresh()->toArray());
    }

    public function destroyRecurring(Request $request, int $id): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $row = RecurringActivity::where('restaurant_id', $rid)->find($id);
        if (! $row) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }
        $row->delete();

        return ApiResponse::success(null, 200, 'Eliminado');
    }

    public function fixedExpenses(Request $request): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        return ApiResponse::success(
            FixedExpense::where('restaurant_id', $rid)->orderBy('name')->get()->toArray()
        );
    }

    public function storeFixedExpense(Request $request): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:GASTO,INGRESO',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:MENSUAL,QUINCENAL,SEMANAL,DIARIO,ANUAL',
            'start_date' => 'required|date',
            'is_active' => 'sometimes|boolean',
        ]);

        $row = FixedExpense::create([
            'restaurant_id' => $rid,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'category' => $data['category'],
            'amount' => $data['amount'],
            'frequency' => $data['frequency'],
            'start_date' => $data['start_date'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success($row->toArray(), 201);
    }

    public function updateFixedExpense(Request $request, int $id): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $row = FixedExpense::where('restaurant_id', $rid)->find($id);
        if (! $row) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'sometimes|in:GASTO,INGRESO',
            'category' => 'sometimes|string|max:100',
            'amount' => 'sometimes|numeric|min:0',
            'frequency' => 'sometimes|in:MENSUAL,QUINCENAL,SEMANAL,DIARIO,ANUAL',
            'start_date' => 'sometimes|date',
            'is_active' => 'sometimes|boolean',
        ]);
        $row->update($data);

        return ApiResponse::success($row->fresh()->toArray());
    }

    public function destroyFixedExpense(Request $request, int $id): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $row = FixedExpense::where('restaurant_id', $rid)->find($id);
        if (! $row) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }
        $row->delete();

        return ApiResponse::success(null, 200, 'Eliminado');
    }

    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $rows = $user->notifications()->limit(30)->get()->map(fn ($n) => [
            'id' => $n->id,
            'type' => class_basename($n->type),
            'data' => $n->data,
            'read_at' => optional($n->read_at)?->toIso8601String(),
            'created_at' => optional($n->created_at)->toIso8601String(),
        ]);

        return ApiResponse::success($rows->values()->all());
    }

    public function markNotificationRead(Request $request, string $id): JsonResponse
    {
        $n = $request->user()->notifications()->where('id', $id)->first();
        if (! $n) {
            return ApiResponse::error('No encontrada', 404, 'NOT_FOUND');
        }
        $n->markAsRead();

        return ApiResponse::success(['id' => $id], 200, 'Leída');
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return ApiResponse::success(null, 200, 'Todas leídas');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function dateRange(Request $request): array
    {
        $from = $request->date('date_from') ?? Carbon::today()->subDays(30);
        $to = $request->date('date_to') ?? Carbon::today();

        return [$from, $to];
    }

    private function rid(Request $request): int|JsonResponse
    {
        $rid = $request->user()?->restaurant_id;
        if (! $rid) {
            return ApiResponse::error('Usuario sin restaurante asignado', 403, 'NO_RESTAURANT');
        }

        return (int) $rid;
    }
}
