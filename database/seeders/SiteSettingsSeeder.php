<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('site_settings')->insert([
            'razorpay_key_id' => 'rzp_test_ABC123',
            'razorpay_key_secret' => 'secret_key_123',
            'platform_fee_value' => 10.00,
            'cancellation_charges' => 20.00,
            'company_name' => 'HelperHub Pvt Ltd',
            'logo' => 'uploads/logo.png',
            'address' => '101, Service Street, Mumbai, India',
            'phone' => '+91-9876543210',
            'email' => 'info@helperhub.com',
            'about_us' => 'HelperHub connects you with trusted professionals for your daily needs.',
            'terms_conditions' => 'All users must agree to the terms and conditions.',
            'privacy_policy' => 'We respect your privacy and protect your data.',
            'refund_policy' => 'Refunds are applicable as per the service terms.',
            'support_email' => 'support@helperhub.com',
            'support_phone' => '+91-9876543211',
            'facebook_url' => 'https://facebook.com/helperhub',
            'instagram_url' => 'https://instagram.com/helperhub',
            'twitter_url' => 'https://twitter.com/helperhub',
            'linkedin_url' => 'https://linkedin.com/company/helperhub',
            'youtube_url' => 'https://youtube.com/@helperhub',
            'whatsapp_number' => '+91-9876543212',
            'telegram_url' => 'https://t.me/helperhub',
            'pinterest_url' => 'https://pinterest.com/helperhub',
            'tiktok_url' => 'https://tiktok.com/@helperhub',
            'snapchat_url' => 'https://snapchat.com/add/helperhub',
            'threads_url' => 'https://threads.net/@helperhub',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
