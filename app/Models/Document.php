<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Documento de una organización (o de un emprendedor) con historial de versiones.
 * GESTION: sólo quien puede editar documentos. MIEMBROS: cualquiera con documents.view.
 */
class Document extends Model
{
    use BelongsToRestaurant;

    public const CATEGORIES = ['MANUAL_MARCA' => 'Manual de marca', 'LOGO' => 'Logo / imagen', 'REGLAMENTO' => 'Reglamento',
        'MATERIAL' => 'Material educativo', 'CONTRATO' => 'Contrato', 'OTRO' => 'Otro'];

    public const VISIBILITIES = ['GESTION' => 'Sólo gestión', 'MIEMBROS' => 'Todos los miembros'];

    protected $fillable = ['restaurant_id', 'entrepreneur_id', 'category', 'title', 'description', 'visibility', 'status', 'published_at', 'created_by'];

    protected $casts = ['published_at' => 'datetime'];

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version');
    }

    public function latestVersion(): ?DocumentVersion
    {
        return $this->versions()->first();
    }

    public function entrepreneur(): BelongsTo
    {
        return $this->belongsTo(Entrepreneur::class);
    }
}
