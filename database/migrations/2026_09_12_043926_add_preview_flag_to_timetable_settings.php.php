<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timetable_settings', function (Blueprint $table) {
            $table->boolean('is_preview')->default(false)->after('is_active');
            $table->timestamp('preview_expires_at')->nullable()->after('is_preview');
            $table->index(['is_preview', 'preview_expires_at'], 'tsp_preview_idx');
        });
    }

    public function down(): void
    {
        Schema::table('timetable_settings', function (Blueprint $table) {
            $table->dropIndex('tsp_preview_idx');
            $table->dropColumn(['is_preview', 'preview_expires_at']);
        });
    }
};