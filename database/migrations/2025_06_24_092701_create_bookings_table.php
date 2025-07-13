<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            /* ─────  Identifier  ───── */
            $table->string('booking_number')->unique();              // ex: #HH20250001

            /* ─────  Foreign Keys  ───── */
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained();          // 1 = one‑time, 2 = monthly
            $table->foreignId('subservice_id')->constrained();
            $table->foreignId('subservice_type_detail_id')
                ->constrained('subservice_type_details')
                ->onDelete('cascade');

            $table->foreignId('user_address_id')                     // chosen address
                ->constrained('user_addresses')
                ->onDelete('cascade');

            /* ─────  Pricing  ───── */
            $table->decimal('service_price', 10, 2);                 // base price from detail
            $table->decimal('platform_fee',  10, 2)->default(0);     // your commission
            $table->decimal('total_amount',  10, 2);                 // service_price + platform_fee

            /* ─────  Schedule  ─────
             * service_id = 1 → one‑time:
             *     schedule_start = exact visit datetime; schedule_end = NULL
             * service_id = 2 → monthly:
             *     schedule_start = user‑selected start datetime
             *     schedule_end   = schedule_start + 1 month
             */
            $table->dateTime('schedule_start')
                ->comment('One‑time: exact datetime  •  Monthly: window start');
            $table->dateTime('schedule_end')->nullable()
                ->comment('NULL for one‑time  •  Monthly: schedule_start + 1 month');

            /* NEW: Pet information */
            $table->boolean('is_dog')->default(false)
                ->comment('Customer indicates a dog is present on site (true/false)');
                
            $table->text('special_instructions')->nullable();

            /* ─────  Payment  ───── */
            $table->unsignedTinyInteger('payment_status')->default(1)
                ->comment('1=Pending, 2=Paid, 3=Failed, 4=Refunded');
            $table->string('payment_id')->nullable();                // gateway txn/reference
            $table->string('payment_method')->nullable();            // UPI, Card, etc.
            $table->string('payment_order_id')->nullable();          // Razorpay/Stripe order
            $table->dateTime('payment_date')->nullable();

            /* ─────  Booking Status  ───── */
            $table->unsignedTinyInteger('booking_status')->default(1)
                ->comment('1=Pending, 2=Confirmed, 3=In Progress, 4=Completed, 5=Cancelled');
            $table->text('cancellation_reason')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
