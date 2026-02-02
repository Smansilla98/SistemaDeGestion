<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Services\DatabaseResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class ConfigurationController extends Controller
{
    public function __construct(
        private DatabaseResetService $databaseResetService
    ) {
        $this->middleware('role:ADMIN');
    }

    /**
     * Mostrar página de configuración
     */
    public function index()
    {
        $restaurant = Restaurant::findOrFail(auth()->user()->restaurant_id);
        
        // Obtener configuraciones visuales
        $settings = $restaurant->settings ?? [];
        $logo = $settings['logo'] ?? null;
        $colors = $settings['colors'] ?? [
            'primary' => '#1e8081',
            'secondary' => '#22565e',
            'accent' => '#c94a2d',
        ];
        $fonts = $settings['fonts'] ?? [
            'primary' => 'Inter',
            'secondary' => 'Roboto',
        ];

        return view('configuration.index', compact('restaurant', 'logo', 'colors', 'fonts'));
    }

    /**
     * Actualizar configuración visual
     */
    public function updateVisual(Request $request)
    {
        $restaurant = Restaurant::findOrFail(auth()->user()->restaurant_id);

        $validated = $request->validate([
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'primary_font' => 'nullable|string|max:100',
            'secondary_font' => 'nullable|string|max:100',
        ]);

        $settings = $restaurant->settings ?? [];

        // Manejar logo
        if ($request->hasFile('logo')) {
            // Eliminar logo anterior si existe
            if (isset($settings['logo']) && $settings['logo']) {
                Storage::disk('public')->delete($settings['logo']);
            }

            // Guardar nuevo logo
            $logoPath = $request->file('logo')->store('logos', 'public');
            $settings['logo'] = $logoPath;
        }

        // Actualizar colores
        if ($request->filled('primary_color')) {
            $settings['colors']['primary'] = $validated['primary_color'];
        }
        if ($request->filled('secondary_color')) {
            $settings['colors']['secondary'] = $validated['secondary_color'];
        }
        if ($request->filled('accent_color')) {
            $settings['colors']['accent'] = $validated['accent_color'];
        }

        // Actualizar fuentes
        if ($request->filled('primary_font')) {
            $settings['fonts']['primary'] = $validated['primary_font'];
        }
        if ($request->filled('secondary_font')) {
            $settings['fonts']['secondary'] = $validated['secondary_font'];
        }

        $restaurant->settings = $settings;
        $restaurant->save();

        return back()->with('success', 'Configuración visual actualizada exitosamente');
    }

    /**
     * Resetear base de datos (manteniendo usuarios)
     */
    public function resetDatabase(Request $request)
    {
        $validated = $request->validate([
            'confirm_text' => 'required|in:RESETEAR',
        ]);

        try {
            $this->databaseResetService->resetDatabase(auth()->user()->restaurant_id);

            return redirect()->route('configuration.index')
                ->with('success', 'Base de datos reseteada exitosamente. Todos los datos han sido eliminados excepto los usuarios.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al resetear la base de datos: ' . $e->getMessage());
        }
    }
}

