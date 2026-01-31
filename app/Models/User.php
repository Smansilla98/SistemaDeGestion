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
    const ROLE_ADMIN = 'ADMIN';
    const ROLE_CAJERO = 'CAJERO';
    const ROLE_MOZO = 'MOZO';
    const ROLE_COCINA = 'COCINA';
    const ROLE_SUPERVISOR = 'SUPERVISOR';
    const ROLE_ENCARGADO = 'ENCARGADO';

    protected $fillable = [
        'restaurant_id',
        'name',
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
     * Obtener todos los roles disponibles
     */
    public static function getRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_CAJERO,
            self::ROLE_MOZO,
            self::ROLE_COCINA,
            self::ROLE_SUPERVISOR,
            self::ROLE_ENCARGADO,
        ];
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
}
