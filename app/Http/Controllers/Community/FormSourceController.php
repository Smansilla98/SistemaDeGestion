<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\ActivityCategory;
use App\Models\AgendaNotice;
use App\Models\Contact;
use App\Models\FormSource;
use App\Models\Workshop;
use App\Services\Forms\FormImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Formularios externos (Google Forms / Excel) que alimentan alumnos, talleristas, contactos y agenda.
 */
class FormSourceController extends Controller
{
    use OrganizationContext;

    public function __construct(private readonly FormImportService $service) {}

    public function index(): View
    {
        $this->requirePermission('contacts.view');

        return view('forms.index', [
            'sources' => FormSource::query()->where('restaurant_id', $this->org())->with(['workshop', 'category'])
                ->withCount(['rows as imported_count' => fn ($q) => $q->where('status', 'IMPORTADA'),
                    'rows as error_count' => fn ($q) => $q->where('status', 'ERROR')])->latest()->get(),
            'canManage' => $this->can('contacts.create'),
        ]);
    }

    public function create(): View
    {
        $this->requirePermission('contacts.create');

        return view('forms.form', $this->formData(new FormSource(['target' => request('destino', 'ALUMNOS'), 'is_active' => true, 'auto_sync' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requirePermission('contacts.create');
        $data = $this->validated($request);
        $request->validate(['file' => [empty($data['sheet_url']) ? 'required' : 'nullable', 'file', 'max:10240', 'mimes:csv,txt,xlsx,xls,ods']],
            ['file.required' => 'Pegá el link de la planilla de Google o subí el archivo de respuestas.']);

        $source = FormSource::query()->create($data + ['restaurant_id' => $this->org(), 'created_by' => $this->user()->id]);
        $this->load($source, $request);

        return redirect()->route('forms.show', $source)->with('success', 'Revisá qué columna corresponde a cada dato y tocá «Guardar e importar».');
    }

    public function show(Request $request, FormSource $form): View
    {
        $this->ensureOrg($form);
        $this->requirePermission('contacts.view');
        $dataset = $this->service->dataset($form);
        $status = $request->input('estado');

        return view('forms.show', [
            'source' => $form->load(['workshop', 'category']),
            'preview' => array_slice($dataset['rows'] ?? [], -5),
            'pendingCount' => $dataset ? max(0, count($dataset['rows']) - $form->rows()->whereIn('status', ['IMPORTADA', 'OMITIDA'])->count()) : 0,
            'rows' => $form->rows()->with('contact')->when($status, fn ($q) => $q->where('status', $status))->latest('id')->paginate(30)->withQueryString(),
            'counts' => $form->rows()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status'),
            'fields' => FormSource::FIELDS,
            'required' => FormImportService::requiredFields($form->target),
            'canManage' => $this->can('contacts.create'),
        ]);
    }

    public function edit(FormSource $form): View
    {
        $this->ensureOrg($form);
        $this->requirePermission('contacts.create');

        return view('forms.form', $this->formData($form));
    }

    public function update(Request $request, FormSource $form): RedirectResponse
    {
        $this->ensureOrg($form);
        $this->requirePermission('contacts.create');
        $form->update($this->validated($request));

        return redirect()->route('forms.show', $form)->with('success', 'Formulario actualizado.');
    }

    /** Guarda el mapeo de columnas e importa las respuestas pendientes. */
    public function mapping(Request $request, FormSource $form): RedirectResponse
    {
        $this->ensureOrg($form);
        $this->requirePermission('contacts.create');
        $headers = (array) $form->headers;
        $data = $request->validate(['mapping' => ['array'], 'mapping.*' => ['nullable', 'string', Rule::in($headers)]]);
        $form->update(['mapping' => array_filter(array_intersect_key($data['mapping'] ?? [], FormSource::FIELDS))]);
        $r = $this->service->import($form->fresh());

        return back()->with('success', $this->summary($r));
    }

    public function sync(Request $request, FormSource $form): RedirectResponse
    {
        $this->ensureOrg($form);
        $this->requirePermission('contacts.create');
        if ($request->hasFile('file')) {
            $this->load($form, $request);
            $r = $this->service->import($form->fresh());
        } elseif ($form->sheet_url) {
            $r = $this->service->sync($form);
        } else {
            return back()->with('error', 'Subí el archivo actualizado de respuestas.');
        }

        return back()->with('success', $this->summary($r));
    }

    public function destroy(FormSource $form): RedirectResponse
    {
        $this->ensureOrg($form);
        $this->requirePermission('contacts.create');
        $form->delete(); // los contactos, inscripciones y actividades ya importados se conservan

        return redirect()->route('forms.index')->with('success', 'Formulario quitado. Lo que ya se importó se conserva.');
    }

    private function load(FormSource $source, Request $request): void
    {
        if ($request->hasFile('file')) {
            $request->validate(['file' => ['file', 'max:10240', 'mimes:csv,txt,xlsx,xls,ods']]);
            $file = $request->file('file');
            $table = $this->service->readFile($file->getRealPath(), $file->getClientOriginalExtension() ?: 'csv');
        } else {
            $table = $this->service->fetchSheet((string) $source->sheet_url);
        }
        if ($table['headers'] === []) {
            throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'La planilla está vacía.']);
        }
        $this->service->storeDataset($source, $table);
    }

    private function summary(array $r): string
    {
        return "Importadas: {$r['imported']} · para revisar: {$r['errors']} · omitidas: {$r['skipped']}.";
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'target' => ['required', Rule::in(array_keys(FormSource::TARGETS))],
            'sheet_url' => ['nullable', 'url', 'max:500'],
            'workshop_id' => ['nullable', 'integer', Rule::exists('workshops', 'id')->where('restaurant_id', $this->org())],
            'activity_category_id' => ['nullable', 'integer', Rule::exists('activity_categories', 'id')->where('restaurant_id', $this->org())],
            'contact_kind' => ['nullable', 'string', 'max:20'],
            'tags' => ['nullable', 'string', 'max:255'],
            'auto_sync' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        if (! empty($data['sheet_url'])) {
            FormImportService::csvUrl($data['sheet_url']); // valida que sea una planilla de Google
        }
        $data['auto_sync'] = $request->boolean('auto_sync') && ! empty($data['sheet_url']);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    private function formData(FormSource $source): array
    {
        return [
            'source' => $source,
            'workshops' => Workshop::query()->where('restaurant_id', $this->org())->whereIn('status', ['ABIERTO', 'EN_CURSO'])->orderBy('name')->get(['id', 'name']),
            'categories' => ActivityCategory::query()->where('restaurant_id', $this->org())->orderBy('name')->get(),
            'contactKinds' => Contact::KINDS,
            'noticeKinds' => AgendaNotice::KINDS,
        ];
    }
}
