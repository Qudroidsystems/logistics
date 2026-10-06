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
        Schema::create('job_progress', function (Blueprint $table) {
        $table->id();
        $table->string('job_id')->unique();
        $table->integer('total_operations');
        $table->integer('completed_operations')->default(0);
        $table->string('status')->default('pending');
        $table->text('errors')->nullable();
        $table->timestamps();
       });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_progress');
    }
};
