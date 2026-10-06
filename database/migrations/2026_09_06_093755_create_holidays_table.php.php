<?php
// database/migrations/2026_09_06_000001_create_holidays_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if the table already exists before creating it
        if (!Schema::hasTable('holidays')) {
            Schema::create('holidays', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->string('title');
                $table->boolean('is_full_day')->default(true);
                $table->time('cutoff_time')->nullable();
                $table->unsignedBigInteger('session_id')->nullable();
                $table->unsignedBigInteger('term_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                // Only add foreign keys if the referenced tables exist
                if (Schema::hasTable('schoolsession')) {
                    $table->foreign('session_id')->references('id')->on('schoolsession')->nullOnDelete();
                }
                if (Schema::hasTable('schoolterm')) {
                    $table->foreign('term_id')->references('id')->on('schoolterm')->nullOnDelete();
                }
                if (Schema::hasTable('users')) {
                    $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                }
                $table->index('date');
            });
        } else {
            // Table already exists, just add any missing columns or foreign keys
            $this->addMissingColumnsAndForeignKeys();
        }
    }

    /**
     * Add any missing columns or foreign keys to the existing holidays table
     */
    private function addMissingColumnsAndForeignKeys(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('holidays', 'session_id')) {
                $table->unsignedBigInteger('session_id')->nullable();
            }
            if (!Schema::hasColumn('holidays', 'term_id')) {
                $table->unsignedBigInteger('term_id')->nullable();
            }
            if (!Schema::hasColumn('holidays', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
            if (!Schema::hasColumn('holidays', 'cutoff_time')) {
                $table->time('cutoff_time')->nullable();
            }
            if (!Schema::hasColumn('holidays', 'is_full_day')) {
                $table->boolean('is_full_day')->default(true);
            }
        });

        // Add foreign keys if they don't exist
        $this->addForeignKeyIfMissing('session_id', 'schoolsession');
        $this->addForeignKeyIfMissing('term_id', 'schoolterm');
        $this->addForeignKeyIfMissing('created_by', 'users');
    }

    /**
     * Add a foreign key if it doesn't exist
     */
    private function addForeignKeyIfMissing(string $column, string $referencesTable): void
    {
        try {
            // Check if the column exists
            if (!Schema::hasColumn('holidays', $column)) {
                return;
            }

            // Check if the referenced table exists
            if (!Schema::hasTable($referencesTable)) {
                return;
            }

            // Check if the foreign key already exists
            $constraintName = "holidays_{$column}_foreign";
            $exists = \DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'holidays'
                AND COLUMN_NAME = ?
                AND REFERENCED_TABLE_NAME = ?
            ", [$column, $referencesTable]);

            if (empty($exists)) {
                Schema::table('holidays', function (Blueprint $table) use ($column, $referencesTable) {
                    $table->foreign($column)->references('id')->on($referencesTable)->nullOnDelete();
                });
            }
        } catch (\Exception $e) {
            // Log error but don't fail the migration
            error_log("Could not add foreign key for {$column}: " . $e->getMessage());
        }
    }

    public function down(): void
    {
        // Only drop if it exists
        if (Schema::hasTable('holidays')) {
            Schema::dropIfExists('holidays');
        }
    }
};