<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sección de la guía de comunicación. `audience` dice a quién se le muestra.
 */
class GuideSection extends Model
{
    use BelongsToRestaurant;

    public const AUDIENCES = [
        'TODOS' => 'Para todos',
        'EQUIPO' => 'Equipo',
        'DOCENTES' => 'Profes y artistas',
        'EMPRENDEDORES' => 'Emprendedores',
    ];

    protected $fillable = ['restaurant_id', 'slug', 'title', 'summary', 'audience', 'icon', 'body', 'template_id', 'position', 'updated_by'];

    protected $casts = ['position' => 'integer'];

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Público de la guía que le toca a un rol (el equipo ve todo). */
    public static function audiencesFor(string $role): array
    {
        return match ($role) {
            User::ROLE_PROFESOR, User::ROLE_ARTISTA => ['TODOS', 'DOCENTES'],
            User::ROLE_EMPRENDEDOR => ['TODOS', 'EMPRENDEDORES'],
            User::ROLE_ALUMNO => ['TODOS'],
            default => array_keys(self::AUDIENCES),
        };
    }
}
