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
        Schema::create('civic_involvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('youth_user_id')->constrained()->cascadeOnDelete();
            $table->string('nameOfOrganization')->nullable();
            $table->string('addressOfOrganization')->nullable();
            $table->date('start')->nullable();
            $table->date('end')->nullable();
            $table->year('yearGraduated')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('civic_involvements');
    }
};
