<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Formulario externo (Google Forms vía su planilla, o un Excel/CSV subido) que alimenta
 * alumnos inscriptos, talleristas, contactos o la agenda de la organización.
 */
class FormSource extends Model
{
    use BelongsToRestaurant;

    public const TARGETS = [
        'ALUMNOS' => 'Inscripciones a talleres',
        'TALLERISTAS' => 'Propuestas de talleristas',
        'CONTACTOS' => 'Contactos',
        'AGENDA' => 'Actividades para la agenda',
    ];

    /** Campos del sistema que se pueden mapear, por destino. */
    public const FIELDS = [
        'name' => 'Nombre (o nombre y apellido)',
        'last_name' => 'Apellido',
        'email' => 'Email',
        'phone' => 'Teléfono / WhatsApp',
        'document' => 'DNI',
        'birth_date' => 'Fecha de nacimiento',
        'workshop' => 'Taller elegido',
        'category' => 'Categoría',
        'title' => 'Título de la actividad / propuesta',
        'description' => 'Descripción',
        'date' => 'Fecha',
        'time' => 'Hora',
        'consent' => 'Acepta recibir novedades',
        'notes' => 'Comentarios',
    ];

    protected $fillable = ['restaurant_id', 'name', 'target', 'sheet_url', 'mapping', 'headers', 'workshop_id', 'activity_category_id',
        'contact_kind', 'tags', 'auto_sync', 'is_active', 'last_synced_at', 'last_error', 'created_by'];

    protected $casts = ['mapping' => 'array', 'headers' => 'array', 'auto_sync' => 'boolean', 'is_active' => 'boolean', 'last_synced_at' => 'datetime'];

    public function rows(): HasMany
    {
        return $this->hasMany(FormSourceRow::class);
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ActivityCategory::class, 'activity_category_id');
    }

    public function targetLabel(): string
    {
        return self::TARGETS[$this->target] ?? $this->target;
    }
}
