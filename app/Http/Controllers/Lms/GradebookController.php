<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Lms\Concerns\InteractsWithLms;
use App\Models\LmsCourse;
use App\Services\Lms\ProgressService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradebookController extends Controller
{
    use InteractsWithLms;

    public function __construct(protected ProgressService $progress)
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage courses|Grade coursework');
    }

    public function show(LmsCourse $course)
    {
        $this->authorizeGrade($course);
        return view('lms.gradebook', [
            'pagetitle' => 'Gradebook — ' . $course->title,
            'course'    => $course,
            'rows'      => $this->progress->gradebook($course),
        ]);
    }

    public function export(LmsCourse $course): StreamedResponse
    {
        $this->authorizeGrade($course);
        $rows = $this->progress->gradebook($course);
        $filename = 'gradebook-' . $course->id . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Admission No', 'Student', 'Progress %', 'Quiz avg %', 'Assignment avg %', 'Overall %', 'Status']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['admissionNo'], $r['name'], $r['progress'],
                    $r['quiz_avg'] ?? '', $r['assignment_avg'] ?? '', $r['overall'] ?? '', $r['status'],
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * CA-ready export: each learner's chosen LMS metric (overall / quiz / assignment)
     * scaled to a CA max, as admission-no + score — ready to paste into score-entry.
     * (A direct write into the broadsheet is intentionally not done here — see the
     * roadmap: it must be verified against the scoring engine first.)
     */
    public function exportCa(Request $request, LmsCourse $course): StreamedResponse
    {
        $this->authorizeGrade($course);

        $metric = in_array($request->query('metric'), ['overall', 'quiz_avg', 'assignment_avg'], true)
            ? $request->query('metric') : 'overall';
        $max = (float) $request->query('max', 100);
        if ($max <= 0) $max = 100;

        $rows = $this->progress->gradebook($course);
        $label = ['overall' => 'Overall', 'quiz_avg' => 'Quiz', 'assignment_avg' => 'Assignment'][$metric];
        $filename = 'lms-ca-' . $course->id . '-' . $metric . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rows, $metric, $max, $label) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Admission No', 'Student', $label . ' %', 'Score (out of ' . rtrim(rtrim(number_format($max, 2), '0'), '.') . ')']);
            foreach ($rows as $r) {
                $pct = $r[$metric] ?? null;
                $score = $pct !== null ? round($pct / 100 * $max, 2) : '';
                fputcsv($out, [$r['admissionNo'], $r['name'], $pct ?? '', $score]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
