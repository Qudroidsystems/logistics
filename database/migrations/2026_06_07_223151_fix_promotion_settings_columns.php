<?php
// database/migrations/xxxx_xx_xx_xxxxxx_fix_promotion_settings_columns.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixPromotionSettingsColumns extends Migration
{
    public function up()
    {
        Schema::table('promotion_settings', function (Blueprint $table) {
            // Fix rule_type column length
            if (Schema::hasColumn('promotion_settings', 'rule_type')) {
                $table->string('rule_type', 50)->default('custom_rules')->change();
            } else {
                $table->string('rule_type', 50)->default('custom_rules');
            }

            // Add promotion_rules column if it doesn't exist
            if (!Schema::hasColumn('promotion_settings', 'promotion_rules')) {
                $table->text('promotion_rules')->nullable();
            }

            // Ensure other string columns have sufficient length
            if (Schema::hasColumn('promotion_settings', 'promoted_label')) {
                $table->string('promoted_label', 100)->default('Promoted')->change();
            }
            if (Schema::hasColumn('promotion_settings', 'trial_label')) {
                $table->string('trial_label', 100)->default('Promoted on Trial')->change();
            }
            if (Schema::hasColumn('promotion_settings', 'see_principal_label')) {
                $table->string('see_principal_label', 100)->default('Advised to See Principal')->change();
            }
            if (Schema::hasColumn('promotion_settings', 'repeat_label')) {
                $table->string('repeat_label', 100)->default('Advice to Repeat')->change();
            }
        });
    }

    public function down()
    {
        Schema::table('promotion_settings', function (Blueprint $table) {
            $table->string('rule_type', 20)->default('compulsory_only')->change();
        });
    }
}
