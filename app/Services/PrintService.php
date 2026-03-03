<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Printer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class PrintService
{
    /** Ancho imprimible Citizen CT-E301: 72 mm en puntos */
    protected const TICKET_WIDTH_PT = 204.09;

    /** Comando ESC/POS corte total (GS V 0) - Citizen CT-E301 guillotina */
    protected const ESCPOS_FULL_CUT = "\x1D\x56\x00";

    /**
     * Altura del PDF en puntos según cantidad de líneas (72mm ancho; corte al final).
     */
    protected function ticketHeightPt(int $itemsCount, bool $withTotalsAndPayments = false): float
    {
        $basePt = $withTotalsAndPayments ? 180 : 130;
        $perItemPt = 20;
        $height = $basePt + ($itemsCount * $perItemPt);
        return max(self::TICKET_WIDTH_PT, min($height, 1200));
    }

    /**
     * Agrupar items del pedido por producto (para la vista print-kitchen)
     */
    protected function groupOrderItems($items)
    {
        $groupedItems = collect();
        foreach ($items as $item) {
            $existingIndex = $groupedItems->search(fn ($i) => $i['product_id'] === $item->product_id);
            if ($existingIndex !== false) {
                $existing = $groupedItems[$existingIndex];
                $existing['quantity'] += $item->quantity;
                $existing['subtotal'] += $item->subtotal;
                if ($item->observations && ($existing['observations'] ?? '') !== $item->observations) {
                    $existing['observations'] = ($existing['observations'] ?? '') . ($existing['observations'] ? '; ' : '') . $item->observations;
                }
                $groupedItems[$existingIndex] = $existing;
            } else {
                $groupedItems->push([
                    'product_id' => $item->product_id,
                    'product' => $item->product,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'modifiers' => $item->modifiers ?? collect(),
                    'observations' => $item->observations,
                ]);
            }
        }
        return $groupedItems;
    }

    /**
     * Imprimir ticket de cocina
     */
    public function printKitchenTicket(Order $order, ?Printer $printer = null)
    {
        $order->load(['table', 'items.product', 'items.modifiers']);
        $groupedItems = $this->groupOrderItems($order->items);

        try {
            $heightPt = $this->ticketHeightPt($groupedItems->count(), false);
            $pdf = Pdf::loadView('orders.print-kitchen', compact('order', 'groupedItems'))
                ->setPaper([0, 0, self::TICKET_WIDTH_PT, $heightPt], 'portrait')
                ->setOption('enable-local-file-access', true);

            if ($printer && $printer->is_active) {
                return $this->sendToPrinter($pdf, $printer, "ticket-cocina-{$order->number}.pdf");
            }

            return $this->saveToFile($pdf, "kitchen", "ticket-cocina-{$order->number}.pdf");
        } catch (\Exception $e) {
            Log::error('Error al imprimir ticket de cocina: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Imprimir ticket de un solo ítem (comanda por ítem para cocina).
     */
    public function printItemTicket(Order $order, OrderItem $item, ?Printer $printer = null)
    {
        if ($item->order_id !== $order->id) {
            throw new \InvalidArgumentException('El ítem no pertenece al pedido');
        }
        $item->load(['product.category', 'modifiers']);
        $order->load(['table', 'user']);

        $heightPt = $this->ticketHeightPt(1, false);
        $pdf = Pdf::loadView('orders.print-ticket-item', compact('order', 'item'))
            ->setPaper([0, 0, self::TICKET_WIDTH_PT, $heightPt], 'portrait')
            ->setOption('enable-local-file-access', true);

        if ($printer && $printer->is_active) {
            return $this->sendToPrinter($pdf, $printer, "ticket-{$order->number}-item-{$item->id}.pdf");
        }
        return $this->saveToFile($pdf, "kitchen", "ticket-{$order->number}-item-{$item->id}.pdf");
    }

    /**
     * Obtener la impresora a usar para ticket de cocina (tipo kitchen, bar o cualquiera activa)
     */
    public function getPrinterForKitchenTicket(int $restaurantId): ?Printer
    {
        $printer = $this->getPrinterByType($restaurantId, 'kitchen');
        if ($printer) {
            return $printer;
        }
        $printer = $this->getPrinterByType($restaurantId, 'bar');
        if ($printer) {
            return $printer;
        }
        return Printer::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Imprimir comanda
     */
    public function printComanda(Order $order, ?Printer $printer = null)
    {
        try {
            $pdf = Pdf::loadView('orders.print-comanda', compact('order'))
                ->setPaper('a5', 'portrait')
                ->setOption('enable-local-file-access', true);

            if ($printer && $printer->is_active) {
                return $this->sendToPrinter($pdf, $printer, "comanda-{$order->number}.pdf");
            }

            return $this->saveToFile($pdf, "comanda", "comanda-{$order->number}.pdf");
        } catch (\Exception $e) {
            Log::error('Error al imprimir comanda: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Imprimir ticket simple
     */
    public function printTicket(Order $order, ?Printer $printer = null)
    {
        try {
            $order->load(['table', 'items.product', 'items.modifiers', 'payments']);
            $groupedItems = $this->groupOrderItems($order->items);
            $extraLines = ($order->payments && $order->payments->count() > 0) ? $order->payments->count() * 2 + 2 : 0;
            $heightPt = $this->ticketHeightPt($groupedItems->count() + $extraLines, true);

            $pdf = Pdf::loadView('orders.print-ticket', compact('order', 'groupedItems'))
                ->setPaper([0, 0, self::TICKET_WIDTH_PT, $heightPt], 'portrait')
                ->setOption('enable-local-file-access', true);

            if ($printer && $printer->is_active) {
                return $this->sendToPrinter($pdf, $printer, "ticket-{$order->number}.pdf");
            }

            return $this->saveToFile($pdf, "ticket", "ticket-{$order->number}.pdf");
        } catch (\Exception $e) {
            Log::error('Error al imprimir ticket: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Enviar a impresora
     */
    protected function sendToPrinter($pdf, Printer $printer, string $filename)
    {
        $output = $pdf->output();
        $tempFile = storage_path('app/temp/' . $filename);
        
        // Asegurar que existe el directorio
        File::ensureDirectoryExists(storage_path('app/temp'));
        file_put_contents($tempFile, $output);

        switch ($printer->connection_type) {
            case 'network':
                return $this->sendToNetworkPrinter($tempFile, $printer);
            
            case 'file':
                return $this->sendToFilePrinter($tempFile, $printer);
            
            case 'usb':
                // Para USB se requeriría librería adicional como mike42/escpos-php
                return $this->saveToFile($pdf, "usb", $filename);
            
            default:
                return $this->saveToFile($pdf, "default", $filename);
        }
    }

    /**
     * Enviar a impresora de red
     */
    protected function sendToNetworkPrinter(string $filePath, Printer $printer)
    {
        if (!$printer->ip_address) {
            throw new \Exception('IP de impresora no configurada');
        }

        try {
            // Usar socket para enviar archivo raw a impresora de red
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if (!$socket) {
                throw new \Exception('No se pudo crear socket');
            }

            $connected = @socket_connect($socket, $printer->ip_address, $printer->port);
            if (!$connected) {
                throw new \Exception("No se pudo conectar a {$printer->ip_address}:{$printer->port}");
            }

            // Leer archivo PDF y enviar como raw (para impresoras que aceptan PDF)
            $content = file_get_contents($filePath);
            socket_write($socket, $content);
            // Enviar comando de corte total ESC/POS (GS V 0) para que la CT-E301 corte al final
            socket_write($socket, self::ESCPOS_FULL_CUT);
            socket_close($socket);

            // Limpiar archivo temporal
            @unlink($filePath);

            return true;
        } catch (\Exception $e) {
            Log::error("Error enviando a impresora de red: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Guardar en archivo (para impresoras de archivo)
     */
    protected function sendToFilePrinter(string $filePath, Printer $printer)
    {
        if (!$printer->path) {
            throw new \Exception('Ruta de impresora no configurada');
        }

        try {
            File::ensureDirectoryExists(dirname($printer->path));
            File::copy($filePath, $printer->path . '/' . basename($filePath));
            @unlink($filePath);
            return true;
        } catch (\Exception $e) {
            Log::error("Error guardando en impresora de archivo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Guardar PDF en directorio
     */
    protected function saveToFile($pdf, string $type, string $filename)
    {
        $directory = storage_path("app/prints/{$type}");
        File::ensureDirectoryExists($directory);
        
        $pdf->save($directory . '/' . $filename);
        
        return $directory . '/' . $filename;
    }

    /**
     * Obtener impresora por tipo
     */
    public function getPrinterByType(int $restaurantId, string $type): ?Printer
    {
        return Printer::where('restaurant_id', $restaurantId)
            ->where('type', $type)
            ->where('is_active', true)
            ->first();
    }
}

