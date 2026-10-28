<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;

/**
 * Fecha especial, aviso institucional, reunión, feria o entrega cargada a mano en la agenda.
 */
class AgendaNotice extends Model
{
    use BelongsToRestaurant;

    public const KINDS = ['AVISO' => 'Aviso institucional', 'FECHA_ESPECIAL' => 'Fecha especial', 'REUNION' => 'Reunión',
        'FERIA' => 'Feria', 'ENTREGA' => 'Entrega de productos', 'RECORDATORIO' => 'Recordatorio'];

    protected $fillable = ['restaurant_id', 'kind', 'title', 'body', 'date', 'end_date', 'time', 'created_by', 'activity_category_id'];

    protected $casts = ['date' => 'date', 'end_date' => 'date'];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ActivityCategory::class, 'activity_category_id');
    }
}
