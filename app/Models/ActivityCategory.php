<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;

/**
 * Categoría de actividades de una organización (Música, Teatro, Feria…), con color para la agenda.
 */
class ActivityCategory extends Model
{
    use BelongsToRestaurant;

    protected $fillable = ['restaurant_id', 'name', 'color'];

    /** Busca por nombre (sin distinguir mayúsculas) o la crea. */
    public static function findOrCreateByName(int $restaurantId, string $name): ?self
    {
        $name = mb_substr(trim($name), 0, 60);
        if ($name === '') {
            return null;
        }
        $found = self::query()->where('restaurant_id', $restaurantId)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        return $found ?? self::query()->create(['restaurant_id' => $restaurantId, 'name' => $name]);
    }
}
