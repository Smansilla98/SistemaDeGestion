<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Roles de usuario
    const ROLE_SUPERADMIN = 'SUPERADMIN';

    const ROLE_ADMIN = 'ADMIN';

    const ROLE_GERENTE = 'GERENTE';  // Entre ADMIN y SUPERVISOR: mismo que admin excepto Cocina, Descuentos, Sectores, Categorías, Impresoras, Finanzas y reportes

    const ROLE_CAJERO = 'CAJERO';

    const ROLE_MOZO = 'MOZO';

    const ROLE_COCINA = 'COCINA';

    const ROLE_SUPERVISOR = 'SUPERVISOR';

    const ROLE_ENCARGADO = 'ENCARGADO';

    // Roles de organizaciones culturales, educativas y comerciales. Se asignan sólo por
    // membresía (organization_memberships), no en users.role.
    const ROLE_COORDINADOR = 'COORDINADOR';

    const ROLE_PROFESOR = 'PROFESOR';

    const ROLE_ALUMNO = 'ALUMNO';

    const ROLE_EMPRENDEDOR = 'EMPRENDEDOR';

    const ROLE_ARTISTA = 'ARTISTA';

    const ROLE_MARKETING = 'MARKETING';

    const ROLE_INVENTARIO = 'INVENTARIO';

    /**
     * Sucursal activa en esta request (no se persiste). Ver useRestaurant().
     */
    private ?int $activeRestaurantId = null;

    /**
     * Rol de la membresía en la organización activa (no se persiste). Ver useRestaurant().
     */
    private ?string $activeRole = null;

    protected $fillable = [
        'restaurant_id',
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Opera esta request como otra sucursal sin tocar la fila del usuario.
     * Quien valida que tenga acceso es BranchAccess, antes de llamar acá.
     */
    public function useRestaurant(?int $restaurantId): static
    {
        $this->activeRestaurantId = $restaurantId;
        $this->activeRole = null;
        $this->unsetRelation('restaurant');

        if ($restaurantId !== null && $restaurantId !== $this->homeRestaurantId() && ($this->attributes['role'] ?? null) !== self::ROLE_SUPERADMIN) {
            $membershipRole = OrganizationMembership::query()
                ->where('user_id', $this->id)
                ->where('restaurant_id', $restaurantId)
                ->where('is_active', true)
                ->value('role');
            $this->activeRole = is_string($membershipRole) ? $membershipRole : null;
        }

        return $this;
    }

    /**
     * Rol con el que opera esta request: el de la membresía si se eligió otra organización.
     */
    public function getRoleAttribute($value)
    {
        return $this->activeRole ?? $value;
    }

    /**
     * Rol guardado en la cuenta, sin importar la organización activa.
     */
    public function homeRole(): ?string
    {
        $raw = $this->attributes['role'] ?? null;

        return is_string($raw) ? $raw : null;
    }

    public function memberships(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    /**
     * Roles que se pueden dar por membresía en otra organización.
     *
     * @return list<string>
     */
    public static function membershipRoles(): array
    {
        return [
            self::ROLE_ADMIN, self::ROLE_COORDINADOR, self::ROLE_ENCARGADO, self::ROLE_PROFESOR,
            self::ROLE_ALUMNO, self::ROLE_EMPRENDEDOR, self::ROLE_ARTISTA, self::ROLE_MARKETING,
            self::ROLE_INVENTARIO, self::ROLE_GERENTE, self::ROLE_CAJERO, self::ROLE_MOZO,
            self::ROLE_COCINA, self::ROLE_SUPERVISOR,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function roleLabels(): array
    {
        return [
            self::ROLE_SUPERADMIN => 'Superadministrador', self::ROLE_ADMIN => 'Administrador',
            self::ROLE_GERENTE => 'Gerente', self::ROLE_CAJERO => 'Cajero', self::ROLE_MOZO => 'Mozo',
            self::ROLE_COCINA => 'Cocina', self::ROLE_SUPERVISOR => 'Supervisor', self::ROLE_ENCARGADO => 'Encargado',
            self::ROLE_COORDINADOR => 'Coordinador', self::ROLE_PROFESOR => 'Profesor', self::ROLE_ALUMNO => 'Alumno',
            self::ROLE_EMPRENDEDOR => 'Emprendedor', self::ROLE_ARTISTA => 'Artista',
            self::ROLE_MARKETING => 'Responsable de marketing', self::ROLE_INVENTARIO => 'Responsable de inventario',
        ];
    }

    /**
     * Local al que pertenece el usuario en la base, sin importar la sucursal activa.
     */
    public function homeRestaurantId(): ?int
    {
        $raw = $this->attributes['restaurant_id'] ?? null;

        return $raw !== null ? (int) $raw : null;
    }

    /**
     * restaurant_id devuelve la sucursal activa si se eligió una; si no, la propia.
     */
    public function getRestaurantIdAttribute($value)
    {
        return $this->activeRestaurantId ?? $value;
    }

    /**
     * Relación: Un usuario pertenece a un restaurante (nullable para super admin)
     */
    public function restaurant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Un usuario tiene muchos pedidos
     */
    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relación: Un usuario tiene muchas mesas asignadas
     */
    public function assignedTables(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Table::class, 'assigned_user_id');
    }

    /**
     * Relación: Un usuario tiene muchos movimientos de stock
     */
    public function stockMovements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Relación: Un usuario tiene muchos logs de auditoría
     */
    public function auditLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Permisos específicos del usuario (sobreescriben los del rol)
     */
    public function userPermissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    /**
     * Obtener todos los roles disponibles
     */
    public static function getRoles(): array
    {
        return [
            self::ROLE_SUPERADMIN,
            self::ROLE_ADMIN,
            self::ROLE_GERENTE,
            self::ROLE_CAJERO,
            self::ROLE_MOZO,
            self::ROLE_COCINA,
            self::ROLE_SUPERVISOR,
            self::ROLE_ENCARGADO,
        ];
    }

    /**
     * Roles que se pueden asignar desde usuarios normales (sin SUPERADMIN).
     *
     * @return list<string>
     */
    public static function getAssignableRoles(?self $actor = null): array
    {
        $all = self::getRoles();
        if ($actor !== null && $actor->isSuperAdmin()) {
            return $all;
        }

        return array_values(array_diff($all, [self::ROLE_SUPERADMIN]));
    }

    /**
     * Si el actor no es superadmin, oculta por completo la existencia del rol SUPERADMIN
     * (listados, matrices de permisos, etc.).
     */
    public static function shouldHideSuperadminFrom(self $actor): bool
    {
        return ! $actor->isSuperAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    /**
     * Mismo nivel que ADMIN en políticas y rutas (no confundir con isAdmin()).
     */
    public function isAdminLevel(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN || $this->role === self::ROLE_ADMIN;
    }

    /**
     * Verificar si el usuario es administrador
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Verificar si el usuario es mozo
     */
    public function isMozo(): bool
    {
        return $this->role === self::ROLE_MOZO;
    }

    /**
     * Verificar si el usuario es de cocina
     */
    public function isCocina(): bool
    {
        return $this->role === self::ROLE_COCINA;
    }

    /**
     * Verificar si el usuario es cajero
     */
    public function isCajero(): bool
    {
        return $this->role === self::ROLE_CAJERO;
    }

    /**
     * Puede gestionar usuarios del restaurante (lista, crear, editar, eliminar).
     */
    public function canManageUsers(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_GERENTE], true);
    }

    /**
     * Pedidos / caja: mismas excepciones que admin y gerente (p. ej. edición ampliada).
     */
    public function canManageOrdersLikeAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_GERENTE], true);
    }

    /**
     * Verificar si el usuario es gerente (admin restringido: sin Cocina, Descuentos, Sectores, Categorías, Impresoras, Reportes)
     */
    public function isGerente(): bool
    {
        return $this->role === self::ROLE_GERENTE;
    }

    /**
     * Verificar si el usuario es supervisor
     */
    public function isSupervisor(): bool
    {
        return $this->role === self::ROLE_SUPERVISOR;
    }

    /**
     * Verificar si el usuario es encargado
     */
    public function isEncargado(): bool
    {
        return $this->role === self::ROLE_ENCARGADO;
    }

    /**
     * Relación: Un usuario tiene muchas sesiones de mesa como mozo
     */
    public function tableSessionsAsWaiter(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TableSession::class, 'waiter_id');
    }

    /**
     * Obtener mesas asignadas actualmente (sesiones abiertas)
     */
    public function getActiveAssignedTables()
    {
        return Table::whereHas('currentSession', function ($query) {
            $query->where('waiter_id', $this->id)
                ->where('status', TableSession::STATUS_OPEN);
        })->with(['currentSession', 'sector'])->get();
    }

    /**
     * Accessor para last_login_at que siempre devuelve Carbon o null
     */
    public function getLastLoginAtAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }

        // Si ya es un objeto Carbon, devolverlo
        if ($value instanceof \Carbon\Carbon) {
            return $value;
        }

        // Si es string, convertirlo a Carbon
        if (is_string($value)) {
            try {
                return \Carbon\Carbon::parse($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return $value;
    }
}
