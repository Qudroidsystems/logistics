<?php
// database/migrations/2026_09_06_093711_add_publish_fields_to_timetable_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Safe to run on databases where some or all of these columns were already
 * added (e.g. by hand): each column and the foreign key are only created
 * when missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('timetable_settings')) {
            return;
        }

        Schema::table('timetable_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('timetable_settings', 'is_published')) {
                $table->boolean('is_published')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('timetable_settings', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('is_published');
            }
            if (!Schema::hasColumn('timetable_settings', 'published_by')) {
                $table->unsignedBigInteger('published_by')->nullable()->after('published_at');
            }
        });

        if (!$this->hasForeignKey('timetable_settings', 'published_by')) {
            Schema::table('timetable_settings', function (Blueprint $table) {
                $table->foreign('published_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('timetable_settings')) {
            return;
        }

        if ($this->hasForeignKey('timetable_settings', 'published_by')) {
            Schema::table('timetable_settings', function (Blueprint $table) {
                $table->dropForeign(['published_by']);
            });
        }

        $drop = array_values(array_filter(
            ['is_published', 'published_at', 'published_by'],
            fn ($c) => Schema::hasColumn('timetable_settings', $c)
        ));
        if ($drop) {
            Schema::table('timetable_settings', function (Blueprint $table) use ($drop) {
                $table->dropColumn($drop);
            });
        }
    }

    /** Is there already a foreign key on this column? */
    private function hasForeignKey(string $table, string $column): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }
};
