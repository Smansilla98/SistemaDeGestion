<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkshopEnrollment extends Model
{
    public const ACTIVA = 'ACTIVA';

    public const EN_ESPERA = 'EN_ESPERA';

    public const CANCELADA = 'CANCELADA';

    public const FINALIZADA = 'FINALIZADA';

    public const LABELS = [self::ACTIVA => 'Activa', self::EN_ESPERA => 'Lista de espera', self::CANCELADA => 'Cancelada', self::FINALIZADA => 'Finalizada'];

    protected $fillable = ['workshop_id', 'contact_id', 'status', 'waitlist_position', 'enrolled_at', 'cancelled_at', 'note'];

    protected $casts = ['enrolled_at' => 'datetime', 'cancelled_at' => 'datetime', 'waitlist_position' => 'integer'];

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
