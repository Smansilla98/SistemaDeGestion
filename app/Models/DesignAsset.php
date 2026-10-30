<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;

/**
 * Imagen del editor: kit de marca compartido (KIT) o subida de una persona (UPLOAD).
 */
class DesignAsset extends Model
{
    use BelongsToRestaurant;

    public const KIT = 'KIT';

    public const UPLOAD = 'UPLOAD';

    protected $fillable = ['restaurant_id', 'user_id', 'kind', 'title', 'path', 'mime', 'bytes'];

    protected $casts = ['bytes' => 'integer'];

    public function url(): string
    {
        return route('design.media', ['path' => $this->path]);
    }
}
