<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSourceRow extends Model
{
    protected $fillable = ['form_source_id', 'row_hash', 'data', 'status', 'message', 'contact_id'];

    protected $casts = ['data' => 'array'];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
