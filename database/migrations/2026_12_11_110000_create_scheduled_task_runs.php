<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The last run of each scheduled command, recorded by SystemHealth, so staff can see (and are told) when background work stops. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_task_runs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->timestampTz('last_started_at')->nullable();
            $table->timestampTz('last_finished_at')->nullable();
            // ok | failed | running
            $table->string('last_status', 8)->nullable();
            $table->string('last_error', 500)->nullable();
            $table->unsignedInteger('runtime_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_task_runs');
    }
};
