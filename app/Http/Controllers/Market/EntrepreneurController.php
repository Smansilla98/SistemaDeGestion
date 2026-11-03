<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Entrepreneur;
use App\Models\EntrepreneurGroup;
use App\Services\Documents\DocumentService;
use App\Services\Market\ConsignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EntrepreneurController extends Controller
{
    use OrganizationContext;

    public function index(Request $request): View
    {
        $this->requirePermission('market.view');
        $q = Entrepreneur::query()->where('restaurant_id', $this->org())->with('groups')->withCount('products')->orderBy('business_name');
        if (($own = $this->ownEntrepreneurId()) !== null) {
            $q->whereKey($own);
        }
        if ($request->filled('group_id')) {
            $q->whereHas('groups', fn ($g) => $g->whereKey((int) $request->input('group_id')));
        }

        return view('market.entrepreneurs.index', [
            'entrepreneurs' => $q->get(),
            'groups' => EntrepreneurGroup::query()->where('restaurant_id', $this->org())->withCount('entrepreneurs')->orderBy('name')->get(),
            'canCreate' => $this->can('market.create'),
        ]);
    }

    public function create(): View
    {
        $this->requirePermission('market.create');

        return view('market.entrepreneurs.form', $this->formData(new Entrepreneur(['is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requirePermission('market.create');
        $data = $this->validated($request);
        if (empty($data['contact_id'])) {
            $data['contact_id'] = Contact::query()->create(['restaurant_id' => $this->org(), 'kind' => 'EMPRENDEDOR', 'name' => $data['business_name']])->id;
        }
        $data['user_id'] = Contact::query()->whereKey($data['contact_id'])->value('user_id');
        $entrepreneur = Entrepreneur::query()->create($data + ['restaurant_id' => $this->org()]);
        $entrepreneur->groups()->sync($this->groupIds($request));

        return redirect()->route('market.entrepreneurs.show', $entrepreneur)->with('success', 'Emprendedor creado.');
    }

    public function show(Entrepreneur $entrepreneur, ConsignmentService $consignments): View
    {
        $this->authorizeEntrepreneur($entrepreneur);

        return view('market.entrepreneurs.show', [
            'entrepreneur' => $entrepreneur->load(['contact', 'groups']),
            'products' => $entrepreneur->products()->orderBy('name')->get(),
            'consignments' => $entrepreneur->consignments()->latest('received_on')->get()->map(fn ($c) => ['c' => $c, 'b' => $consignments->balance($c)]),
            'documents' => Document::query()->where('restaurant_id', $this->org())->where('entrepreneur_id', $entrepreneur->id)->with('versions')->latest()->get(),
            'canEdit' => $this->can('market.update'),
            'canDocs' => $this->can('documents.create'),
        ]);
    }

    public function edit(Entrepreneur $entrepreneur): View
    {
        $this->ensureOrg($entrepreneur);
        $this->requirePermission('market.update');

        return view('market.entrepreneurs.form', $this->formData($entrepreneur));
    }

    public function update(Request $request, Entrepreneur $entrepreneur): RedirectResponse
    {
        $this->ensureOrg($entrepreneur);
        $this->requirePermission('market.update');
        $data = $this->validated($request);
        if (! empty($data['contact_id'])) {
            $data['user_id'] = Contact::query()->whereKey($data['contact_id'])->value('user_id');
        }
        $entrepreneur->update($data);
        $entrepreneur->groups()->sync($this->groupIds($request));

        return redirect()->route('market.entrepreneurs.show', $entrepreneur)->with('success', 'Emprendedor actualizado.');
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        $this->requirePermission('market.create');
        $data = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('entrepreneur_groups')->where('restaurant_id', $this->org())],
            'description' => ['nullable', 'string', 'max:255']]);
        EntrepreneurGroup::query()->create($data + ['restaurant_id' => $this->org()]);

        return back()->with('success', 'Grupo de emprendedores creado.');
    }

    public function brandManual(Entrepreneur $entrepreneur, DocumentService $documents): RedirectResponse
    {
        $this->ensureOrg($entrepreneur);
        $this->requirePermission('documents.create');
        $version = $documents->generateBrandManual($entrepreneur, $this->user());

        return redirect()->route('documents.show', $version->document_id)->with('success', "Manual de marca generado (versión {$version->version}).");
    }

    private function authorizeEntrepreneur(Entrepreneur $entrepreneur): void
    {
        $this->ensureOrg($entrepreneur);
        $this->requirePermission('market.view');
        $own = $this->ownEntrepreneurId();
        abort_if($own !== null && $own !== $entrepreneur->id, 403, 'Sólo ves tu propio emprendimiento.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'],
            'contact_id' => ['nullable', 'integer', Rule::exists('contacts', 'id')->where('restaurant_id', $this->org())],
            'payment_alias' => ['nullable', 'string', 'max:60'], 'is_active' => ['nullable', 'boolean'],
            'brand_tagline' => ['nullable', 'string', 'max:255'], 'brand_colors' => ['nullable', 'string', 'max:255'],
            'brand_font_primary' => ['nullable', 'string', 'max:80'], 'brand_font_secondary' => ['nullable', 'string', 'max:80'],
            'brand_values' => ['nullable', 'string', 'max:2000'], 'brand_usage' => ['nullable', 'string', 'max:3000'],
        ]);
        $colors = collect(preg_split('/[\s,;]+/', (string) ($data['brand_colors'] ?? '')) ?: [])
            ->map(fn ($c) => '#'.ltrim(trim($c), '#'))->filter(fn ($c) => preg_match('/^#[0-9a-fA-F]{6}$/', $c))->values()->all();

        return [
            'business_name' => $data['business_name'], 'description' => $data['description'] ?? null,
            'contact_id' => $data['contact_id'] ?? null, 'payment_alias' => $data['payment_alias'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'brand' => ['tagline' => $data['brand_tagline'] ?? null, 'colors' => $colors, 'font_primary' => $data['brand_font_primary'] ?? null,
                'font_secondary' => $data['brand_font_secondary'] ?? null, 'values' => $data['brand_values'] ?? null, 'usage' => $data['brand_usage'] ?? null],
        ];
    }

    private function groupIds(Request $request): array
    {
        return EntrepreneurGroup::query()->where('restaurant_id', $this->org())->whereIn('id', (array) $request->input('group_ids', []))->pluck('id')->all();
    }

    private function formData(Entrepreneur $entrepreneur): array
    {
        return [
            'entrepreneur' => $entrepreneur,
            'contacts' => Contact::query()->where('restaurant_id', $this->org())->where('is_active', true)->orderByRaw("kind = 'EMPRENDEDOR' DESC")->orderBy('name')->get(['id', 'name', 'kind']),
            'groups' => EntrepreneurGroup::query()->where('restaurant_id', $this->org())->orderBy('name')->get(),
        ];
    }
}
