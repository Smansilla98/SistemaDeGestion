<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Comunicación de difusión a un segmento de contactos (sólo a quienes aceptaron recibirlas).
 */
class Campaign extends Model
{
    use BelongsToRestaurant;

    protected $fillable = ['restaurant_id', 'created_by', 'title', 'body', 'link', 'audience', 'send_email', 'status', 'sent_at', 'recipients_count'];

    protected $casts = ['audience' => 'array', 'send_email' => 'boolean', 'sent_at' => 'datetime'];

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
