<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_rule_templates', function (Blueprint $table) {
            // Add each column only if it doesn't already exist
            if (!Schema::hasColumn('promotion_rule_templates', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('rules');
            }
            if (!Schema::hasColumn('promotion_rule_templates', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('promotion_rule_templates', 'grade_scale')) {
                $table->string('grade_scale')->default('senior')->after('description');
            }
            if (!Schema::hasColumn('promotion_rule_templates', 'rules')) {
                $table->json('rules')->nullable()->after('grade_scale');
            }
            if (!Schema::hasColumn('promotion_rule_templates', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
        });

        // Do the same safety check on promotion_settings (in case any column is missing)
        Schema::table('promotion_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('promotion_settings', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('promotion_rules');
            }
            if (!Schema::hasColumn('promotion_settings', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('promotion_settings', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->after('is_default');
            }
            if (!Schema::hasColumn('promotion_settings', 'rule_logic')) {
                $table->string('rule_logic')->default('grade_count')->after('repeat_label');
            }
            if (!Schema::hasColumn('promotion_settings', 'promotion_pass_average')) {
                $table->float('promotion_pass_average')->nullable()->after('rule_logic');
            }
            if (!Schema::hasColumn('promotion_settings', 'priority')) {
                $table->integer('priority')->default(999)->after('rule_set_name');
            }
            if (!Schema::hasColumn('promotion_settings', 'rule_set_name')) {
                $table->string('rule_set_name')->default('Custom Rules')->after('term_id');
            }
            if (!Schema::hasColumn('promotion_settings', 'promoted_label')) {
                $table->string('promoted_label')->default('PROMOTED')->after('priority');
            }
            if (!Schema::hasColumn('promotion_settings', 'trial_label')) {
                $table->string('trial_label')->default('PROMOTED ON TRIAL')->after('promoted_label');
            }
            if (!Schema::hasColumn('promotion_settings', 'see_principal_label')) {
                $table->string('see_principal_label')->default('PARENTS TO SEE PRINCIPAL')->after('trial_label');
            }
            if (!Schema::hasColumn('promotion_settings', 'repeat_label')) {
                $table->string('repeat_label')->default('ADVISED TO REPEAT')->after('see_principal_label');
            }
        });
    }

    public function down(): void
    {
        // No rollback needed — we're only adding safety columns
    }
};