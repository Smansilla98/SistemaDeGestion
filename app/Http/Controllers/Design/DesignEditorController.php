<?php

declare(strict_types=1);

namespace App\Http\Controllers\Design;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\DesignAsset;
use App\Models\DesignPage;
use App\Models\Document;
use App\Models\Entrepreneur;
use App\Models\Restaurant;
use App\Support\Branding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Editor gráfico con OpenDesign (resources/opendesign, MIT): la API que espera el editor,
 * sobre `designs`, `design_pages` y `design_assets`, separada por organización.
 * Cada persona edita sus diseños; quien tiene design.kit ve y edita todos y maneja el kit de marca.
 */
class DesignEditorController extends Controller
{
    use OrganizationContext;

    public const DISK = 'local';

    /** Tamaños con nombre (para reconocer el formato en listados). */
    private const FORMATS = [
        '1080x1350' => 'flyer_feed', '1080x1920' => 'historia', '1080x1080' => 'post_cuadrado',
        '1240x1754' => 'afiche_a4', '874x1240' => 'flyer_a5', '1200x628' => 'banner_web',
    ];

    private const MAX_CANVAS = 5 * 1024 * 1024;

    /** SPA del editor: /diseno y /diseno/design/{id}. */
    public function app(): View
    {
        $this->requirePermission('design.view');

        return view('design.editor', ['config' => [
            'basePath' => '/diseno',
            'apiBase' => url('/diseno/api'),
            'panelUrl' => route('dashboard'),
            'logoUrl' => Branding::logoUrl($this->settings(), true) ?? asset(config('app.brand.icon', 'brand/patuka-icon.png')),
            'brandName' => Branding::name(),
            'csrfToken' => csrf_token(),
            'puedeGestionarKit' => $this->can('design.kit'),
        ]]);
    }

    // ── Diseños ────────────────────────────────────────────────────────

    public function index(): JsonResponse
    {
        $this->requirePermission('design.view');
        $q = Design::query()->where('restaurant_id', $this->org())->latest('updated_at');
        if (! $this->can('design.kit')) {
            $q->where('user_id', $this->user()->id);
        }

        return response()->json($q->limit(200)->get()->map->forEditor()->values());
    }

    public function store(Request $request): JsonResponse
    {
        $this->requirePermission('design.view');
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'canvas_json' => 'nullable|string|max:'.self::MAX_CANVAS,
            'width' => 'nullable|integer|min:50|max:10000',
            'height' => 'nullable|integer|min:50|max:10000',
        ]);
        $canvas = $this->validCanvas($data['canvas_json'] ?? null);
        $width = (int) ($data['width'] ?? 1080);
        $height = (int) ($data['height'] ?? 1350);

        $design = DB::transaction(function () use ($data, $canvas, $width, $height) {
            $design = Design::query()->create([
                'restaurant_id' => $this->org(), 'user_id' => $this->user()->id,
                'title' => $data['name'] ?? 'Diseño sin título', 'format' => self::FORMATS[$width.'x'.$height] ?? 'custom',
                'width' => $width, 'height' => $height,
            ]);
            $design->pages()->create(['title' => 'Página 1', 'canvas_json' => $canvas, 'position' => 0]);

            return $design;
        });

        return response()->json($design->forEditor());
    }

    public function show(Design $design): JsonResponse
    {
        $this->authorizeDesign($design);

        return response()->json($design->forEditor() + ['pages' => $design->pages()->get()->map->forEditor()->values()]);
    }

    public function update(Request $request, Design $design): JsonResponse
    {
        $this->authorizeDesign($design);
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'canvas_json' => 'nullable|string|max:'.self::MAX_CANVAS,
            'width' => 'nullable|integer|min:50|max:10000',
            'height' => 'nullable|integer|min:50|max:10000',
            'thumbnail_data' => 'nullable|string|max:2000000',
        ]);
        if (filled($data['name'] ?? null)) {
            $design->title = $data['name'];
        }
        if (! empty($data['width']) && ! empty($data['height'])) {
            $design->fill(['width' => (int) $data['width'], 'height' => (int) $data['height'],
                'format' => self::FORMATS[$data['width'].'x'.$data['height']] ?? 'custom']);
        }
        if (! empty($data['thumbnail_data'])) {
            $design->preview_path = $this->storeThumbnail($design, $data['thumbnail_data']) ?? $design->preview_path;
        }
        $design->save();
        $design->touch();

        return response()->json($design->fresh()?->forEditor());
    }

    public function destroy(Design $design): JsonResponse
    {
        $this->authorizeDesign($design);
        if ($design->preview_path) {
            Storage::disk(self::DISK)->delete($design->preview_path);
        }
        $design->delete();

        return response()->json(['ok' => true]);
    }

    // ── Páginas ────────────────────────────────────────────────────────

    public function storePage(Request $request, Design $design): JsonResponse
    {
        $this->authorizeDesign($design);
        $data = $request->validate([
            'title' => 'nullable|string|max:120',
            'canvas_json' => 'nullable|string|max:'.self::MAX_CANVAS,
            'after_sort_order' => 'nullable|integer|min:0',
        ]);
        $canvas = $this->validCanvas($data['canvas_json'] ?? null);

        $page = DB::transaction(function () use ($design, $data, $canvas) {
            if (isset($data['after_sort_order'])) {
                $design->pages()->where('position', '>', $data['after_sort_order'])->increment('position');
                $position = $data['after_sort_order'] + 1;
            } else {
                $position = (int) ($design->pages()->max('position') ?? -1) + 1;
            }

            return $design->pages()->create(['title' => $data['title'] ?? 'Página '.($design->pages()->count() + 1),
                'canvas_json' => $canvas, 'position' => $position]);
        });

        return response()->json($page->forEditor());
    }

    public function duplicatePage(DesignPage $page): JsonResponse
    {
        $this->authorizeDesign($page->design);
        $copy = DB::transaction(function () use ($page) {
            DesignPage::query()->where('design_id', $page->design_id)->where('position', '>', $page->position)->increment('position');

            return DesignPage::query()->create(['design_id' => $page->design_id, 'title' => Str::limit($page->title.' (copia)', 120, ''),
                'canvas_json' => $page->canvas_json, 'position' => $page->position + 1]);
        });

        return response()->json($copy->forEditor());
    }

    public function updatePage(Request $request, DesignPage $page): JsonResponse
    {
        $this->authorizeDesign($page->design);
        $data = $request->validate(['title' => 'nullable|string|max:120', 'canvas_json' => 'nullable|string|max:'.self::MAX_CANVAS]);
        if (isset($data['canvas_json'])) {
            $page->canvas_json = $this->validCanvas($data['canvas_json']);
        }
        if (filled($data['title'] ?? null)) {
            $page->title = $data['title'];
        }
        $page->save();
        $page->design->touch();

        return response()->json($page->forEditor());
    }

    public function destroyPage(DesignPage $page): JsonResponse
    {
        $this->authorizeDesign($page->design);
        if (DesignPage::query()->where('design_id', $page->design_id)->count() <= 1) {
            return response()->json(['error' => 'No se puede eliminar la única página.'], 400);
        }
        $page->delete();

        return response()->json(['ok' => true]);
    }

    // ── Plantillas ─────────────────────────────────────────────────────

    public function templates(): JsonResponse
    {
        $this->requirePermission('design.view');

        return response()->json($this->templateList()->values());
    }

    public function template(string $id): JsonResponse
    {
        $this->requirePermission('design.view');
        $template = $this->templateList()->firstWhere('id', $id);

        return $template ? response()->json($template) : response()->json(['error' => 'No existe la plantilla.'], 404);
    }

    /** @return Collection<int, non-empty-array<string, mixed>> Plantillas de database/data/design-templates.json. */
    private function templateList(): Collection
    {
        $logoDark = '/'.ltrim((string) config('app.brand.logo'), '/');
        $logoLight = '/'.ltrim((string) config('app.brand.logo_light'), '/');
        $raw = str_replace(['{{logo_blanco}}', '{{logo_negro}}', '{{nombre}}'],
            [$logoDark, $logoLight, addslashes(mb_strtoupper(Branding::name()))],
            (string) file_get_contents(database_path('data/design-templates.json')));

        /** @var list<array<string, mixed>> $templates */
        $templates = json_decode($raw, true) ?: [];

        return collect($templates)->map(fn (array $t): array => $t + ['thumbnail_url' => null])->sortBy('sort_order');
    }

    // ── Imágenes y marca ───────────────────────────────────────────────

    /** Imagen para el lienzo. SVG no: se sirve desde el mismo dominio. */
    public function upload(Request $request): JsonResponse
    {
        $this->requirePermission('design.view');
        $request->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp,gif|max:10240'], [
            'file.mimes' => 'Formato no admitido. Usá PNG, JPG, WebP o GIF.', 'file.max' => 'La imagen supera los 10 MB.',
        ]);
        $asset = $this->storeAsset($request->file('file'), DesignAsset::UPLOAD, null);

        return response()->json(['url' => $asset->url()]);
    }

    /** Pestaña "Marca": paleta, logos, kit compartido y paletas de los emprendedores. */
    public function brand(): JsonResponse
    {
        $this->requirePermission('design.view');
        $settings = $this->settings();
        $palette = Branding::palette($settings);

        $colors = collect([
            ['nombre' => 'Principal', 'hex' => $palette['primary'], 'uso' => 'Fondos y títulos'],
            ['nombre' => 'Negro', 'hex' => $palette['secondary'], 'uso' => 'Fondos'],
            ['nombre' => 'Acento', 'hex' => $palette['accent'], 'uso' => 'Detalles'],
            ['nombre' => 'Gris claro', 'hex' => '#cccccc', 'uso' => 'Separadores'],
            ['nombre' => 'Blanco', 'hex' => '#ffffff', 'uso' => 'Texto sobre negro'],
        ])->unique('hex')->values();

        $logos = collect([
            ['id' => 'logo-blanco', 'path' => config('app.brand.logo'), 'label' => 'Logo blanco'],
            ['id' => 'logo-negro', 'path' => config('app.brand.logo_light'), 'label' => 'Logo negro'],
            ['id' => 'isotipo', 'path' => config('app.brand.icon'), 'label' => 'Isotipo'],
        ])->filter(fn ($l) => is_string($l['path']) && is_file(public_path($l['path'])))
            ->map(fn ($l) => ['id' => $l['id'], 'url' => '/'.ltrim($l['path'], '/'), 'label' => $l['label']])->values();

        $kit = DesignAsset::query()->where('restaurant_id', $this->org())->where('kind', DesignAsset::KIT)->latest()->limit(100)->get()
            ->map(fn (DesignAsset $a) => ['id' => 'kit-'.$a->id, 'url' => $a->url(), 'label' => $a->title, 'kit_id' => $a->id])->values();

        $mine = DesignAsset::query()->where('restaurant_id', $this->org())->where('kind', DesignAsset::UPLOAD)
            ->where('user_id', $this->user()->id)->latest()->limit(40)->get()
            ->map(fn (DesignAsset $a) => ['id' => 'up-'.$a->id, 'url' => $a->url(), 'label' => $a->title])->values();

        // Emprendedores: cada uno ve su paleta; la gestión ve todas.
        $entrepreneurs = Entrepreneur::query()->where('restaurant_id', $this->org())->where('is_active', true)
            ->when(! $this->can('design.kit'), fn ($q) => $q->where('user_id', $this->user()->id))
            ->orderBy('business_name')->get()
            ->filter(fn (Entrepreneur $e) => count((array) ($e->brand['colors'] ?? [])) > 0)
            ->map(function (Entrepreneur $e) {
                $c = array_values((array) $e->brand['colors']);

                return ['sede' => $e->business_name, 'fondo' => $c[0], 'sombra' => $c[1] ?? $c[0], 'acento' => $c[2] ?? $c[1] ?? $c[0]];
            })->values();

        $manual = Document::query()->where('restaurant_id', $this->org())->where('category', 'MANUAL_MARCA')
            ->whereNull('entrepreneur_id')->where('status', 'PUBLICADO')->latest('updated_at')->first();

        return response()->json([
            'manual' => [
                'version' => $manual ? $manual->title : 'Paleta de '.Branding::name(),
                'url' => $manual ? route('documents.show', $manual) : null,
                'paleta' => $colors,
                'sedes' => $entrepreneurs,
                'sedes_titulo' => 'Paletas de emprendedores',
            ],
            'grupos' => [
                ['clave' => 'logos', 'titulo' => 'Logos', 'items' => $logos],
                ['clave' => 'kit', 'titulo' => 'Kit de marca', 'items' => $kit],
                ['clave' => 'subidas', 'titulo' => 'Mis imágenes', 'items' => $mine],
            ],
        ]);
    }

    public function kitStore(Request $request): JsonResponse
    {
        $this->requirePermission('design.kit');
        $data = $request->validate(['archivo' => 'required|file|mimes:jpg,jpeg,png,webp,gif|max:8192', 'titulo' => 'nullable|string|max:120']);
        $asset = $this->storeAsset($request->file('archivo'), DesignAsset::KIT, $data['titulo'] ?? null);

        return response()->json(['ok' => true, 'id' => $asset->id, 'url' => $asset->url()]);
    }

    public function kitDestroy(DesignAsset $kit): JsonResponse
    {
        $this->requirePermission('design.kit');
        $this->ensureOrg($kit);
        abort_unless($kit->kind === DesignAsset::KIT, 404);
        Storage::disk(self::DISK)->delete($kit->path);
        $kit->delete();

        return response()->json(['ok' => true]);
    }

    /** Sirve imágenes y miniaturas del editor (mismo dominio: el lienzo se puede exportar). */
    public function media(string $path): StreamedResponse
    {
        $this->requirePermission('design.view');
        abort_unless(str_starts_with($path, 'design/'.$this->org().'/') && ! str_contains($path, '..'), 404);
        abort_unless(Storage::disk(self::DISK)->exists($path), 404);

        return Storage::disk(self::DISK)->response($path, null, ['Cache-Control' => 'private, max-age=86400']);
    }

    // ── Auxiliares ─────────────────────────────────────────────────────

    private function authorizeDesign(Design $design): void
    {
        $this->requirePermission('design.view');
        $this->ensureOrg($design);
        abort_unless((int) $design->user_id === (int) $this->user()->id || $this->can('design.kit'), 403, 'Este diseño es de otra persona.');
    }

    private function validCanvas(?string $json): string
    {
        if ($json === null || trim($json) === '') {
            return '{}';
        }
        if (! is_array(json_decode($json, true))) {
            throw ValidationException::withMessages(['canvas_json' => 'El diseño recibido no es válido.']);
        }

        return $json;
    }

    private function storeAsset(UploadedFile $file, string $kind, ?string $title): DesignAsset
    {
        $folder = 'design/'.$this->org().'/'.($kind === DesignAsset::KIT ? 'kit' : 'uploads');
        $path = $file->storeAs($folder, Str::uuid().'.'.($file->extension() ?: 'png'), self::DISK);
        abort_unless(is_string($path), 500, 'No se pudo guardar la imagen.');

        return DesignAsset::query()->create([
            'restaurant_id' => $this->org(), 'user_id' => $this->user()->id, 'kind' => $kind,
            'title' => Str::limit($title ?: (pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Imagen'), 120, ''),
            'path' => $path, 'mime' => (string) $file->getMimeType(), 'bytes' => (int) $file->getSize(),
        ]);
    }

    private function storeThumbnail(Design $design, string $dataUrl): ?string
    {
        if (! preg_match('#^data:image/(jpeg|png);base64,(.+)$#', $dataUrl, $m)) {
            return null;
        }
        $binary = base64_decode($m[2], true);
        if ($binary === false || strlen($binary) > 1_500_000 || @getimagesizefromstring($binary) === false) {
            return null;
        }
        $path = 'design/'.$design->restaurant_id.'/previews/'.$design->id.'.'.($m[1] === 'png' ? 'png' : 'jpg');
        if ($design->preview_path && $design->preview_path !== $path) {
            Storage::disk(self::DISK)->delete($design->preview_path);
        }
        Storage::disk(self::DISK)->put($path, $binary);

        return $path;
    }

    /** @return array<string, mixed>|null */
    private function settings(): ?array
    {
        $settings = Restaurant::query()->find($this->org())?->settings;

        return is_array($settings) ? $settings : null;
    }
}
