<?php

namespace App\Http\Controllers\Manual;

use App\Http\Controllers\Controller;
use App\Services\ModuleLicenseService;
use App\Support\UserManual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Manual de uso: abre el del rol de quien entra y deja consultar el de los demás roles.
 */
class ManualController extends Controller
{
    public function __invoke(Request $request, ModuleLicenseService $licenses, ?string $rol = null)
    {
        $user = $request->user();
        $ownRole = UserManual::normalizeRole($user->role);
        $role = $rol !== null ? UserManual::normalizeRole($rol) : $ownRole;
        $isOwn = $role === $ownRole;

        $library = UserManual::sections();
        $sections = [];
        foreach (UserManual::roles()[$role]['sections'] as $key) {
            $section = $library[$key] ?? null;
            if ($section === null) {
                continue;
            }

            $routeName = $section['route'] ?? null;
            if ($routeName !== null && ! Route::has($routeName)) {
                $routeName = null;
            }

            // Lo que el local no tiene contratado no se explica en el manual propio.
            $module = $routeName !== null ? $licenses->moduleForWebRoute($routeName) : null;
            if ($isOwn && $module !== null && ! $licenses->enabledForUser($user, $module)) {
                continue;
            }

            $section['key'] = $key;
            $section['url'] = $isOwn && $routeName !== null ? route($routeName) : null;
            $sections[] = $section;
        }

        $faqs = array_values(array_filter(
            UserManual::faqs(),
            fn (array $faq) => $faq['roles'] === [] || in_array($role, $faq['roles'], true)
        ));

        return view('manual.index', [
            'roles' => UserManual::roles(),
            'role' => $role,
            'ownRole' => $ownRole,
            'isOwn' => $isOwn,
            'meta' => UserManual::roles()[$role],
            'sections' => $sections,
            'faqs' => $faqs,
        ]);
    }
}
