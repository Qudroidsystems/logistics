<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clubs & sports: a student can belong to many of each, per session, with a
 * role (president, captain…). Copies the old one-club / one-sport picks.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('activity_memberships')) {
            Schema::create('activity_memberships', function (Blueprint $table) {
                $table->id();
                $table->string('type', 10);                          // club | sport
                $table->unsignedBigInteger('activity_id');            // clubs.id / sports.id
                $table->unsignedBigInteger('student_id');             // studentRegistration.id
                $table->unsignedBigInteger('session_id');
                $table->string('role', 40)->default('member');
                $table->unsignedBigInteger('team_id')->nullable();    // sport_teams.id
                $table->date('joined_on')->nullable();
                $table->string('source', 10)->default('manual');      // manual | form (student form)
                $table->unsignedBigInteger('added_by')->nullable();
                $table->string('note', 255)->nullable();
                $table->timestamps();

                $table->unique(['type', 'activity_id', 'student_id', 'session_id'], 'uq_activity_member');
                $table->index(['student_id', 'session_id']);
                $table->index(['type', 'activity_id', 'session_id']);
            });
        }

        foreach (['clubs', 'sports'] as $t) {
            if (!Schema::hasTable($t)) continue;
            Schema::table($t, function (Blueprint $table) use ($t) {
                if (!Schema::hasColumn($t, 'meeting_day'))  $table->string('meeting_day', 20)->nullable();
                if (!Schema::hasColumn($t, 'meeting_time')) $table->string('meeting_time', 20)->nullable();
                if (!Schema::hasColumn($t, 'venue'))        $table->string('venue', 120)->nullable();
                if (!Schema::hasColumn($t, 'capacity'))     $table->unsignedInteger('capacity')->nullable();
                if (!Schema::hasColumn($t, 'is_active'))    $table->boolean('is_active')->default(true);
            });
        }

        // Copy the old single picks.
        $current = DB::table('schoolsession')->where('status', 'Current')->value('id');
        foreach ([['studentclubs', 'clubid', 'club'], ['studentsports', 'sportid', 'sport']] as [$tbl, $col, $type]) {
            if (!Schema::hasTable($tbl)) continue;
            DB::table($tbl)->whereNotNull($col)->orderBy('studentid')->chunk(500, function ($rows) use ($col, $type, $current) {
                $insert = [];
                foreach ($rows as $r) {
                    $session = $r->sessionid ?: $current;
                    if (!$session) continue;
                    $insert[] = ['type' => $type, 'activity_id' => $r->$col, 'student_id' => $r->studentid, 'session_id' => $session,
                                 'role' => 'member', 'source' => 'form', 'joined_on' => $r->created_at ? substr((string) $r->created_at, 0, 10) : null,
                                 'created_at' => now(), 'updated_at' => now()];
                }
                if ($insert) DB::table('activity_memberships')->insertOrIgnore($insert);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_memberships');
    }
};
