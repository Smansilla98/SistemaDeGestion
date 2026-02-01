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
     * Generar PDF de ticket de cocina
     */
    public function kitchenTicket(Order $order, Request $request)
    {
        $order->load(['table', 'items.product', 'items.modifiers']);

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
        $pdf = Pdf::loadView('orders.print-kitchen', compact('order'))
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

        $pdf = Pdf::loadView('orders.print-comanda', compact('order'))
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

        $pdf = Pdf::loadView('orders.print-invoice', compact('order'))
            ->setPaper([0, 0, 226.77, 841.89], 'portrait') // 80mm de ancho
            ->setOption('enable-local-file-access', true);

        return $pdf->stream("factura-{$order->number}.pdf");
    }

    /**
     * Generar PDF de ticket simple
     */
    public function ticket(Order $order)
    {
        $order->load(['table', 'items.product', 'payments']);

        $pdf = Pdf::loadView('orders.print-ticket', compact('order'))
            ->setPaper([0, 0, 226.77, 841.89], 'portrait') // 80mm de ancho
            ->setOption('enable-local-file-access', true);

        return $pdf->stream("ticket-{$order->number}.pdf");
    }
}

