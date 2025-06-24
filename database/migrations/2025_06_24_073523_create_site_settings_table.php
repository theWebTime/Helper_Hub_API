<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, image, json, number
            $table->timestamps();
        });
//         // Payment
// 'razorpay_key_id', 'razorpay_key_secret', 'razorpay_mode'

// // Fees & Charges  
// 'platform_fee_type', 'platform_fee_value', 'cancellation_charges'

// // Company Info
// 'company_name', 'logo', 'address', 'phone', 'email'

// // Social Media
// 'facebook_url', 'instagram_url', 'twitter_url', 'linkedin_url'

// // Content
// 'about_us'
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
