<?php

namespace App\Http\Middleware;

use App\Models\FeatureFlag;
use Closure;
use Illuminate\Http\Request;

/**
 * Blocks a module's pages when its feature flag is OFF, even if the URL is
 * typed directly. Matches the current route name against a prefix → flag-key
 * map. Fail-open: a flag that is missing or has never been set never blocks,
 * and core routes (login, dashboard, self-service, maintenance, this module's
 * own admin page) are always allowed so no one can be stranded.
 */
class FeatureRouteGuard
{
    /** Route-name prefix => feature key. First match wins. */
    protected array $map;

    /** Route-name prefix => feature key. Shared with the API catalog. */
    public static function moduleRouteMap(): array
    {
        return [
        'accounting.' => 'accounting',
        'payroll.' => 'payroll',
        'staff.payments.' => 'payroll',
        'finance.' => 'expenses',
        'online-fees.' => 'online_payments',
        'instalment-plans.' => 'finance',
        'schoolpayment.' => 'finance',
        'payment.' => 'finance',
        'sibling.' => 'finance',
        'admin.scholarship.' => 'scholarships',
        'admin.discount.' => 'scholarships',
        'reports.financial.' => 'accounting',
        'reports.analysis.' => 'reports',
        'analysis.' => 'reports',
        'exams.' => 'exams',
        'questions.' => 'exams',
        'assessments' => 'exams',
        'cbt.' => 'cbt',
        'timetable.' => 'timetable',
        'rooms.' => 'timetable',
        'exam-timetable.' => 'timetable',
        'holidays.' => 'timetable',
        'subjectoperation.' => 'subjects',
        'subjects.' => 'subjects',
        'transcript.' => 'transcripts',
        'promotions.' => 'promotions',
        'promotion-settings.' => 'promotions',
        'promotion.' => 'promotions',
        'report-approvals.' => 'results',
        'studentreports.' => 'results',
        'studentmockreports.' => 'results',
        'broadsheet.' => 'results',
        'myresultroom.' => 'results',
        'admin.score-entry.' => 'results',
        'student-id-cards.' => 'students',
        'lms.' => 'elearning',
        'studentbatch' => 'students',
        ];
    }

    /** Route names that are never blocked (prefix match). */
    protected array $allow;

    public static function allowList(): array
    {
        return [
        'login', 'logout', 'password', 'dashboard', 'home', 'management.dashboard',
        'maintenance.', 'feature-flags.', 'my-pay.', 'leave.', 'parent.',
        'profile.', 'users.', 'roles.', 'permissions.', 'notifications.',
        'admin.payment-gateways.', 'student.payments', 'student.fees',
        ];
    }

    public function __construct()
    {
        $this->map = self::moduleRouteMap();
        $this->allow = self::allowList();
    }

    public function handle(Request $request, Closure $next)
    {
        $name = optional($request->route())->getName();
        if (!$name) {
            return $next($request);
        }

        foreach ($this->allow as $a) {
            if ($name === $a || str_starts_with($name, $a)) {
                return $next($request);
            }
        }

        foreach ($this->map as $prefix => $key) {
            if ($name === $prefix || str_starts_with($name, $prefix)) {
                // Fail-open: only an explicit OFF blocks.
                if (!FeatureFlag::enabled($key)) {
                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'This module is currently switched off.'], 403);
                    }
                    $to = \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/home');
                    return redirect($to)->with('error', 'That section is currently switched off for this school.');
                }
                break;
            }
        }

        return $next($request);
    }
}
