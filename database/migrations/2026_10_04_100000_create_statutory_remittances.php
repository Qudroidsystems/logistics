<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Money owed to government bodies from each payroll month (PAYE per state,
 * pension per PFA, NHF, NHIA, NSITF, ITF) and the payments made to them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('statutory_remittances')) {
            Schema::create('statutory_remittances', function (Blueprint $table) {
                $table->id();
                $table->string('type', 10);                      // paye | pension | nhf | nhia | nsitf | itf
                $table->unsignedBigInteger('payroll_period_id')->index();
                $table->string('authority', 120);                // state / PFA / FMBN / NSITF…
                $table->unsignedInteger('staff_count')->default(0);
                $table->decimal('employee_amount', 15, 2)->default(0);
                $table->decimal('employer_amount', 15, 2)->default(0);
                $table->decimal('amount_due', 15, 2)->default(0);
                $table->decimal('amount_paid', 15, 2)->default(0);
                $table->date('due_date')->nullable()->index();
                $table->string('status', 10)->default('pending'); // pending | partial | paid
                $table->date('paid_at')->nullable();
                $table->string('reference', 120)->nullable();    // receipt / RRR / transaction ref
                $table->string('payment_method', 30)->nullable();
                $table->string('evidence')->nullable();          // uploaded receipt
                $table->text('notes')->nullable();
                $table->boolean('needs_review')->default(false); // payroll changed after payment
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();
                $table->unique(['type', 'payroll_period_id', 'authority'], 'uq_remit_type_period_authority');
            });
        }

        if (!Schema::hasTable('statutory_remittance_lines')) {
            Schema::create('statutory_remittance_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('remittance_id')->index();
                $table->unsignedBigInteger('payroll_run_id')->index();
                $table->unsignedBigInteger('staff_id')->index();
                $table->decimal('employee_amount', 15, 2)->default(0);
                $table->decimal('employer_amount', 15, 2)->default(0);
                $table->decimal('amount', 15, 2)->default(0);
                $table->json('meta')->nullable();                // name, TIN / RSA PIN / NHF no., base pay
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_remittance_lines');
        Schema::dropIfExists('statutory_remittances');
    }
};
