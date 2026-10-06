<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll phase 1:
 *  - payroll_statutory_rates: PAYE bands, pension, NHF… with effective dates
 *  - staff_pay_profiles: bank, tax, pension details and which deductions apply
 *  - payroll_run_lines: permanent copy of every payslip line
 *  - extra columns on payroll_runs / payroll_periods
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payroll_statutory_rates')) {
            Schema::create('payroll_statutory_rates', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->index();            // paye | pension | nhf | nhia | nsitf | itf | limits
                $table->string('name', 150);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->json('config');
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });

            $now = now();
            foreach (\App\Services\Payroll\PayrollCalculator::defaults() as $d) {
                DB::table('payroll_statutory_rates')->insert([
                    'code' => $d['code'], 'name' => $d['name'], 'effective_from' => $d['effective_from'], 'effective_to' => $d['effective_to'],
                    'config' => json_encode($d['config']), 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        if (!Schema::hasTable('staff_pay_profiles')) {
            Schema::create('staff_pay_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('staff_id')->unique();          // staffbioinfo.id
                $table->string('employment_type', 20)->default('full_time'); // full_time | part_time | contract
                $table->string('bank_code', 20)->nullable();
                $table->string('bank_name', 100)->nullable();
                $table->text('account_number')->nullable();                // encrypted
                $table->string('account_last4', 4)->nullable();
                $table->string('account_name', 150)->nullable();
                $table->timestamp('account_verified_at')->nullable();
                $table->string('paystack_recipient_code', 60)->nullable();
                $table->string('tin', 30)->nullable();
                $table->string('tax_state', 40)->nullable();               // state tax office PAYE goes to
                $table->string('pfa_name', 100)->nullable();
                $table->string('rsa_pin', 30)->nullable();
                $table->string('nhf_number', 30)->nullable();
                $table->boolean('paye_enabled')->default(true);
                $table->boolean('pension_enabled')->default(true);
                $table->boolean('nhf_enabled')->default(false);
                $table->boolean('nhia_enabled')->default(false);
                $table->decimal('annual_rent', 15, 2)->default(0);         // for rent relief
                $table->string('rent_evidence')->nullable();
                $table->decimal('other_reliefs_annual', 15, 2)->default(0);// life assurance, mortgage interest
                $table->string('pay_status', 10)->default('active');       // active | hold | exited
                $table->string('hold_reason', 255)->nullable();
                $table->date('exit_date')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });

            // Bring across what staffbioinfo already holds.
            if (Schema::hasTable('staffbioinfo')) {
                $cols = Schema::getColumnListing('staffbioinfo');
                $pick = fn ($c) => in_array($c, $cols, true) ? $c : DB::raw("NULL as $c");
                foreach (DB::table('staffbioinfo')->get(['id', $pick('bank_name'), $pick('account_number'), $pick('account_name'), $pick('pension_id'), $pick('nhf_number'), $pick('tin_number')]) as $s) {
                    $acct = preg_replace('/\D/', '', (string) $s->account_number);
                    DB::table('staff_pay_profiles')->insertOrIgnore([
                        'staff_id' => $s->id, 'bank_name' => $s->bank_name ?: null,
                        'account_number' => $acct ? \Illuminate\Support\Facades\Crypt::encryptString($acct) : null,
                        'account_last4' => $acct ? substr($acct, -4) : null, 'account_name' => $s->account_name ?: null,
                        'rsa_pin' => $s->pension_id ?: null, 'nhf_number' => $s->nhf_number ?: null, 'nhf_enabled' => !empty($s->nhf_number),
                        'tin' => $s->tin_number ?: null, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }

        if (!Schema::hasTable('payroll_run_lines')) {
            Schema::create('payroll_run_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payroll_run_id')->index();
                $table->unsignedBigInteger('payroll_period_id')->index();
                $table->unsignedBigInteger('staff_id')->index();
                $table->string('code', 30);
                $table->string('label', 120);
                $table->string('type', 12);                 // earning | deduction | employer
                $table->decimal('amount', 15, 2);
                $table->boolean('taxable')->default(false);
                $table->boolean('pensionable')->default(false);
                $table->unsignedSmallInteger('sort')->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['staff_id', 'code']);
            });
        }

        Schema::table('payroll_runs', function (Blueprint $table) {
            $add = fn ($c, $cb) => Schema::hasColumn('payroll_runs', $c) ? null : $cb();
            $add('taxable_income', fn () => $table->decimal('taxable_income', 15, 2)->default(0));
            $add('annual_chargeable', fn () => $table->decimal('annual_chargeable', 15, 2)->default(0));
            $add('rent_relief', fn () => $table->decimal('rent_relief', 15, 2)->default(0));
            $add('pension_base', fn () => $table->decimal('pension_base', 15, 2)->default(0));
            $add('nhia', fn () => $table->decimal('nhia', 15, 2)->default(0));
            $add('employer_nhia', fn () => $table->decimal('employer_nhia', 15, 2)->default(0));
            $add('itf', fn () => $table->decimal('itf', 15, 2)->default(0));
            $add('employer_cost', fn () => $table->decimal('employer_cost', 15, 2)->default(0));
            $add('proration', fn () => $table->decimal('proration', 6, 4)->default(1));
            $add('tax_rule', fn () => $table->string('tax_rule', 20)->nullable());
            $add('tax_breakdown', fn () => $table->json('tax_breakdown')->nullable());
            $add('tax_state', fn () => $table->string('tax_state', 40)->nullable());
            $add('tin', fn () => $table->string('tin', 30)->nullable());
            $add('pfa_name', fn () => $table->string('pfa_name', 100)->nullable());
            $add('rsa_pin', fn () => $table->string('rsa_pin', 30)->nullable());
            $add('warnings', fn () => $table->json('warnings')->nullable());
            $add('verify_code', fn () => $table->string('verify_code', 40)->nullable()->index());
        });

        Schema::table('payroll_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_periods', 'locked_by')) $table->unsignedBigInteger('locked_by')->nullable();
            if (!Schema::hasColumn('payroll_periods', 'locked_at')) $table->timestamp('locked_at')->nullable();
            if (!Schema::hasColumn('payroll_periods', 'total_employer_cost')) $table->decimal('total_employer_cost', 15, 2)->default(0);
            if (!Schema::hasColumn('payroll_periods', 'staff_count')) $table->unsignedInteger('staff_count')->default(0);
            if (!Schema::hasColumn('payroll_periods', 'notes')) $table->text('notes')->nullable();
        });

        // Runs are created fresh by the new engine; salary_structure_id/processed_by must allow nulls.
        try { DB::statement('ALTER TABLE payroll_runs MODIFY salary_structure_id BIGINT UNSIGNED NULL'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE payroll_runs MODIFY processed_by BIGINT UNSIGNED NULL'); } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_lines');
        Schema::dropIfExists('staff_pay_profiles');
        Schema::dropIfExists('payroll_statutory_rates');
    }
};
