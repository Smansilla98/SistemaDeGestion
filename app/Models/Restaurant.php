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

    /** @return HasMany<OrganizationMembership, $this> */
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
     *
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Relación: sucursales que dependen de este restaurante
     *
     * @return HasMany<self, $this>
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
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relación: Un restaurante tiene muchos sectores
     *
     * @return HasMany<Sector, $this>
     */
    public function sectors(): HasMany
    {
        return $this->hasMany(Sector::class);
    }

    /**
     * Relación: Un restaurante tiene muchas mesas
     *
     * @return HasMany<Table, $this>
     */
    public function tables(): HasMany
    {
        return $this->hasMany(Table::class);
    }

    /**
     * Relación: Un restaurante tiene muchas categorías
     *
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Relación: Un restaurante tiene muchos productos
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Relación: Un restaurante tiene muchos pedidos
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relación: Un restaurante tiene muchos movimientos de stock
     *
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Relación: Un restaurante tiene muchas cajas registradoras
     *
     * @return HasMany<CashRegister, $this>
     */
    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }

    /**
     * Relación: Un restaurante tiene muchos pagos
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relación: Un restaurante tiene muchos logs de auditoría
     *
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Relación: Un restaurante tiene muchas impresoras
     *
     * @return HasMany<Printer, $this>
     */
    public function printers(): HasMany
    {
        return $this->hasMany(Printer::class);
    }
}
