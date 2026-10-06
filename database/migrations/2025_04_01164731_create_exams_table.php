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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('staffId');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('duration');
            $table->dateTime('start_time'); // Change from timestamp to datetime
            $table->dateTime('end_time');   // Change from timestamp to datetime
            $table->string('termid')->nullable();
            $table->string('session')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::dropIfExists('exams');
    }
};
