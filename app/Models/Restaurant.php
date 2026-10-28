<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'kind',
        'address',
        'phone',
        'email',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /** Tipos de organización. RESTAURANTE es el comportamiento original del sistema. */
    public const KINDS = [
        'RESTAURANTE' => 'Restaurante / bar',
        'ESPACIO_CULTURAL' => 'Espacio cultural',
        'ESCUELA' => 'Escuela / talleres',
        'MERCADO' => 'Mercado territorial',
        'NODO_AGROECOLOGICO' => 'Nodo agroecológico',
        'EVENTOS' => 'Organización de eventos',
        'OTRO' => 'Otra organización',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind ?? 'RESTAURANTE'] ?? (string) $this->kind;
    }

    /**
     * Relación: casa matriz de esta sucursal (null si es la matriz o un local único)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Relación: sucursales que dependen de este restaurante
     */
    public function branches(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Id de la casa matriz del grupo (este mismo si no tiene padre).
     */
    public function groupRootId(): int
    {
        return (int) ($this->parent_id ?? $this->id);
    }

    /**
     * Relación: Un restaurante tiene muchos usuarios
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relación: Un restaurante tiene muchos sectores
     */
    public function sectors(): HasMany
    {
        return $this->hasMany(Sector::class);
    }

    /**
     * Relación: Un restaurante tiene muchas mesas
     */
    public function tables(): HasMany
    {
        return $this->hasMany(Table::class);
    }

    /**
     * Relación: Un restaurante tiene muchas categorías
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Relación: Un restaurante tiene muchos productos
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Relación: Un restaurante tiene muchos pedidos
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relación: Un restaurante tiene muchos movimientos de stock
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Relación: Un restaurante tiene muchas cajas registradoras
     */
    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }

    /**
     * Relación: Un restaurante tiene muchos pagos
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relación: Un restaurante tiene muchos logs de auditoría
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Relación: Un restaurante tiene muchas impresoras
     */
    public function printers(): HasMany
    {
        return $this->hasMany(Printer::class);
    }
}
