<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Salary payouts: a batch of bank transfers for one payroll month
 * (Paystack Transfers or a manual bank upload), one item per staff member.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payout_batches')) {
            Schema::create('payout_batches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payroll_period_id')->nullable()->index();
                $table->string('reference', 40)->unique();
                $table->string('provider', 20)->default('paystack');   // paystack | manual
                $table->string('mode', 10)->default('test');           // test | live (Paystack key used)
                $table->string('status', 20)->default('draft');        // draft | processing | completed | partial | failed | cancelled
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->unsignedInteger('item_count')->default(0);
                $table->unsignedInteger('success_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->unsignedBigInteger('prepared_by')->nullable();
                $table->unsignedBigInteger('released_by')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->string('note', 255)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payout_items')) {
            Schema::create('payout_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payout_batch_id')->index();
                $table->unsignedBigInteger('payroll_run_id')->nullable()->index();
                $table->unsignedBigInteger('staff_id')->index();
                $table->string('purpose', 20)->default('salary');      // salary | loan | expense
                $table->unsignedBigInteger('source_id')->nullable();   // loan / expense id for non-salary payouts
                $table->decimal('amount', 15, 2);
                $table->string('bank_name', 120)->nullable();
                $table->string('account_last4', 4)->nullable();
                $table->string('account_name', 150)->nullable();
                $table->string('recipient_code', 60)->nullable();
                $table->string('reference', 60)->unique();
                $table->string('transfer_code', 60)->nullable();
                $table->string('status', 20)->default('pending');      // pending | queued | otp | success | failed | reversed | skipped | manual
                $table->string('failure_reason', 255)->nullable();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_items');
        Schema::dropIfExists('payout_batches');
    }
};
