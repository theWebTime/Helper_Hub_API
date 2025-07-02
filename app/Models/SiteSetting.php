<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['razorpay_key_id', 'razorpay_key_secret', 'platform_fee_value', 'cancellation_charges', 'company_name', 'logo', 'address', 'phone', 'email', 'about_us', 'terms_conditions', 'privacy_policy', 'refund_policy', 'support_email', 'support_phone', 'facebook_url', 'instagram_url', 'twitter_url', 'linkedin_url', 'youtube_url', 'whatsapp_number', 'telegram_url', 'pinterest_url', 'tiktok_url', 'snapchat_url', 'threads_url'];
}
