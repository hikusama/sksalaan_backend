<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('youth_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('youth_user_id')->constrained()->cascadeOnDelete();
            $table->string('fname');
            $table->string('mname');
            $table->string('lname');
            $table->integer('age');
            $table->string('address');
            $table->timestamp('dateOfBirth');
            $table->string('placeOfBirth');
            $table->integer('height');
            $table->integer('weight');
            $table->string('religion');
            $table->string('occupation');
            $table->string('sex');
            $table->string('civilStatus')->nullable();
            $table->string('gender')->nullable();
            $table->integer('noOfChildren')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youth_infos');
    }
};
