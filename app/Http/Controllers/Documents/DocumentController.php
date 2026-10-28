<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Entrepreneur;
use App\Services\Documents\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    use OrganizationContext;

    public function __construct(private readonly DocumentService $service) {}

    public function index(Request $request): View
    {
        $this->requirePermission('documents.view');
        $q = Document::query()->where('restaurant_id', $this->org())->with(['entrepreneur', 'versions'])->latest('updated_at');
        if (! $this->service->canManage($this->user())) {
            $own = Entrepreneur::query()->where('restaurant_id', $this->org())->where('user_id', $this->user()->id)->pluck('id');
            $q->where(fn ($w) => $w->where(fn ($p) => $p->where('status', 'PUBLICADO')->where('visibility', 'MIEMBROS'))
                ->orWhereIn('entrepreneur_id', $own));
        }
        if ($request->filled('category')) {
            $q->where('category', $request->string('category')->toString());
        }

        return view('documents.index', ['documents' => $q->paginate(30)->withQueryString(), 'canManage' => $this->service->canManage($this->user()),
            'canCreate' => $this->can('documents.create')]);
    }

    public function create(): View
    {
        $this->requirePermission('documents.create');

        return view('documents.create', ['entrepreneurs' => Entrepreneur::query()->where('restaurant_id', $this->org())->orderBy('business_name')->get(['id', 'business_name'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requirePermission('documents.create');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', Rule::in(array_keys(Document::CATEGORIES))], 'visibility' => ['required', Rule::in(array_keys(Document::VISIBILITIES))],
            'entrepreneur_id' => ['nullable', 'integer', Rule::exists('entrepreneurs', 'id')->where('restaurant_id', $this->org())],
            'file' => ['required', 'file', 'max:'.DocumentService::MAX_KB, 'mimes:'.DocumentService::MIMES],
            'publish' => ['nullable', 'boolean'],
        ]);
        $document = Document::query()->create(collect($data)->except(['file', 'publish'])->all() + [
            'restaurant_id' => $this->org(), 'status' => 'BORRADOR', 'created_by' => $this->user()->id,
        ]);
        $this->service->addVersion($document, $request->file('file'), $this->user());
        if ($request->boolean('publish')) {
            $this->service->publish($document);
        }

        return redirect()->route('documents.show', $document)->with('success', 'Documento guardado.');
    }

    public function show(Document $document): View
    {
        $this->ensureOrg($document);
        abort_unless($this->service->canView($this->user(), $document), 403);

        return view('documents.show', ['document' => $document->load(['entrepreneur', 'versions.uploader']), 'canManage' => $this->service->canManage($this->user())]);
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $this->ensureOrg($document);
        abort_unless($this->service->canManage($this->user()), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', Rule::in(array_keys(Document::CATEGORIES))], 'visibility' => ['required', Rule::in(array_keys(Document::VISIBILITIES))],
            'status' => ['required', Rule::in(['BORRADOR', 'PUBLICADO', 'ARCHIVADO'])],
        ]);
        $publish = $data['status'] === 'PUBLICADO' && $document->status !== 'PUBLICADO';
        $document->update(collect($data)->except('status')->all() + ($publish ? [] : ['status' => $data['status']]));
        $sent = $publish ? $this->service->publish($document->fresh()) : 0;

        return back()->with('success', 'Documento actualizado.'.($sent ? " Se avisó a {$sent} personas." : ''));
    }

    public function storeVersion(Request $request, Document $document): RedirectResponse
    {
        $this->ensureOrg($document);
        abort_unless($this->service->canManage($this->user()), 403);
        $request->validate(['file' => ['required', 'file', 'max:'.DocumentService::MAX_KB, 'mimes:'.DocumentService::MIMES], 'note' => ['nullable', 'string', 'max:255']]);
        $v = $this->service->addVersion($document, $request->file('file'), $this->user(), $request->input('note'));

        return back()->with('success', "Versión {$v->version} subida.");
    }

    public function download(Document $document, DocumentVersion $version): StreamedResponse
    {
        $this->ensureOrg($document);
        abort_unless($version->document_id === $document->id && $this->service->canView($this->user(), $document), 404);
        abort_unless(Storage::disk(DocumentService::DISK)->exists($version->path), 404, 'El archivo ya no está disponible.');

        return Storage::disk(DocumentService::DISK)->download($version->path, $version->original_name);
    }
}
