<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\ContactTag;
use App\Models\Workshop;
use App\Services\Community\ContactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    use OrganizationContext;

    public function __construct(private readonly ContactService $service) {}

    public function index(Request $request): View
    {
        $this->requirePermission('contacts.view');
        $filters = $request->only(['q', 'kind', 'tag_id', 'group_id', 'workshop_id', 'marketing']);

        return view('contacts.index', [
            'contacts' => $this->service->query($this->org(), $filters)->with(['tags', 'groups'])->orderBy('name')->paginate(30)->withQueryString(),
            'filters' => $filters,
            'tags' => ContactTag::query()->where('restaurant_id', $this->org())->orderBy('name')->get(),
            'groups' => ContactGroup::query()->where('restaurant_id', $this->org())->withCount('contacts')->orderBy('name')->get(),
            'workshops' => Workshop::query()->where('restaurant_id', $this->org())->orderBy('name')->get(['id', 'name']),
            'can' => ['create' => $this->can('contacts.create'), 'update' => $this->can('contacts.update'),
                'export' => $this->can('contacts.export'), 'campaign' => $this->can('contacts.campaign')],
        ]);
    }

    public function create(): View
    {
        $this->requirePermission('contacts.create');

        return view('contacts.form', $this->formData(new Contact(['kind' => 'PUBLICO', 'is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requirePermission('contacts.create');
        $contact = Contact::query()->create($this->validated($request) + ['restaurant_id' => $this->org()]);
        $this->syncRelations($contact, $request);

        return redirect()->route('contacts.index')->with('success', "Contacto «{$contact->name}» agregado.");
    }

    public function edit(Contact $contact): View
    {
        $this->ensureOrg($contact);
        $this->requirePermission('contacts.update');

        return view('contacts.form', $this->formData($contact));
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $this->ensureOrg($contact);
        $this->requirePermission('contacts.update');
        $contact->update($this->validated($request, $contact));
        $this->syncRelations($contact, $request);

        return redirect()->route('contacts.index')->with('success', 'Contacto actualizado.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->ensureOrg($contact);
        $this->requirePermission('contacts.delete');
        // Se desactiva: inscripciones, asistencias y pedidos conservan a quién pertenecían.
        $contact->update(['is_active' => false, 'accepts_marketing' => false]);

        return back()->with('success', "«{$contact->name}» quedó inactivo.");
    }

    public function importForm(): View
    {
        $this->requirePermission('contacts.create');

        return view('contacts.import', ['columns' => ContactService::CSV_COLUMNS]);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->requirePermission('contacts.create');
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $r = $this->service->import($this->org(), $request->file('file')->getRealPath());

        return redirect()->route('contacts.index')
            ->with('success', "Importación: {$r['created']} nuevos, {$r['updated']} actualizados, {$r['skipped']} salteados.")
            ->with('import_errors', array_slice($r['errors'], 0, 20));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->requirePermission('contacts.export');
        $rows = $this->service->exportRows($this->service->query($this->org(), $request->only(['q', 'kind', 'tag_id', 'group_id', 'workshop_id', 'marketing'])));

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'contactos-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ── Grupos ────────────────────────────────────────────────────────────────

    public function storeGroup(Request $request): RedirectResponse
    {
        $this->requirePermission('contacts.update');
        $data = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('contact_groups')->where('restaurant_id', $this->org())],
            'description' => ['nullable', 'string', 'max:255']]);
        ContactGroup::query()->create($data + ['restaurant_id' => $this->org()]);

        return back()->with('success', 'Grupo creado.');
    }

    public function addToGroup(Request $request): RedirectResponse
    {
        $this->requirePermission('contacts.update');
        $data = $request->validate(['group_id' => ['required', 'integer'], 'contact_ids' => ['required', 'array'], 'contact_ids.*' => ['integer']]);
        $group = ContactGroup::query()->where('restaurant_id', $this->org())->findOrFail($data['group_id']);
        $ids = Contact::query()->where('restaurant_id', $this->org())->whereIn('id', $data['contact_ids'])->pluck('id');
        $group->contacts()->syncWithoutDetaching($ids->all());

        return back()->with('success', $ids->count().' contactos agregados a «'.$group->name.'».');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validated(Request $request, ?Contact $contact = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'kind' => ['required', Rule::in(array_keys(Contact::KINDS))],
            'alias' => ['nullable', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:40'],
            'document' => ['nullable', 'string', 'max:30'], 'birth_date' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:5000'],
            'accepts_marketing' => ['nullable', 'boolean'], 'is_active' => ['nullable', 'boolean'],
        ]);
        $data['accepts_marketing'] = $request->boolean('accepts_marketing');
        $data['is_active'] = $request->boolean('is_active', true);
        if ($data['accepts_marketing'] && ! $contact?->accepts_marketing) {
            $data['marketing_consent_at'] = now();
        }

        return $data;
    }

    private function syncRelations(Contact $contact, Request $request): void
    {
        $names = collect(preg_split('/[,|]/', (string) $request->input('tags', '')) ?: [])->map(fn ($t) => trim($t))->filter()->unique();
        $contact->tags()->sync($names->map(fn ($n) => ContactTag::query()->firstOrCreate(['restaurant_id' => $this->org(), 'name' => mb_substr($n, 0, 60)])->id)->all());
        $contact->groups()->sync(ContactGroup::query()->where('restaurant_id', $this->org())->whereIn('id', (array) $request->input('group_ids', []))->pluck('id')->all());
    }

    private function formData(Contact $contact): array
    {
        return ['contact' => $contact, 'groups' => ContactGroup::query()->where('restaurant_id', $this->org())->orderBy('name')->get(),
            'tagString' => $contact->exists ? $contact->tags->pluck('name')->implode(', ') : ''];
    }
}
