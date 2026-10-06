<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Create timetable_settings table
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('timetable_settings')) {

            Schema::create('timetable_settings', function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger('schoolclass_id');
                $table->unsignedBigInteger('session_id');
                $table->unsignedBigInteger('term_id')->nullable();

                $table->time('school_day_start')
                    ->default('08:00:00');

                $table->time('school_day_end')
                    ->default('14:30:00');

                $table->unsignedSmallInteger('period_duration_minutes')
                    ->default(40);

                $table->unsignedSmallInteger('short_break_duration_minutes')
                    ->default(20);

                $table->unsignedSmallInteger('long_break_duration_minutes')
                    ->default(40);

                $table->boolean('is_active')
                    ->default(true);

                $table->json('active_days')
                    ->nullable();

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index([
                    'schoolclass_id',
                    'session_id',
                    'term_id'
                ]);

                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                if (Schema::hasTable('schoolclass')) {
                    $table->foreign('schoolclass_id')
                        ->references('id')
                        ->on('schoolclass')
                        ->onDelete('cascade');
                }

                if (Schema::hasTable('schoolsession')) {
                    $table->foreign('session_id')
                        ->references('id')
                        ->on('schoolsession')
                        ->onDelete('cascade');
                }

                if (Schema::hasTable('schoolterm')) {
                    $table->foreign('term_id')
                        ->references('id')
                        ->on('schoolterm')
                        ->onDelete('cascade');
                }
            });

            info('Table "timetable_settings" created successfully.');

        } else {

            info('Table "timetable_settings" already exists. Skipping creation.');
        }


        /*
        |--------------------------------------------------------------------------
        | Add foreign keys to dependent timetable tables
        |--------------------------------------------------------------------------
        */

        $this->addForeignKeysToDependentTables();
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Remove foreign keys from dependent tables first
        |--------------------------------------------------------------------------
        */

        $dependentTables = [
            'timetable_constraints'      => 'setting_id',
            'timetable_periods'         => 'setting_id',
            'timetable_slots'           => 'setting_id',
            'timetable_overrides'       => 'setting_id',
            'timetable_change_requests' => 'setting_id',
        ];

        foreach ($dependentTables as $tableName => $columnName) {

            if (!Schema::hasTable($tableName)) {
                continue;
            }

            if (!Schema::hasColumn($tableName, $columnName)) {
                continue;
            }

            try {

                $foreignKeyName = $tableName . '_' . $columnName . '_foreign';

                $foreignKeys = $this->getTableForeignKeys($tableName);

                if (in_array($foreignKeyName, $foreignKeys)) {

                    Schema::table($tableName, function (Blueprint $table) use ($foreignKeyName) {
                        $table->dropForeign($foreignKeyName);
                    });

                    info(
                        "Removed foreign key from {$tableName}.{$columnName}"
                    );
                }

            } catch (\Throwable $e) {

                warning(
                    "Could not remove foreign key from {$tableName}.{$columnName}: "
                    . $e->getMessage()
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Drop timetable_settings
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('timetable_settings')) {

            Schema::dropIfExists('timetable_settings');

            info('Table "timetable_settings" dropped successfully.');
        }
    }


    /**
     * Add foreign keys to all dependent tables.
     */
    private function addForeignKeysToDependentTables(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Make sure timetable_settings exists
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('timetable_settings')) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Dependent tables
        |--------------------------------------------------------------------------
        */

        $dependentTables = [
            'timetable_constraints'      => 'setting_id',
            'timetable_periods'         => 'setting_id',
            'timetable_slots'           => 'setting_id',
            'timetable_overrides'       => 'setting_id',
            'timetable_change_requests' => 'setting_id',
        ];


        /*
        |--------------------------------------------------------------------------
        | Process each table
        |--------------------------------------------------------------------------
        */

        foreach ($dependentTables as $tableName => $columnName) {

            /*
            |--------------------------------------------------------------------------
            | Check table exists
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasTable($tableName)) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Check column exists
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn($tableName, $columnName)) {
                continue;
            }


            try {

                /*
                |--------------------------------------------------------------------------
                | Foreign key name
                |--------------------------------------------------------------------------
                */

                $foreignKeyName = $tableName . '_' . $columnName . '_foreign';


                /*
                |--------------------------------------------------------------------------
                | Check if foreign key already exists
                |--------------------------------------------------------------------------
                */

                $foreignKeys = $this->getTableForeignKeys($tableName);


                if (in_array($foreignKeyName, $foreignKeys)) {

                    info(
                        "Foreign key {$foreignKeyName} already exists. Skipping."
                    );

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Add foreign key
                |--------------------------------------------------------------------------
                */

                Schema::table($tableName, function (Blueprint $table) use ($columnName) {

                    $table->foreign($columnName)
                        ->references('id')
                        ->on('timetable_settings')
                        ->onDelete('cascade');

                });


                info(
                    "Added foreign key for {$tableName}.{$columnName}"
                );

            } catch (\Throwable $e) {

                warning(
                    "Could not add foreign key to {$tableName}.{$columnName}: "
                    . $e->getMessage()
                );
            }
        }
    }


    /**
     * Get all foreign key names for a table.
     */
    private function getTableForeignKeys(string $tableName): array
    {
        /*
        |--------------------------------------------------------------------------
        | Get database name
        |--------------------------------------------------------------------------
        */

        $database = config('database.connections.mysql.database');


        try {

            $results = DB::select(
                "
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
                ",
                [
                    $database,
                    $tableName
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | Return foreign key names
            |--------------------------------------------------------------------------
            */

            return array_map(
                function ($row) {
                    return $row->CONSTRAINT_NAME;
                },
                $results
            );

        } catch (\Throwable $e) {

            warning(
                "Could not retrieve foreign keys for {$tableName}: "
                . $e->getMessage()
            );

            return [];
        }
    }
};