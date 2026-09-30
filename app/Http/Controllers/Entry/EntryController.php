<?php

declare(strict_types=1);

namespace App\Http\Controllers\Entry;

use App\Http\Controllers\Controller;
use App\Services\ModuleLicenseService;
use App\Support\DemoEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntryController extends Controller
{
    public function choose(): View
    {
        return view('entry.choose');
    }

    public function demo(): RedirectResponse
    {
        DemoEntry::enterDemo();

        return redirect()->route(DemoEntry::homeRoute(request()->user()));
    }

    public function modules(ModuleLicenseService $licenses): View
    {
        $selected = session('preview_modules');

        return view('entry.modules', [
            'offered' => $licenses->offered(),
            'selected' => is_array($selected) ? $selected : null,
        ]);
    }

    public function storeModules(Request $request, ModuleLicenseService $licenses): RedirectResponse
    {
        $request->validate([
            'modules' => ['nullable', 'array'],
        ]);

        $input = $request->input('modules', []);
        $map = [];
        foreach (array_keys($licenses->offered()) as $key) {
            $map[$key] = filter_var($input[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        DemoEntry::enterCustom($map);

        return redirect()->route(DemoEntry::homeRoute($request->user()));
    }
}
