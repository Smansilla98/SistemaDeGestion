<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;

class ConsignmentSettlement extends Model
{
    use BelongsToRestaurant;

    protected $fillable = ['restaurant_id', 'consignment_id', 'gross_cents', 'commission_cents', 'paid_cents', 'paid_on', 'method', 'note', 'created_by'];

    protected $casts = ['paid_on' => 'date', 'gross_cents' => 'integer', 'commission_cents' => 'integer', 'paid_cents' => 'integer'];
}
