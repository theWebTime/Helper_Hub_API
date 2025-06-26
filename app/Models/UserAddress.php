<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'pin_code_id',
        'type',
        'title',
        'name',
        'phone',
        'address',
        'landmark',
        'is_default',
    ];
}
