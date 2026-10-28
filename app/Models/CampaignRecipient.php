<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignRecipient extends Model
{
    protected $fillable = ['campaign_id', 'contact_id', 'channels'];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
