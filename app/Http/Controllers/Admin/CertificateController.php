<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateLog;
use App\Models\CertificateTemplate;
use App\Services\Certificate\CertificateDataResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    public function __construct(protected CertificateDataResolver $resolver)
    {
        $this->middleware('auth');
        $this->middleware('permission:Generate certificates|View certificate audit')->only(['index', 'show']);
        $this->middleware('permission:Generate certificates')->only(['generate', 'issue', 'print', 'generateHit', 'storeRendered', 'download']);
        $this->middleware('permission:Approve certificates')->only(['approve']);
        $this->middleware('permission:Revoke certificates')->only(['revoke']);
        $this->middleware('permission:View certificate audit')->only(['logs']);
    }

    public function index(Request $request)
    {
        $q = Certificate::with('template', 'student')
            ->when($request->filled('kind'), fn ($x) => $x->whereHas('template', fn ($t) => $t->where('kind', $request->kind)))
            ->when($request->filled('template'), fn ($x) => $x->where('template_id', $request->template))
            ->when($request->filled('status'), fn ($x) => $x->where('status', $request->status))
            ->when($request->filled('q'), fn ($x) => $x->whereIn('student_id',
                DB::table('studentRegistration')->where('firstname', 'like', "%{$request->q}%")
                    ->orWhere('lastname', 'like', "%{$request->q}%")->orWhere('admissionNo', 'like', "%{$request->q}%")->pluck('id'))
                ->orWhere('serial', 'like', "%{$request->q}%"))
            ->orderByDesc('id');

        return view('certificates.index', [
            'pagetitle' => 'Certificates',
            'rows'      => $q->paginate(25)->withQueryString(),
            'templates' => CertificateTemplate::orderBy('name')->get(),
            'statuses'  => Certificate::STATUS,
            'stats'     => [
                'issued'  => Certificate::where('status', 'issued')->count(),
                'draft'   => Certificate::whereIn('status', ['draft', 'approved'])->count(),
                'revoked' => Certificate::where('status', 'revoked')->count(),
                'prints'  => (int) Certificate::sum('generation_count'),
            ],
        ]);
    }

    public function generate(Request $request)
    {
        return view('certificates.generate', [
            'pagetitle' => 'Generate Certificate',
            'templates' => CertificateTemplate::where('is_active', true)->orderBy('name')->get(),
            'sessions'  => DB::table('schoolsession')->orderByDesc('id')->get(),
            'classes'   => DB::table('schoolclass')->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->selectRaw("schoolclass.id, TRIM(CONCAT(schoolclass.schoolclass,' ',COALESCE(schoolarm.arm,''))) as name")
                ->orderBy('schoolclass.schoolclass')->get(),
        ]);
    }

    /** Students of a class/session (AJAX for the generate page). */
    public function students(Request $request)
    {
        $rows = DB::table('studentclass as sc')
            ->join('studentRegistration as s', 's.id', '=', 'sc.studentId')
            ->when($request->filled('class_id'), fn ($q) => $q->where('sc.schoolclassid', $request->class_id))
            ->when($request->filled('session_id'), fn ($q) => $q->where('sc.sessionid', $request->session_id))
            ->orderBy('s.lastname')->orderBy('s.firstname')
            ->selectRaw("s.id, TRIM(CONCAT(s.firstname,' ',s.lastname)) as name, s.admissionNo")
            ->limit(1000)->get();
        return response()->json(['data' => $rows]);
    }

    public function issue(Request $request)
    {
        $d = $request->validate([
            'template_id' => 'required|exists:certificate_templates,id',
            'session_id'  => 'nullable|integer',
            'term_id'     => 'nullable|integer',
            'class_id'    => 'nullable|integer',
            'student_id'  => 'nullable|integer',
            'whole_class' => 'nullable|boolean',
            'title'       => 'nullable|string|max:180',
            'custom'      => 'nullable|array',
            'testimonial' => 'nullable|array',
        ]);

        $template = CertificateTemplate::findOrFail($d['template_id']);
        abort_unless($template->is_active, 422, 'This template is inactive.');

        $ctx = [
            'title'        => $d['title'] ?? $template->name,
            'session_id'   => $d['session_id'] ?? null,
            'session_name' => !empty($d['session_id']) ? DB::table('schoolsession')->where('id', $d['session_id'])->value('session') : null,
            'term_name'    => !empty($d['term_id']) ? DB::table('schoolterm')->where('id', $d['term_id'])->value('term') : null,
            'custom'       => $d['custom'] ?? [],
            'testimonial'  => $d['testimonial'] ?? [],
        ];

        // Determine target students.
        $studentIds = [];
        if ($request->boolean('whole_class') && !empty($d['class_id'])) {
            $studentIds = DB::table('studentclass')->where('schoolclassid', $d['class_id'])
                ->when($d['session_id'] ?? null, fn ($q) => $q->where('sessionid', $d['session_id']))
                ->pluck('studentId')->map(fn ($v) => (int) $v)->unique()->all();
        } elseif (!empty($d['student_id'])) {
            $studentIds = [(int) $d['student_id']];
        }
        abort_if(!$studentIds, 422, 'Select a student or a class.');

        $made = 0; $single = null;
        foreach ($studentIds as $sid) {
            $cert = $this->makeCertificate($template, $sid, $d, $ctx);
            $single = $cert; $made++;
        }

        if ($made === 1) {
            return redirect()->route('certificates.print', $single)->with('success', 'Certificate ready.');
        }
        return redirect()->route('certificates.index', ['template' => $template->id])
            ->with('success', "{$made} certificate(s) created" . ($template->requires_approval ? ' as drafts awaiting approval.' : '.'));
    }

    protected function makeCertificate(CertificateTemplate $template, int $studentId, array $d, array $ctx): Certificate
    {
        // Reprint = same serial: reuse an existing certificate for this template+student.
        $cert = Certificate::where('template_id', $template->id)->where('student_id', $studentId)->first();

        if (!$cert) {
            $serial = $this->nextSerial($template);
            $cert = new Certificate([
                'template_id' => $template->id,
                'student_id'  => $studentId,
                'serial'      => $serial,
                'verify_token' => $this->uniqueToken(),
                'title'       => $ctx['title'],
                'status'      => $template->requires_approval ? 'draft' : 'approved',
                'class_id'    => $d['class_id'] ?? null,
                'term_id'     => $d['term_id'] ?? null,
                'session_id'  => $d['session_id'] ?? null,
                'created_by'  => auth()->id(),
            ]);
        }

        // (Re)resolve the data + snapshot the design so the issued cert is immutable.
        $verifyUrl = route('certificates.verify', ['token' => $cert->verify_token ?: ($cert->verify_token = $this->uniqueToken())]);
        $resolved = $this->resolver->resolve($studentId, array_merge($ctx, [
            'serial' => $cert->serial, 'verify_url' => $verifyUrl, 'date' => now()->format('d M Y'),
            'testimonial' => $ctx['testimonial'] ?? [],
        ]));

        $cert->title = $ctx['title'];
        $cert->data_snapshot = json_encode($resolved);
        $cert->design_snapshot = $template->design;
        $cert->save();

        CertificateLog::record('created', [
            'certificate_id' => $cert->id, 'template_id' => $template->id, 'student_id' => $studentId,
            'serial' => $cert->serial, 'note' => 'Certificate prepared',
        ]);
        return $cert;
    }

    public function print(Certificate $certificate)
    {
        $certificate->load('template', 'student');
        $template = $certificate->template;
        $limit = $template->generation_limit;
        $canOverride = auth()->user()->can('Override certificate limit');
        $limitReached = $limit !== null && $certificate->generation_count >= $limit && !$canOverride;
        $awaitingApproval = $template->requires_approval && !in_array($certificate->status, ['approved', 'issued'], true);

        return view('certificates.print', [
            'pagetitle'        => 'Certificate — ' . $certificate->serial,
            'certificate'      => $certificate,
            'design'           => json_decode($certificate->design_snapshot ?: $template->design ?: '{}'),
            'data'             => $certificate->dataSnapshot(),
            'verifyUrl'        => route('certificates.verify', ['token' => $certificate->verify_token]),
            'limit'            => $limit,
            'limitReached'     => $limitReached,
            'awaitingApproval' => $awaitingApproval,
            'canApprove'       => auth()->user()->can('Approve certificates'),
        ]);
    }

    /** Called when the client actually produces the PDF — enforces the lock, counts it. */
    public function generateHit(Request $request, Certificate $certificate)
    {
        $template = $certificate->template;
        if ($template->requires_approval && !in_array($certificate->status, ['approved', 'issued'], true)) {
            return response()->json(['success' => false, 'message' => 'This certificate must be approved before it can be generated.'], 422);
        }
        if ($certificate->status === 'revoked') {
            return response()->json(['success' => false, 'message' => 'This certificate has been revoked.'], 422);
        }

        $limit = $template->generation_limit;
        $canOverride = $request->user()->can('Override certificate limit');
        if ($limit !== null && $certificate->generation_count >= $limit && !$canOverride) {
            return response()->json(['success' => false, 'message' => "Generation limit ({$limit}) reached for this student. A higher role can override."], 423);
        }

        $reprint = $certificate->generation_count > 0;
        $certificate->generation_count += 1;
        $certificate->last_generated_at = now();
        if ($certificate->status !== 'issued') {
            $certificate->status = 'issued';
            $certificate->issued_at = now();
            $certificate->issued_by = $request->user()->id;
        }
        $certificate->save();

        CertificateLog::record($reprint ? 'reprinted' : 'issued', [
            'certificate_id' => $certificate->id, 'template_id' => $certificate->template_id,
            'student_id' => $certificate->student_id, 'serial' => $certificate->serial,
            'note' => 'Copy #' . $certificate->generation_count,
        ]);

        return response()->json([
            'success' => true,
            'count'   => $certificate->generation_count,
            'message' => $reprint ? 'Reprint recorded.' : 'Certificate issued.',
        ]);
    }

    /** Store the rendered PNG (base64) of the issued certificate. */
    public function storeRendered(Request $request, Certificate $certificate)
    {
        $data = $request->validate(['image' => 'required|string']);
        if (!preg_match('/^data:image\/png;base64,/', $data['image'])) {
            return response()->json(['success' => false], 422);
        }
        $binary = base64_decode(substr($data['image'], strlen('data:image/png;base64,')));
        if ($binary === false || strlen($binary) > 8 * 1024 * 1024) {
            return response()->json(['success' => false, 'message' => 'Image too large.'], 422);
        }
        $path = 'certificates/rendered/' . $certificate->serial_safe() . '.png';
        Storage::disk('local')->put($path, $binary);
        $certificate->rendered_path = $path;
        $certificate->save();
        return response()->json(['success' => true]);
    }

    public function download(Certificate $certificate)
    {
        abort_unless($certificate->rendered_path && Storage::disk('local')->exists($certificate->rendered_path), 404, 'No saved copy. Open Print to generate it.');
        CertificateLog::record('downloaded', [
            'certificate_id' => $certificate->id, 'student_id' => $certificate->student_id, 'serial' => $certificate->serial,
        ]);
        return Storage::disk('local')->download($certificate->rendered_path, $certificate->serial_safe() . '.png');
    }

    public function approve(Certificate $certificate)
    {
        abort_if($certificate->status === 'revoked', 422, 'Certificate is revoked.');
        $certificate->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => auth()->id()]);
        CertificateLog::record('approved', [
            'certificate_id' => $certificate->id, 'student_id' => $certificate->student_id, 'serial' => $certificate->serial,
        ]);
        return back()->with('success', 'Certificate approved — it can now be generated.');
    }

    public function revoke(Request $request, Certificate $certificate)
    {
        $d = $request->validate(['reason' => 'nullable|string|max:300']);
        $certificate->update([
            'status' => 'revoked', 'revoked_at' => now(), 'revoked_by' => auth()->id(), 'revoke_reason' => $d['reason'] ?? null,
        ]);
        CertificateLog::record('revoked', [
            'certificate_id' => $certificate->id, 'student_id' => $certificate->student_id, 'serial' => $certificate->serial,
            'note' => $d['reason'] ?? null,
        ]);
        return back()->with('success', 'Certificate revoked. Its QR now shows REVOKED.');
    }

    public function show(Certificate $certificate)
    {
        $certificate->load('template', 'student');
        return view('certificates.show', [
            'pagetitle'   => 'Certificate — ' . $certificate->serial,
            'certificate' => $certificate,
            'logs'        => CertificateLog::where('certificate_id', $certificate->id)->orderByDesc('id')->get(),
            'verifyUrl'   => route('certificates.verify', ['token' => $certificate->verify_token]),
        ]);
    }

    public function logs(Request $request)
    {
        $q = CertificateLog::query()
            ->when($request->filled('action'), fn ($x) => $x->where('action', $request->action))
            ->when($request->filled('q'), fn ($x) => $x->where(fn ($w) => $w->where('serial', 'like', "%{$request->q}%")->orWhere('user_name', 'like', "%{$request->q}%")))
            ->orderByDesc('id');
        return view('certificates.logs', [
            'pagetitle' => 'Certificate Audit Log',
            'rows'      => $q->paginate(40)->withQueryString(),
            'actions'   => CertificateLog::ACTIONS,
        ]);
    }

    protected function nextSerial(CertificateTemplate $template): string
    {
        $prefix = $template->serial_prefix ?: 'CERT';
        $year = date('Y');
        do {
            $n = Certificate::where('serial', 'like', "{$prefix}/{$year}/%")->count() + 1;
            $serial = sprintf('%s/%s/%05d', $prefix, $year, $n);
            $exists = Certificate::where('serial', $serial)->exists();
            if ($exists) { $serial = sprintf('%s/%s/%05d-%s', $prefix, $year, $n, Str::upper(Str::random(3))); }
        } while (Certificate::where('serial', $serial)->exists());
        return $serial;
    }

    protected function uniqueToken(): string
    {
        do { $t = Str::random(48); } while (Certificate::where('verify_token', $t)->exists());
        return $t;
    }
}
