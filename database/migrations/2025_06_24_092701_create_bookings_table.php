<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            /* ───── Identifier ───── */
            $table->string('booking_number')->unique(); // e.g. #HH20250001

            /* ───── Foreign Keys ───── */
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained(); // 1 = one-time, 2 = monthly
            $table->foreignId('subservice_id')->constrained();
            $table->foreignId('user_address_id')->constrained('user_addresses')->onDelete('cascade');

            /* 🔄 Removed subservice_type_detail_id — now handled via pivot */

            /* ───── Pricing ───── */
            $table->decimal('service_price', 10, 2);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);

            /* 🔄 Schedule Split */
            $table->date('schedule_date')->comment('Date of booking');
            $table->time('schedule_time')->comment('Time of visit');

            $table->date('schedule_end_date')->nullable()
                ->comment('Only for monthly bookings: end date');

            /* Pet & Special instructions */
            $table->boolean('is_dog')->default(false);
            $table->text('special_instructions')->nullable();

            /* ───── Payment ───── */
            $table->unsignedTinyInteger('payment_status')->default(1);
            $table->string('payment_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_order_id')->nullable();
            $table->dateTime('payment_date')->nullable();

            /* ───── Booking Status ───── */
            $table->unsignedTinyInteger('booking_status')->default(1);
            $table->text('cancellation_reason')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // 🔄 NEW: Pivot table for subservice_type_details
        Schema::create('booking_subservice_type_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('subservice_type_detail_id')->constrained()->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('booking_subservice_type_detail');
        Schema::dropIfExists('bookings');
    }
};
