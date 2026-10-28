<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkshopAttendance extends Model
{
    public const STATUSES = ['PRESENTE' => 'Presente', 'TARDE' => 'Tarde', 'AUSENTE' => 'Ausente', 'JUSTIFICADA' => 'Justificada'];

    protected $fillable = ['workshop_session_id', 'contact_id', 'status', 'recorded_by'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(WorkshopSession::class, 'workshop_session_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
