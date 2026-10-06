<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timetable_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('timetable_settings', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
            if (!Schema::hasColumn('timetable_settings', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }
            if (!Schema::hasColumn('timetable_settings', 'is_published')) {
                $table->boolean('is_published')->default(false);
            }
            if (!Schema::hasColumn('timetable_settings', 'published_at')) {
                $table->timestamp('published_at')->nullable();
            }
            if (!Schema::hasColumn('timetable_settings', 'published_by')) {
                $table->unsignedBigInteger('published_by')->nullable();
            }
            if (!Schema::hasColumn('timetable_settings', 'editing_by')) {
                $table->unsignedBigInteger('editing_by')->nullable();
            }
            if (!Schema::hasColumn('timetable_settings', 'editing_at')) {
                $table->timestamp('editing_at')->nullable();
            }
        });

        // Foreign keys added separately, each guarded against already existing.
        $this->addForeignKeyIfMissing('created_by', 'users');
        $this->addForeignKeyIfMissing('updated_by', 'users');
        $this->addForeignKeyIfMissing('published_by', 'users');
        $this->addForeignKeyIfMissing('editing_by', 'users');
    }

    private function addForeignKeyIfMissing(string $column, string $referencesTable): void
    {
        $exists = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'timetable_settings'
            AND COLUMN_NAME = ?
            AND REFERENCED_TABLE_NAME = ?
        ", [$column, $referencesTable]))->isNotEmpty();

        if (!$exists) {
            Schema::table('timetable_settings', function (Blueprint $table) use ($column, $referencesTable) {
                $table->foreign($column)->references('id')->on($referencesTable)->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('timetable_settings', function (Blueprint $table) {
            foreach (['created_by', 'updated_by', 'published_by', 'editing_by'] as $col) {
                if (Schema::hasColumn('timetable_settings', $col)) {
                    try { $table->dropForeign([$col]); } catch (\Exception $e) {}
                }
            }
            foreach (['created_by', 'updated_by', 'is_published', 'published_at', 'published_by', 'editing_by', 'editing_at'] as $col) {
                if (Schema::hasColumn('timetable_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};