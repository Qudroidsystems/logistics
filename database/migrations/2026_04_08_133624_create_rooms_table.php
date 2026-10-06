<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_code', 50)->unique();
            $table->string('room_name', 100);
            $table->enum('type', ['classroom', 'laboratory', 'auditorium', 'library', 'sports', 'other'])->default('classroom');
            $table->integer('capacity')->default(30);
            $table->json('facilities')->nullable(); // Remove default
            $table->string('building', 100)->nullable();
            $table->string('floor', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
