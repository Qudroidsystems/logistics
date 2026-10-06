<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Lms\Concerns\InteractsWithLms;
use App\Models\LmsCourse;
use App\Models\LmsLesson;
use App\Models\LmsSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Curriculum builder: sections and lessons within a course.
 */
class ContentController extends Controller
{
    use InteractsWithLms;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage courses|Grade coursework');
    }

    // ── Sections ────────────────────────────────────────────────────────────
    public function storeSection(Request $request, LmsCourse $course)
    {
        $this->authorizeManage($course);
        $data = $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
        ]);
        $data['course_id'] = $course->id;
        $data['position']  = (int) $course->sections()->max('position') + 1;
        LmsSection::create($data);
        return back()->with('success', 'Section added.');
    }

    public function updateSection(Request $request, LmsCourse $course, LmsSection $section)
    {
        $this->authorizeManage($course);
        abort_unless($section->course_id === $course->id, 404);
        $section->update($request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'is_published'=> 'nullable|boolean',
        ]) + ['is_published' => $request->boolean('is_published')]);
        return back()->with('success', 'Section updated.');
    }

    public function destroySection(LmsCourse $course, LmsSection $section)
    {
        $this->authorizeManage($course);
        abort_unless($section->course_id === $course->id, 404);
        // detach lessons rather than delete them
        $section->lessons()->update(['section_id' => null]);
        $section->delete();
        return back()->with('success', 'Section removed (its lessons were kept).');
    }

    // ── Lessons ─────────────────────────────────────────────────────────────
    public function storeLesson(Request $request, LmsCourse $course)
    {
        $this->authorizeManage($course);
        $data = $this->lessonRules($request, $course);
        $data['course_id'] = $course->id;
        $data['position']  = (int) $course->lessons()->max('position') + 1;
        $this->handleLessonFiles($request, $data);
        LmsLesson::create($data);
        return back()->with('success', 'Lesson added.');
    }

    public function updateLesson(Request $request, LmsCourse $course, LmsLesson $lesson)
    {
        $this->authorizeManage($course);
        abort_unless($lesson->course_id === $course->id, 404);
        $data = $this->lessonRules($request, $course);
        $this->handleLessonFiles($request, $data, $lesson);
        $lesson->update($data);
        return back()->with('success', 'Lesson updated.');
    }

    public function destroyLesson(LmsCourse $course, LmsLesson $lesson)
    {
        $this->authorizeManage($course);
        abort_unless($lesson->course_id === $course->id, 404);
        if ($lesson->attachment_path) Storage::disk('public')->delete($lesson->attachment_path);
        DB::table('lms_lesson_progress')->where('lesson_id', $lesson->id)->delete();
        $lesson->delete();
        return back()->with('success', 'Lesson deleted.');
    }

    /** Persist a new lesson order (array of lesson ids). Optional section_id to
     *  also move a lesson into a section during the drag. */
    public function reorderLessons(Request $request, LmsCourse $course)
    {
        $this->authorizeManage($course);
        $order = (array) $request->input('order', []);
        $sectionId = $request->input('section_id');
        if ($sectionId !== null && $sectionId !== '' && !$course->sections()->where('id', $sectionId)->exists()) {
            $sectionId = null;
        }
        foreach (array_values($order) as $i => $id) {
            $update = ['position' => $i + 1];
            if ($request->has('section_id')) {
                $update['section_id'] = ($sectionId === '' ? null : $sectionId);
            }
            LmsLesson::where('course_id', $course->id)->where('id', (int) $id)->update($update);
        }
        return response()->json(['ok' => true]);
    }

    /** Persist a new section order (array of section ids). */
    public function reorderSections(Request $request, LmsCourse $course)
    {
        $this->authorizeManage($course);
        $order = (array) $request->input('order', []);
        foreach (array_values($order) as $i => $id) {
            LmsSection::where('course_id', $course->id)->where('id', (int) $id)->update(['position' => $i + 1]);
        }
        return response()->json(['ok' => true]);
    }

    // ── helpers ─────────────────────────────────────────────────────────────
    protected function lessonRules(Request $request, LmsCourse $course): array
    {
        $data = $request->validate([
            'title'            => 'required|string|max:200',
            'section_id'       => 'nullable|integer',
            'type'            => 'required|in:text,file,video_embed,video_upload,cbt,live',
            'content'          => 'nullable|string',
            'video_url'        => 'nullable|string|max:1000',
            'exam_id'          => 'nullable|integer',
            'duration_minutes' => 'nullable|integer|min:0|max:100000',
            'is_preview'       => 'nullable|boolean',
            'is_published'     => 'nullable|boolean',
        ]);
        // section must belong to this course
        if (!empty($data['section_id']) && !$course->sections()->where('id', $data['section_id'])->exists()) {
            $data['section_id'] = null;
        }
        $data['is_preview']   = $request->boolean('is_preview');
        $data['is_published'] = $request->boolean('is_published', true);
        return $data;
    }

    protected function handleLessonFiles(Request $request, array &$data, ?LmsLesson $lesson = null): void
    {
        $type = $data['type'] ?? ($lesson->type ?? 'text');

        if (in_array($type, ['file', 'video_upload'], true) && $request->hasFile('attachment')) {
            $request->validate([
                'attachment' => 'file|max:307200', // 300 MB
            ]);
            if ($lesson && $lesson->attachment_path) Storage::disk('public')->delete($lesson->attachment_path);
            $file = $request->file('attachment');
            $data['attachment_path'] = $file->store('lms/lessons', 'public');
            $data['attachment_name'] = $file->getClientOriginalName();
        }
    }
}
