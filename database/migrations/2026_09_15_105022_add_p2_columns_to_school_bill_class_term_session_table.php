<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_bill_class_term_session', function (Blueprint $table) {
            // SoftDeletes column
            if (!Schema::hasColumn('school_bill_class_term_session', 'deleted_at')) {
                $table->softDeletes();
            }

            // Active flag
            if (!Schema::hasColumn('school_bill_class_term_session', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('created_by');
            }

            // Display order
            if (!Schema::hasColumn('school_bill_class_term_session', 'display_order')) {
                $table->integer('display_order')->default(0)->after('is_active');
            }

            // Required flag
            if (!Schema::hasColumn('school_bill_class_term_session', 'is_required')) {
                $table->boolean('is_required')->default(false)->after('display_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('school_bill_class_term_session', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'is_active', 'display_order', 'is_required']);
        });
    }
};