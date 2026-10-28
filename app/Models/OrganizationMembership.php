<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Participación de una cuenta en una organización distinta de la propia, con su rol.
 */
class OrganizationMembership extends Model
{
    protected $fillable = ['user_id', 'restaurant_id', 'role', 'is_active', 'created_by'];

    protected $casts = ['is_active' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
