<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Services\DatabaseResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfigurationController extends Controller
{
    public function __construct(
        private DatabaseResetService $databaseResetService
    ) {
        $this->middleware('role:ADMIN,GERENTE');
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
        $colors = \App\Support\Branding::palette(is_array($settings) ? $settings : null);
        $fonts = $settings['fonts'] ?? [
            'primary' => 'Roboto',
            'secondary' => 'Roboto',
        ];

        $public = \App\Support\PublicSite::info($restaurant);

        return view('configuration.index', compact('restaurant', 'logo', 'colors', 'fonts', 'public'));
    }

    /**
     * Datos que muestra la web pública: presentación, horarios, redes y contacto.
     */
    public function updatePublic(Request $request)
    {
        $restaurant = Restaurant::findOrFail(auth()->user()->restaurant_id);

        $validated = $request->validate([
            'about' => 'nullable|string|max:400',
            'hours' => 'nullable|string|max:150',
            'instagram' => ['nullable', 'string', 'max:60', 'regex:/^@?[A-Za-z0-9._]+$/'],
            'whatsapp' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:150',
        ], [], ['about' => 'presentación', 'hours' => 'horarios']);

        $settings = $restaurant->settings ?? [];
        $settings['public'] = [
            'about' => $validated['about'] ?? null,
            'hours' => $validated['hours'] ?? null,
            'instagram' => $validated['instagram'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
        ];
        $restaurant->settings = $settings;
        $restaurant->fill([
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
        ])->save();

        return back()->with('success', 'Datos de la web pública actualizados.');
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

        // Inicializar arrays si no existen
        if (! isset($settings['colors'])) {
            $settings['colors'] = [];
        }
        if (! isset($settings['fonts'])) {
            $settings['fonts'] = [];
        }

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
            return back()->with('error', 'Error al resetear la base de datos: '.$e->getMessage());
        }
    }
}
