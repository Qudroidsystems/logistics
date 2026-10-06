<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links every automatic journal to what created it (payroll month, payout,
 * expense, fee receipt day…) so nothing is ever posted twice.
 * Also makes sure the core ledger tables exist on older installs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('journal_entries')) {
            Schema::create('journal_entries', function (Blueprint $table) {
                $table->id();
                $table->string('entry_no', 50)->unique();
                $table->date('entry_date');
                $table->string('entry_type', 20)->default('journal');
                $table->text('description');
                $table->string('status', 10)->default('draft');
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('reversal_reason')->nullable();
                $table->unsignedBigInteger('reversed_by')->nullable();
                $table->timestamp('reversed_at')->nullable();
                $table->timestamps();
                $table->index(['entry_date', 'status'], 'idx_je_date_status');
            });
        }
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
            });
        }
        // The original enum lacks some automatic entry types; widen it to a string.
        try { \Illuminate\Support\Facades\DB::statement("ALTER TABLE journal_entries MODIFY entry_type VARCHAR(20) NOT NULL DEFAULT 'journal'"); } catch (\Throwable $e) {}
        try { \Illuminate\Support\Facades\DB::statement("ALTER TABLE journal_entries MODIFY status VARCHAR(10) NOT NULL DEFAULT 'draft'"); } catch (\Throwable $e) {}
        try { \Illuminate\Support\Facades\DB::statement('ALTER TABLE journal_entries MODIFY created_by BIGINT UNSIGNED NULL'); } catch (\Throwable $e) {}

        if (!Schema::hasTable('ledger_postings')) {
            Schema::create('ledger_postings', function (Blueprint $table) {
                $table->id();
                $table->string('source', 40);                  // payroll_accrual | payout | expense | fees_day | loan_out | …
                $table->string('source_key', 80);              // id or date
                $table->unsignedBigInteger('journal_entry_id');
                $table->timestamps();
                $table->unique(['source', 'source_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_postings');
    }
};
