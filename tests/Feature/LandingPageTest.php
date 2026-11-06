<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_va_directo_al_login_sin_portada_comercial(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_el_login_muestra_la_marca_conurbania(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Conurbania')
            ->assertSee('logo.png', false)
            ->assertDontSee('Al Toque');
    }

    public function test_usuario_con_sesion_va_al_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_sin_demo_no_se_puede_entrar_a_la_eleccion_de_demo_ni_al_presupuesto(): void
    {
        config(['app.demo_entry' => false]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('entry.choose'))->assertNotFound();
        $this->actingAs($admin)->post(route('entry.demo'))->assertNotFound();
        $this->actingAs($admin)->get(route('entry.quote'))->assertNotFound();
        $this->assertNull(session('entry_mode'));
    }
}
