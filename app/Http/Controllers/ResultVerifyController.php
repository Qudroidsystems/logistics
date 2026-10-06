<?php

namespace App\Http\Controllers;

use App\Models\SchoolInformation;
use App\Services\ResultAccessService;
use App\Support\ResultVerification;
use Illuminate\Support\Facades\DB;

/**
 * Public page opened by the QR code on a report card. Shows the result as
 * recorded in the portal so anyone (another school, an employer, a parent)
 * can compare it with the paper copy.
 */
class ResultVerifyController extends Controller
{
    public function show(string $code)
    {
        $school = SchoolInformation::getActiveSchool() ?? SchoolInformation::first();
        $parts  = ResultVerification::parse($code);
        if (!$parts) {
            return response()->view('result-verify.show', ['school' => $school, 'valid' => false], 404);
        }
        [$sid, $classId, $sessionId, $termId] = $parts;

        $student = DB::table('studentRegistration')->where('id', $sid)->first(['id', 'firstname', 'lastname', 'othername', 'admissionNo']);
        $class   = DB::table('schoolclass as c')->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')->where('c.id', $classId)
            ->selectRaw("TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(a.arm,''))) as class_label")->value('class_label');
        $term    = DB::table('schoolterm')->where('id', $termId)->value('term');
        $session = DB::table('schoolsession')->where('id', $sessionId)->value('session');

        if (!$student) {
            return response()->view('result-verify.show', ['school' => $school, 'valid' => false], 404);
        }

        // A result on hold (e.g. fees) is confirmed as genuine but scores are not shown.
        $held = false;
        if (class_exists(ResultAccessService::class)) {
            $check = app(ResultAccessService::class)->check($sid, $termId, $sessionId, false);
            $held = empty($check['allowed']);
        }

        $scores = $held ? collect() : DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->where('broadsheet_records.student_id', $sid)
            ->where('broadsheet_records.schoolclass_id', $classId)
            ->where('broadsheet_records.session_id', $sessionId)
            ->where('broadsheets.term_id', $termId)
            ->whereExists(function ($q) use ($sid, $termId, $sessionId, $classId) {
                $q->select(DB::raw(1))->from('subjectRegistrationStatus')
                    ->join('subjectclass as sjc', 'sjc.id', '=', 'subjectRegistrationStatus.subjectclassid')
                    ->join('subjectteacher as st', 'st.id', '=', 'sjc.subjectteacherid')
                    ->whereColumn('st.subjectid', 'broadsheet_records.subject_id')
                    ->where('subjectRegistrationStatus.studentid', $sid)
                    ->where('subjectRegistrationStatus.termid', $termId)
                    ->where('subjectRegistrationStatus.sessionid', $sessionId)
                    ->where('sjc.schoolclassid', $classId);
            })
            ->orderBy('subject.subject')
            ->get(['subject.subject as subject', 'broadsheets.total', 'broadsheets.grade', 'broadsheets.updated_at']);

        $count   = $scores->count();
        $sum     = $scores->sum(fn ($s) => (float) $s->total);
        $average = $count ? round($sum / $count, 2) : null;
        $updated = $scores->max('updated_at');

        return view('result-verify.show', [
            'school'  => $school,
            'valid'   => true,
            'held'    => $held,
            'student' => $student,
            'class'   => $class,
            'term'    => $term,
            'session' => $session,
            'scores'  => $scores,
            'total'   => $sum,
            'average' => $average,
            'updated' => $updated,
            'code'    => $code,
        ]);
    }
}
