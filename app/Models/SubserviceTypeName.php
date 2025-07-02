<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubserviceTypeName extends Model
{
    protected $fillable = ['name', 'slug', 'unit_label', 'example'];
}
