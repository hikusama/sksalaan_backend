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
            $table->string('smsStatus')->default('pending');
            $table->string('webStatus')->default('pending');
            $table->dateTime('when');
            $table->string('where');
            $table->string('who');
            $table->string('what');
            $table->string('addresses');
            $table->foreignId('registration_cycle_id')->nullable()->constrained()->cascadeOnDelete();
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
