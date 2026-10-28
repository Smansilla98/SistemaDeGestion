<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entrepreneur extends Model
{
    use BelongsToRestaurant;

    protected $fillable = ['restaurant_id', 'contact_id', 'user_id', 'business_name', 'description', 'payment_alias', 'brand', 'is_active'];

    protected $casts = ['brand' => 'array', 'is_active' => 'boolean'];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(EntrepreneurGroup::class, 'entrepreneur_group_member');
    }

    public function products(): HasMany
    {
        return $this->hasMany(MarketProduct::class);
    }

    public function consignments(): HasMany
    {
        return $this->hasMany(Consignment::class);
    }
}
