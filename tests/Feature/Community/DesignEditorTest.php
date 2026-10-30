<?php

use App\Models\Design;
use App\Models\DesignAsset;
use App\Models\Entrepreneur;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo_entry' => false]);
    Storage::fake('local');
    $this->withoutVite();
    $this->org = communityOrg();
    $this->admin = orgUser($this->org);
    $this->profe = orgUser($this->org, 'PROFESOR', ['username' => 'profe']);
});

it('abre el editor con la configuración de la organización', function () {
    $this->actingAs($this->profe)->get(route('design.index'))->assertOk()
        ->assertSee('__OPENDESIGN__', false)->assertSee('"apiBase"', false)->assertSee('"puedeGestionarKit":false', false);
    $this->actingAs($this->admin)->get('/diseno/design/5')->assertOk()->assertSee('"puedeGestionarKit":true', false);
});

it('crea, edita páginas y guarda un diseño propio; otro usuario no lo ve', function () {
    $this->actingAs($this->profe);
    $d = $this->postJson(route('design.api.store'), ['name' => 'Flyer percusión', 'width' => 1080, 'height' => 1350])->assertOk()->json();
    $design = Design::query()->findOrFail($d['id']);
    expect($design->format)->toBe('flyer_feed')->and($design->restaurant_id)->toBe($this->org->id);

    $show = $this->getJson(route('design.api.show', $design))->assertOk()->json();
    $pageId = $show['pages'][0]['id'];
    $this->putJson(route('design.api.pages.update', $pageId), ['canvas_json' => '{"objects":[]}'])->assertOk();
    $this->putJson(route('design.api.pages.update', $pageId), ['canvas_json' => 'no-json'])->assertStatus(422);
    $this->postJson(route('design.api.pages.duplicate', $pageId))->assertOk();
    expect($design->pages()->count())->toBe(2);

    $png = 'data:image/png;base64,'.base64_encode(UploadedFile::fake()->image('t.png', 20, 20)->getContent());
    $this->putJson(route('design.api.update', $design), ['name' => 'Flyer final', 'thumbnail_data' => $png])->assertOk()->assertJsonPath('name', 'Flyer final');
    expect($design->fresh()->preview_path)->not->toBeNull();
    $this->get($design->fresh()->forEditor()['thumbnail_url'])->assertOk();

    $otro = orgUser($this->org, 'ARTISTA', ['username' => 'artista']);
    $this->actingAs($otro)->getJson(route('design.api.index'))->assertOk()->assertJsonCount(0);
    $this->actingAs($otro)->getJson(route('design.api.show', $design))->assertForbidden();
    $this->actingAs($this->admin)->getJson(route('design.api.index'))->assertJsonCount(1);
});

it('no deja ver diseños ni imágenes de otra organización', function () {
    $other = communityOrg();
    $ajeno = Design::query()->create(['restaurant_id' => $other->id, 'title' => 'Ajeno', 'width' => 100, 'height' => 100]);
    Storage::disk('local')->put("design/{$other->id}/uploads/x.png", 'x');

    $this->actingAs($this->admin)->getJson(route('design.api.show', $ajeno))->assertNotFound();
    $this->get(route('design.media', ['path' => "design/{$other->id}/uploads/x.png"]))->assertNotFound();
});

it('marca: paleta, logos, kit compartido sólo para gestión y paleta del emprendedor', function () {
    $emp = orgUser($this->org, 'EMPRENDEDOR', ['username' => 'huerta']);
    Entrepreneur::query()->create(['restaurant_id' => $this->org->id, 'business_name' => 'Huerta', 'user_id' => $emp->id,
        'brand' => ['colors' => ['#2E7D32', '#F9A825', '#5D4037']]]);
    Entrepreneur::query()->create(['restaurant_id' => $this->org->id, 'business_name' => 'Otra marca', 'brand' => ['colors' => ['#123456']]]);

    $this->actingAs($emp)->postJson(route('design.api.kit.store'), ['archivo' => UploadedFile::fake()->image('logo.png')])->assertForbidden();
    $this->postJson(route('design.api.uploads'), ['file' => UploadedFile::fake()->image('foto.jpg')])->assertOk()->assertJsonStructure(['url']);
    $brand = $this->getJson(route('design.api.brand'))->assertOk()->json();
    expect(collect($brand['manual']['sedes'])->pluck('sede')->all())->toBe(['Huerta'])
        ->and($brand['manual']['paleta'][0]['hex'])->toBe('#111111')
        ->and(collect($brand['grupos'])->firstWhere('clave', 'subidas')['items'])->toHaveCount(1);

    $this->actingAs($this->admin)->postJson(route('design.api.kit.store'), ['archivo' => UploadedFile::fake()->image('logo.png')])->assertOk();
    $kit = DesignAsset::query()->where('kind', 'KIT')->firstOrFail();
    expect(collect($this->getJson(route('design.api.brand'))->json('manual.sedes'))->pluck('sede')->all())->toBe(['Huerta', 'Otra marca']);
    $this->deleteJson(route('design.api.kit.destroy', $kit))->assertOk();
    expect(DesignAsset::query()->count())->toBe(1);
});

it('plantillas de Patuka con el logo de la organización', function () {
    $list = $this->actingAs($this->profe)->getJson(route('design.api.templates'))->assertOk()->json();
    expect(collect($list)->pluck('id'))->toContain('ptk-taller')
        ->and($list[0]['canvas_json'])->toContain('/brand/patuka-logo-blanco.png')->not->toContain('{{logo');
    $this->getJson(route('design.api.template', 'ptk-show'))->assertOk()->assertJsonPath('height', 1920);
    $this->getJson(route('design.api.template', 'nada'))->assertNotFound();
});

it('roles sin el permiso no entran al editor', function () {
    $this->actingAs(orgUser($this->org, 'MOZO', ['username' => 'mozo']))->get(route('design.index'))->assertForbidden();
});
