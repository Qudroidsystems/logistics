<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityMembership extends Model
{
    protected $fillable = ['type', 'activity_id', 'student_id', 'session_id', 'role', 'team_id', 'joined_on', 'source', 'added_by', 'note'];

    protected $casts = ['joined_on' => 'date'];

    /**
     * Keep the old student-form pick (studentclubs / studentsports) in step:
     * called from those models' saved events.
     */
    public static function syncFromForm(string $type, $studentId, $newId, $oldId, $sessionId): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('activity_memberships')) return;
        $sessionId = $sessionId ?: \Illuminate\Support\Facades\DB::table('schoolsession')->where('status', 'Current')->value('id');
        if (!$sessionId || !$studentId) return;

        if ($oldId && (string) $oldId !== (string) $newId) {
            static::where(['type' => $type, 'activity_id' => $oldId, 'student_id' => $studentId, 'session_id' => $sessionId, 'source' => 'form'])->delete();
        }
        if ($newId) {
            static::firstOrCreate(
                ['type' => $type, 'activity_id' => $newId, 'student_id' => $studentId, 'session_id' => $sessionId],
                ['role' => 'member', 'source' => 'form', 'joined_on' => now()->toDateString()]
            );
        }
    }
}
