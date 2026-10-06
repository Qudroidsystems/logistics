<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sportid');
            $table->string('team_name');
            $table->unsignedBigInteger('captainid')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('sportid')->references('id')->on('sports')->onDelete('cascade');
            $table->foreign('captainid')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_teams');
    }
};