<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The ORIGINAL holidays table
     * (2026_04_08_140215_create_holidays_table.php) defined `name`,
     * `start_date`, `end_date`, `type` as NOT NULL with no default. The app
     * was later rebuilt around a simpler single-date schema (`date`,
     * `title`, `is_full_day`, `cutoff_time`, `session_id`, `term_id` — see
     * 2026_09_06_093755_create_holidays_table.php.php and
     * 2026_09_08_061004_add_date_title_to_holidays_table.php.php), and the
     * Holiday model / HolidayController now only ever populate those newer
     * columns. Because the old columns are never written any more, every
     * insert fails with "Field 'name' doesn't have a default value" (and
     * would go on to fail on start_date/end_date/type in turn once name is
     * fixed). Making them nullable — rather than editing the original
     * migration — lets old and new-schema rows coexist without breaking
     * inserts.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `holidays` MODIFY `name` VARCHAR(100) NULL");
        DB::statement("ALTER TABLE `holidays` MODIFY `start_date` DATE NULL");
        DB::statement("ALTER TABLE `holidays` MODIFY `end_date` DATE NULL");
        DB::statement("ALTER TABLE `holidays` MODIFY `type` ENUM('public_holiday', 'school_holiday', 'exam_period', 'special_event') NULL");
    }

    public function down(): void
    {
        // Restoring NOT NULL could fail if any row now has nulls in these
        // columns, so backfill safe placeholder values from the new-schema
        // columns first.
        DB::statement("UPDATE `holidays` SET `name` = COALESCE(`name`, `title`, 'Holiday') WHERE `name` IS NULL");
        DB::statement("UPDATE `holidays` SET `start_date` = COALESCE(`start_date`, `date`, CURDATE()) WHERE `start_date` IS NULL");
        DB::statement("UPDATE `holidays` SET `end_date` = COALESCE(`end_date`, `date`, CURDATE()) WHERE `end_date` IS NULL");
        DB::statement("UPDATE `holidays` SET `type` = COALESCE(`type`, 'school_holiday') WHERE `type` IS NULL");

        DB::statement("ALTER TABLE `holidays` MODIFY `name` VARCHAR(100) NOT NULL");
        DB::statement("ALTER TABLE `holidays` MODIFY `start_date` DATE NOT NULL");
        DB::statement("ALTER TABLE `holidays` MODIFY `end_date` DATE NOT NULL");
        DB::statement("ALTER TABLE `holidays` MODIFY `type` ENUM('public_holiday', 'school_holiday', 'exam_period', 'special_event') NOT NULL");
    }
};
