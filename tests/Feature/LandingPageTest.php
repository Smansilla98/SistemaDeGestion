<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_ve_la_portada_con_acceso_al_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Todo el servicio en un solo sistema')
            ->assertSee(route('login'), false);
    }

    public function test_usuario_con_sesion_va_al_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }
}
