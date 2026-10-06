<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timetable_generation_runs', function (Blueprint $table) {
            $table->timestamp('last_restored_at')->nullable()->after('seed');
            $table->foreignId('last_restored_by')
                  ->nullable()
                  ->after('last_restored_at')
                  ->constrained('users')
                  ->nullOnDelete();

            $table->unsignedInteger('restore_count')->default(0)->after('last_restored_by');

            $table->timestamp('reverted_at')->nullable()->after('restore_count');
            $table->foreignId('reverted_by')
                  ->nullable()
                  ->after('reverted_at')
                  ->constrained('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('timetable_generation_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_restored_by');
            $table->dropConstrainedForeignId('reverted_by');
            $table->dropColumn(['last_restored_at', 'restore_count', 'reverted_at']);
        });
    }
};