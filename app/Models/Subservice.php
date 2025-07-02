<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subservice extends Model
{
    protected $fillable = ['service_id', 'type_slugs', 'name', 'description', 'image', 'status'];
}
