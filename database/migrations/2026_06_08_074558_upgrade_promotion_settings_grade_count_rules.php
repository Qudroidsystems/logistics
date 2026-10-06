<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('promotion_settings', 'rule_logic')) {
                $table->string('rule_logic', 20)->default('subject_only')->after('repeat_label');
            }
            if (!Schema::hasColumn('promotion_settings', 'promotion_pass_average')) {
                $table->decimal('promotion_pass_average', 5, 2)->nullable()->after('rule_logic');
            }
            if (!Schema::hasColumn('promotion_settings', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('promotion_pass_average');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotion_settings', function (Blueprint $table) {
            $cols = ['rule_logic', 'promotion_pass_average', 'is_active'];
            $drop = array_filter($cols, fn($c) => Schema::hasColumn('promotion_settings', $c));
            if ($drop) $table->dropColumn(array_values($drop));
        });
    }
};
