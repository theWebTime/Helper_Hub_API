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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('pin_code_id')->constrained('pin_codes')->onDelete('cascade');

            $table->enum('type', ['home', 'office', 'other'])->default('home');
            $table->string('title')->nullable();    // Optional: "Main Office", "Nani's home"
            $table->string('name')->nullable();     // Optional: Delivery contact
            $table->string('phone')->nullable();    // Optional: Delivery contact

            $table->text('address');
            $table->string('landmark')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_addresses');
    }

};
