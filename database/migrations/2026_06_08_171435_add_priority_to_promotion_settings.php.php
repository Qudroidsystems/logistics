<?php
// database/migrations/2026_06_08_171435_add_priority_to_promotion_settings.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddPriorityToPromotionSettings extends Migration
{
    public function up()
    {
        Schema::table('promotion_settings', function (Blueprint $table) {
            // Check if the column exists before adding
            if (!Schema::hasColumn('promotion_settings', 'priority')) {
                $table->integer('priority')->default(0)->after('term_id');
            }
        });

        // Drop the old unique constraint safely
        $this->dropUniqueIfExists('promotion_settings', 'promotion_settings_schoolclass_id_session_id_term_id_unique');

        // Also check for other possible index names
        $this->dropUniqueIfExists('promotion_settings', 'promotion_settings_schoolclass_id_session_id_term_id_unique');
        $this->dropUniqueIfExists('promotion_settings', 'promotion_settings_schoolclass_id_session_id_term_id');

        // Add new unique constraint
        Schema::table('promotion_settings', function (Blueprint $table) {
            // Only add if columns exist
            if (Schema::hasColumn('promotion_settings', 'schoolclass_id') &&
                Schema::hasColumn('promotion_settings', 'session_id') &&
                Schema::hasColumn('promotion_settings', 'term_id')) {

                // Add the new unique constraint including priority if needed
                // Or just add a regular index if you don't want unique constraint
                $table->index(['schoolclass_id', 'session_id', 'term_id'], 'idx_promotion_class_session_term');
            }
        });
    }

    public function down()
    {
        Schema::table('promotion_settings', function (Blueprint $table) {
            // Drop the index we added
            $table->dropIndexIfExists('idx_promotion_class_session_term');

            // Drop the priority column
            if (Schema::hasColumn('promotion_settings', 'priority')) {
                $table->dropColumn('priority');
            }
        });

        // Restore the original unique constraint
        try {
            Schema::table('promotion_settings', function (Blueprint $table) {
                $table->unique(['schoolclass_id', 'session_id', 'term_id'], 'promotion_settings_schoolclass_id_session_id_term_id_unique');
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore error
        }
    }

    private function dropUniqueIfExists($table, $indexName)
    {
        try {
            // Check if the index exists
            $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);

            if (!empty($result)) {
                Schema::table($table, function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            }
        } catch (\Exception $e) {
            // Index doesn't exist or can't be dropped, continue
        }
    }
}
