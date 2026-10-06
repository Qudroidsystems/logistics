<?php
// database/migrations/2026_04_24_000000_create_all_finance_tables_safe.php

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
        // ============================================
        // 1. CHART OF ACCOUNTS (No dependencies)
        // ============================================
        if (!Schema::hasTable('chart_of_accounts')) {
            Schema::create('chart_of_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('account_code', 20)->unique();
                $table->string('account_name');
                $table->enum('account_type', ['asset', 'liability', 'equity', 'income', 'expense']);
                $table->enum('normal_balance', ['debit', 'credit']);
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->unsignedBigInteger('school_bill_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_bank_account')->default(false);
                $table->string('bank_name')->nullable();
                $table->string('bank_account_no')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index('account_code');
                $table->index('account_type');
                $table->index('is_active');
            });
        }

        // ============================================
        // 2. BILL CATEGORIES
        // ============================================
        if (!Schema::hasTable('bill_categories')) {
            Schema::create('bill_categories', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_mandatory')->default(false);
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('code');
            });
        }

        // ============================================
        // 3. EXPENSE CATEGORIES
        // ============================================
        if (!Schema::hasTable('expense_categories')) {
            Schema::create('expense_categories', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('account_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('code');
                $table->index('account_id');
                $table->index('is_active');
            });
        }

        // ============================================
        // 4. SIBLING GROUPS
        // ============================================
        if (!Schema::hasTable('sibling_groups')) {
            Schema::create('sibling_groups', function (Blueprint $table) {
                $table->id();
                $table->string('group_no')->unique();
                $table->string('family_name');
                $table->string('parent_phone')->nullable();
                $table->string('parent_email')->nullable();
                $table->text('address')->nullable();
                $table->integer('total_children')->default(0);
                $table->enum('discount_type', ['percentage', 'fixed_per_child'])->nullable();
                $table->decimal('discount_value', 15, 2)->nullable();
                $table->unsignedBigInteger('primary_contact_id')->nullable();
                $table->timestamps();

                $table->index('group_no');
                $table->index('family_name');
            });
        }

        // ============================================
        // 5. SIBLING GROUP STUDENTS
        // ============================================
        if (!Schema::hasTable('sibling_group_students')) {
            Schema::create('sibling_group_students', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sibling_group_id');
                $table->unsignedBigInteger('student_id');
                $table->integer('birth_order')->nullable();
                $table->timestamps();

                $table->unique(['sibling_group_id', 'student_id'], 'uk_sgs_group_student');
                $table->index('sibling_group_id');
                $table->index('student_id');
            });
        }

        // ============================================
        // 6. SCHOLARSHIP TYPES
        // ============================================
        if (!Schema::hasTable('scholarship_types')) {
            Schema::create('scholarship_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 50)->unique();
                $table->enum('type', ['full', 'percentage', 'fixed_amount', 'category_specific']);
                $table->enum('application', ['auto', 'manual'])->default('manual');
                $table->text('description')->nullable();
                $table->json('criteria')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // ============================================
        // 7. SCHOLARSHIPS
        // ============================================
        if (!Schema::hasTable('scholarships')) {
            Schema::create('scholarships', function (Blueprint $table) {
                $table->id();
                $table->string('scholarship_no')->unique();
                $table->unsignedBigInteger('scholarship_type_id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->enum('value_type', ['percentage', 'fixed_amount']);
                $table->decimal('value', 15, 2);
                $table->decimal('cap_amount', 15, 2)->nullable();
                $table->boolean('requires_application')->default(false);
                $table->json('eligible_classes')->nullable();
                $table->json('eligible_status_ids')->nullable();
                $table->json('excluded_bill_categories')->nullable();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->integer('max_recipients')->nullable();
                $table->integer('renewal_frequency')->nullable();
                $table->decimal('budget_amount', 15, 2)->nullable();
                $table->decimal('utilized_amount', 15, 2)->default(0);
                $table->enum('status', ['draft', 'active', 'expired', 'suspended'])->default('draft');
                $table->unsignedBigInteger('created_by');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['effective_from', 'effective_to'], 'idx_scholarships_dates');
                $table->index('status');
                $table->index('scholarship_no');
            });
        }

        // ============================================
        // 8. SCHOLARSHIP ASSIGNMENTS
        // ============================================
        if (!Schema::hasTable('scholarship_assignments')) {
            Schema::create('scholarship_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('scholarship_id');
                $table->unsignedBigInteger('student_id');
                $table->string('application_no')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'expired', 'revoked']);
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('renewed_at')->nullable();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->enum('value_type', ['percentage', 'fixed_amount']);
                $table->decimal('value', 15, 2);
                $table->decimal('cap_amount', 15, 2)->nullable();
                $table->text('reason')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->text('revocation_reason')->nullable();
                $table->unsignedBigInteger('assigned_by');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['scholarship_id', 'student_id', 'effective_from'], 'uk_sa_unique');
                $table->index(['student_id', 'status'], 'idx_sa_student_status');
            });
        }

        // ============================================
        // 9. DISCOUNT TYPES
        // ============================================
        if (!Schema::hasTable('discount_types')) {
            Schema::create('discount_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 50)->unique();
                $table->enum('type', ['percentage', 'fixed_amount', 'per_child']);
                $table->text('description')->nullable();
                $table->json('criteria')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // ============================================
        // 10. DISCOUNTS
        // ============================================
        if (!Schema::hasTable('discounts')) {
            Schema::create('discounts', function (Blueprint $table) {
                $table->id();
                $table->string('discount_no')->unique();
                $table->unsignedBigInteger('discount_type_id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->enum('value_type', ['percentage', 'fixed_amount']);
                $table->decimal('value', 15, 2);
                $table->decimal('max_amount', 15, 2)->nullable();
                $table->enum('applicable_to', ['all_bills', 'specific_bills', 'specific_categories']);
                $table->json('applicable_bill_ids')->nullable();
                $table->json('applicable_categories')->nullable();
                $table->json('eligible_classes')->nullable();
                $table->enum('condition_type', ['none', 'early_payment', 'min_amount', 'sibling_count'])->default('none');
                $table->decimal('condition_value', 15, 2)->nullable();
                $table->integer('days_before_due')->nullable();
                $table->boolean('stackable_with_scholarship')->default(false);
                $table->boolean('stackable_with_other_discounts')->default(false);
                $table->integer('stacking_priority')->default(1);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->enum('status', ['draft', 'active', 'expired', 'suspended'])->default('draft');
                $table->unsignedBigInteger('created_by');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('status');
                $table->index('discount_no');
            });
        }

        // ============================================
        // 11. DISCOUNT ASSIGNMENTS
        // ============================================
        if (!Schema::hasTable('discount_assignments')) {
            Schema::create('discount_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('discount_id');
                $table->unsignedBigInteger('student_id');
                $table->enum('value_type', ['percentage', 'fixed_amount']);
                $table->decimal('value', 15, 2);
                $table->decimal('max_amount', 15, 2)->nullable();
                $table->unsignedBigInteger('sibling_group_id')->nullable();
                $table->integer('sibling_count')->nullable();
                $table->decimal('per_child_discount', 15, 2)->nullable();
                $table->enum('status', ['active', 'expired', 'removed']);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('assigned_by');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['student_id', 'status'], 'idx_da_student_status');
                $table->index('sibling_group_id');
            });
        }

        // ============================================
        // 12. SCHOLARSHIP APPLICATIONS
        // ============================================
        if (!Schema::hasTable('scholarship_applications')) {
            Schema::create('scholarship_applications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('scholarship_id');
                $table->unsignedBigInteger('student_id');
                $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'revoked']);
                $table->text('motivation_letter')->nullable();
                $table->json('documents')->nullable();
                $table->text('admin_notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamps();

                $table->index(['student_id', 'status'], 'idx_schapp_student_status');
                $table->index('submitted_at');
            });
        }

        // ============================================
        // 13. JOURNAL ENTRIES
        // ============================================
        if (!Schema::hasTable('journal_entries')) {
            Schema::create('journal_entries', function (Blueprint $table) {
                $table->id();
                $table->string('entry_no', 50)->unique();
                $table->date('entry_date');
                $table->enum('entry_type', ['payment', 'receipt', 'contra', 'journal', 'payroll', 'adjustment', 'reversal', 'petty_cash']);
                $table->text('description');
                $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('reversal_reason')->nullable();
                $table->unsignedBigInteger('reversed_by')->nullable();
                $table->timestamp('reversed_at')->nullable();
                $table->timestamps();

                $table->index(['entry_date', 'status'], 'idx_je_date_status');
                $table->index('entry_no');
            });
        }

        // ============================================
        // 14. JOURNAL ENTRY LINES
        // ============================================
        if (!Schema::hasTable('journal_entry_lines')) {
            Schema::create('journal_entry_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('journal_entry_id');
                $table->unsignedBigInteger('account_id');
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->text('narration')->nullable();
                $table->unsignedBigInteger('student_id')->nullable();
                $table->unsignedBigInteger('staff_id')->nullable();
                $table->unsignedBigInteger('expense_id')->nullable();
                $table->timestamps();

                $table->index(['journal_entry_id', 'account_id'], 'idx_jel_entry_account');
                $table->index('debit');
                $table->index('credit');
            });
        }

        // ============================================
        // 15. PAYMENT GATEWAYS
        // ============================================
        if (!Schema::hasTable('payment_gateways')) {
            Schema::create('payment_gateways', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('provider_key')->unique();
                $table->string('secret_key')->nullable();
                $table->string('public_key')->nullable();
                $table->enum('mode', ['sandbox', 'live'])->default('sandbox');
                $table->decimal('fee_percentage', 5, 2)->default(0);
                $table->decimal('fee_fixed', 15, 2)->default(0);
                $table->json('config')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // ============================================
        // 16. PAYMENT BATCHES
        // ============================================
        if (!Schema::hasTable('payment_batches')) {
            Schema::create('payment_batches', function (Blueprint $table) {
                $table->id();
                $table->string('batch_no')->unique();
                $table->unsignedBigInteger('student_id');
                $table->date('payment_date');
                $table->decimal('total_amount', 15, 2);
                $table->enum('payment_method', ['cash', 'bank_transfer', 'pos', 'online', 'cheque']);
                $table->string('reference_no')->nullable();
                $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('pending');
                $table->text('notes')->nullable();
                $table->json('receipt_data')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->unsignedBigInteger('reversed_by')->nullable();
                $table->timestamp('reversed_at')->nullable();
                $table->text('reversal_reason')->nullable();
                $table->timestamps();

                $table->index('batch_no');
                $table->index('payment_date');
                $table->index('status');
            });
        }

        // ============================================
        // 17. PAYMENT BATCH ITEMS
        // ============================================
        if (!Schema::hasTable('payment_batch_items')) {
            Schema::create('payment_batch_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_batch_id');
                $table->unsignedBigInteger('school_bill_id');
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('termid_id');
                $table->unsignedBigInteger('session_id');
                $table->decimal('original_amount', 15, 2);
                $table->decimal('scholarship_deduction', 15, 2)->default(0);
                $table->decimal('discount_deduction', 15, 2)->default(0);
                $table->decimal('adjusted_amount', 15, 2);
                $table->decimal('amount_paid', 15, 2);
                $table->decimal('balance_before', 15, 2);
                $table->decimal('balance_after', 15, 2);
                $table->unsignedBigInteger('student_bill_payment_id')->nullable();
                $table->timestamps();

                $table->index(['payment_batch_id', 'school_bill_id'], 'idx_pbi_batch_bill');
            });
        }

        // ============================================
        // Add Foreign Keys (After all tables exist, with existence checks)
        // ============================================
        $this->addForeignKeys();
    }

    /**
     * Add foreign keys after all tables are created (with checks)
     */
    private function addForeignKeys(): void
    {
        // Get existing foreign keys for each table
        $existingForeignKeys = $this->getExistingForeignKeys();

        // Add foreign key to expense_categories
        if (Schema::hasTable('expense_categories') && Schema::hasTable('chart_of_accounts')) {
            if (!in_array('fk_exp_cat_account', $existingForeignKeys)) {
                Schema::table('expense_categories', function (Blueprint $table) {
                    try {
                        $table->foreign('account_id', 'fk_exp_cat_account')
                              ->references('id')->on('chart_of_accounts')
                              ->onDelete('set null');
                    } catch (\Exception $e) {
                        // Silently fail if foreign key already exists
                    }
                });
            }
        }

        // Add foreign key to sibling_group_students
        if (Schema::hasTable('sibling_group_students') && Schema::hasTable('sibling_groups')) {
            if (!in_array('fk_sgs_group', $existingForeignKeys)) {
                Schema::table('sibling_group_students', function (Blueprint $table) {
                    try {
                        $table->foreign('sibling_group_id', 'fk_sgs_group')
                              ->references('id')->on('sibling_groups')->onDelete('cascade');
                    } catch (\Exception $e) {}
                });
            }
        }

        // Add foreign key to discount_assignments
        if (Schema::hasTable('discount_assignments') && Schema::hasTable('sibling_groups')) {
            if (!in_array('fk_da_sibling', $existingForeignKeys)) {
                Schema::table('discount_assignments', function (Blueprint $table) {
                    try {
                        $table->foreign('sibling_group_id', 'fk_da_sibling')
                              ->references('id')->on('sibling_groups')->onDelete('set null');
                    } catch (\Exception $e) {}
                });
            }
        }

        // Add foreign key to scholarships
        if (Schema::hasTable('scholarships') && Schema::hasTable('scholarship_types')) {
            if (!in_array('fk_scholarships_type', $existingForeignKeys)) {
                Schema::table('scholarships', function (Blueprint $table) {
                    try {
                        $table->foreign('scholarship_type_id', 'fk_scholarships_type')
                              ->references('id')->on('scholarship_types');
                    } catch (\Exception $e) {}
                });
            }
        }

        // Add foreign key to discounts
        if (Schema::hasTable('discounts') && Schema::hasTable('discount_types')) {
            if (!in_array('fk_discounts_type', $existingForeignKeys)) {
                Schema::table('discounts', function (Blueprint $table) {
                    try {
                        $table->foreign('discount_type_id', 'fk_discounts_type')
                              ->references('id')->on('discount_types');
                    } catch (\Exception $e) {}
                });
            }
        }

        // Add foreign key to scholarship_assignments
        if (Schema::hasTable('scholarship_assignments') && Schema::hasTable('scholarships')) {
            if (!in_array('fk_sa_scholarship', $existingForeignKeys)) {
                Schema::table('scholarship_assignments', function (Blueprint $table) {
                    try {
                        $table->foreign('scholarship_id', 'fk_sa_scholarship')
                              ->references('id')->on('scholarships');
                    } catch (\Exception $e) {}
                });
            }
        }

        // Add foreign key to discount_assignments for discount_id
        if (Schema::hasTable('discount_assignments') && Schema::hasTable('discounts')) {
            if (!in_array('fk_da_discount', $existingForeignKeys)) {
                Schema::table('discount_assignments', function (Blueprint $table) {
                    try {
                        $table->foreign('discount_id', 'fk_da_discount')
                              ->references('id')->on('discounts');
                    } catch (\Exception $e) {}
                });
            }
        }
    }

    /**
     * Get all existing foreign key names in the database
     */
    private function getExistingForeignKeys(): array
    {
        try {
            $result = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            return array_column($result, 'CONSTRAINT_NAME');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys if they exist
        $foreignKeys = ['fk_exp_cat_account', 'fk_sgs_group', 'fk_da_sibling', 'fk_scholarships_type', 'fk_discounts_type', 'fk_sa_scholarship', 'fk_da_discount'];

        foreach ($foreignKeys as $fk) {
            try {
                Schema::table('expense_categories', function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk);
                });
            } catch (\Exception $e) {}
        }

        // Drop tables in reverse order
        Schema::dropIfExists('payment_batch_items');
        Schema::dropIfExists('payment_batches');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('scholarship_applications');
        Schema::dropIfExists('discount_assignments');
        Schema::dropIfExists('discounts');
        Schema::dropIfExists('discount_types');
        Schema::dropIfExists('scholarship_assignments');
        Schema::dropIfExists('scholarships');
        Schema::dropIfExists('scholarship_types');
        Schema::dropIfExists('sibling_group_students');
        Schema::dropIfExists('sibling_groups');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('bill_categories');
        Schema::dropIfExists('chart_of_accounts');
    }
};
