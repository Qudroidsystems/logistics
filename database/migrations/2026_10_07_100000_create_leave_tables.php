<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Staff leave: types, requests (HOD → Principal), balance adjustments. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('leave_types')) {
            Schema::create('leave_types', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80);
                $table->string('code', 20)->unique();
                $table->decimal('days_per_year', 5, 1)->default(0);   // 0 = no yearly limit
                $table->boolean('paid')->default(true);
                $table->boolean('requires_document')->default(false);
                $table->string('gender', 10)->nullable();             // null | male | female
                $table->decimal('carry_over_max', 5, 1)->default(0);
                $table->string('color', 20)->default('#0f766e');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
            $now = now();
            foreach ([
                ['Annual leave', 'ANNUAL', 20, 1, 0, null, 5, '#0f766e'], ['Sick leave', 'SICK', 10, 1, 1, null, 0, '#dc2626'],
                ['Maternity leave', 'MATERNITY', 84, 1, 1, 'female', 0, '#db2777'], ['Paternity leave', 'PATERNITY', 14, 1, 0, 'male', 0, '#2563eb'],
                ['Compassionate leave', 'COMPASSION', 5, 1, 0, null, 0, '#7c3aed'], ['Study leave', 'STUDY', 10, 1, 1, null, 0, '#0891b2'],
                ['Unpaid leave', 'UNPAID', 0, 0, 0, null, 0, '#64748b'],
            ] as [$n, $c, $d, $p, $doc, $g, $co, $col]) {
                DB::table('leave_types')->insert(['name' => $n, 'code' => $c, 'days_per_year' => $d, 'paid' => $p, 'requires_document' => $doc,
                    'gender' => $g, 'carry_over_max' => $co, 'color' => $col, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        if (!Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('staff_id')->index();      // staffbioinfo.id
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('leave_type_id')->index();
                $table->date('start_date')->index();
                $table->date('end_date')->index();
                $table->boolean('half_day')->default(false);
                $table->decimal('days', 5, 1);
                $table->text('reason');
                $table->string('attachment')->nullable();
                $table->unsignedBigInteger('relief_staff_id')->nullable(); // users.id covering classes
                $table->string('contact_phone', 30)->nullable();
                $table->string('status', 20)->default('pending_hod')->index(); // pending_hod | pending_principal | approved | rejected | cancelled
                $table->unsignedBigInteger('hod_id')->nullable();
                $table->timestamp('hod_at')->nullable();
                $table->string('hod_note', 500)->nullable();
                $table->unsignedBigInteger('approver_id')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->string('approver_note', 500)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('leave_adjustments')) {
            Schema::create('leave_adjustments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('staff_id')->index();
                $table->unsignedBigInteger('leave_type_id');
                $table->unsignedSmallInteger('year');
                $table->decimal('days', 5, 1);                        // + extra, − deduction, or carried over
                $table->string('note', 255)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_adjustments');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
    }
};
