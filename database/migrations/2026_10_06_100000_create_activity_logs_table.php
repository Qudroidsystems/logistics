<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Staff activity log (logins, logouts, failed logins, every change) + "last seen" for who's online. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('event', 20)->index();          // login | logout | login_failed | create | update | delete | action
                $table->string('description', 255);
                $table->string('route', 150)->nullable();
                $table->string('method', 8)->nullable();
                $table->string('url', 500)->nullable();
                $table->unsignedSmallInteger('status')->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('device', 80)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->json('properties')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'last_seen_at')) $table->timestamp('last_seen_at')->nullable()->index();
            if (!Schema::hasColumn('users', 'last_seen_url')) $table->string('last_seen_url', 255)->nullable();
            if (!Schema::hasColumn('users', 'last_login_at')) $table->timestamp('last_login_at')->nullable();
            if (!Schema::hasColumn('users', 'last_login_ip')) $table->string('last_login_ip', 45)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
