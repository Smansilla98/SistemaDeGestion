<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EntrepreneurGroup extends Model
{
    use BelongsToRestaurant;

    protected $fillable = ['restaurant_id', 'name', 'description'];

    public function entrepreneurs(): BelongsToMany
    {
        return $this->belongsToMany(Entrepreneur::class, 'entrepreneur_group_member');
    }
}
