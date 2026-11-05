<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Errors the app hit, one row per kind (same exception class at the same place), shown on Ops > System. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_reports', function (Blueprint $table) {
            $table->id();
            $table->char('fingerprint', 40)->unique();
            $table->string('class', 200);
            $table->string('message', 500);
            $table->string('file', 300)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->string('url', 300)->nullable();
            $table->string('method', 8)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('trace')->nullable();
            $table->unsignedInteger('count')->default(1);
            // open | resolved (comes back to open if it happens again)
            $table->string('status', 10)->default('open');
            $table->timestampTz('first_seen_at');
            $table->timestampTz('last_seen_at');

            $table->index(['status', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_reports');
    }
};
