<?php

declare(strict_types=1);

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\GuideSection;
use App\Models\Restaurant;
use App\Support\GuideDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Guía de comunicación: manual interno de qué publicar, cómo, cuándo y con qué formato.
 * La ve cualquier miembro (según su público); la edita quien tiene guide.update.
 */
class GuideController extends Controller
{
    use OrganizationContext;

    public function index(Request $request): View
    {
        $canEdit = $this->can('guide.update');
        $audiences = $canEdit ? array_keys(GuideSection::AUDIENCES) : GuideSection::audiencesFor((string) $this->user()->role);
        $filter = $request->string('para')->toString();

        $sections = GuideSection::query()->where('restaurant_id', $this->org())->whereIn('audience', $audiences)
            ->when($filter !== '' && isset(GuideSection::AUDIENCES[$filter]), fn ($q) => $q->where('audience', $filter))
            ->orderBy('position')->orderBy('id')->get();

        return view('guide.index', [
            'sections' => $sections,
            'canEdit' => $canEdit,
            'canDesign' => $this->can('design.view'),
            'audiences' => array_intersect_key(GuideSection::AUDIENCES, array_flip($audiences)),
            'filter' => $filter,
            'templates' => collect(json_decode((string) file_get_contents(database_path('data/design-templates.json')), true) ?: [])->pluck('name', 'id'),
            'html' => fn (GuideSection $s) => Str::markdown($s->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]),
        ]);
    }

    public function install(): RedirectResponse
    {
        $this->requirePermission('guide.update');
        $added = GuideDefaults::install(Restaurant::query()->findOrFail($this->org()), $this->user()->id);

        return redirect()->route('guide.index')->with('success', $added
            ? "Se agregaron {$added} secciones de la guía base. Editalas a gusto del espacio."
            : 'La guía ya tenía todas las secciones base.');
    }

    public function create(): View
    {
        $this->requirePermission('guide.update');

        return view('guide.form', ['section' => new GuideSection(['audience' => 'TODOS', 'icon' => 'bi-megaphone'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requirePermission('guide.update');
        $data = $this->validated($request);
        $section = GuideSection::query()->create($data + [
            'restaurant_id' => $this->org(), 'updated_by' => $this->user()->id,
            'slug' => Str::limit(Str::slug($data['title']), 60, '').'-'.Str::lower(Str::random(5)),
            'position' => (int) GuideSection::query()->where('restaurant_id', $this->org())->max('position') + 1,
        ]);

        return redirect()->route('guide.index')->withFragment($section->slug)->with('success', 'Sección agregada.');
    }

    public function edit(GuideSection $section): View
    {
        $this->requirePermission('guide.update');
        $this->ensureOrg($section);

        return view('guide.form', ['section' => $section]);
    }

    public function update(Request $request, GuideSection $section): RedirectResponse
    {
        $this->requirePermission('guide.update');
        $this->ensureOrg($section);
        $section->update($this->validated($request) + ['updated_by' => $this->user()->id]);

        return redirect()->route('guide.index')->withFragment($section->slug)->with('success', 'Sección guardada.');
    }

    public function destroy(GuideSection $section): RedirectResponse
    {
        $this->requirePermission('guide.update');
        $this->ensureOrg($section);
        $section->delete();

        return redirect()->route('guide.index')->with('success', 'Sección eliminada.');
    }

    public function move(Request $request, GuideSection $section): RedirectResponse
    {
        $this->requirePermission('guide.update');
        $this->ensureOrg($section);
        $up = $request->input('dir') === 'up';
        $all = GuideSection::query()->where('restaurant_id', $this->org())->orderBy('position')->orderBy('id')->get()->values();
        $i = $all->search(fn (GuideSection $s) => $s->id === $section->id);
        $j = $up ? $i - 1 : $i + 1;
        if ($i !== false && isset($all[$j])) {
            $ordered = $all->all();
            [$ordered[$i], $ordered[$j]] = [$ordered[$j], $ordered[$i]];
            foreach ($ordered as $pos => $s) {
                $s->update(['position' => $pos]);
            }
        }

        return redirect()->route('guide.index')->withFragment($section->slug);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:300'],
            'audience' => ['required', Rule::in(array_keys(GuideSection::AUDIENCES))],
            'icon' => ['nullable', 'string', 'max:40', 'regex:/^bi-[a-z0-9-]+$/'],
            'body' => ['required', 'string', 'max:20000'],
            'template_id' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/'],
        ], [], ['title' => 'título', 'body' => 'contenido', 'audience' => 'público']);
        $data['icon'] = ($data['icon'] ?? null) ?: 'bi-megaphone';

        return $data;
    }
}
