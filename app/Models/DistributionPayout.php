<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;

class DistributionPayout extends Model
{
    use BelongsToRestaurant;

    protected $fillable = ['restaurant_id', 'payoutable_type', 'payoutable_id', 'party', 'amount_cents', 'paid_on', 'note', 'recorded_by', 'voided_at'];

    protected $casts = ['paid_on' => 'date', 'amount_cents' => 'integer', 'voided_at' => 'datetime'];
}
