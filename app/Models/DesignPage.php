<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignPage extends Model
{
    protected $fillable = ['design_id', 'title', 'canvas_json', 'position'];

    protected $casts = ['position' => 'integer'];

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    /** @return array<string, mixed> */
    public function forEditor(): array
    {
        return [
            'id' => (string) $this->id,
            'design_id' => (string) $this->design_id,
            'title' => $this->title,
            'canvas_json' => $this->canvas_json ?: '{}',
            'sort_order' => $this->position,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
