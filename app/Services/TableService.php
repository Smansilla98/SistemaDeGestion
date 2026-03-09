<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\CashRegisterSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TableService
{
    public function __construct(
        private OrderService $orderService
    ) {}

    /**
     * Obtener datos para el recibo consolidado de una mesa (sesión actual o desde BD).
     */
    public function getConsolidatedReceiptData(Table $table): array
    {
        $closedOrders = collect(session('orders_closed', []));
        $consolidatedItems = collect(session('consolidated_items', []));
        $totalAmount = session('total_amount', 0);
        $totalSubtotal = session('total_subtotal', 0);
        $totalDiscount = session('total_discount', 0);
        $sessionId = session('table_session_id');
        $payments = collect(session('payments', []));

        if ($closedOrders->isEmpty() || $consolidatedItems->isEmpty()) {
            if (!$sessionId) {
                if ($table->current_session_id) {
                    $sessionId = $table->current_session_id;
                } else {
                    $recentPayment = Payment::where('restaurant_id', $table->restaurant_id)
                        ->whereHas('order', fn ($q) => $q->where('table_id', $table->id))
                        ->whereNotNull('table_session_id')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($recentPayment) {
                        $sessionId = $recentPayment->table_session_id;
                    } else {
                        $lastSession = TableSession::where('table_id', $table->id)
                            ->where('status', TableSession::STATUS_CERRADA)
                            ->orderBy('ended_at', 'desc')
                            ->first();
                        if ($lastSession) {
                            $sessionId = $lastSession->id;
                        }
                    }
                }
            }

            if (!$sessionId) {
                Log::warning('No se pudo determinar table_session_id para el recibo consolidado', [
                    'table_id' => $table->id,
                    'restaurant_id' => $table->restaurant_id,
                ]);
                $closedOrders = collect();
                $consolidatedItems = collect();
                $totalAmount = 0;
                $totalSubtotal = 0;
                $totalDiscount = 0;
            } else {
                $sessionOrders = Order::where('table_id', $table->id)
                    ->where('table_session_id', $sessionId)
                    ->with(['items.product.category', 'items.modifiers', 'user', 'payments'])
                    ->orderBy('created_at', 'asc')
                    ->get();

                $closedOrders = $sessionOrders->isNotEmpty() ? $sessionOrders : collect();

                if ($sessionOrders->isNotEmpty()) {
                    $consolidatedItems = collect();
                    foreach ($sessionOrders as $order) {
                        if (!$order->relationLoaded('items')) {
                            $order->load('items.product.category', 'items.modifiers');
                        }
                        foreach ($order->items as $item) {
                            $existingItemIndex = $consolidatedItems->search(fn ($i) => $i['product_id'] === $item->product_id);
                            if ($existingItemIndex !== false) {
                                $existingItem = $consolidatedItems[$existingItemIndex];
                                $newQuantity = $existingItem['quantity'] + $item->quantity;
                                $newSubtotal = $existingItem['subtotal'] + $item->subtotal;
                                $consolidatedItems[$existingItemIndex] = [
                                    'product_id' => $existingItem['product_id'],
                                    'product_name' => $existingItem['product_name'],
                                    'quantity' => $newQuantity,
                                    'unit_price' => $newSubtotal / $newQuantity,
                                    'subtotal' => $newSubtotal,
                                    'modifiers' => $existingItem['modifiers'] ?? $item->modifiers,
                                    'observations' => $existingItem['observations'] ?? $item->observations,
                                ];
                            } else {
                                $consolidatedItems->push([
                                    'product_id' => $item->product_id,
                                    'product_name' => $item->product->name,
                                    'quantity' => $item->quantity,
                                    'unit_price' => $item->unit_price,
                                    'subtotal' => $item->subtotal,
                                    'modifiers' => $item->modifiers,
                                    'observations' => $item->observations,
                                ]);
                            }
                        }
                    }
                    $totalSubtotal = $sessionOrders->sum('subtotal');
                    $totalDiscount = $sessionOrders->sum('discount');
                    $totalAmount = $sessionOrders->sum('total');
                }
            }
        }

        if ($closedOrders->isNotEmpty()) {
            $totalSubtotal = $closedOrders->sum('subtotal');
            $totalDiscount = $closedOrders->sum('discount');
            $totalAmount = $closedOrders->sum('total');
        } elseif ($consolidatedItems->isNotEmpty()) {
            $totalSubtotal = $consolidatedItems->sum('subtotal');
            $totalDiscount = $totalDiscount ?? 0;
            $totalAmount = $totalSubtotal - $totalDiscount;
        }

        if ($sessionId && $payments->isEmpty()) {
            $dbPayments = Payment::where('table_session_id', $sessionId)->with('user')->get();
            if ($dbPayments->isNotEmpty()) {
                $payments = $dbPayments;
            }
        }

        $table->load('sector');

        return compact('table', 'closedOrders', 'consolidatedItems', 'totalAmount', 'totalSubtotal', 'totalDiscount', 'payments');
    }

    /**
     * Procesar pago de mesa y cerrar sesión. Retorna array con datos para la respuesta.
     * Ejecuta la lógica dentro de una transacción DB.
     *
     * @return array{success: bool, message?: string, redirect?: string, session_id?: int, change?: float, flash?: array}
     */
    public function processTablePayment(Table $table, array $validated, int $userId): array
    {
        return DB::transaction(function () use ($table, $validated, $userId) {
            return $this->executeProcessTablePayment($table, $validated, $userId);
        });
    }

    /**
     * Lógica de procesamiento de pago (llamada dentro de transacción).
     */
    protected function executeProcessTablePayment(Table $table, array $validated, int $userId): array
    {
        $activeOrders = Order::where('table_id', $table->id)
            ->where('table_session_id', $table->current_session_id)
            ->whereNotIn('status', [Order::STATUS_CERRADO, Order::STATUS_CANCELADO])
            ->with(['items.product'])
            ->get();

        if ($activeOrders->isEmpty()) {
            return ['success' => false, 'message' => 'No hay pedidos activos para cerrar'];
        }

        $baseSubtotal = $activeOrders->sum('subtotal');
        $baseDiscount = $activeOrders->sum('discount');
        $additionalDiscount = 0;
        if (!empty($validated['discount_type_id'])) {
            $discountType = \App\Models\DiscountType::find($validated['discount_type_id']);
            if ($discountType && $discountType->restaurant_id === $table->restaurant_id) {
                $additionalDiscount = $discountType->calculateDiscount($baseSubtotal);
            }
        }
        $totalDiscount = $baseDiscount + $additionalDiscount;
        $totalAmount = $baseSubtotal - $totalDiscount;
        $totalPaid = collect($validated['payments'])->sum('amount');

        if ($totalPaid < $totalAmount - 0.01) {
            return [
                'success' => false,
                'message' => "El total pagado (\${$totalPaid}) es menor al total a pagar (\${$totalAmount}). Faltan $" . number_format($totalAmount - $totalPaid, 2),
            ];
        }

        $change = $totalPaid - $totalAmount;

        if ($additionalDiscount > 0 && $baseSubtotal > 0) {
            foreach ($activeOrders as $order) {
                $orderProportion = $order->subtotal / $baseSubtotal;
                $orderAdditionalDiscount = round($additionalDiscount * $orderProportion, 2);
                $order->discount += $orderAdditionalDiscount;
                $order->total = $order->subtotal - $order->discount;
                $order->save();
            }
        }

        $ordersClosed = [];
        $allItems = collect();
        $finalTotalDiscount = 0;

        foreach ($activeOrders as $order) {
            $order->load(['items.product.category', 'items.modifiers']);
            $this->orderService->closeOrder($order, false);
            $finalTotalDiscount += $order->discount;
            foreach ($order->items as $item) {
                $existingIndex = $allItems->search(fn ($i) => $i['product_id'] === $item->product_id);
                if ($existingIndex !== false) {
                    $existing = $allItems[$existingIndex];
                    $existing['quantity'] += $item->quantity;
                    $existing['subtotal'] += $item->subtotal;
                    $existing['unit_price'] = $existing['subtotal'] / $existing['quantity'];
                    $allItems[$existingIndex] = $existing;
                } else {
                    $allItems->push([
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                        'modifiers' => $item->modifiers,
                        'observations' => $item->observations,
                    ]);
                }
            }
            $ordersClosed[] = $order;
        }

        foreach ($ordersClosed as $order) {
            foreach ($order->items as $item) {
                if ($item->product->has_stock) {
                    $currentStock = $item->product->getCurrentStock($order->restaurant_id);
                    if ($currentStock < 0) {
                        Log::warning('Stock negativo al cerrar mesa', [
                            'product' => $item->product->name,
                            'stock' => $currentStock,
                        ]);
                    }
                }
            }
        }

        $firstOrder = $ordersClosed[0];
        $cashRegisterSession = CashRegisterSession::where('restaurant_id', $table->restaurant_id)
            ->where('status', CashRegisterSession::STATUS_ABIERTA)
            ->orderBy('opened_at', 'desc')
            ->first();

        if (!$cashRegisterSession) {
            Log::warning('No se encontró sesión de caja activa al procesar pago', [
                'table_id' => $table->id,
                'restaurant_id' => $table->restaurant_id,
                'user_id' => $userId,
            ]);
        }

        $waiterName = $firstOrder->user->name ?? 'N/A';
        $paymentsCreated = [];
        foreach ($validated['payments'] as $paymentData) {
            $notes = $paymentData['notes'] ?? '';
            $additionalInfo = "Mesa: {$table->number} | Mozo: {$waiterName}";
            $notes = $notes ? "{$additionalInfo} | {$notes}" : $additionalInfo;
            $paymentsCreated[] = Payment::create([
                'restaurant_id' => $table->restaurant_id,
                'order_id' => $firstOrder->id,
                'table_session_id' => $table->current_session_id,
                'cash_register_session_id' => $cashRegisterSession->id ?? null,
                'user_id' => $userId,
                'payment_method' => $paymentData['payment_method'],
                'amount' => $paymentData['amount'],
                'operation_number' => $paymentData['operation_number'] ?? null,
                'notes' => $notes,
            ]);
        }

        $savedSessionId = $table->current_session_id;
        DB::table('table_sessions')
            ->where('id', $savedSessionId)
            ->update([
                'ended_at' => now(),
                'status' => 'CERRADA',
                'updated_at' => now(),
            ]);
        $table->update([
            'status' => 'LIBRE',
            'current_order_id' => null,
            'current_session_id' => null,
        ]);

        $finalTotalSubtotal = $allItems->sum('subtotal');
        $finalTotalAmount = $finalTotalSubtotal - $finalTotalDiscount;
        $successMessage = 'Mesa cerrada y pago procesado exitosamente.';
        if ($change > 0.01) {
            $successMessage .= ' Cambio: $' . number_format($change, 2);
        }

        return [
            'success' => true,
            'message' => $successMessage,
            'redirect' => route('tables.consolidated-receipt', $table),
            'session_id' => $savedSessionId,
            'change' => $change > 0.01 ? $change : 0,
            'flash' => [
                'table_session_id' => $savedSessionId,
                'total_amount' => $finalTotalAmount,
                'total_subtotal' => $finalTotalSubtotal,
                'total_discount' => $finalTotalDiscount,
                'change' => $change > 0.01 ? $change : 0,
                'orders_closed' => $ordersClosed,
                'consolidated_items' => $allItems,
                'payments' => collect($paymentsCreated),
            ],
        ];
    }
}
