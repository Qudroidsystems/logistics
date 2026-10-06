<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Extra lessons, overtime and weekend duty claimed by staff and paid through payroll. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('staff_duty_claims')) {
            Schema::create('staff_duty_claims', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('staff_id')->index();
                $table->date('work_date');
                $table->string('type', 20);                          // extra_lesson | overtime_hour | weekend_duty | other
                $table->decimal('quantity', 8, 2)->default(1);        // lessons / hours / days
                $table->decimal('rate', 15, 2)->default(0);
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('description', 255)->nullable();
                $table->string('status', 12)->default('pending')->index(); // pending | approved | rejected | paid
                $table->unsignedBigInteger('submitted_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->string('rejection_reason', 255)->nullable();
                $table->unsignedBigInteger('payroll_period_id')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_duty_claims');
    }
};
