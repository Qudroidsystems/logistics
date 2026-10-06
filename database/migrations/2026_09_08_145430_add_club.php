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
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->string('club')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('patronid');
            $table->unsignedBigInteger('termid');
            $table->unsignedBigInteger('sessionid');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('patronid')
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
            $table->index(['patronid', 'termid', 'sessionid']);
            $table->index('termid');
            $table->index('sessionid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};