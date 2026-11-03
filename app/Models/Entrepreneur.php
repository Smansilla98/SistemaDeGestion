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

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<EntrepreneurGroup, $this> */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(EntrepreneurGroup::class, 'entrepreneur_group_member');
    }

    /** @return HasMany<MarketProduct, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(MarketProduct::class);
    }

    /** @return HasMany<Consignment, $this> */
    public function consignments(): HasMany
    {
        return $this->hasMany(Consignment::class);
    }
}
