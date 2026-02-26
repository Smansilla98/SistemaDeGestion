<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Mostrar formulario de login
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Mostrar formulario de registro
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /**
     * Procesar registro de nuevo usuario
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'username.unique' => 'Ese nombre de usuario ya está en uso.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $restaurant = Restaurant::where('is_active', true)->first();
        if (!$restaurant) {
            return back()->withErrors(['register' => 'No hay restaurantes disponibles para el registro. Contacta al administrador.'])->withInput();
        }

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => 'email@email.com',
            'password' => Hash::make($validated['password']),
            'restaurant_id' => $restaurant->id,
            'role' => 'MOZO',
            'is_active' => true,
        ]);

        return redirect()->route('login')->with('success', 'Cuenta creada. Ya podés iniciar sesión.');
    }

    /**
     * Procesar login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Actualizar último login
            $user->update(['last_login_at' => now()]);

            // Redirigir según rol
            return $this->redirectByRole($user->role);
        }

        return back()->withErrors([
            'username' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('username');
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Redirigir según rol
     */
    private function redirectByRole(string $role): \Illuminate\Http\RedirectResponse
    {
        return match ($role) {
            'ADMIN' => redirect()->route('dashboard'),
            'MOZO' => redirect()->route('tables.index'),
            'COCINA' => redirect()->route('kitchen.index'),
            'CAJERO' => redirect()->route('cash-register.index'),
            default => redirect()->route('dashboard'),
        };
    }
}

