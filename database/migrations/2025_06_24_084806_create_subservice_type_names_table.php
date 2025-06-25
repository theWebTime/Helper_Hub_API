<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('subservice_type_names', function (Blueprint $table) {
            $table->id();
            $table->string('name');        // Display name e.g., "BHK"
            $table->string('slug')->unique(); // e.g., "bhk"
            $table->string('unit_label')->nullable(); // e.g., "Hour"
            $table->string('example')->nullable(); // Add this line
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('subservice_type_names');
    }
};
