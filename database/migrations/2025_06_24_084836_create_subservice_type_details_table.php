<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('subservice_type_details', function (Blueprint $table) {
            $table->id();
            $table->string('subservice_type_name_slug'); // e.g., "bhk"
            $table->string('label'); // e.g., "1 BHK"
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->foreign('subservice_type_name_slug')
                ->references('slug')
                ->on('subservice_type_names')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subservice_type_details');
    }
};
