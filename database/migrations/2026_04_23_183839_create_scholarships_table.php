<?php
// database/migrations/2026_04_23_183839_create_scholarships_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================
        // 1. Sibling Groups (Check if exists first)
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
        // 2. Sibling Group Students (Check if exists first)
        // ============================================
        if (!Schema::hasTable('sibling_group_students')) {
            Schema::create('sibling_group_students', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sibling_group_id');
                $table->unsignedBigInteger('student_id');
                $table->integer('birth_order')->nullable();
                $table->timestamps();

                $table->foreign('sibling_group_id', 'fk_sgs_group')->references('id')->on('sibling_groups')->onDelete('cascade');
                $table->foreign('student_id', 'fk_sgs_student')->references('id')->on('studentRegistration')->onDelete('cascade');
                $table->unique(['sibling_group_id', 'student_id'], 'uk_sgs_group_student');
            });
        }

        // ============================================
        // 3. Scholarship Types (Check if exists first)
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
        // 4. Scholarships (Check if exists first)
        // ============================================
        if (!Schema::hasTable('scholarships')) {
            Schema::create('scholarships', function (Blueprint $table) {
                $table->id();
                $table->string('scholarship_no')->unique();
                $table->unsignedBigInteger('scholarship_type_id');
                $table->string('title');
                $table->text('description')->nullable();

                // Scholarship value
                $table->enum('value_type', ['percentage', 'fixed_amount']);
                $table->decimal('value', 15, 2);
                $table->decimal('cap_amount', 15, 2)->nullable();

                // Eligibility
                $table->boolean('requires_application')->default(false);
                $table->json('eligible_classes')->nullable();
                $table->json('eligible_status_ids')->nullable();
                $table->json('excluded_bill_categories')->nullable();

                // Effective dates
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->integer('max_recipients')->nullable();
                $table->integer('renewal_frequency')->nullable();

                // Financial tracking
                $table->decimal('budget_amount', 15, 2)->nullable();
                $table->decimal('utilized_amount', 15, 2)->default(0);

                // Approval workflow
                $table->enum('status', ['draft', 'active', 'expired', 'suspended'])->default('draft');
                $table->unsignedBigInteger('created_by');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();

                $table->timestamps();
                $table->softDeletes();

                // Foreign keys (using short names)
                $table->foreign('scholarship_type_id', 'fk_scholarships_type')->references('id')->on('scholarship_types');
                $table->foreign('created_by', 'fk_scholarships_created')->references('id')->on('users');
                $table->foreign('approved_by', 'fk_scholarships_approved')->references('id')->on('users');

                // Indexes
                $table->index(['effective_from', 'effective_to'], 'idx_scholarships_dates');
                $table->index('status', 'idx_scholarships_status');
            });
        }

        // ============================================
        // 5. Scholarship Assignments (Check if exists first)
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

                // Values at assignment time
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

                // Foreign keys
                $table->foreign('scholarship_id', 'fk_sa_scholarship')->references('id')->on('scholarships');
                $table->foreign('student_id', 'fk_sa_student')->references('id')->on('studentRegistration');
                $table->foreign('assigned_by', 'fk_sa_assigned')->references('id')->on('users');
                $table->foreign('approved_by', 'fk_sa_approved')->references('id')->on('users');

                // Unique constraint
                $table->unique(['scholarship_id', 'student_id', 'effective_from'], 'uk_sa_unique');

                // Indexes
                $table->index(['student_id', 'status'], 'idx_sa_student_status');
            });
        }

        // ============================================
        // 6. Discount Types (Check if exists first)
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
        // 7. Discounts (Check if exists first)
        // ============================================
        if (!Schema::hasTable('discounts')) {
            Schema::create('discounts', function (Blueprint $table) {
                $table->id();
                $table->string('discount_no')->unique();
                $table->unsignedBigInteger('discount_type_id');
                $table->string('title');
                $table->text('description')->nullable();

                // Discount value
                $table->enum('value_type', ['percentage', 'fixed_amount']);
                $table->decimal('value', 15, 2);
                $table->decimal('max_amount', 15, 2)->nullable();

                // Eligibility
                $table->enum('applicable_to', ['all_bills', 'specific_bills', 'specific_categories']);
                $table->json('applicable_bill_ids')->nullable();
                $table->json('applicable_categories')->nullable();
                $table->json('eligible_classes')->nullable();

                // Conditions
                $table->enum('condition_type', ['none', 'early_payment', 'min_amount', 'sibling_count'])->default('none');
                $table->decimal('condition_value', 15, 2)->nullable();
                $table->integer('days_before_due')->nullable();

                // Stacking rules
                $table->boolean('stackable_with_scholarship')->default(false);
                $table->boolean('stackable_with_other_discounts')->default(false);
                $table->integer('stacking_priority')->default(1);

                // Effective dates
                $table->date('effective_from');
                $table->date('effective_to')->nullable();

                $table->enum('status', ['draft', 'active', 'expired', 'suspended'])->default('draft');
                $table->unsignedBigInteger('created_by');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();

                $table->timestamps();
                $table->softDeletes();

                // Foreign keys
                $table->foreign('discount_type_id', 'fk_discounts_type')->references('id')->on('discount_types');
                $table->foreign('created_by', 'fk_discounts_created')->references('id')->on('users');
                $table->foreign('approved_by', 'fk_discounts_approved')->references('id')->on('users');

                // Indexes
                $table->index('status', 'idx_discounts_status');
            });
        }

        // ============================================
        // 8. Discount Assignments (Check if exists first)
        // ============================================
        if (!Schema::hasTable('discount_assignments')) {
            Schema::create('discount_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('discount_id');
                $table->unsignedBigInteger('student_id');

                // Values at assignment time
                $table->enum('value_type', ['percentage', 'fixed_amount']);
                $table->decimal('value', 15, 2);
                $table->decimal('max_amount', 15, 2)->nullable();

                // Sibling discount specific fields
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

                // Foreign keys
                $table->foreign('discount_id', 'fk_da_discount')->references('id')->on('discounts');
                $table->foreign('student_id', 'fk_da_student')->references('id')->on('studentRegistration');
                $table->foreign('assigned_by', 'fk_da_assigned')->references('id')->on('users');

                // Sibling group foreign key - sibling_groups table created above
                $table->foreign('sibling_group_id', 'fk_da_sibling')->references('id')->on('sibling_groups')->onDelete('set null');

                // Indexes
                $table->index(['student_id', 'status'], 'idx_da_student_status');
                $table->index('sibling_group_id', 'idx_da_sibling');
            });
        }

        // ============================================
        // 9. Scholarship Applications (Check if exists first)
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

                // Foreign keys
                $table->foreign('scholarship_id', 'fk_schapp_scholarship')->references('id')->on('scholarships');
                $table->foreign('student_id', 'fk_schapp_student')->references('id')->on('studentRegistration');
                $table->foreign('reviewed_by', 'fk_schapp_reviewed')->references('id')->on('users');

                // Indexes
                $table->index(['student_id', 'status'], 'idx_schapp_student_status');
                $table->index('submitted_at', 'idx_schapp_submitted');
            });
        }
    }

    public function down(): void
    {
        // Drop in reverse order (check if tables exist before dropping)
        Schema::dropIfExists('scholarship_applications');
        Schema::dropIfExists('discount_assignments');
        Schema::dropIfExists('discounts');
        Schema::dropIfExists('discount_types');
        Schema::dropIfExists('scholarship_assignments');
        Schema::dropIfExists('scholarships');
        Schema::dropIfExists('scholarship_types');
        Schema::dropIfExists('sibling_group_students');
        Schema::dropIfExists('sibling_groups');
    }
};
