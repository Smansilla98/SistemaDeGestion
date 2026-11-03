<?php

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\UserMessage;
use App\Services\Community\MembershipService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo_entry' => false]);
    $this->org = communityOrg();
    $this->admin = orgUser($this->org);
    $this->actingAs($this->admin);
});

it('importa un CSV sin duplicar y exporta con los mismos datos', function () {
    Contact::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Viejo nombre', 'email' => 'ana@test.com', 'kind' => 'PUBLICO']);
    $csv = "nombre;tipo;email;telefono;alias;etiquetas;acepta_difusion\n"
        ."Ana Pérez;artista;ana@test.com;;La Negra;percusión|zona sur;si\n"
        ."Bruno;profesor;;11 4444-5555;;;no\n"
        .";cliente;sin@nombre.com;;;;\n"
        ."Malo;publico;no-es-mail;;;;\n";
    $file = UploadedFile::fake()->createWithContent('contactos.csv', $csv);

    $this->post(route('contacts.import.store'), ['file' => $file])->assertRedirect(route('contacts.index'))
        ->assertSessionHas('success', 'Importación: 1 nuevos, 1 actualizados, 2 salteados.');

    $ana = Contact::query()->where('email', 'ana@test.com')->first();
    expect(Contact::query()->count())->toBe(2)
        ->and($ana->name)->toBe('Ana Pérez')->and($ana->kind)->toBe('ARTISTA')
        ->and($ana->accepts_marketing)->toBeTrue()->and($ana->tags->pluck('name')->sort()->values()->all())->toBe(['percusión', 'zona sur']);

    $out = $this->get(route('contacts.export', ['kind' => 'ARTISTA']))->assertOk()->streamedContent();
    expect($out)->toContain('Ana Pérez')->toContain('percusión|zona sur')->not->toContain('Bruno');
});

it('la comunicación sólo llega a quienes aceptaron y no se reenvía', function () {
    Mail::fake();
    $withAccount = orgUser($this->org, 'MOZO');
    Contact::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Acepta', 'kind' => 'PUBLICO', 'accepts_marketing' => true, 'email' => 'a@x.com', 'user_id' => $withAccount->id]);
    Contact::query()->create(['restaurant_id' => $this->org->id, 'name' => 'No acepta', 'kind' => 'PUBLICO', 'accepts_marketing' => false, 'email' => 'b@x.com']);
    Contact::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Otro tipo', 'kind' => 'PROVEEDOR', 'accepts_marketing' => true]);

    $this->post(route('campaigns.store'), ['title' => 'Abrimos inscripción', 'body' => 'Vení', 'send_email' => 1, 'kind' => ['PUBLICO']])->assertRedirect();
    $campaign = Campaign::query()->firstOrFail();
    $this->get(route('campaigns.show', $campaign))->assertSee('Acepta')->assertDontSee('No acepta')->assertDontSee('Otro tipo');

    $this->post(route('campaigns.send', $campaign))->assertRedirect();
    expect($campaign->fresh()->recipients_count)->toBe(1)
        ->and($campaign->recipients()->value('channels'))->toBe('app,email')
        ->and($withAccount->fresh()->notifications()->count())->toBe(1);

    $this->post(route('campaigns.send', $campaign))->assertSessionHasErrors('campaign');
});

it('los mensajes van sólo a miembros de la organización y avisan', function () {
    $member = orgUser(communityOrg(), 'MOZO', ['username' => 'socia']);
    app(MembershipService::class)->add($this->org->id, 'socia', 'ARTISTA', $this->admin);
    $stranger = orgUser(communityOrg(), 'MOZO');

    $this->post(route('messages.store'), ['recipient_id' => $stranger->id, 'body' => 'Hola'])->assertSessionHasErrors('recipient_id');
    $this->post(route('messages.store'), ['recipient_id' => $member->id, 'subject' => 'Ensayo', 'body' => 'El jueves'])->assertRedirect();

    $msg = UserMessage::query()->firstOrFail();
    expect($member->fresh()->notifications()->first()->data['url'])->toBe(route('messages.show', $msg));

    $this->actingAs($member)->post(route('branch.store'), ['restaurant_id' => $this->org->id]);
    $this->get(route('messages.show', $msg))->assertOk()->assertSee('El jueves');
    expect($msg->fresh()->read_at)->not->toBeNull();
    $this->actingAs($stranger)->get(route('messages.show', $msg))->assertNotFound();
});
