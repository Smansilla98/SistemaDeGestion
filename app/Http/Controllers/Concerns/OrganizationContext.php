<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Entrepreneur;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Model;

/**
 * Organización activa, permisos y alcance personal (profesor, alumno, emprendedor)
 * para los módulos de organizaciones.
 */
trait OrganizationContext
{
    protected function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    protected function org(): int
    {
        $id = $this->user()->restaurant_id;
        abort_if($id === null, 403, 'Elegí una organización para continuar.');

        return (int) $id;
    }

    protected function can(string $permission): bool
    {
        return app(PermissionService::class)->allowed($this->user(), $permission);
    }

    protected function requirePermission(string $permission): void
    {
        abort_unless($this->can($permission), 403, 'No tenés permiso para esta acción.');
    }

    /** 404 si el registro es de otra organización (el superadmin no tiene scope global). */
    protected function ensureOrg(Model $model): void
    {
        abort_if((int) $model->getAttribute('restaurant_id') !== $this->org(), 404);
    }

    /** Id del emprendedor de la cuenta si opera como EMPRENDEDOR; null si ve todo. */
    protected function ownEntrepreneurId(): ?int
    {
        if ($this->user()->role !== User::ROLE_EMPRENDEDOR) {
            return null;
        }

        return (int) (Entrepreneur::query()->where('restaurant_id', $this->org())->where('user_id', $this->user()->id)->value('id') ?? -1);
    }

    protected function isTeacherOnly(): bool
    {
        return $this->user()->role === User::ROLE_PROFESOR;
    }
}
