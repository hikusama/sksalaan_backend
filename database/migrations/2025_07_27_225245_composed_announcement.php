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
        Schema::create('composed_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('pending');
            $table->dateTime('when');
            $table->string('where');
            $table->string('what');
            $table->string('addresses');
            $table->string('cycle');
            $table->string('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('composed_announcements');
    }
};
