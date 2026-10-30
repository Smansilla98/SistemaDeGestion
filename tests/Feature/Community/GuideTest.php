<?php

use App\Models\GuideSection;
use App\Support\GuideDefaults;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo_entry' => false]);
    $this->org = communityOrg();
    $this->admin = orgUser($this->org);
});

it('la gestión carga la guía base una sola vez y la puede editar', function () {
    $this->actingAs($this->admin)->get(route('guide.index'))->assertOk()->assertSee('Cargar la guía base');
    $this->post(route('guide.install'))->assertRedirect(route('guide.index'));
    $total = count(GuideDefaults::sections('Patuka'));
    expect(GuideSection::query()->count())->toBe($total);
    $this->post(route('guide.install'))->assertSessionHas('success', 'La guía ya tenía todas las secciones base.');
    expect(GuideSection::query()->count())->toBe($total);

    $this->get(route('guide.index'))->assertOk()->assertSee('Qué publicar: los pilares')
        ->assertSee('<table>', false)->assertSee('Plantilla sugerida');

    $s = GuideSection::query()->where('slug', 'como-escribir')->firstOrFail();
    $this->put(route('guide.update', $s), ['title' => 'Cómo escribimos', 'audience' => 'TODOS', 'body' => "Hola <script>alert(1)</script>\n\n**Claro**", 'icon' => ''])
        ->assertRedirect();
    expect($s->fresh()->icon)->toBe('bi-megaphone');
    $this->get(route('guide.index'))->assertSee('Cómo escribimos')->assertDontSee('<script>alert(1)</script>', false)->assertSee('<strong>Claro</strong>', false);

    $first = GuideSection::query()->orderBy('position')->first();
    $second = GuideSection::query()->orderBy('position')->skip(1)->first();
    $this->post(route('guide.move', $second), ['dir' => 'up']);
    expect(GuideSection::query()->orderBy('position')->first()->id)->toBe($second->id);
    $this->delete(route('guide.destroy', $first))->assertRedirect();
    expect(GuideSection::query()->count())->toBe($total - 1);
});

it('cada rol ve las secciones de su público y no puede editar', function () {
    GuideDefaults::install($this->org);
    $emp = orgUser($this->org, 'EMPRENDEDOR', ['username' => 'huerta']);
    $this->actingAs($emp)->get(route('guide.index'))->assertOk()
        ->assertSee('Para emprendedores de la feria')->assertDontSee('Para profes y artistas')->assertDontSee('Cuándo publicar: semana tipo')
        ->assertDontSee('Nueva sección');
    $this->post(route('guide.install'))->assertForbidden();
    $this->get(route('guide.edit', GuideSection::query()->first()))->assertForbidden();

    $this->actingAs(orgUser($this->org, 'PROFESOR', ['username' => 'profe']))->get(route('guide.index'))
        ->assertSee('Para profes y artistas')->assertDontSee('Para emprendedores de la feria');

    $this->actingAs(orgUser($this->org, 'MARKETING', ['username' => 'mkt']))->get(route('guide.index'))->assertSee('Nueva sección');
});

it('no se edita la guía de otra organización', function () {
    $other = communityOrg();
    GuideDefaults::install($other);
    $this->actingAs($this->admin)->get(route('guide.edit', GuideSection::withoutGlobalScopes()->where('restaurant_id', $other->id)->first()))->assertNotFound();
});

it('patuka:setup deja la guía cargada', function () {
    $this->artisan('patuka:setup', ['--org' => $this->org->id])->assertSuccessful();
    expect(GuideSection::withoutGlobalScopes()->where('restaurant_id', $this->org->id)->count())->toBe(count(GuideDefaults::sections('Patuka')));
});
