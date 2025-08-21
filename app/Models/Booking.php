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
        'user_address_id',
        'service_price',
        'platform_fee',
        'total_amount',
        'schedule_date',
        'schedule_time',
        'schedule_end_date',
        'is_dog',
        'gender',
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

    public function subserviceTypeDetails(array $details = [])
{
    // Manually insert into pivot table
    if (!empty($details)) {
        foreach ($details as $detailId) {
            \DB::table('booking_subservice_type_detail')->insert([
                'booking_id' => $this->id,
                'subservice_type_detail_id' => $detailId,
            ]);
        }
    }
}
}