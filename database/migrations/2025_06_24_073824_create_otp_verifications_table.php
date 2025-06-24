<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    
    /**
     * Reverse the migrations.
     */
    public function up()
    {
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('mobile');
            $table->string('otp');
            $table->enum('type', ['registration', 'login', 'forgot_password']);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['mobile', 'otp', 'type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('otp_verifications');
    }
};
