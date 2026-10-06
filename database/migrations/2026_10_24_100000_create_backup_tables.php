<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Database backups: a log of taken backups + a single settings row for the
 * automatic schedule and the email they are sent to.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('database_backups')) {
            Schema::create('database_backups', function (Blueprint $table) {
                $table->id();
                $table->string('filename');
                $table->string('path');
                $table->string('disk', 30)->default('local');
                $table->unsignedBigInteger('size')->default(0);   // bytes
                $table->string('type', 12)->default('manual');    // manual | scheduled
                $table->string('status', 12)->default('success'); // success | failed
                $table->string('method', 20)->nullable();         // mysqldump | php
                $table->string('emailed_to')->nullable();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }

        if (!Schema::hasTable('backup_settings')) {
            Schema::create('backup_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('enabled')->default(false);
                $table->string('frequency', 10)->default('daily'); // daily | weekly | monthly
                $table->unsignedTinyInteger('day_of_week')->default(1); // 0=Sun..6=Sat (weekly)
                $table->unsignedTinyInteger('day_of_month')->default(1); // (monthly)
                $table->string('run_time', 5)->default('02:00');   // HH:MM
                $table->string('email')->nullable();               // where to send
                $table->boolean('email_attach')->default(true);    // attach the file when small enough
                $table->unsignedSmallInteger('keep_last')->default(14); // how many to retain
                $table->timestamp('last_run_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
            DB::table('backup_settings')->insert([
                'enabled' => false, 'frequency' => 'daily', 'run_time' => '02:00',
                'keep_last' => 14, 'email_attach' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_settings');
        Schema::dropIfExists('database_backups');
    }
};
