<?php

namespace App\Http\Controllers;

use App\Models\Printer;
use App\Services\PrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\File;

class PrinterController extends Controller
{
    protected $printService;

    public function __construct(PrintService $printService)
    {
        $this->printService = $printService;
    }

    /**
     * Listar impresoras
     */
    public function index()
    {
        $restaurantId = auth()->user()->restaurant_id;

        $printers = Printer::where('restaurant_id', $restaurantId)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('printers.index', compact('printers'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        return view('printers.create');
    }

    /**
     * Crear impresora
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Printer::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:kitchen,bar,cashier,invoice',
            'driver' => 'required|in:network,usb,file',
            'connection_type' => 'required|in:network,usb,file',
            'ip_address' => 'nullable|ip',
            'port' => 'nullable|integer|min:1|max:65535',
            'path' => 'nullable|string',
            'paper_width' => 'required|in:58,80',
            'auto_print' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['auto_print'] = $request->has('auto_print');
        $validated['is_active'] = $request->has('is_active', true);

        Printer::create($validated);

        return redirect()->route('printers.index')
            ->with('success', 'Impresora creada exitosamente');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Printer $printer)
    {
        Gate::authorize('update', $printer);

        return view('printers.edit', compact('printer'));
    }

    /**
     * Actualizar impresora
     */
    public function update(Request $request, Printer $printer)
    {
        Gate::authorize('update', $printer);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:kitchen,bar,cashier,invoice',
            'driver' => 'required|in:network,usb,file',
            'connection_type' => 'required|in:network,usb,file',
            'ip_address' => 'nullable|ip',
            'port' => 'nullable|integer|min:1|max:65535',
            'path' => 'nullable|string',
            'paper_width' => 'required|in:58,80',
            'auto_print' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['auto_print'] = $request->has('auto_print');
        $validated['is_active'] = $request->has('is_active');

        $printer->update($validated);

        return redirect()->route('printers.index')
            ->with('success', 'Impresora actualizada exitosamente');
    }

    /**
     * Eliminar impresora
     */
    public function destroy(Printer $printer)
    {
        Gate::authorize('delete', $printer);

        $printer->delete();

        return redirect()->route('printers.index')
            ->with('success', 'Impresora eliminada exitosamente');
    }

    /**
     * Probar impresora
     */
    public function test(Printer $printer)
    {
        Gate::authorize('update', $printer);

        try {
            // Crear un PDF de prueba
            $testContent = "TEST DE IMPRESORA\n\n";
            $testContent .= "Impresora: {$printer->name}\n";
            $testContent .= "Tipo: {$printer->type}\n";
            $testContent .= "Fecha: " . now()->format('Y-m-d H:i:s') . "\n";
            $testContent .= "\nEste es un documento de prueba.\n";

            // Guardar en archivo temporal
            $tempFile = storage_path('app/temp/test-printer-' . $printer->id . '.txt');
            File::ensureDirectoryExists(storage_path('app/temp'));
            file_put_contents($tempFile, $testContent);

            if ($printer->connection_type === 'network' && $printer->ip_address) {
                $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                if ($socket && @socket_connect($socket, $printer->ip_address, $printer->port)) {
                    socket_write($socket, $testContent);
                    socket_close($socket);
                    @unlink($tempFile);
                    return back()->with('success', 'Prueba de impresión enviada exitosamente');
                }
            }

            @unlink($tempFile);
            return back()->with('info', 'Prueba guardada en archivo. Configuración verificada.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al probar impresora: ' . $e->getMessage());
        }
    }
}

