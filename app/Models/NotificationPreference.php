<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'category', 'in_app', 'email'];

    protected $casts = ['in_app' => 'boolean', 'email' => 'boolean'];
}
