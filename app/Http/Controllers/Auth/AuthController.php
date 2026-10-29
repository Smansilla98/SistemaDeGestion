<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Community\ContactService;
use App\Services\Community\OrganizationDirectory;
use App\Services\Notifier;
use App\Support\BranchAccess;
use App\Support\DemoEntry;
use App\Support\DemoLogin;
use App\Support\PublicSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $labels = User::roleLabels();

        return view('auth.register', [
            'communityRoles' => array_intersect_key($labels, array_flip(User::SELF_SIGNUP_COMMUNITY)),
            'staffRoles' => array_intersect_key($labels, array_flip(User::SELF_SIGNUP_STAFF)),
        ]);
    }

    /**
     * Procesar registro de nuevo usuario
     */
    public function register(RegisterRequest $request, ContactService $contacts, OrganizationDirectory $directory, Notifier $notifier)
    {
        $validated = $request->validated();

        $org = PublicSite::organization();
        if (! $org || ! $org->is_active) {
            return back()->withErrors(['register' => 'El registro no está disponible. Escribile a la administración del espacio.'])->withInput();
        }

        $role = $validated['role'];
        $isStaff = in_array($role, User::SELF_SIGNUP_STAFF, true);

        $user = DB::transaction(function () use ($validated, $org, $role, $isStaff, $contacts) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? 'email@email.com',
                'password' => Hash::make($validated['password']),
                'restaurant_id' => $org->id,
                'role' => $role,
                // Los roles del equipo dan acceso a caja, contactos o finanzas: esperan aprobación.
                'is_active' => ! $isStaff,
            ]);

            // Alumnos, profes, artistas y emprendedores quedan en Contactos, ligados a su cuenta
            // (así el profe ve sus talleres y el alumno sus clases).
            if (! $isStaff) {
                $contact = $contacts->findOrCreate((int) $org->id, $validated['name'], $validated['email'] ?? null, $validated['phone'] ?? null, $role);
                if ($contact->user_id === null) {
                    $contact->forceFill(['user_id' => $user->id])->save();
                }
            }

            return $user;
        });

        $label = User::roleLabels()[$role] ?? $role;
        // Avisa a quienes pueden activar cuentas (Usuarios: administración y gerencia).
        $notifier->sendMany($directory->usersWithRoles((int) $org->id, [User::ROLE_ADMIN, User::ROLE_GERENTE]), 'administrativo',
            $isStaff ? "Cuenta para aprobar: {$user->name}" : "Nueva cuenta: {$user->name}",
            $isStaff ? "Pidió el rol {$label}. Revisala y activala en Usuarios si corresponde." : "Se registró como {$label}.",
            route('users.edit', $user), "signup:{$user->id}", (int) $org->id);

        return redirect()->route('login')->with('success', $isStaff
            ? "Cuenta creada. Como pediste el rol {$label}, la administración tiene que aprobarla antes de que puedas ingresar."
            : 'Cuenta creada. Ya podés iniciar sesión.');
    }

    /**
     * Procesar login
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (DemoLogin::matches(
            (string) ($credentials['username'] ?? ''),
            (string) ($credentials['password'] ?? '')
        )) {
            return back()
                ->with('success', DemoLogin::message())
                ->onlyInput('username');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            if (! $user->is_active) {
                Auth::logout();

                return back()->withErrors(['username' => 'Tu cuenta todavía no está activa: la administración tiene que aprobarla.'])->onlyInput('username');
            }

            $request->session()->regenerate();

            // Actualizar último login
            $user->update(['last_login_at' => now()]);

            // Quien opera más de una sucursal elige explícitamente antes de entrar.
            if (app(BranchAccess::class)->requiresSelection($user)) {
                return redirect()->route('branch.select');
            }

            if (DemoEntry::decides($user)) {
                return redirect()->route('entry.choose');
            }

            return redirect()->route(DemoEntry::homeRoute($user));
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
}
