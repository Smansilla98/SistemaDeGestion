<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\Workshop;
use App\Services\ModuleLicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Conurbania es un restaurante: talleres, agenda, contactos, mercado y documentos no aparecen
 * en el menú ni en el inicio (tampoco al superadmin). Sólo están en el Monitor de módulos,
 * donde el superadmin puede prenderlos para un local.
 */
class ConurbaniaSoloRestauranteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.demo_entry' => false]);
    }

    public function test_el_superadmin_no_ve_modulos_de_centro_cultural_en_el_inicio_ni_en_el_menu(): void
    {
        $superadmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        Workshop::query()->create(['restaurant_id' => $superadmin->restaurant_id, 'name' => 'Percusión inicial (ejemplo)', 'status' => 'ABIERTO']);

        $this->actingAs($superadmin)->get(route('dashboard'))->assertOk()
            ->assertDontSee('Últimas inscripciones')
            ->assertDontSee('>Comunidad<', false)
            ->assertDontSee('>Agenda<', false)
            ->assertDontSee('>Talleres<', false)
            ->assertDontSee('Organización y miembros')
            ->assertSee('Eventos')
            ->assertSee('Actividades Recurrentes');
    }

    public function test_las_rutas_de_centro_cultural_quedan_cerradas_y_eventos_funciona_solo(): void
    {
        $superadmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);

        $this->actingAs($superadmin)->get(route('workshops.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($superadmin)->get(route('agenda.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($superadmin)->get(route('contacts.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($superadmin)->get(route('events.index'))->assertOk()->assertSee('Calendario de Eventos');
    }

    public function test_el_superadmin_los_ve_en_el_monitor_y_los_prende_para_un_local(): void
    {
        $superadmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $licenses = app(ModuleLicenseService::class);

        $this->actingAs($superadmin)->get(route('module-usage.index', ['restaurant_id' => $superadmin->restaurant_id]))->assertOk()
            ->assertSee('Talleres y agenda')->assertSee('Contactos y difusión');
        $this->assertFalse($licenses->enabledForUser($superadmin, 'workshops'));
        $this->assertFalse($licenses->mapFor($superadmin->restaurant_id)['workshops']);

        $licenses->save($superadmin->restaurant()->firstOrFail(), array_fill_keys(array_keys($licenses->catalog()), true));

        $this->assertTrue($licenses->enabledForUser($superadmin, 'workshops'));
        $this->actingAs($superadmin)->get(route('dashboard'))->assertOk()->assertSee('>Agenda<', false);
    }

    public function test_la_migracion_borra_el_evento_de_ejemplo_que_cargaba_la_demo(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $ejemplo = Event::query()->create(['restaurant_id' => $admin->restaurant_id, 'created_by' => $admin->id, 'name' => 'Peña de primavera (ejemplo)',
            'date' => today()->addWeek(), 'time' => '20:00', 'status' => Event::STATUS_PROGRAMADO]);
        $ejemplo->stages()->create(['name' => 'Sonido', 'position' => 0, 'status' => 'PENDIENTE']);
        $real = Event::query()->create(['restaurant_id' => $admin->restaurant_id, 'created_by' => $admin->id, 'name' => 'Cumpleaños de Juan',
            'date' => today()->addWeek(), 'time' => '21:00', 'status' => Event::STATUS_PROGRAMADO]);

        $migration = require database_path('migrations/2026_11_06_000001_remove_demo_example_event.php');
        $migration->up();

        $this->assertDatabaseMissing('events', ['id' => $ejemplo->id]);
        $this->assertDatabaseMissing('event_stages', ['event_id' => $ejemplo->id]);
        $this->assertDatabaseHas('events', ['id' => $real->id]);
    }
}
