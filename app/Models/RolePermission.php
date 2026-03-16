<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends Model
{
    protected $fillable = ['role', 'permission_key', 'allowed'];

    protected function casts(): array
    {
        return ['allowed' => 'boolean'];
    }
}
