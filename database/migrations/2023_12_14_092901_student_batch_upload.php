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
        Schema::create('student_batch_upload', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('studentid')->nullable();
            $table->string('schoolclassid')->nullable();
            $table->string('termid')->nullable();
            $table->string('session')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_batch_upload');
    }
};
