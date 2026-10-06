<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Feature flags / module entitlements.
 *
 * Each flag is a simple on/off (1/0) that a remote control portal can set
 * through the API. A sidebar link (and, if wired, a route) is shown only when
 * its flag is on AND the signed-in user has the usual permission.
 *
 * Flags are fail-open: a key that has never been received counts as ON, so a
 * school is never crippled by an unreachable remote or a missing key. The
 * remote sends 0 to hide a module and 1 to show it again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('feature_flags')) {
            Schema::create('feature_flags', function (Blueprint $table) {
                $table->id();
                $table->string('key', 60)->unique();
                $table->string('label', 120)->nullable();
                $table->string('group', 60)->nullable();
                $table->boolean('enabled')->default(true);
                $table->boolean('remote_controlled')->default(true); // set by the remote; local toggle disabled
                $table->string('description', 255)->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();
            });
        }

        // A single row of sync configuration.
        if (!Schema::hasTable('feature_sync')) {
            Schema::create('feature_sync', function (Blueprint $table) {
                $table->id();
                $table->text('api_key')->nullable();        // encrypted; the remote presents this to us
                $table->text('remote_url')->nullable();     // where we pull flags from
                $table->text('remote_key')->nullable();     // encrypted; what we present to the remote
                $table->boolean('auto_pull')->default(false);
                $table->timestamp('last_pulled_at')->nullable();
                $table->string('last_pull_status', 255)->nullable();
                $table->timestamp('last_pushed_at')->nullable();
                $table->timestamps();
            });
            DB::table('feature_sync')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Seed the module keys the sidebar knows about (all ON by default).
        $now = now();
        $seed = [
            ['dashboard', 'Dashboards', 'Core'],
            ['users', 'Users & Privileges', 'Admin'],
            ['students', 'Student & Parents', 'Academics'],
            ['subjects', 'Subject Registration', 'Academics'],
            ['exams', 'Exams', 'Academics'],
            ['cbt', 'CBT', 'Academics'],
            ['timetable', 'Timetable', 'Academics'],
            ['classes', 'Classes & Records', 'Academics'],
            ['attendance', 'Attendance', 'Academics'],
            ['results', 'Results & Report Cards', 'Academics'],
            ['transcripts', 'Transcripts', 'Academics'],
            ['finance', 'Bursary & Finance', 'Finance'],
            ['online_payments', 'Online Payments', 'Finance'],
            ['payroll', 'Payroll', 'Finance'],
            ['accounting', 'Accounting', 'Finance'],
            ['expenses', 'Expenses & Assets', 'Finance'],
            ['scholarships', 'Scholarships & Discounts', 'Finance'],
            ['communication', 'Communication / Notices', 'Communication'],
            ['promotions', 'Promotions', 'Academics'],
            ['reports', 'Reports & Analysis', 'Reports'],
        ];
        foreach ($seed as [$key, $label, $group]) {
            if (!DB::table('feature_flags')->where('key', $key)->exists()) {
                DB::table('feature_flags')->insert([
                    'key' => $key, 'label' => $label, 'group' => $group,
                    'enabled' => true, 'remote_controlled' => true,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('feature_sync');
    }
};
