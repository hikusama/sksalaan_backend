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
        Schema::create('educ_b_g_s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('youth_user_id')->constrained()->cascadeOnDelete();
            $table->string('level')->nullable();
            $table->string('nameOfSchool')->nullable();
            $table->date('periodOfAttendance')->nullable();
            $table->year('yearGraduate')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educ_b_g_s');
    }
};
