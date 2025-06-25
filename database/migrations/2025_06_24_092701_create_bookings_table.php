<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Unique booking number (ex: #HH20250001)
            $table->string('booking_number')->unique();

            // Relationships
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained();
            $table->foreignId('subservice_id')->constrained();
            $table->foreignId('subservice_type_detail_id')->constrained('subservice_type_details')->onDelete('cascade');

            $table->foreignId('pin_code_id')->constrained(); // only allowed pincodes

            // Customer Info
            $table->string('customer_name');
            $table->string('customer_mobile');
            $table->text('customer_address');

            // Booking / pricing
            $table->decimal('service_price', 10, 2); // from subservice detail
            $table->decimal('platform_fee', 10, 2)->default(0); // optional platform commission
            $table->decimal('total_amount', 10, 2); // service_price + platform_fee

            // Schedule
            $table->dateTime('preferred_date');
            $table->string('preferred_time')->nullable();
            $table->text('special_instructions')->nullable();

            // Payment Info
            $table->unsignedTinyInteger('payment_status')->default(1)->comment('1=Pending, 2=Paid, 3=Failed, 4=Refunded');
            $table->string('payment_id')->nullable(); // gateway ID
            $table->string('payment_method')->nullable(); // UPI, card, etc.
            $table->dateTime('payment_date')->nullable();

            // Booking Status
            $table->unsignedTinyInteger('booking_status')->default(1)->comment('1=Pending, 2=Confirmed, 3=In Progress, 4=Completed, 5=Cancelled');
            $table->text('cancellation_reason')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};
