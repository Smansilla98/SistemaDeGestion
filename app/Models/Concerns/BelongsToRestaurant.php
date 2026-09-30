<?php

namespace App\Models\Concerns;

use App\Support\CurrentRestaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToRestaurant
{
    public static function bootBelongsToRestaurant(): void
    {
        static::addGlobalScope('restaurant', function (Builder $builder) {
            $id = app(CurrentRestaurant::class)->id();
            if ($id) {
                $builder->where($builder->getModel()->getTable().'.restaurant_id', $id);
            }
        });

        static::creating(function (Model $model) {
            if (! $model->getAttribute('restaurant_id')) {
                $id = app(CurrentRestaurant::class)->id();
                if ($id) {
                    $model->setAttribute('restaurant_id', $id);
                }
            }
        });
    }
}
