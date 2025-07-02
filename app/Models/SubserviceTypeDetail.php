<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubserviceTypeDetail extends Model
{
    protected $fillable = ['subservice_type_name_slug', 'label', 'price'];
}
