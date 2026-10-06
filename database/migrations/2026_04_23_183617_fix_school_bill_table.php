<?php
// database/migrations/2026_04_23_183617_fix_school_bill_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================
        // PART 1: Add missing columns to school_bill
        // ============================================
        if (Schema::hasTable('school_bill')) {
            Schema::table('school_bill', function (Blueprint $table) {
                if (!Schema::hasColumn('school_bill', 'statusId')) {
                    $table->unsignedBigInteger('statusId')->default(1)->after('bill_amount');
                }
                if (!Schema::hasColumn('school_bill', 'effective_from')) {
                    $table->date('effective_from')->nullable()->after('statusId');
                }
                if (!Schema::hasColumn('school_bill', 'effective_to')) {
                    $table->date('effective_to')->nullable()->after('effective_from');
                }
                if (!Schema::hasColumn('school_bill', 'is_mandatory')) {
                    $table->boolean('is_mandatory')->default(true)->after('effective_to');
                }
                if (!Schema::hasColumn('school_bill', 'due_date')) {
                    $table->date('due_date')->nullable()->after('is_mandatory');
                }
                if (!Schema::hasColumn('school_bill', 'late_fee')) {
                    $table->decimal('late_fee', 15, 2)->default(0)->after('due_date');
                }
                if (!Schema::hasColumn('school_bill', 'late_fee_type')) {
                    $table->enum('late_fee_type', ['fixed', 'percentage'])->default('fixed')->after('late_fee');
                }
                if (!Schema::hasColumn('school_bill', 'payment_frequency')) {
                    $table->enum('payment_frequency', ['one_time', 'termly', 'monthly'])->default('one_time')->after('late_fee_type');
                }
                if (!Schema::hasColumn('school_bill', 'grace_period_days')) {
                    $table->integer('grace_period_days')->default(0)->after('payment_frequency');
                }
                if (!Schema::hasColumn('school_bill', 'is_scholarship_eligible')) {
                    $table->boolean('is_scholarship_eligible')->default(true)->after('grace_period_days');
                }
                if (!Schema::hasColumn('school_bill', 'is_discount_eligible')) {
                    $table->boolean('is_discount_eligible')->default(true)->after('is_scholarship_eligible');
                }
                if (!Schema::hasColumn('school_bill', 'max_discount_percentage')) {
                    $table->decimal('max_discount_percentage', 5, 2)->nullable()->after('is_discount_eligible');
                }
                if (!Schema::hasColumn('school_bill', 'category')) {
                    $table->string('category')->nullable()->after('max_discount_percentage');
                }
                if (!Schema::hasColumn('school_bill', 'priority')) {
                    $table->enum('priority', ['low', 'medium', 'high'])->default('medium')->after('category');
                }
                if (!Schema::hasColumn('school_bill', 'attachment')) {
                    $table->string('attachment')->nullable()->after('priority');
                }
                if (!Schema::hasColumn('school_bill', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('attachment');
                }
                if (!Schema::hasColumn('school_bill', 'deleted_at')) {
                    $table->softDeletes()->after('updated_at');
                }
            });
        }

        // ============================================
        // PART 2: Fix school_bill_class_term_session table
        // ============================================
        
        if (!Schema::hasTable('school_bill_class_term_session')) {
            // Create the table if it doesn't exist
            Schema::create('school_bill_class_term_session', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bill_id');
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('termid_id');
                $table->unsignedBigInteger('session_id');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                
                $table->unique(['bill_id', 'class_id', 'termid_id', 'session_id'], 'uk_sbcts_unique');
                $table->index(['class_id', 'termid_id', 'session_id'], 'idx_sbcts_cts');
                $table->index('bill_id', 'idx_sbcts_bill');
            });
        } else {
            // Table exists, fix columns
            Schema::table('school_bill_class_term_session', function (Blueprint $table) {
                // Add created_by column if it doesn't exist
                if (!Schema::hasColumn('school_bill_class_term_session', 'created_by')) {
                    // Check if createdBy exists and rename it
                    if (Schema::hasColumn('school_bill_class_term_session', 'createdBy')) {
                        $table->renameColumn('createdBy', 'created_by');
                    } else {
                        $table->unsignedBigInteger('created_by')->nullable()->after('session_id');
                    }
                } else {
                    // Column exists, check its type and fix if needed
                    try {
                        $columnType = DB::getSchemaBuilder()->getColumnType('school_bill_class_term_session', 'created_by');
                        if ($columnType !== 'bigint' && $columnType !== 'integer' && $columnType !== 'bigint unsigned') {
                            // Convert to unsigned big integer
                            DB::statement('ALTER TABLE school_bill_class_term_session MODIFY created_by BIGINT UNSIGNED NULL');
                        }
                    } catch (\Exception $e) {
                        // If column exists but we can't determine type, try to modify it anyway
                        try {
                            DB::statement('ALTER TABLE school_bill_class_term_session MODIFY created_by BIGINT UNSIGNED NULL');
                        } catch (\Exception $e) {
                            // If modification fails, we'll skip the foreign key
                            error_log('Could not modify created_by column type: ' . $e->getMessage());
                        }
                    }
                }
            });

            // Convert string IDs to integers if needed
            $this->convertStringIdsToIntegers();
        }

        // ============================================
        // PART 3: Add Foreign Keys with proper checks
        // ============================================

        $this->addForeignKeysSafely();

        // ============================================
        // PART 4: Add Indexes (if they don't exist)
        // ============================================
        
        $this->addIndexesSafely();
    }

    /**
     * Convert string IDs to integers
     */
    private function convertStringIdsToIntegers(): void
    {
        $tableName = 'school_bill_class_term_session';
        
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $columnsToCheck = ['bill_id', 'class_id', 'termid_id', 'session_id'];
        $needsConversion = false;

        foreach ($columnsToCheck as $column) {
            if (Schema::hasColumn($tableName, $column)) {
                try {
                    $columnType = DB::getSchemaBuilder()->getColumnType($tableName, $column);
                    if ($columnType !== 'bigint' && $columnType !== 'integer' && $columnType !== 'bigint unsigned') {
                        $needsConversion = true;
                        break;
                    }
                } catch (\Exception $e) {
                    $needsConversion = true;
                }
            }
        }

        if ($needsConversion) {
            try {
                // Add new integer columns
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'bill_id_new')) {
                        $table->unsignedBigInteger('bill_id_new')->nullable()->after('id');
                        $table->unsignedBigInteger('class_id_new')->nullable()->after('bill_id_new');
                        $table->unsignedBigInteger('termid_id_new')->nullable()->after('class_id_new');
                        $table->unsignedBigInteger('session_id_new')->nullable()->after('termid_id_new');
                    }
                });

                // Migrate data
                DB::statement('UPDATE school_bill_class_term_session SET bill_id_new = CAST(bill_id AS UNSIGNED) WHERE bill_id IS NOT NULL AND bill_id != ""');
                DB::statement('UPDATE school_bill_class_term_session SET class_id_new = CAST(class_id AS UNSIGNED) WHERE class_id IS NOT NULL AND class_id != ""');
                DB::statement('UPDATE school_bill_class_term_session SET termid_id_new = CAST(termid_id AS UNSIGNED) WHERE termid_id IS NOT NULL AND termid_id != ""');
                DB::statement('UPDATE school_bill_class_term_session SET session_id_new = CAST(session_id AS UNSIGNED) WHERE session_id IS NOT NULL AND session_id != ""');

                // Drop old columns and rename new ones
                Schema::table($tableName, function (Blueprint $table) {
                    if (Schema::hasColumn('school_bill_class_term_session', 'bill_id')) {
                        $table->dropColumn('bill_id');
                    }
                    if (Schema::hasColumn('school_bill_class_term_session', 'class_id')) {
                        $table->dropColumn('class_id');
                    }
                    if (Schema::hasColumn('school_bill_class_term_session', 'termid_id')) {
                        $table->dropColumn('termid_id');
                    }
                    if (Schema::hasColumn('school_bill_class_term_session', 'session_id')) {
                        $table->dropColumn('session_id');
                    }

                    if (Schema::hasColumn('school_bill_class_term_session', 'bill_id_new')) {
                        $table->renameColumn('bill_id_new', 'bill_id');
                        $table->renameColumn('class_id_new', 'class_id');
                        $table->renameColumn('termid_id_new', 'termid_id');
                        $table->renameColumn('session_id_new', 'session_id');
                    }
                });
            } catch (\Exception $e) {
                error_log('Error converting IDs: ' . $e->getMessage());
            }
        }
    }

    /**
     * Add foreign keys safely with proper compatibility checks
     */
    private function addForeignKeysSafely(): void
    {
        $tableName = 'school_bill_class_term_session';
        
        if (!Schema::hasTable($tableName)) {
            error_log("Table {$tableName} doesn't exist, skipping foreign keys");
            return;
        }

        // Check if users table exists and has the right structure
        $usersTableExists = Schema::hasTable('users');
        $userIdType = null;
        
        if ($usersTableExists) {
            try {
                $userIdType = DB::getSchemaBuilder()->getColumnType('users', 'id');
            } catch (\Exception $e) {
                error_log('Could not determine users.id column type: ' . $e->getMessage());
            }
        }

        // Get existing foreign keys
        $existingForeignKeys = $this->getExistingForeignKeys($tableName);

        // Define foreign keys with proper column types
        $foreignKeys = [
            'bill_id' => ['table' => 'school_bill', 'constraint' => 'fk_sbcts_bill', 'columnType' => 'unsignedBigInteger'],
            'class_id' => ['table' => 'schoolclass', 'constraint' => 'fk_sbcts_class', 'columnType' => 'unsignedBigInteger'],
            'termid_id' => ['table' => 'schoolterm', 'constraint' => 'fk_sbcts_term', 'columnType' => 'unsignedBigInteger'],
            'session_id' => ['table' => 'schoolsession', 'constraint' => 'fk_sbcts_session', 'columnType' => 'unsignedBigInteger'],
        ];

        // Only add created_by foreign key if users table exists and column types are compatible
        if ($usersTableExists && $userIdType && in_array($userIdType, ['bigint', 'integer', 'bigint unsigned'])) {
            $foreignKeys['created_by'] = [
                'table' => 'users', 
                'constraint' => 'fk_sbcts_created', 
                'columnType' => 'unsignedBigInteger'
            ];
        } else {
            error_log('Skipping created_by foreign key - users table or column type issue');
        }

        // Add each foreign key if it doesn't exist
        foreach ($foreignKeys as $column => $config) {
            if (!Schema::hasColumn($tableName, $column)) {
                error_log("Column {$column} doesn't exist in {$tableName}");
                continue;
            }

            if (!in_array($config['constraint'], $existingForeignKeys)) {
                // Check if the referenced table exists
                if (!Schema::hasTable($config['table'])) {
                    error_log("Referenced table {$config['table']} doesn't exist, skipping foreign key");
                    continue;
                }

                try {
                    // First, ensure the column has the correct type
                    $this->ensureColumnType($tableName, $column, $config['columnType']);
                    
                    // Then add the foreign key
                    Schema::table($tableName, function (Blueprint $table) use ($column, $config) {
                        $table->foreign($column, $config['constraint'])
                              ->references('id')
                              ->on($config['table'])
                              ->onDelete('cascade');
                    });
                    
                    error_log("Added foreign key {$config['constraint']} on {$tableName}.{$column}");
                } catch (\Exception $e) {
                    error_log("Could not add foreign key {$config['constraint']}: " . $e->getMessage());
                }
            } else {
                error_log("Foreign key {$config['constraint']} already exists, skipping");
            }
        }
    }

    /**
     * Ensure a column has the correct data type
     */
    private function ensureColumnType(string $table, string $column, string $type): void
    {
        try {
            $currentType = DB::getSchemaBuilder()->getColumnType($table, $column);
            
            // Map type names to their MySQL equivalents
            $typeMap = [
                'unsignedBigInteger' => 'bigint unsigned',
                'bigint' => 'bigint',
                'integer' => 'int',
                'unsignedInteger' => 'int unsigned',
                'string' => 'varchar',
                'text' => 'text',
            ];
            
            $expectedType = $typeMap[$type] ?? $type;
            
            // Only modify if the type is different
            if ($currentType !== $expectedType && !str_contains($currentType, $expectedType)) {
                DB::statement("ALTER TABLE {$table} MODIFY {$column} {$expectedType} NULL");
                error_log("Changed {$table}.{$column} type to {$expectedType}");
            }
        } catch (\Exception $e) {
            // If we can't determine or modify the type, skip
            error_log("Could not ensure column type for {$table}.{$column}: " . $e->getMessage());
        }
    }

    /**
     * Get existing foreign keys for a table
     */
    private function getExistingForeignKeys(string $tableName): array
    {
        try {
            $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                                       WHERE TABLE_SCHEMA = DATABASE()
                                       AND TABLE_NAME = ?
                                       AND REFERENCED_TABLE_NAME IS NOT NULL", [$tableName]);
            return array_column($foreignKeys, 'CONSTRAINT_NAME');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Add indexes safely
     */
    private function addIndexesSafely(): void
    {
        $tableName = 'school_bill_class_term_session';
        
        if (!Schema::hasTable($tableName)) {
            return;
        }

        // Check existing indexes
        $existingIndexes = [];
        try {
            $indexes = DB::select("SHOW INDEX FROM {$tableName}");
            $existingIndexes = array_column($indexes, 'Key_name');
        } catch (\Exception $e) {
            error_log('Could not fetch existing indexes: ' . $e->getMessage());
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($existingIndexes) {
            if (!in_array('idx_sbcts_cts', $existingIndexes)) {
                try {
                    $table->index(['class_id', 'termid_id', 'session_id'], 'idx_sbcts_cts');
                } catch (\Exception $e) {
                    error_log('Could not add index idx_sbcts_cts: ' . $e->getMessage());
                }
            }
            if (!in_array('idx_sbcts_bill', $existingIndexes)) {
                try {
                    $table->index('bill_id', 'idx_sbcts_bill');
                } catch (\Exception $e) {
                    error_log('Could not add index idx_sbcts_bill: ' . $e->getMessage());
                }
            }
            if (!in_array('uk_sbcts_unique', $existingIndexes)) {
                try {
                    if (Schema::hasColumn('school_bill_class_term_session', 'bill_id')) {
                        $table->unique(['bill_id', 'class_id', 'termid_id', 'session_id'], 'uk_sbcts_unique');
                    }
                } catch (\Exception $e) {
                    error_log('Could not add unique constraint uk_sbcts_unique: ' . $e->getMessage());
                }
            }
        });
    }

    public function down(): void
    {
        // Drop foreign keys if they exist
        if (Schema::hasTable('school_bill_class_term_session')) {
            Schema::table('school_bill_class_term_session', function (Blueprint $table) {
                $foreignKeys = ['fk_sbcts_bill', 'fk_sbcts_class', 'fk_sbcts_term', 'fk_sbcts_session', 'fk_sbcts_created'];
                foreach ($foreignKeys as $fk) {
                    try {
                        $table->dropForeign($fk);
                    } catch (\Exception $e) {
                        // Foreign key might not exist
                    }
                }

                try {
                    $table->dropUnique('uk_sbcts_unique');
                } catch (\Exception $e) {}
                try {
                    $table->dropIndex('idx_sbcts_cts');
                } catch (\Exception $e) {}
                try {
                    $table->dropIndex('idx_sbcts_bill');
                } catch (\Exception $e) {}
            });
        }

        // Drop added columns from school_bill
        if (Schema::hasTable('school_bill')) {
            Schema::table('school_bill', function (Blueprint $table) {
                $columns = [
                    'statusId', 'effective_from', 'effective_to', 'is_mandatory',
                    'due_date', 'late_fee', 'late_fee_type', 'payment_frequency',
                    'grace_period_days', 'is_scholarship_eligible', 'is_discount_eligible',
                    'max_discount_percentage', 'category', 'priority', 'attachment',
                    'is_active', 'deleted_at'
                ];
                
                foreach ($columns as $column) {
                    try {
                        $table->dropColumnIfExists($column);
                    } catch (\Exception $e) {}
                }
            });
        }
    }
};