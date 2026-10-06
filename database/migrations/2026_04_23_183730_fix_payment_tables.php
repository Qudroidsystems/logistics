<?php
// database/migrations/2026_04_23_183730_fix_payment_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================
        // PART 1: Add missing columns to student_bill_payment
        // ============================================
        Schema::table('student_bill_payment', function (Blueprint $table) {
            if (!Schema::hasColumn('student_bill_payment', 'total_paid')) {
                $table->decimal('total_paid', 15, 2)->default(0)->after('payment_method');
            }
            if (!Schema::hasColumn('student_bill_payment', 'total_balance')) {
                $table->decimal('total_balance', 15, 2)->default(0)->after('total_paid');
            }
            if (!Schema::hasColumn('student_bill_payment', 'last_payment_date')) {
                $table->timestamp('last_payment_date')->nullable()->after('total_balance');
            }
            if (!Schema::hasColumn('student_bill_payment', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'partial', 'completed', 'failed', 'refunded'])->default('pending')->after('status');
            }
            if (!Schema::hasColumn('student_bill_payment', 'session_token')) {
                $table->string('session_token', 100)->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('student_bill_payment', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // ============================================
        // PART 2: Fix column types
        // ============================================
        $this->fixColumnTypes('student_bill_payment', ['student_id', 'school_bill_id', 'class_id', 'termid_id', 'session_id', 'generated_by']);

        // ============================================
        // PART 3: Add Foreign Keys (check if they don't exist)
        // ============================================

        // Get existing foreign keys for student_bill_payment
        $existingForeignKeys = $this->getExistingForeignKeys('student_bill_payment');

        Schema::table('student_bill_payment', function (Blueprint $table) use ($existingForeignKeys) {
            // student_id foreign key
            if (Schema::hasColumn('student_bill_payment', 'student_id') && !in_array('fk_sbp_student', $existingForeignKeys)) {
                try {
                    $table->foreign('student_id', 'fk_sbp_student')->references('id')->on('studentRegistration')->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist with different name
                }
            }

            // school_bill_id foreign key
            if (Schema::hasColumn('student_bill_payment', 'school_bill_id') && !in_array('fk_sbp_bill', $existingForeignKeys)) {
                try {
                    $table->foreign('school_bill_id', 'fk_sbp_bill')->references('id')->on('school_bill')->onDelete('cascade');
                } catch (\Exception $e) {}
            }

            // class_id foreign key
            if (Schema::hasColumn('student_bill_payment', 'class_id') && !in_array('fk_sbp_class', $existingForeignKeys)) {
                try {
                    $table->foreign('class_id', 'fk_sbp_class')->references('id')->on('schoolclass');
                } catch (\Exception $e) {}
            }

            // termid_id foreign key
            if (Schema::hasColumn('student_bill_payment', 'termid_id') && !in_array('fk_sbp_term', $existingForeignKeys)) {
                try {
                    $table->foreign('termid_id', 'fk_sbp_term')->references('id')->on('schoolterm');
                } catch (\Exception $e) {}
            }

            // session_id foreign key
            if (Schema::hasColumn('student_bill_payment', 'session_id') && !in_array('fk_sbp_session', $existingForeignKeys)) {
                try {
                    $table->foreign('session_id', 'fk_sbp_session')->references('id')->on('schoolsession');
                } catch (\Exception $e) {}
            }

            // generated_by foreign key
            if (Schema::hasColumn('student_bill_payment', 'generated_by') && !in_array('fk_sbp_generated', $existingForeignKeys)) {
                try {
                    $table->foreign('generated_by', 'fk_sbp_generated')->references('id')->on('users');
                } catch (\Exception $e) {}
            }
        });

        // ============================================
        // PART 4: Add Indexes (if they don't exist)
        // ============================================

        $existingIndexes = $this->getExistingIndexes('student_bill_payment');

        Schema::table('student_bill_payment', function (Blueprint $table) use ($existingIndexes) {
            if (!in_array('idx_sbp_student_term_session', $existingIndexes)) {
                try {
                    $table->index(['student_id', 'termid_id', 'session_id'], 'idx_sbp_student_term_session');
                } catch (\Exception $e) {}
            }
            if (!in_array('idx_sbp_status', $existingIndexes)) {
                try {
                    $table->index('payment_status', 'idx_sbp_status');
                } catch (\Exception $e) {}
            }
        });

        // ============================================
        // PART 5: Fix student_bill_payment_record table
        // ============================================

        // Add missing columns
        Schema::table('student_bill_payment_record', function (Blueprint $table) {
            if (!Schema::hasColumn('student_bill_payment_record', 'last_payment')) {
                $table->decimal('last_payment', 15, 2)->default(0)->after('amount_paid');
            }
            if (!Schema::hasColumn('student_bill_payment_record', 'is_reversal')) {
                $table->boolean('is_reversal')->default(false)->after('complete_payment');
            }
            if (!Schema::hasColumn('student_bill_payment_record', 'reversal_reason')) {
                $table->text('reversal_reason')->nullable()->after('is_reversal');
            }
            if (!Schema::hasColumn('student_bill_payment_record', 'invoiceNo')) {
                $table->string('invoiceNo', 100)->nullable()->after('reversal_reason');
            }
            if (!Schema::hasColumn('student_bill_payment_record', 'transaction_reference')) {
                $table->string('transaction_reference', 100)->nullable()->after('invoiceNo');
            }
            if (!Schema::hasColumn('student_bill_payment_record', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Fix column type
        try {
            DB::statement('ALTER TABLE student_bill_payment_record MODIFY student_bill_payment_id BIGINT UNSIGNED NOT NULL');
        } catch (\Exception $e) {}

        // Add foreign key for student_bill_payment_record
        $existingRecordForeignKeys = $this->getExistingForeignKeys('student_bill_payment_record');

        Schema::table('student_bill_payment_record', function (Blueprint $table) use ($existingRecordForeignKeys) {
            if (!in_array('fk_sbpr_payment', $existingRecordForeignKeys)) {
                try {
                    $table->foreign('student_bill_payment_id', 'fk_sbpr_payment')
                          ->references('id')->on('student_bill_payment')->onDelete('cascade');
                } catch (\Exception $e) {}
            }
        });

        // ============================================
        // PART 6: Fix student_bill_payment_book table
        // ============================================
        Schema::table('student_bill_payment_book', function (Blueprint $table) {
            // Fix data types
            if (Schema::hasColumn('student_bill_payment_book', 'amount_paid')) {
                try {
                    $table->decimal('amount_paid', 15, 2)->default(0)->change();
                } catch (\Exception $e) {}
            }
            if (Schema::hasColumn('student_bill_payment_book', 'amount_owed')) {
                try {
                    $table->decimal('amount_owed', 15, 2)->default(0)->change();
                } catch (\Exception $e) {}
            }

            // Add missing columns
            if (!Schema::hasColumn('student_bill_payment_book', 'original_amount')) {
                $table->decimal('original_amount', 15, 2)->default(0)->after('school_bill_id');
            }
            if (!Schema::hasColumn('student_bill_payment_book', 'scholarship_deduction')) {
                $table->decimal('scholarship_deduction', 15, 2)->default(0)->after('original_amount');
            }
            if (!Schema::hasColumn('student_bill_payment_book', 'discount_deduction')) {
                $table->decimal('discount_deduction', 15, 2)->default(0)->after('scholarship_deduction');
            }
            if (!Schema::hasColumn('student_bill_payment_book', 'adjusted_amount')) {
                $table->decimal('adjusted_amount', 15, 2)->default(0)->after('discount_deduction');
            }
        });

        // ============================================
        // PART 7: Fix student_bill_invoice table
        // ============================================

        // Fix column types
        $this->fixColumnTypes('student_bill_invoice', ['student_id', 'school_bill_id', 'class_id', 'termid_id', 'session_id', 'generated_by']);

        Schema::table('student_bill_invoice', function (Blueprint $table) {
            // Add missing columns
            if (!Schema::hasColumn('student_bill_invoice', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('student_bill_invoice', 'amount')) {
                $table->decimal('amount', 15, 2)->default(0)->after('pdf_path');
            }
            if (!Schema::hasColumn('student_bill_invoice', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * Get existing foreign keys for a table
     */
    private function getExistingForeignKeys($table): array
    {
        try {
            $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                                       WHERE TABLE_SCHEMA = DATABASE()
                                       AND TABLE_NAME = '{$table}'
                                       AND REFERENCED_TABLE_NAME IS NOT NULL");
            return array_column($foreignKeys, 'CONSTRAINT_NAME');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get existing indexes for a table
     */
    private function getExistingIndexes($table): array
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$table}`");
            return array_column($indexes, 'Key_name');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Fix column types from string to unsigned big integer
     */
    private function fixColumnTypes($table, $columns): void
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                try {
                    // Check current column type
                    $columnType = DB::getSchemaBuilder()->getColumnType($table, $column);
                    if ($columnType !== 'bigint' && $columnType !== 'integer' && $columnType !== 'bigint unsigned') {
                        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT UNSIGNED NULL");
                    }
                } catch (\Exception $e) {
                    // If column has data that can't be converted, skip
                }
            }
        }
    }

    public function down(): void
    {
        // Drop foreign keys from student_bill_payment
        Schema::table('student_bill_payment', function (Blueprint $table) {
            try {
                $table->dropForeign('fk_sbp_student');
            } catch (\Exception $e) {}
            try {
                $table->dropForeign('fk_sbp_bill');
            } catch (\Exception $e) {}
            try {
                $table->dropForeign('fk_sbp_class');
            } catch (\Exception $e) {}
            try {
                $table->dropForeign('fk_sbp_term');
            } catch (\Exception $e) {}
            try {
                $table->dropForeign('fk_sbp_session');
            } catch (\Exception $e) {}
            try {
                $table->dropForeign('fk_sbp_generated');
            } catch (\Exception $e) {}

            try {
                $table->dropIndex('idx_sbp_student_term_session');
            } catch (\Exception $e) {}
            try {
                $table->dropIndex('idx_sbp_status');
            } catch (\Exception $e) {}
        });

        // Drop foreign keys from student_bill_payment_record
        Schema::table('student_bill_payment_record', function (Blueprint $table) {
            try {
                $table->dropForeign('fk_sbpr_payment');
            } catch (\Exception $e) {}
        });

        // Drop added columns
        Schema::table('student_bill_payment', function (Blueprint $table) {
            $table->dropColumnIfExists('total_paid');
            $table->dropColumnIfExists('total_balance');
            $table->dropColumnIfExists('last_payment_date');
            $table->dropColumnIfExists('payment_status');
            $table->dropColumnIfExists('session_token');
            $table->dropColumnIfExists('deleted_at');
        });

        Schema::table('student_bill_payment_record', function (Blueprint $table) {
            $table->dropColumnIfExists('last_payment');
            $table->dropColumnIfExists('is_reversal');
            $table->dropColumnIfExists('reversal_reason');
            $table->dropColumnIfExists('invoiceNo');
            $table->dropColumnIfExists('transaction_reference');
            $table->dropColumnIfExists('deleted_at');
        });

        Schema::table('student_bill_payment_book', function (Blueprint $table) {
            $table->dropColumnIfExists('original_amount');
            $table->dropColumnIfExists('scholarship_deduction');
            $table->dropColumnIfExists('discount_deduction');
            $table->dropColumnIfExists('adjusted_amount');
        });

        Schema::table('student_bill_invoice', function (Blueprint $table) {
            $table->dropColumnIfExists('pdf_path');
            $table->dropColumnIfExists('amount');
            $table->dropColumnIfExists('deleted_at');
        });
    }
};
