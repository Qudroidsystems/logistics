<?php
// database/migrations/2026_06_08_171435_allow_multiple_promotion_settings.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AllowMultiplePromotionSettings extends Migration
{
    public function up()
    {
        Schema::table('promotion_settings', function (Blueprint $table) {
            // Add a unique identifier for multiple rule sets
            if (!Schema::hasColumn('promotion_settings', 'rule_set_name')) {
                $table->string('rule_set_name')->nullable()->after('term_id');
            }

            // Add priority for ordering rule sets
            if (!Schema::hasColumn('promotion_settings', 'priority')) {
                $table->integer('priority')->default(0)->after('rule_set_name');
            }

            // Add status for individual rule sets
            if (!Schema::hasColumn('promotion_settings', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_active');
            }
        });

        // Drop existing unique constraints if they exist
        $this->dropConstraintIfExists('promotion_settings', 'promotion_settings_schoolclass_id_session_id_term_id_unique');
        $this->dropConstraintIfExists('promotion_settings', 'promotion_settings_schoolclass_id_session_id_term_id');

        // Add new composite unique constraint that allows multiple rule sets per class
        try {
            DB::statement('ALTER TABLE `promotion_settings` ADD UNIQUE KEY `unique_promotion_rule_set` (`schoolclass_id`, `session_id`, `term_id`, `rule_set_name`)');
        } catch (\Exception $e) {
            // Constraint might already exist
        }
    }

    public function down()
    {
        Schema::table('promotion_settings', function (Blueprint $table) {
            $table->dropColumn(['rule_set_name', 'priority', 'is_default']);
        });
    }

    private function dropConstraintIfExists($table, $constraintName)
    {
        try {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$constraintName}`");
        } catch (\Exception $e) {
            // Constraint doesn't exist, continue
        }
    }
}
