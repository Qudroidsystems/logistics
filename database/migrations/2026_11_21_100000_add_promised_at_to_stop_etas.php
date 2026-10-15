<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The first arrival time we showed once the rider set off. due_at moves forward after each delay notice; promised_at
 * never does, so on-time rates compare the rider against what the receiver was first told.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stop_etas', function (Blueprint $table) {
            $table->timestampTz('promised_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('stop_etas', fn (Blueprint $table) => $table->dropColumn('promised_at'));
    }
};
