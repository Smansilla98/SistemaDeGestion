<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\PrintService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OrderPrintController extends Controller
{
    protected $printService;

    public function __construct(PrintService $printService)
    {
        $this->printService = $printService;
    }

    /**
     * Agrupar items de un pedido por producto (sumando cantidades y subtotales)
     */
    protected function groupOrderItems($items)
    {
        $groupedItems = collect();
        
        foreach ($items as $item) {
            // Buscar si ya existe un item con el mismo product_id
            $existingItemIndex = $groupedItems->search(function ($i) use ($item) {
                return $i['product_id'] === $item->product_id;
            });
            
            if ($existingItemIndex !== false) {
                // Si existe, sumar cantidad y subtotal
                $existingItem = $groupedItems[$existingItemIndex];
                $existingItem['quantity'] += $item->quantity;
                $existingItem['subtotal'] += $item->subtotal;
                // Mantener el precio unitario del primer item (no promediar)
                // Si hay observaciones diferentes, combinarlas
                if ($item->observations && $existingItem['observations'] !== $item->observations) {
                    $existingItem['observations'] = ($existingItem['observations'] ? $existingItem['observations'] . '; ' : '') . $item->observations;
                }
                $groupedItems[$existingItemIndex] = $existingItem;
            } else {
                // Si no existe, agregarlo
                $groupedItems->push([
                    'product_id' => $item->product_id,
                    'product' => $item->product,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'modifiers' => $item->modifiers,
                    'observations' => $item->observations,
                ]);
            }
        }
        
        return $groupedItems;
    }

    /** Ancho imprimible Citizen CT-E301: 72 mm en puntos (203 dpi) */
    protected const TICKET_WIDTH_PT = 204.09;

    /**
     * Calcular altura del PDF en puntos para que el ticket se adapte al contenido.
     * Ancho 72mm (área imprimible CT-E301). Alto mínimo 72mm, crece con los ítems.
     */
    protected function ticketHeightPt(int $itemsCount, bool $withTotalsAndPayments = false): float
    {
        $basePt = $withTotalsAndPayments ? 180 : 130; // cabecera + pie; factura/ticket llevan totales y pagos
        $perItemPt = 20;
        $height = $basePt + ($itemsCount * $perItemPt);
        return max(self::TICKET_WIDTH_PT, min($height, 1200)); // mínimo 72mm, máximo ~423mm
    }

    /**
     * Generar PDF de ticket de cocina
     */
    public function kitchenTicket(Order $order, Request $request)
    {
        $order->load(['table', 'items.product', 'items.modifiers']);

        // Agrupar items por producto
        $groupedItems = $this->groupOrderItems($order->items);

        // Si se solicita impresión directa
        if ($request->has('print') && $request->print === 'true') {
            $printer = $this->printService->getPrinterByType($order->restaurant_id, 'kitchen');
            try {
                $this->printService->printKitchenTicket($order, $printer);
                return redirect()->back()->with('success', 'Ticket enviado a impresora');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Error al imprimir: ' . $e->getMessage());
            }
        }

        $heightPt = $this->ticketHeightPt($groupedItems->count(), false);
        $pdf = Pdf::loadView('orders.print-kitchen', compact('order', 'groupedItems'))
            ->setPaper([0, 0, self::TICKET_WIDTH_PT, $heightPt], 'portrait')
            ->setOption('enable-local-file-access', true);

        return $pdf->stream("ticket-cocina-{$order->number}.pdf");
    }

    /**
     * Página HTML del ticket que dispara la impresión automática (usa la impresora del navegador).
     * El contenido es HTML (no iframe/PDF) para que window.print() se ejecute de forma fiable.
     */
    public function kitchenTicketAuto(Order $order)
    {
        $order->load(['table', 'items.product', 'items.modifiers', 'user']);
        $groupedItems = $this->groupOrderItems($order->items);

        return response()->view('orders.print-kitchen-auto', [
            'order' => $order,
            'groupedItems' => $groupedItems,
        ]);
    }

    /**
     * Generar PDF de comanda
     */
    public function comanda(Order $order)
    {
        $order->load(['table', 'items.product', 'items.modifiers', 'user']);

        // Agrupar items por producto
        $groupedItems = $this->groupOrderItems($order->items);

        $pdf = Pdf::loadView('orders.print-comanda', compact('order', 'groupedItems'))
            ->setPaper('a5', 'portrait')
            ->setOption('enable-local-file-access', true);

        return $pdf->stream("comanda-{$order->number}.pdf");
    }

    /**
     * Generar PDF de factura (formato ticket térmico 72mm, Citizen CT-E301)
     */
    public function invoice(Order $order)
    {
        $order->load(['table', 'items.product', 'items.modifiers', 'user', 'payments', 'restaurant']);

        // Agrupar items por producto
        $groupedItems = $this->groupOrderItems($order->items);

        $extraLines = 0;
        if ($order->payments && $order->payments->count() > 0) {
            $extraLines = $order->payments->count() * 2 + 2; // líneas de pago + total pagado + vuelto
        }
        $heightPt = $this->ticketHeightPt($groupedItems->count() + $extraLines, true);

        $pdf = Pdf::loadView('orders.print-invoice', compact('order', 'groupedItems'))
            ->setPaper([0, 0, self::TICKET_WIDTH_PT, $heightPt], 'portrait')
            ->setOption('enable-local-file-access', true);

        return $pdf->stream("factura-{$order->number}.pdf");
    }

    /**
     * Generar PDF de ticket simple
     */
    public function ticket(Order $order)
    {
        $order->load(['table', 'items.product', 'items.modifiers', 'payments']);

        // Agrupar items por producto
        $groupedItems = $this->groupOrderItems($order->items);

        $extraLines = ($order->payments && $order->payments->count() > 0) ? $order->payments->count() * 2 + 2 : 0;
        $heightPt = $this->ticketHeightPt($groupedItems->count() + $extraLines, true);

        $pdf = Pdf::loadView('orders.print-ticket', compact('order', 'groupedItems'))
            ->setPaper([0, 0, self::TICKET_WIDTH_PT, $heightPt], 'portrait')
            ->setOption('enable-local-file-access', true);

        return $pdf->stream("ticket-{$order->number}.pdf");
    }

    /**
     * Ticket simple de un solo ítem (para cocina: una comanda por ítem).
     */
    public function itemTicket(Order $order, OrderItem $item)
    {
        if ($item->order_id !== $order->id) {
            abort(404);
        }
        $item->load(['product.category', 'modifiers']);
        $order->load(['user']);

        $heightPt = $this->ticketHeightPt(1, false);

        $pdf = Pdf::loadView('orders.print-ticket-item', compact('order', 'item'))
            ->setPaper([0, 0, self::TICKET_WIDTH_PT, $heightPt], 'portrait')
            ->setOption('enable-local-file-access', true);

        return $pdf->stream("ticket-{$order->number}-item-{$item->id}.pdf");
    }

    /**
     * Ticket de un ítem en HTML con impresión automática (para órdenes rápidas).
     * Abre el diálogo de impresión del navegador y cierra la ventana después.
     */
    public function itemTicketAuto(Order $order, OrderItem $item)
    {
        if ($item->order_id !== $order->id) {
            abort(404);
        }
        $item->load(['product.category', 'modifiers']);
        $order->load(['table', 'user']);

        return response()->view('orders.print-ticket-item-auto', [
            'order' => $order,
            'item' => $item,
        ]);
    }
}

