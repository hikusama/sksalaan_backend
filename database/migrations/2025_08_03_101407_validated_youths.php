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
        Schema::create('validated_youths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('youth_user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('registration_cycle_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('validated_youths');
    }
};
