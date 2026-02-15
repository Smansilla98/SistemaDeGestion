<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
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

        // Por defecto, mostrar PDF
        $pdf = Pdf::loadView('orders.print-kitchen', compact('order', 'groupedItems'))
            ->setPaper([0, 0, 226.77, 841.89], 'portrait')
            ->setOption('enable-local-file-access', true);

        return $pdf->stream("ticket-cocina-{$order->number}.pdf");
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
     * Generar PDF de factura (formato ticket térmico 80mm)
     */
    public function invoice(Order $order)
    {
        $order->load(['table', 'items.product', 'items.modifiers', 'user', 'payments', 'restaurant']);

        // Agrupar items por producto
        $groupedItems = $this->groupOrderItems($order->items);

        $pdf = Pdf::loadView('orders.print-invoice', compact('order', 'groupedItems'))
            ->setPaper([0, 0, 226.77, 841.89], 'portrait') // 80mm de ancho
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

        $pdf = Pdf::loadView('orders.print-ticket', compact('order', 'groupedItems'))
            ->setPaper([0, 0, 226.77, 841.89], 'portrait') // 80mm de ancho
            ->setOption('enable-local-file-access', true);

        return $pdf->stream("ticket-{$order->number}.pdf");
    }
}

