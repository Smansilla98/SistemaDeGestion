<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Persona de una organización: alumno, profesor, artista, emprendedor, cliente, proveedor,
 * participante o público. Puede tener cuenta (user_id) o no; no duplica los datos del usuario.
 */
class Contact extends Model
{
    use BelongsToRestaurant;

    public const KINDS = [
        'ALUMNO' => 'Alumno', 'PROFESOR' => 'Profesor', 'ARTISTA' => 'Artista', 'EMPRENDEDOR' => 'Emprendedor',
        'CLIENTE' => 'Cliente', 'PROVEEDOR' => 'Proveedor', 'PARTICIPANTE' => 'Participante de eventos', 'PUBLICO' => 'Público general',
    ];

    protected $fillable = ['restaurant_id', 'user_id', 'kind', 'name', 'alias', 'email', 'phone', 'document', 'birth_date',
        'notes', 'accepts_marketing', 'marketing_consent_at', 'is_active'];

    protected $casts = ['birth_date' => 'date', 'accepts_marketing' => 'boolean', 'marketing_consent_at' => 'datetime', 'is_active' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ContactTag::class, 'contact_tag');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(ContactGroup::class, 'contact_group_member');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(WorkshopEnrollment::class);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? (string) $this->kind;
    }

    /** Teléfono en formato wa.me (sólo dígitos, con 54 si es un número argentino sin código). */
    public function whatsappNumber(): ?string
    {
        $digits = preg_replace('/\\D+/', '', (string) $this->phone) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }
        if (! str_starts_with($digits, '54')) {
            $digits = '54'.$digits;
        }

        return $digits;
    }
}
