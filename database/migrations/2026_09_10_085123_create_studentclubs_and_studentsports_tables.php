<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors the shape of studenthouses (single-column primary key on
     * studentid, so a student has at most one club/sport at a time,
     * overwritten across terms) so Club and Sport behave identically to
     * House in the student form.
     */
    public function up(): void
    {
        Schema::create('studentclubs', function (Blueprint $table) {
            $table->unsignedBigInteger('studentid')->primary();
            $table->unsignedBigInteger('clubid')->nullable();
            $table->unsignedBigInteger('termid')->nullable();
            $table->unsignedBigInteger('sessionid')->nullable();
            $table->timestamps();
        });

        Schema::create('studentsports', function (Blueprint $table) {
            $table->unsignedBigInteger('studentid')->primary();
            $table->unsignedBigInteger('sportid')->nullable();
            $table->unsignedBigInteger('termid')->nullable();
            $table->unsignedBigInteger('sessionid')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studentclubs');
        Schema::dropIfExists('studentsports');
    }
};