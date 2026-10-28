<?php

declare(strict_types=1);

namespace App\Http\Controllers\Entry;

use App\Http\Controllers\Controller;
use App\Services\ModuleLicenseService;
use App\Support\CommercialQuote;
use App\Support\DemoCommunityData;
use App\Support\DemoEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        DemoCommunityData::ensure(request()->user());

        return redirect()->route(DemoEntry::homeRoute(request()->user()));
    }

    /**
     * Demo de centro cultural / escuela / mercado: sólo los módulos de gestión comunitaria,
     * con datos de ejemplo para crear y recorrer talleres, eventos, consignaciones y pedidos.
     */
    public function culture(ModuleLicenseService $licenses): RedirectResponse
    {
        $map = array_fill_keys(array_keys($licenses->offered()), false);
        foreach (array_keys($licenses->community()) as $key) {
            $map[$key] = true;
        }
        DemoEntry::enterCustom($map);
        DemoCommunityData::ensure(request()->user());

        return redirect()->route('agenda.index');
    }

    public function modules(ModuleLicenseService $licenses): View
    {
        $selected = session('preview_modules');

        return view('entry.modules', [
            'offered' => $licenses->offered(),
            'community' => $licenses->community(),
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
        foreach (array_keys($licenses->offered() + $licenses->community()) as $key) {
            $map[$key] = filter_var($input[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        DemoEntry::enterCustom($map);
        if (collect($licenses->community())->keys()->contains(fn ($k) => $map[$k])) {
            DemoCommunityData::ensure($request->user());
        }

        return redirect()->route(DemoEntry::homeRoute($request->user()));
    }

    public function quote(Request $request, CommercialQuote $quotes): View
    {
        $keys = $this->quoteKeys($request, $quotes);

        return view('entry.quote', [
            'quote' => $quotes->present($keys),
            'selected' => $keys,
        ]);
    }

    public function quotePdf(Request $request, CommercialQuote $quotes): Response
    {
        $keys = $this->quoteKeys($request, $quotes);
        $pdf = Pdf::loadView('entry.quote-pdf', [
            'quote' => $quotes->present($keys),
        ])->setPaper('a4');

        return $pdf->download('Al-Toque-Presupuesto-modular.pdf');
    }

    /**
     * @return list<string>
     */
    private function quoteKeys(Request $request, CommercialQuote $quotes): array
    {
        $raw = $request->query('modules');
        if (! is_array($raw)) {
            return array_keys(app(ModuleLicenseService::class)->offered());
        }

        $keys = $quotes->sanitizeKeys(array_map('strval', $raw));

        return $keys;
    }
}
