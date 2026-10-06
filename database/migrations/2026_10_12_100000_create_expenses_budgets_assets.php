<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spending: vendors, purchase requests, expense vouchers (with approval and
 * receipts), budgets with budget-vs-actual, and the fixed-asset register
 * with monthly depreciation.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendors')) {
            Schema::create('vendors', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('contact_person', 120)->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('email', 120)->nullable();
                $table->string('address', 255)->nullable();
                $table->string('bank_name', 120)->nullable();
                $table->string('account_number', 20)->nullable();
                $table->string('account_name', 150)->nullable();
                $table->string('tin', 30)->nullable();
                $table->string('category', 60)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('budgets')) {
            Schema::create('budgets', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status', 12)->default('draft');           // draft | active | closed
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('budget_lines')) {
            Schema::create('budget_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('budget_id')->index();
                $table->string('line_type', 12)->default('category');     // category | payroll
                $table->unsignedBigInteger('expense_category_id')->nullable();
                $table->string('label', 150)->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('purchase_requests')) {
            Schema::create('purchase_requests', function (Blueprint $table) {
                $table->id();
                $table->string('pr_no', 30)->unique();
                $table->string('title', 180);
                $table->string('department', 100)->nullable();
                $table->unsignedBigInteger('requested_by')->index();
                $table->date('needed_by')->nullable();
                $table->json('items');
                $table->decimal('estimated_total', 15, 2)->default(0);
                $table->unsignedBigInteger('expense_category_id')->nullable();
                $table->unsignedBigInteger('vendor_id')->nullable();
                $table->string('status', 12)->default('submitted')->index(); // submitted | approved | rejected | ordered | received | closed
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->string('rejection_reason', 255)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('expense_voucher_id')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('expense_vouchers')) {
            Schema::create('expense_vouchers', function (Blueprint $table) {
                $table->id();
                $table->string('voucher_no', 30)->unique();
                $table->date('expense_date')->index();
                $table->unsignedBigInteger('expense_category_id')->nullable()->index();
                $table->unsignedBigInteger('vendor_id')->nullable();
                $table->unsignedBigInteger('staff_id')->nullable();           // reimbursement to a staff member
                $table->string('payee_name', 150);
                $table->string('description', 500);
                $table->decimal('amount', 15, 2);
                $table->string('payment_method', 20)->default('bank_transfer'); // cash | bank_transfer | cheque | pos | paystack
                $table->string('paid_from', 10)->nullable();                  // chart-of-accounts code (bank / cash)
                $table->string('reference', 80)->nullable();
                $table->string('receipt_path')->nullable();
                $table->boolean('capitalise')->default(false);                // becomes a fixed asset when paid
                $table->string('asset_account', 10)->nullable();
                $table->unsignedSmallInteger('asset_life_months')->nullable();
                $table->string('status', 16)->default('draft')->index();      // draft | submitted | approved | paid | rejected | cancelled
                $table->boolean('needs_second_approval')->default(false);
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('second_approved_by')->nullable();
                $table->timestamp('second_approved_at')->nullable();
                $table->unsignedBigInteger('paid_by')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('rejection_reason', 255)->nullable();
                $table->unsignedBigInteger('purchase_request_id')->nullable();
                $table->unsignedBigInteger('fixed_asset_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('fixed_assets')) {
            Schema::create('fixed_assets', function (Blueprint $table) {
                $table->id();
                $table->string('asset_tag', 30)->unique();
                $table->string('name', 150);
                $table->string('account_code', 10)->default('1102');         // 1101 buildings, 1102 furniture, 1103 vehicles, 1104 IT
                $table->string('description', 500)->nullable();
                $table->string('serial_no', 80)->nullable();
                $table->string('location', 120)->nullable();
                $table->unsignedBigInteger('custodian_staff_id')->nullable();
                $table->unsignedBigInteger('vendor_id')->nullable();
                $table->date('acquisition_date');
                $table->decimal('cost', 15, 2);
                $table->decimal('salvage_value', 15, 2)->default(0);
                $table->unsignedSmallInteger('useful_life_months')->default(60);
                $table->decimal('accumulated_depreciation', 15, 2)->default(0);
                $table->string('last_depreciated_period', 7)->nullable();     // Y-m
                $table->string('status', 16)->default('active')->index();      // active | under_repair | disposed | lost
                $table->date('disposal_date')->nullable();
                $table->decimal('disposal_amount', 15, 2)->nullable();
                $table->string('disposal_note', 255)->nullable();
                $table->unsignedBigInteger('expense_voucher_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('asset_depreciations')) {
            Schema::create('asset_depreciations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fixed_asset_id');
                $table->string('period', 7);
                $table->decimal('amount', 15, 2);
                $table->unsignedBigInteger('journal_entry_id')->nullable();
                $table->timestamps();
                $table->unique(['fixed_asset_id', 'period']);
            });
        }
    }

    public function down(): void
    {
        foreach (['asset_depreciations', 'fixed_assets', 'expense_vouchers', 'purchase_requests', 'budget_lines', 'budgets', 'vendors'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
