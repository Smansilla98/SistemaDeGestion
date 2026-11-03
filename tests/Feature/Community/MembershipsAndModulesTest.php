<?php

use App\Models\Contact;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Community\MembershipService;
use App\Support\BranchAccess;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo_entry' => false]);
});

it('una cuenta participa en otra organización con otro rol sin crear otra cuenta', function () {
    $resto = communityOrg(false, 'RESTAURANTE');
    $centro = communityOrg();
    $mozo = orgUser($resto, 'MOZO', ['username' => 'lucia']);
    $admin = orgUser($centro);

    app(MembershipService::class)->add($centro->id, 'lucia', User::ROLE_PROFESOR, $admin);

    $branches = app(BranchAccess::class)->branchesFor($mozo->fresh());
    expect($branches->pluck('id')->all())->toBe([$resto->id, $centro->id]);

    // Se crea (o vincula) su ficha de contacto en el centro, sin duplicar el usuario.
    expect(Contact::withoutGlobalScopes()->where('restaurant_id', $centro->id)->where('user_id', $mozo->id)->value('kind'))->toBe('PROFESOR');

    $u = $mozo->fresh()->useRestaurant($centro->id);
    expect($u->role)->toBe('PROFESOR')->and($u->homeRole())->toBe('MOZO');
    expect($mozo->fresh()->useRestaurant($resto->id)->role)->toBe('MOZO');
    expect(User::query()->find($mozo->id)->getRawOriginal('role'))->toBe('MOZO');
});

it('al elegir la organización el profesor ve sólo sus talleres y no los módulos del restaurante', function () {
    $resto = communityOrg(false, 'RESTAURANTE');
    $centro = communityOrg();
    $mozo = orgUser($resto, 'MOZO', ['username' => 'lucia']);
    app(MembershipService::class)->add($centro->id, 'lucia', User::ROLE_PROFESOR, orgUser($centro));
    $mine = Workshop::query()->create(['restaurant_id' => $centro->id, 'name' => 'Percusión', 'status' => 'ABIERTO',
        'teacher_contact_id' => Contact::withoutGlobalScopes()->where('user_id', $mozo->id)->value('id')]);
    $other = Workshop::query()->create(['restaurant_id' => $centro->id, 'name' => 'Teatro', 'status' => 'ABIERTO']);

    $this->actingAs($mozo)->post(route('branch.store'), ['restaurant_id' => $centro->id])->assertRedirect();

    $this->get(route('workshops.index'))->assertOk()->assertSee('Percusión')->assertDontSee('Teatro');
    $this->get(route('workshops.show', $other))->assertForbidden();
    $this->get(route('workshops.show', $mine))->assertOk();
    $this->get(route('contacts.index'))->assertForbidden();
});

it('los módulos nuevos vienen apagados para las organizaciones existentes', function () {
    $resto = communityOrg(false, 'RESTAURANTE');
    $admin = orgUser($resto);

    $this->actingAs($admin)->get(route('workshops.index'))->assertRedirect(route('dashboard'));
    $this->get(route('market.products.index'))->assertRedirect(route('dashboard'));
    $this->get(route('dashboard'))->assertOk()->assertDontSee('Talleres</span>', false);
    // Lo existente sigue igual.
    $this->get(route('tables.index'))->assertOk();
});

it('las páginas de todos los módulos cargan para un admin con los módulos habilitados', function () {
    $org = communityOrg();
    $this->actingAs(orgUser($org));

    foreach (['organization.show', 'workshops.index', 'workshops.create', 'agenda.index', 'contacts.index', 'contacts.create', 'contacts.import',
        'campaigns.index', 'campaigns.create', 'messages.index', 'messages.create', 'notifications.preferences',
        'market.entrepreneurs.index', 'market.entrepreneurs.create', 'market.products.index', 'market.consignments.index',
        'market.consignments.create', 'market.cycles.index', 'documents.index', 'documents.create', 'finance.index'] as $route) {
        $this->get(route($route))->assertOk();
    }
});

it('un admin no accede a registros de otra organización', function () {
    $a = communityOrg();
    $b = communityOrg();
    $ws = Workshop::query()->create(['restaurant_id' => $a->id, 'name' => 'Ajeno', 'status' => 'ABIERTO']);

    $this->actingAs(orgUser($b))->get(route('workshops.show', $ws))->assertNotFound();
    $this->get(route('finance.activity', ['workshop', $ws->id]))->assertNotFound();
});

it('sumar miembros es sólo para administradores y valida la cuenta', function () {
    $org = communityOrg();
    orgUser(communityOrg(), 'MOZO', ['username' => 'pepe']);

    $this->actingAs(orgUser($org, 'GERENTE'))->get(route('organization.show'))->assertForbidden();
    $this->actingAs(orgUser($org))->post(route('organization.members.store'), ['identifier' => 'nadie', 'role' => 'ALUMNO'])
        ->assertSessionHasErrors('identifier');
    $this->post(route('organization.members.store'), ['identifier' => 'pepe', 'role' => 'ALUMNO'])->assertSessionHasNoErrors();
    expect(OrganizationMembership::query()->where('restaurant_id', $org->id)->value('role'))->toBe('ALUMNO');
});
