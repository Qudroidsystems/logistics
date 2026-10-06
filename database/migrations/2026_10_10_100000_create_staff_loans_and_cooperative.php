<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff loans & salary advances (repaid through payroll), repayment
 * schedules and receipts, the staff cooperative, and a small key/value
 * table for finance settings (loan rules, attendance pay rules, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('finance_settings')) {
            Schema::create('finance_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 60)->unique();
                $table->json('value')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('loans_advances')) {
            Schema::create('loans_advances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('staff_id')->index();
                $table->string('type', 20)->default('loan');            // loan | advance | cooperative
                $table->string('reference_no', 30)->nullable()->unique();
                $table->decimal('amount', 15, 2);
                $table->decimal('interest_rate', 6, 2)->default(0);      // % per year, flat
                $table->unsignedSmallInteger('repayment_months')->default(1);
                $table->decimal('monthly_repayment', 15, 2)->default(0);
                $table->decimal('balance', 15, 2)->default(0);
                $table->date('approval_date')->nullable();
                $table->date('first_repayment_date')->nullable();
                $table->text('purpose')->nullable();
                $table->string('attachment')->nullable();
                $table->string('status', 20)->default('pending')->index();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::table('loans_advances', function (Blueprint $table) {
            $add = fn ($c) => !Schema::hasColumn('loans_advances', $c);
            if ($add('total_repayable')) $table->decimal('total_repayable', 15, 2)->default(0);
            if ($add('guarantor_staff_id')) $table->unsignedBigInteger('guarantor_staff_id')->nullable();
            if ($add('guarantor_status')) $table->string('guarantor_status', 12)->nullable();   // pending | accepted | declined
            if ($add('paused_until')) $table->date('paused_until')->nullable();
            if ($add('disbursed_at')) $table->timestamp('disbursed_at')->nullable();
            if ($add('disbursement_method')) $table->string('disbursement_method', 20)->nullable();
            if ($add('disbursement_reference')) $table->string('disbursement_reference', 80)->nullable();
            if ($add('notes')) $table->text('notes')->nullable();
        });

        if (!Schema::hasTable('loan_repayment_schedule')) {
            Schema::create('loan_repayment_schedule', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('loan_id')->index();
                $table->unsignedSmallInteger('installment_no');
                $table->date('due_date');
                $table->decimal('amount', 15, 2);
                $table->decimal('principal', 15, 2)->default(0);
                $table->decimal('interest', 15, 2)->default(0);
                $table->decimal('paid_amount', 15, 2)->default(0);
                $table->string('status', 12)->default('due');          // due | partial | paid | waived
                $table->date('paid_date')->nullable();
                $table->string('transaction_reference')->nullable();
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('loan_repayment_schedule', 'paid_amount')) {
            Schema::table('loan_repayment_schedule', fn (Blueprint $t) => $t->decimal('paid_amount', 15, 2)->default(0));
        }

        if (!Schema::hasTable('loan_repayments')) {
            Schema::create('loan_repayments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('loan_id')->index();
                $table->decimal('amount', 15, 2);
                $table->string('source', 12);                           // payroll | cash | transfer | waiver
                $table->unsignedBigInteger('payroll_period_id')->nullable();
                $table->string('reference', 80)->nullable();
                $table->date('paid_on');
                $table->string('note', 255)->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();
                $table->unique(['loan_id', 'payroll_period_id'], 'uk_loan_period');
            });
        }

        if (!Schema::hasTable('coop_members')) {
            Schema::create('coop_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('staff_id')->unique();
                $table->decimal('monthly_contribution', 15, 2)->default(0);
                $table->string('status', 12)->default('active');        // active | suspended | left
                $table->date('joined_on')->nullable();
                $table->date('left_on')->nullable();
                $table->string('note', 255)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('coop_transactions')) {
            Schema::create('coop_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('staff_id')->index();
                $table->string('type', 16);                              // contribution | withdrawal | dividend | adjustment
                $table->decimal('amount', 15, 2);                        // + adds to savings, − takes out
                $table->unsignedBigInteger('payroll_period_id')->nullable();
                $table->date('txn_date');
                $table->string('reference', 80)->nullable();
                $table->string('note', 255)->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();
                $table->index(['staff_id', 'txn_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coop_transactions');
        Schema::dropIfExists('coop_members');
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('finance_settings');
    }
};
