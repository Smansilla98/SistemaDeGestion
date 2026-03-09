<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Usuario puede hacer login con username
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
            'role' => UserRole::MOZO,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('tables.index'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test: Usuario no puede hacer login con credenciales incorrectas
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Test: Usuario autenticado puede hacer logout
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create(['username' => 'logoutuser']);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}

