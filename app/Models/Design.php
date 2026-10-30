<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pieza gráfica del editor (flyer, historia, afiche…). Cada página guarda su lienzo de Fabric.
 */
class Design extends Model
{
    use BelongsToRestaurant;

    protected $fillable = ['restaurant_id', 'user_id', 'title', 'format', 'width', 'height', 'preview_path'];

    protected $casts = ['width' => 'integer', 'height' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(DesignPage::class)->orderBy('position')->orderBy('id');
    }

    /** @return array<string, mixed> Formato que espera el editor (OpenDesign). */
    public function forEditor(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->title,
            'canvas_json' => '{}',
            'width' => $this->width,
            'height' => $this->height,
            'thumbnail_url' => $this->preview_path ? route('design.media', ['path' => $this->preview_path, 'v' => $this->updated_at?->timestamp]) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
