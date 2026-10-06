<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('school_bill_class_term_session', function (Blueprint $table) {

            // ── Soft deletes ───────────────────────────────────────────
            // Required by SoftDeletes trait on SchoolBillTermSession model
            if (!Schema::hasColumn('school_bill_class_term_session', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }

            // ── Status / ordering / requirement flags ──────────────────
            if (!Schema::hasColumn('school_bill_class_term_session', 'is_active')) {
                $table->boolean('is_active')
                      ->default(true)
                      ->after('created_by')
                      ->comment('Whether this bill assignment is currently active');
            }

            if (!Schema::hasColumn('school_bill_class_term_session', 'display_order')) {
                $table->unsignedInteger('display_order')
                      ->default(0)
                      ->after('is_active')
                      ->comment('Sort order when listing bills for a student');
            }

            if (!Schema::hasColumn('school_bill_class_term_session', 'is_required')) {
                $table->boolean('is_required')
                      ->default(true)
                      ->after('display_order')
                      ->comment('Whether payment of this bill is mandatory');
            }
        });

        // ── Back-fill existing rows ────────────────────────────────────
        // Existing records should be active, required, and in default order
        DB::table('school_bill_class_term_session')
            ->whereNull('deleted_at')
            ->update([
                'is_active'     => true,
                'display_order' => 0,
                'is_required'   => true,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_bill_class_term_session', function (Blueprint $table) {
            $table->dropSoftDeletes();

            $columns = ['is_active', 'display_order', 'is_required'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('school_bill_class_term_session', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
