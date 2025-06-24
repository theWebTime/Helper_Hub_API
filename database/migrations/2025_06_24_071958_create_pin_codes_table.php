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
        Schema::create('pin_codes', function (Blueprint $table) {
            $table->id();
            $table->string('pin_code', 6)->unique();
            // $table->string('area_name');
            // $table->string('city');
            // $table->string('state');
            // $table->json('service_ids'); // Store array of service IDs available in this pincode
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pin_codes');
    }
};
