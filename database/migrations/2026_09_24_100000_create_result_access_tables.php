<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Result access control for students who owe fees.
 *
 *  result_access_settings   -- one row: is blocking on, which debt counts,
 *                              thresholds, what the student is told.
 *  result_access_exceptions -- admin-granted "let this owing student see
 *                              their results" passes, with scope, expiry,
 *                              reason and a full grant/revoke trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('result_access_settings')) {
            Schema::create('result_access_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('enabled')->default(false);
                // 'term'            -- only the selected term's unpaid balance counts
                // 'term_and_arrears'-- plus unpaid balances from earlier terms
                $table->string('debt_scope', 30)->default('term');
                // 'any' | 'amount' (owed > value) | 'percent' (owed > value % of payable)
                $table->string('threshold_type', 20)->default('any');
                $table->decimal('threshold_value', 12, 2)->default(0);
                $table->boolean('apply_to_mock')->default(true);
                $table->boolean('show_amount_owed')->default(true);
                $table->text('blocked_message')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });

            DB::table('result_access_settings')->insert([
                'enabled' => false, 'debt_scope' => 'term', 'threshold_type' => 'any', 'threshold_value' => 0,
                'apply_to_mock' => true, 'show_amount_owed' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        if (!Schema::hasTable('result_access_exceptions')) {
            Schema::create('result_access_exceptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id')->index();
                // null term + null session = every term
                $table->unsignedBigInteger('term_id')->nullable();
                $table->unsignedBigInteger('session_id')->nullable();
                $table->date('expires_on')->nullable();
                $table->string('reason', 500)->nullable();
                $table->unsignedBigInteger('granted_by')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->unsignedBigInteger('revoked_by')->nullable();
                $table->timestamps();

                $table->index(['student_id', 'session_id', 'term_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('result_access_exceptions');
        Schema::dropIfExists('result_access_settings');
    }
};
