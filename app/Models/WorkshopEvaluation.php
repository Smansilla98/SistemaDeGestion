<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkshopEvaluation extends Model
{
    protected $fillable = ['workshop_id', 'contact_id', 'title', 'grade', 'comment', 'evaluated_on', 'recorded_by'];

    protected $casts = ['evaluated_on' => 'date'];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
