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
        Schema::create('job_supports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('youth_user_id')->constrained()->cascadeOnDelete();
            $table->string('task');
            $table->integer('paid_at');
            $table->string('location');
            $table->date('start')->nullable();
            $table->date('end')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_supports');
    }
};
