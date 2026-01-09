<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Printer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class PrintService
{
    /**
     * Imprimir ticket de cocina
     */
    public function printKitchenTicket(Order $order, ?Printer $printer = null)
    {
        try {
            // Generar PDF
            $pdf = Pdf::loadView('orders.print-kitchen', compact('order'))
                ->setPaper([0, 0, 226.77, 841.89], 'portrait')
                ->setOption('enable-local-file-access', true);

            // Si hay impresora configurada, intentar imprimir
            if ($printer && $printer->is_active) {
                return $this->sendToPrinter($pdf, $printer, "ticket-cocina-{$order->number}.pdf");
            }

            // Por defecto, guardar en archivo (simulación)
            return $this->saveToFile($pdf, "kitchen", "ticket-cocina-{$order->number}.pdf");
        } catch (\Exception $e) {
            Log::error('Error al imprimir ticket de cocina: ' . $e->getMessage());
            throw $e;
        }
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
            $pdf = Pdf::loadView('orders.print-ticket', compact('order'))
                ->setPaper([0, 0, 226.77, 841.89], 'portrait')
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

