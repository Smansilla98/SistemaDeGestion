<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\OrganizationMembership;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Community\MembershipService;
use App\Services\Community\OrganizationDirectory;
use App\Services\ModuleLicenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Organización activa: tipo, módulos habilitados y personas de otras cuentas que participan
 * con un rol propio (membresías). Sólo administradores.
 */
class OrganizationController extends Controller
{
    use OrganizationContext;

    public function show(OrganizationDirectory $directory, ModuleLicenseService $licenses): View
    {
        $organization = Restaurant::query()->findOrFail($this->org());

        return view('organization.show', [
            'organization' => $organization,
            'members' => $directory->members($organization->id),
            'memberships' => OrganizationMembership::query()->with('user')->where('restaurant_id', $organization->id)->orderByDesc('is_active')->get(),
            'roles' => User::membershipRoles(),
            'roleLabels' => User::roleLabels(),
            'modules' => collect($licenses->catalog())->map(fn ($m, $k) => ['label' => $m['label'], 'on' => $licenses->isEnabled($organization->id, $k)]),
        ]);
    }

    public function updateKind(Request $request): RedirectResponse
    {
        $data = $request->validate(['kind' => ['required', Rule::in(array_keys(Restaurant::KINDS))]]);
        Restaurant::query()->whereKey($this->org())->update(['kind' => $data['kind']]);

        return back()->with('success', 'Tipo de organización actualizado.');
    }

    public function storeMember(Request $request, MembershipService $service): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(User::membershipRoles())],
        ]);
        $service->add($this->org(), $data['identifier'], $data['role'], $this->user());

        return back()->with('success', 'La persona ya puede entrar a esta organización con su misma cuenta.');
    }

    public function updateMember(Request $request, OrganizationMembership $membership): RedirectResponse
    {
        $this->ensureOrg($membership);
        $data = $request->validate([
            'role' => ['required', Rule::in(User::membershipRoles())],
            'is_active' => ['required', 'boolean'],
        ]);
        $membership->update($data);

        return back()->with('success', 'Membresía actualizada.');
    }
}
