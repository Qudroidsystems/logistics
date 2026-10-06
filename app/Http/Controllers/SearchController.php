<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\User;
use App\Models\Staff;
use App\Models\Schoolclass;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\Subject;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Assessment;
use App\Models\SubAssessment;
use App\Models\TimetableSlot;
use App\Models\Room;
use App\Models\AttendanceHoliday;
use App\Models\PayrollPeriod;
use App\Models\Discount;
use App\Models\Scholarship;
use App\Models\SiblingGroup;
use App\Models\SchoolBill;
use App\Models\SchoolInformation;

class SearchController extends Controller
{
    /**
     * Main search endpoint that returns results with navigation URLs
     */
    public function search(Request $request)
    {
        $query = trim($request->get('q', ''));
        $results = [];

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        // ============================================================
        // STUDENTS
        // ============================================================
        if (auth()->user()->can('View student')) {
            Student::where(function($q) use ($query) {
                    $q->where('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name', 'like', "%{$query}%")
                      ->orWhere('admissionNo', 'like', "%{$query}%")
                      ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$query}%");
                })
                ->limit(8)
                ->get()
                ->each(function ($student) use (&$results) {
                    $results[] = [
                        'title'     => $student->first_name . ' ' . $student->last_name,
                        'subtitle'  => 'Student · ' . ($student->admissionNo ?? 'No Admission No'),
                        'url'       => route('student.overview', $student->id),
                        'icon'      => 'mdi-school',
                        'category'  => 'Students',
                        'model_type'=> 'student',
                        'model_id'  => $student->id,
                    ];
                });
        }

        // ============================================================
        // USERS / STAFF
        // ============================================================
        if (auth()->user()->can('View user')) {
            User::where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                })
                ->limit(6)
                ->get()
                ->each(function ($user) use (&$results) {
                    $roleNames = $user->roles->pluck('name')->take(2)->implode(', ');
                    $results[] = [
                        'title'     => $user->name,
                        'subtitle'  => 'User · ' . $user->email . ($roleNames ? ' (' . $roleNames . ')' : ''),
                        'url'       => route('users.overview', $user->id),
                        'icon'      => 'mdi-account',
                        'category'  => 'Users',
                        'model_type'=> 'user',
                        'model_id'  => $user->id,
                    ];
                });
        }

        // ============================================================
        // CLASSES
        // ============================================================
        if (auth()->user()->can('View school-class')) {
            Schoolclass::where('class_name', 'like', "%{$query}%")
                ->limit(5)
                ->get()
                ->each(function ($class) use (&$results) {
                    $results[] = [
                        'title'     => $class->class_name,
                        'subtitle'  => 'Class · ' . ($class->classcategory?->category ?? 'N/A'),
                        'url'       => route('schoolclass.index') . '?search=' . urlencode($class->class_name),
                        'icon'      => 'mdi-google-classroom',
                        'category'  => 'Classes',
                        'model_type'=> 'class',
                        'model_id'  => $class->id,
                    ];
                });
        }

        // ============================================================
        // SUBJECTS
        // ============================================================
        if (auth()->user()->can('View subjects')) {
            Subject::where('subject_name', 'like', "%{$query}%")
                ->orWhere('subject_code', 'like', "%{$query}%")
                ->limit(5)
                ->get()
                ->each(function ($subject) use (&$results) {
                    $results[] = [
                        'title'     => $subject->subject_name,
                        'subtitle'  => 'Subject · ' . ($subject->subject_code ?? 'No Code'),
                        'url'       => route('subject.index') . '?search=' . urlencode($subject->subject_name),
                        'icon'      => 'mdi-book-open-variant',
                        'category'  => 'Subjects',
                        'model_type'=> 'subject',
                        'model_id'  => $subject->id,
                    ];
                });
        }

        // ============================================================
        // EXAMS
        // ============================================================
        if (auth()->user()->can('View exam')) {
            Exam::where('title', 'like', "%{$query}%")
                ->limit(5)
                ->get()
                ->each(function ($exam) use (&$results) {
                    $results[] = [
                        'title'     => $exam->title,
                        'subtitle'  => 'Exam · ' . ($exam->subject?->subject_name ?? 'N/A'),
                        'url'       => route('exams.show', $exam->id),
                        'icon'      => 'mdi-clipboard-text',
                        'category'  => 'Exams',
                        'model_type'=> 'exam',
                        'model_id'  => $exam->id,
                    ];
                });
        }

        // ============================================================
        // QUESTIONS
        // ============================================================
        if (auth()->user()->can('View question')) {
            Question::where('question_text', 'like', "%{$query}%")
                ->limit(4)
                ->get()
                ->each(function ($question) use (&$results) {
                    $results[] = [
                        'title'     => substr($question->question_text, 0, 60) . (strlen($question->question_text) > 60 ? '...' : ''),
                        'subtitle'  => 'Question · ' . ($question->exam?->title ?? 'N/A'),
                        'url'       => route('questions.edit', $question->id),
                        'icon'      => 'mdi-help-circle',
                        'category'  => 'Questions',
                        'model_type'=> 'question',
                        'model_id'  => $question->id,
                    ];
                });
        }

        // ============================================================
        // ASSESSMENTS
        // ============================================================
        Assessment::where('name', 'like', "%{$query}%")
            ->limit(4)
            ->get()
            ->each(function ($assessment) use (&$results) {
                $results[] = [
                    'title'     => $assessment->name,
                    'subtitle'  => 'Assessment · Max Score: ' . $assessment->max_score,
                    'url'       => '#', // No direct edit route, show in context
                    'icon'      => 'mdi-chart-box',
                    'category'  => 'Assessments',
                    'model_type'=> 'assessment',
                    'model_id'  => $assessment->id,
                ];
            });

        // ============================================================
        // SUB-ASSESSMENTS
        // ============================================================
        SubAssessment::where('name', 'like', "%{$query}%")
            ->limit(3)
            ->get()
            ->each(function ($subAssessment) use (&$results) {
                $results[] = [
                    'title'     => $subAssessment->name,
                    'subtitle'  => 'Sub-Assessment · Max Score: ' . $subAssessment->max_score,
                    'url'       => '#',
                    'icon'      => 'mdi-chart-bell-curve',
                    'category'  => 'Sub-Assessments',
                    'model_type'=> 'sub_assessment',
                    'model_id'  => $subAssessment->id,
                ];
            });

        // ============================================================
        // ROOMS
        // ============================================================
        if (auth()->user()->can('View rooms')) {
            Room::where('room_name', 'like', "%{$query}%")
                ->orWhere('room_code', 'like', "%{$query}%")
                ->limit(4)
                ->get()
                ->each(function ($room) use (&$results) {
                    $results[] = [
                        'title'     => $room->room_name,
                        'subtitle'  => 'Room · ' . ($room->room_code ?? 'No Code') . ' · Capacity: ' . ($room->capacity ?? 'N/A'),
                        'url'       => route('rooms.show', $room->id),
                        'icon'      => 'mdi-door',
                        'category'  => 'Rooms',
                        'model_type'=> 'room',
                        'model_id'  => $room->id,
                    ];
                });
        }

        // ============================================================
        // BILLS / FEES
        // ============================================================
        if (auth()->user()->can('View school-bills')) {
            SchoolBill::where('bill_name', 'like', "%{$query}%")
                ->limit(4)
                ->get()
                ->each(function ($bill) use (&$results) {
                    $results[] = [
                        'title'     => $bill->bill_name,
                        'subtitle'  => 'Fee/Bill · Amount: ' . number_format($bill->amount, 2),
                        'url'       => route('schoolbill.show', $bill->id),
                        'icon'      => 'mdi-receipt',
                        'category'  => 'Fees & Bills',
                        'model_type'=> 'bill',
                        'model_id'  => $bill->id,
                    ];
                });
        }

        // ============================================================
        // DISCOUNTS
        // ============================================================
        if (auth()->user()->can('View discount')) {
            Discount::where('title', 'like', "%{$query}%")
                ->limit(4)
                ->get()
                ->each(function ($discount) use (&$results) {
                    $valueDisplay = $discount->value_type === 'percentage'
                        ? $discount->value . '%'
                        : '₦' . number_format($discount->value, 2);
                    $results[] = [
                        'title'     => $discount->title,
                        'subtitle'  => 'Discount · ' . $valueDisplay,
                        'url'       => route('admin.discount.show', $discount->id),
                        'icon'      => 'mdi-tag',
                        'category'  => 'Discounts',
                        'model_type'=> 'discount',
                        'model_id'  => $discount->id,
                    ];
                });
        }

        // ============================================================
        // SCHOLARSHIPS
        // ============================================================
        if (auth()->user()->can('View scholarship')) {
            Scholarship::where('title', 'like', "%{$query}%")
                ->limit(4)
                ->get()
                ->each(function ($scholarship) use (&$results) {
                    $results[] = [
                        'title'     => $scholarship->title,
                        'subtitle'  => 'Scholarship · ' . ($scholarship->value_type === 'percentage' ? $scholarship->value . '%' : '₦' . number_format($scholarship->value, 2)),
                        'url'       => route('admin.scholarship.show', $scholarship->id),
                        'icon'      => 'mdi-medal',
                        'category'  => 'Scholarships',
                        'model_type'=> 'scholarship',
                        'model_id'  => $scholarship->id,
                    ];
                });
        }

        // ============================================================
        // SIBLING GROUPS
        // ============================================================
        if (auth()->user()->can('View sibling groups')) {
            SiblingGroup::where('group_name', 'like', "%{$query}%")
                ->limit(3)
                ->get()
                ->each(function ($group) use (&$results) {
                    $results[] = [
                        'title'     => $group->group_name,
                        'subtitle'  => 'Sibling Group · ' . $group->students()->count() . ' students',
                        'url'       => route('sibling.show', $group->id),
                        'icon'      => 'mdi-account-multiple',
                        'category'  => 'Sibling Groups',
                        'model_type'=> 'sibling_group',
                        'model_id'  => $group->id,
                    ];
                });
        }

        // ============================================================
        // TIMETABLE SLOTS (for teacher search)
        // ============================================================
        if (auth()->user()->can('View timetable') && auth()->user()->isStaff()) {
            TimetableSlot::whereHas('subject', function($q) use ($query) {
                    $q->where('subject_name', 'like', "%{$query}%");
                })
                ->where('teacher_id', auth()->id())
                ->limit(3)
                ->get()
                ->each(function ($slot) use (&$results) {
                    $results[] = [
                        'title'     => $slot->subject?->subject_name ?? 'Unknown Subject',
                        'subtitle'  => 'Timetable · ' . ucfirst($slot->day) . ' Period ' . ($slot->period?->period_number ?? 'N/A'),
                        'url'       => route('timetable.teacher'),
                        'icon'      => 'mdi-table-clock',
                        'category'  => 'My Timetable',
                        'model_type'=> 'timetable_slot',
                        'model_id'  => $slot->id,
                    ];
                });
        }

        // ============================================================
        // PAYROLL PERIODS
        // ============================================================
        if (auth()->user()->can('View payroll')) {
            PayrollPeriod::where('period_name', 'like', "%{$query}%")
                ->limit(3)
                ->get()
                ->each(function ($period) use (&$results) {
                    $results[] = [
                        'title'     => $period->period_name,
                        'subtitle'  => 'Payroll Period · ' . ($period->status ?? 'Unknown'),
                        'url'       => route('payroll.summary'),
                        'icon'      => 'mdi-cash-multiple',
                        'category'  => 'Payroll',
                        'model_type'=> 'payroll_period',
                        'model_id'  => $period->id,
                    ];
                });
        }

        // ============================================================
        // ACTIVE TERM & SESSION (always useful)
        // ============================================================
        $activeTerm = Schoolterm::where('status', true)->first();
        $activeSession = Schoolsession::where('status', 'Current')->first();

        if ($activeTerm && stripos($activeTerm->term, $query) !== false) {
            $results[] = [
                'title'     => 'Active Term: ' . $activeTerm->term,
                'subtitle'  => 'Current Academic Term',
                'url'       => route('term.index'),
                'icon'      => 'mdi-calendar',
                'category'  => 'System Settings',
                'model_type'=> 'term',
                'model_id'  => $activeTerm->id,
            ];
        }

        if ($activeSession && stripos($activeSession->session, $query) !== false) {
            $results[] = [
                'title'     => 'Active Session: ' . $activeSession->session,
                'subtitle'  => 'Current Academic Session',
                'url'       => route('session.index'),
                'icon'      => 'mdi-calendar-range',
                'category'  => 'System Settings',
                'model_type'=> 'session',
                'model_id'  => $activeSession->id,
            ];
        }

        // ============================================================
        // STATIC PAGE LINKS (from previous implementation)
        // ============================================================
        $staticPages = $this->getStaticPages();
        foreach ($staticPages as $page) {
            if (stripos($page['title'], $query) !== false || stripos($page['category'], $query) !== false) {
                $results[] = $page;
            }
        }

        // Remove duplicates by URL
        $seen = [];
        $deduped = array_filter($results, function($r) use (&$seen) {
            if (isset($seen[$r['url']])) {
                return false;
            }
            $seen[$r['url']] = true;
            return true;
        });

        // Limit total results
        $deduped = array_slice($deduped, 0, 30);

        return response()->json(['results' => array_values($deduped)]);
    }

    /**
     * Get all static page links for the application
     */
    private function getStaticPages(): array
    {
        return [
            // Dashboards
            ['title' => 'Administration Dashboard', 'url' => route('dashboard'), 'icon' => 'mdi-gauge', 'category' => 'Dashboards'],

            // Users & Privileges
            ['title' => 'User Management', 'url' => route('users.index'), 'icon' => 'mdi-account-group', 'category' => 'Users & Privileges'],
            ['title' => 'Roles', 'url' => route('roles.index'), 'icon' => 'mdi-shield-account', 'category' => 'Users & Privileges'],
            ['title' => 'Permissions', 'url' => route('permissions.index'), 'icon' => 'mdi-lock', 'category' => 'Users & Privileges'],

            // Students
            ['title' => 'All Students', 'url' => route('student.index'), 'icon' => 'mdi-school', 'category' => 'Students'],
            ['title' => 'Batch Student Registration', 'url' => route('studentbatchindex'), 'icon' => 'mdi-account-multiple-plus', 'category' => 'Students'],
            ['title' => 'ID Card Generator', 'url' => route('student-id-cards.index'), 'icon' => 'mdi-card-account-details', 'category' => 'Students'],
            ['title' => 'Student Promotions', 'url' => route('promotions.index'), 'icon' => 'mdi-arrow-up-circle', 'category' => 'Students'],

            // My Account
            ['title' => 'My Profile', 'url' => route('users.overview', auth()->id()), 'icon' => 'mdi-account-circle', 'category' => 'My Account'],
            ['title' => 'Account Settings', 'url' => route('profile.settings', auth()->id()), 'icon' => 'mdi-cog', 'category' => 'My Account'],

            // School Settings
            ['title' => 'School Information', 'url' => route('school-information.index'), 'icon' => 'mdi-domain', 'category' => 'School Settings'],
            ['title' => 'School Session', 'url' => route('session.index'), 'icon' => 'mdi-calendar-range', 'category' => 'School Settings'],
            ['title' => 'School Term', 'url' => route('term.index'), 'icon' => 'mdi-calendar', 'category' => 'School Settings'],
            ['title' => 'School House', 'url' => route('schoolhouse.index'), 'icon' => 'mdi-home-group', 'category' => 'School Settings'],
            ['title' => 'Class Arm', 'url' => route('schoolarm.index'), 'icon' => 'mdi-table-chair', 'category' => 'School Settings'],
            ['title' => 'Class Category', 'url' => route('classcategories.index'), 'icon' => 'mdi-format-list-bulleted', 'category' => 'School Settings'],
            ['title' => 'Class Name', 'url' => route('schoolclass.index'), 'icon' => 'mdi-google-classroom', 'category' => 'School Settings'],
            ['title' => 'Class Teacher', 'url' => route('classteacher.index'), 'icon' => 'mdi-human-male-board', 'category' => 'School Settings'],

            // Subjects
            ['title' => 'Subjects', 'url' => route('subject.index'), 'icon' => 'mdi-book-open-variant', 'category' => 'Subjects'],
            ['title' => 'Assign Subject Teacher', 'url' => route('subjectteacher.index'), 'icon' => 'mdi-account-tie', 'category' => 'Subjects'],
            ['title' => 'Assign Class Subject', 'url' => route('subjectclass.index'), 'icon' => 'mdi-book-plus', 'category' => 'Subjects'],

            // Subject Registration
            ['title' => 'Student Subject Registration', 'url' => route('subjectoperation.index'), 'icon' => 'mdi-clipboard-list', 'category' => 'Subject Registration'],

            // Classes & Records
            ['title' => 'My Class', 'url' => route('myclass.index'), 'icon' => 'mdi-google-classroom', 'category' => 'Classes & Records'],
            ['title' => 'My Subject', 'url' => route('mysubject.index'), 'icon' => 'mdi-book-open', 'category' => 'Classes & Records'],
            ['title' => 'Subjects to Vet', 'url' => route('mysubjectvettings.index'), 'icon' => 'mdi-check-decagram', 'category' => 'Classes & Records'],

            // Records & Results
            ['title' => 'Terminal Records', 'url' => route('myresultroom.index'), 'icon' => 'mdi-file-chart', 'category' => 'Records & Results'],
            ['title' => 'Terminal Result Reports', 'url' => route('studentreports.index'), 'icon' => 'mdi-file-document', 'category' => 'Records & Results'],
            ['title' => 'Terminal Result Broadsheet', 'url' => route('broadsheet.index'), 'icon' => 'mdi-table-large', 'category' => 'Records & Results'],
            ['title' => 'Mock Result Reports', 'url' => route('studentmockreports.index'), 'icon' => 'mdi-file-document-edit', 'category' => 'Records & Results'],

            // Exams & CBT
            ['title' => 'All Examinations', 'url' => route('exams.index'), 'icon' => 'mdi-clipboard-text', 'category' => 'Exams & CBT'],
            ['title' => 'Questions Management', 'url' => route('questions.all'), 'icon' => 'mdi-help-circle', 'category' => 'Exams & CBT'],
            ['title' => 'CBT Exercise', 'url' => route('cbt.index'), 'icon' => 'mdi-monitor', 'category' => 'Exams & CBT'],

            // Timetable
            ['title' => 'Admin Timetable', 'url' => route('timetable.index'), 'icon' => 'mdi-table-clock', 'category' => 'Timetable'],
            ['title' => 'My Timetable', 'url' => route('timetable.teacher'), 'icon' => 'mdi-calendar-clock', 'category' => 'Timetable'],
            ['title' => 'Room Management', 'url' => route('rooms.index'), 'icon' => 'mdi-door', 'category' => 'Timetable'],
            ['title' => 'Exam Timetable', 'url' => route('exam-timetable.index'), 'icon' => 'mdi-calendar-check', 'category' => 'Timetable'],
            ['title' => 'Holidays', 'url' => route('holidays.index'), 'icon' => 'mdi-calendar-blank', 'category' => 'Timetable'],

            // Attendance
            ['title' => 'Mark Attendance', 'url' => route('attendance.my-classes'), 'icon' => 'mdi-clipboard-check', 'category' => 'Attendance'],
            ['title' => 'Attendance Settings', 'url' => route('attendance.settings'), 'icon' => 'mdi-cog', 'category' => 'Attendance'],
            ['title' => 'Attendance School Report', 'url' => route('attendance.school-report'), 'icon' => 'mdi-chart-line', 'category' => 'Attendance'],

            // Finance
            ['title' => 'Student Bill', 'url' => route('schoolpayment.index'), 'icon' => 'mdi-receipt', 'category' => 'Finance'],
            ['title' => 'Payment Portal', 'url' => route('payment.index'), 'icon' => 'mdi-wallet', 'category' => 'Finance'],
            ['title' => 'Payment Analysis', 'url' => route('analysis.index'), 'icon' => 'mdi-chart-bar', 'category' => 'Finance'],
            ['title' => 'All Scholarships', 'url' => route('admin.scholarship.index'), 'icon' => 'mdi-medal', 'category' => 'Finance'],
            ['title' => 'All Discounts', 'url' => route('admin.discount.index'), 'icon' => 'mdi-tag-multiple', 'category' => 'Finance'],
            ['title' => 'Sibling Family Groups', 'url' => route('sibling.index'), 'icon' => 'mdi-account-multiple', 'category' => 'Finance'],
            ['title' => 'Payment Gateways', 'url' => route('admin.payment-gateways.index'), 'icon' => 'mdi-credit-card', 'category' => 'Finance'],

            // Payroll
            ['title' => 'Payroll Periods', 'url' => route('payroll.periods'), 'icon' => 'mdi-calendar-clock', 'category' => 'Payroll'],
            ['title' => 'Payroll Summary', 'url' => route('payroll.summary'), 'icon' => 'mdi-cash-multiple', 'category' => 'Payroll'],
            ['title' => 'Salary Structures', 'url' => route('payroll.salary-structures'), 'icon' => 'mdi-bank', 'category' => 'Payroll'],
            ['title' => 'Staff Payments', 'url' => route('staff.payments.index'), 'icon' => 'mdi-cash', 'category' => 'Payroll'],

            // Accounting Reports
            ['title' => 'Balance Sheet', 'url' => route('reports.financial.balance-sheet'), 'icon' => 'mdi-scale-balance', 'category' => 'Accounting'],
            ['title' => 'Income Statement', 'url' => route('reports.financial.income-statement'), 'icon' => 'mdi-chart-line', 'category' => 'Accounting'],
            ['title' => 'Cash Flow', 'url' => route('reports.financial.cash-flow'), 'icon' => 'mdi-cash-refund', 'category' => 'Accounting'],
            ['title' => 'Student Debtors List', 'url' => route('reports.financial.debtors'), 'icon' => 'mdi-account-alert', 'category' => 'Accounting'],
            ['title' => 'Class Analysis', 'url' => route('reports.analysis.index'), 'icon' => 'mdi-school', 'category' => 'Accounting'],

            // Transcript
            ['title' => 'Generate Transcript', 'url' => route('transcript.index'), 'icon' => 'mdi-file-account', 'category' => 'Transcripts'],

            // Admin Tools
            ['title' => 'Admin Score Entry', 'url' => route('admin.score-entry.index'), 'icon' => 'mdi-clipboard-edit', 'category' => 'Admin Tools'],
            ['title' => 'Score Entry Lock Management', 'url' => route('admin.score-entry.lock-management'), 'icon' => 'mdi-lock', 'category' => 'Admin Tools'],
            ['title' => 'Student Result Manager', 'url' => route('admin.score-entry.student-result-manager'), 'icon' => 'mdi-chart-line', 'category' => 'Admin Tools'],
        ];
    }
}
