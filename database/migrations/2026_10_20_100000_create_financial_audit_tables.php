<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Financial audit trail + exception review.
 * - financial_audit_logs: an immutable before/after record of every user-made
 *   change to a financial record (created/updated/deleted).
 * - financial_audit_reviews: an auditor's sign-off on a detected exception
 *   (open → cleared/flagged) with a note, so the exception list is actionable.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('financial_audit_logs')) {
            Schema::create('financial_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('auditable_type', 60)->index();   // short class label e.g. ExpenseVoucher
                $table->unsignedBigInteger('auditable_id')->index();
                $table->string('model_label', 60)->nullable();   // human module name e.g. "Expense voucher"
                $table->string('event', 10)->index();            // created | updated | deleted
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_name', 120)->nullable();
                $table->string('ref', 80)->nullable();           // voucher_no / entry_no / reference
                $table->decimal('amount', 15, 2)->nullable();
                $table->json('changes')->nullable();             // {field: [old, new]}
                $table->string('summary', 255)->nullable();
                $table->date('business_date')->nullable()->index(); // entry/expense/txn date if any
                $table->string('ip', 45)->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        if (!Schema::hasTable('financial_audit_reviews')) {
            Schema::create('financial_audit_reviews', function (Blueprint $table) {
                $table->id();
                $table->string('kind', 40)->index();             // exception category
                $table->string('ref_key', 191);                  // stable key for the exception instance
                $table->string('status', 12)->default('open');   // open | cleared | flagged
                $table->string('note', 500)->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->unique(['kind', 'ref_key'], 'fin_audit_review_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_audit_reviews');
        Schema::dropIfExists('financial_audit_logs');
    }
};
