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
        Schema::create('sports', function (Blueprint $table) {
            $table->id();
            $table->string('sport')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('coachid');
            $table->unsignedBigInteger('termid');
            $table->unsignedBigInteger('sessionid');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('coachid')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('termid')
                  ->references('id')
                  ->on('schoolterm')
                  ->onDelete('cascade');

            $table->foreign('sessionid')
                  ->references('id')
                  ->on('schoolsession')
                  ->onDelete('cascade');

            // Indexes for better performance
            $table->index(['coachid', 'termid', 'sessionid']);
            $table->index('termid');
            $table->index('sessionid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sports');
    }
};