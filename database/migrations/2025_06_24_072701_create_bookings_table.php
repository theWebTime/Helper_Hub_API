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
         Schema::create('bookings', function (Blueprint $table) {
             $table->id();
             $table->string('booking_number')->unique();
             $table->foreignId('user_id')->constrained()->onDelete('cascade');
             $table->foreignId('service_id')->constrained();
             $table->foreignId('subservice_id')->constrained();
             $table->foreignId('pin_code_id')->constrained();
             
             // Customer details
             $table->string('customer_name');
             $table->string('customer_mobile');
             $table->text('customer_address');
             
             // Booking details
             $table->decimal('service_price', 10, 2);
             $table->decimal('platform_fee', 10, 2)->default(0);
             $table->decimal('total_amount', 10, 2);
             $table->datetime('preferred_date');
             $table->string('preferred_time')->nullable();
             $table->text('special_instructions')->nullable();
             
             // Payment details
             $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
             $table->string('payment_id')->nullable();
             $table->string('payment_method')->nullable();
             $table->datetime('payment_date')->nullable();
             
             // Booking status
             $table->enum('booking_status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
             $table->text('cancellation_reason')->nullable();
             $table->datetime('cancelled_at')->nullable();
             
             $table->timestamps();
             $table->softDeletes();
         });
     }
 
     public function down()
     {
         Schema::dropIfExists('bookings');
     }
};
