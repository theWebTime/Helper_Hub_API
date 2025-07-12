<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'booking_number',
        'user_id',
        'service_id',
        'subservice_id',
        'subservice_type_detail_id',
        'pin_code_id',
        'customer_name',
        'customer_mobile',
        'customer_address',
        'service_price',
        'platform_fee',
        'total_amount',
        'preferred_date',
        'preferred_time',
        'special_instructions',
        'payment_status',
        'payment_id',
        'payment_method',
        'payment_order_id',
        'payment_date',
        'booking_status',
        'cancellation_reason',
        'cancelled_at',
    ];
}