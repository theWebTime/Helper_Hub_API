<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();

            // Payment
            $table->string('razorpay_key_id')->nullable();
            $table->string('razorpay_key_secret')->nullable();

            // Fees & Charges
            $table->decimal('platform_fee_value', 10, 2)->nullable();
            $table->decimal('cancellation_charges', 10, 2)->nullable();

            // Company Info
            $table->string('company_name')->nullable();
            $table->string('logo')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Content
            $table->text('about_us')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('privacy_policy')->nullable();
            $table->text('refund_policy')->nullable();

            // Support
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();

            // Social Media
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('telegram_url')->nullable();
            $table->string('pinterest_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('snapchat_url')->nullable();
            $table->string('threads_url')->nullable(); // new Meta app

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
