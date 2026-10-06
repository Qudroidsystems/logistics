<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Messaging\MessagingService;
use App\Services\Parents\ParentAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Admin: parent portal accounts — create from records, send login details,
 * link/unlink children, disable.
 */
class ParentAccountController extends Controller
{
    public function __construct(protected ParentAccountService $parents)
    {
        $this->middleware('permission:Manage parent accounts');
    }

    public function index(Request $request)
    {
        $role = ParentAccountService::role();
        $q = User::query()
            ->whereHas('roles', fn ($r) => $r->where('id', $role->id))
            ->withCount(['parentChildren as children_count']);

        if ($s = trim((string) $request->get('search'))) {
            $phones = ParentAccountService::phoneVariants($s);
            $q->where(function ($w) use ($s, $phones) {
                $w->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->orWhere('phone_number', 'like', "%$s%");
                if ($phones) $w->orWhereIn('phone_number', $phones);
                $w->orWhereIn('id', DB::table('parent_student as ps')->join('studentRegistration as st', 'st.id', '=', 'ps.student_id')
                    ->where(fn ($x) => $x->where('st.admissionNo', 'like', "%$s%")->orWhereRaw("CONCAT(st.firstname,' ',st.lastname) LIKE ?", ["%$s%"]))
                    ->select('ps.user_id'));
            });
        }
        match ($request->get('status')) {
            'never_sent' => $q->whereNull('credentials_sent_at'),
            'never_login' => $q->whereNull('last_login_at'),
            'active'     => $q->whereNotNull('last_login_at'),
            'disabled'   => $q->where('is_disabled', true),
            default      => null,
        };

        $accounts = $q->orderBy('name')->paginate(25)->withQueryString();
        $ids = $accounts->pluck('id')->all();
        $kids = DB::table('parent_student as ps')->join('studentRegistration as st', 'st.id', '=', 'ps.student_id')
            ->whereIn('ps.user_id', $ids)
            ->get(['ps.user_id', 'ps.student_id', 'ps.relationship', 'ps.source', 'st.firstname', 'st.lastname', 'st.admissionNo'])
            ->groupBy('user_id');

        $base = User::whereHas('roles', fn ($r) => $r->where('id', $role->id));
        $stats = [
            'total'      => (clone $base)->count(),
            'never_sent' => (clone $base)->whereNull('credentials_sent_at')->count(),
            'logged_in'  => (clone $base)->whereNotNull('last_login_at')->count(),
            'disabled'   => (clone $base)->where('is_disabled', true)->count(),
        ];

        return view('parent-accounts.index', [
            'pagetitle' => 'Parent Accounts',
            'accounts'  => $accounts, 'kids' => $kids, 'stats' => $stats,
            'channels'  => collect(['sms', 'whatsapp', 'email'])->filter(fn ($c) => app(MessagingService::class)->enabled($c))->values(),
        ]);
    }

    public function sync()
    {
        try {
            $s = $this->parents->sync();
        } catch (\Throwable $e) {
            Log::error('Parent sync failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Could not create accounts: ' . $e->getMessage());
        }
        return back()->with('success', "Done: {$s['accounts']} parent accounts ({$s['created']} new), {$s['linked']} children linked, {$s['unlinked']} old links removed.");
    }

    public function sendCredentials(Request $request, User $user)
    {
        $this->ensureParent($user);
        $channels = array_values(array_intersect((array) $request->input('channels', ['sms', 'whatsapp', 'email']), ['sms', 'whatsapp', 'email']));
        $r = $this->parents->sendCredentials($user, $channels ?: ['sms']);

        $sent = collect($r['results'])->map(fn ($v, $k) => strtoupper($k) . ' ' . $v)->implode(', ') ?: 'no channel is switched on';
        return back()->with('success', "New password for {$user->name}: {$r['password']} — ({$sent}). It is shown only once.");
    }

    /** Send login details to every parent who has never received them (runs after the response). */
    public function sendAll(Request $request)
    {
        $channels = array_values(array_intersect((array) $request->input('channels', ['sms']), ['sms', 'whatsapp', 'email'])) ?: ['sms'];
        $ids = User::whereHas('roles', fn ($r) => $r->where('name', ParentAccountService::ROLE))
            ->whereNull('credentials_sent_at')->where('is_disabled', false)->pluck('id')->all();

        if (!$ids) return back()->with('success', 'Every parent has already received login details.');

        dispatch(function () use ($ids, $channels) {
            $svc = app(ParentAccountService::class);
            foreach (User::whereIn('id', $ids)->whereNull('credentials_sent_at')->cursor() as $u) {
                try { $svc->sendCredentials($u, $channels); } catch (\Throwable $e) {
                    Log::warning('Parent credentials failed', ['user' => $u->id, 'error' => $e->getMessage()]);
                }
            }
        })->afterResponse();

        return back()->with('success', 'Sending login details to ' . count($ids) . ' parents in the background. Refresh in a few minutes.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:120',
            'phone'        => 'required|string|max:20',
            'email'        => 'nullable|email|max:150',
            'admission_no' => 'required|string|max:50',
            'relationship' => 'nullable|in:father,mother,guardian',
        ]);
        $phone = MessagingService::normalizePhone($data['phone']);
        if (!$phone) return back()->withErrors(['phone' => 'Enter a valid phone number.'])->withInput();

        $studentId = DB::table('studentRegistration')->where('admissionNo', trim($data['admission_no']))->value('id');
        if (!$studentId) return back()->withErrors(['admission_no' => 'No student has that admission number.'])->withInput();

        $user = ParentAccountService::findByPhone($phone);
        if (!$user) {
            $host  = preg_replace('/^www\./', '', parse_url(config('app.url'), PHP_URL_HOST) ?: 'school.ng');
            $email = $data['email'] && !User::where('email', $data['email'])->exists() ? strtolower($data['email']) : 'p' . $phone . '@parents.' . $host;
            if (User::where('email', $email)->exists()) $email = 'p' . $phone . '.' . Str::lower(Str::random(4)) . '@parents.' . $host;
            $user = User::create(['name' => $data['name'], 'email' => $email, 'password' => Hash::make(Str::random(32)),
                                  'phone_number' => ParentAccountService::localPhone($phone)]);
        }
        if (!$user->hasRole(ParentAccountService::ROLE)) $user->assignRole(ParentAccountService::role());

        DB::table('parent_student')->updateOrInsert(
            ['user_id' => $user->id, 'student_id' => $studentId],
            ['relationship' => $data['relationship'] ?? null, 'source' => 'manual', 'updated_at' => now(), 'created_at' => now()]
        );

        return back()->with('success', "{$user->name} is linked. Send login details when ready.");
    }

    public function link(Request $request, User $user)
    {
        $this->ensureParent($user);
        $data = $request->validate(['admission_no' => 'required|string|max:50', 'relationship' => 'nullable|in:father,mother,guardian']);
        $studentId = DB::table('studentRegistration')->where('admissionNo', trim($data['admission_no']))->value('id');
        if (!$studentId) return back()->with('error', 'No student has admission number ' . $data['admission_no'] . '.');

        DB::table('parent_student')->updateOrInsert(
            ['user_id' => $user->id, 'student_id' => $studentId],
            ['relationship' => $data['relationship'] ?? null, 'source' => 'manual', 'updated_at' => now(), 'created_at' => now()]
        );
        return back()->with('success', 'Child linked.');
    }

    public function unlink(User $user, int $student)
    {
        $this->ensureParent($user);
        DB::table('parent_student')->where('user_id', $user->id)->where('student_id', $student)->delete();
        return back()->with('success', 'Child unlinked. Note: an automatic link comes back on the next sync while the phone is still in the student record.');
    }

    public function toggle(User $user)
    {
        $this->ensureParent($user);
        $user->forceFill(['is_disabled' => !$user->is_disabled])->save();
        return back()->with('success', $user->name . ($user->is_disabled ? ' can no longer sign in.' : ' can sign in again.'));
    }

    protected function ensureParent(User $user): void
    {
        abort_unless($user->hasRole(ParentAccountService::ROLE), 404);
    }
}
