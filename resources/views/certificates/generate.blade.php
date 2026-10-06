{{-- resources/views/certificates/generate.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Generate Certificate" icon="ri-award-line" subtitle="Pick a template and a student (or a whole class), then print." :back="route('certificates.index')" back-label="Certificates" />

    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <x-cb.card title="Details" icon="ri-file-add-line">
        <form method="POST" action="{{ route('certificates.issue') }}" class="row g-3">@csrf
            <div class="col-md-6">
                <label class="form-label small">Template *</label>
                <select name="template_id" class="form-select" required>
                    <option value="">Choose…</option>
                    @foreach($templates as $t)<option value="{{ $t->id }}" data-kind="{{ $t->kind ?? 'certificate' }}">{{ $t->name }}{{ $t->kind==='testimonial' ? ' [Testimonial]' : '' }}{{ $t->requires_approval ? ' (needs approval)' : '' }}</option>@endforeach
                </select>
                @if($templates->isEmpty())<div class="small text-danger mt-1">No active templates. Create one first.</div>@endif
            </div>
            <div class="col-md-6">
                <label class="form-label small">Certificate title</label>
                <input name="title" class="form-control" maxlength="180" placeholder="e.g. Certificate of Completion">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Session</label>
                <select name="session_id" id="genSession" class="form-select"><option value="">—</option>@foreach($sessions as $s)<option value="{{ $s->id }}">{{ $s->session }}</option>@endforeach</select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Class</label>
                <select name="class_id" id="genClass" class="form-select"><option value="">—</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Student</label>
                <select name="student_id" id="genStudent" class="form-select"><option value="">— choose class first —</option></select>
            </div>
            <div class="col-12 d-none" id="testimonialFields">
                <div class="card border"><div class="card-body">
                    <h6 class="fw-semibold mb-2"><i class="ri-file-user-line me-1"></i>Testimonial details</h6>
                    <div class="row g-2">
                        <div class="col-md-3"><label class="form-label small">Date of admission</label><input type="date" name="testimonial[admission_date]" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><label class="form-label small">Date of leaving</label><input type="date" name="testimonial[leaving_date]" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><label class="form-label small">Class admitted into</label><input name="testimonial[entry_class]" class="form-control form-control-sm" placeholder="e.g. JSS 1"></div>
                        <div class="col-md-3"><label class="form-label small">Class on leaving</label><input name="testimonial[leaving_class]" class="form-control form-control-sm" placeholder="auto — override if needed"></div>
                        <div class="col-md-3"><label class="form-label small">Conduct</label>
                            <select name="testimonial[conduct]" class="form-select form-select-sm"><option value="">—</option>@foreach(['Excellent','Very Good','Good','Satisfactory','Fair'] as $c)<option>{{ $c }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label small">Academic ability</label>
                            <select name="testimonial[academic]" class="form-select form-select-sm"><option value="">—</option>@foreach(['Excellent','Very Good','Good','Average','Below Average'] as $c)<option>{{ $c }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label small">Reason for leaving</label>
                            <select name="testimonial[reason]" class="form-select form-select-sm"><option value="">—</option>@foreach(['Graduated','Completed studies','Transferred','Withdrawn','Relocation'] as $c)<option>{{ $c }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label small">Duration of stay</label><input name="testimonial[duration]" class="form-control form-control-sm" placeholder="e.g. 2018 – 2024"></div>
                        <div class="col-md-4"><label class="form-label small">Positions held</label><input name="testimonial[positions]" class="form-control form-control-sm" placeholder="e.g. Head Boy, Class Prefect"></div>
                        <div class="col-md-4"><label class="form-label small">Clubs / societies</label><input name="testimonial[clubs]" class="form-control form-control-sm" placeholder="e.g. Press Club, JETS"></div>
                        <div class="col-md-4"><label class="form-label small">Awards</label><input name="testimonial[awards]" class="form-control form-control-sm" placeholder="e.g. Best in Mathematics"></div>
                        <div class="col-12"><label class="form-label small">Character remark</label><input name="testimonial[character]" class="form-control form-control-sm" placeholder="e.g. honest, respectful and hardworking"></div>
                        <div class="col-12"><label class="form-label small">Principal's remark</label><textarea name="testimonial[remark]" rows="2" class="form-control form-control-sm" placeholder="A closing recommendation…"></textarea></div>
                    </div>
                    <div class="small text-muted mt-2">These fill the testimonial fields on the template. Leave a field blank to use its auto value where available.</div>
                </div></div>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="whole_class" id="wholeClass" value="1">
                    <label class="form-check-label" for="wholeClass">Create for the <strong>whole class</strong> (drafts for every student in the selected class/session)</label>
                </div>
            </div>
            <div class="col-12">
                <button class="action-btn btn-primary-cb" @disabled($templates->isEmpty())><i class="ri-magic-line"></i>Generate</button>
            </div>
            <div class="small text-muted">Re-generating an existing certificate keeps its original serial &amp; QR (a reprint). Each print is counted and audited.</div>
        </form>
    </x-cb.card>
</div></div></div>

<script>
(function () {
    const cls = document.getElementById('genClass'), ses = document.getElementById('genSession'), stu = document.getElementById('genStudent');
    function load() {
        if (!cls.value) { stu.innerHTML = '<option value="">— choose class first —</option>'; return; }
        stu.innerHTML = '<option value="">Loading…</option>';
        const url = '{{ route('certificates.students') }}?class_id=' + cls.value + '&session_id=' + (ses.value || '');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()).then(j => {
            stu.innerHTML = '<option value="">— select a student —</option>';
            (j.data || []).forEach(function (s) {
                const o = document.createElement('option'); o.value = s.id;
                o.textContent = s.name + (s.admissionNo ? ' · ' + s.admissionNo : '');
                stu.appendChild(o);
            });
        }).catch(() => { stu.innerHTML = '<option value="">Could not load students</option>'; });
    }
    cls.addEventListener('change', load); ses.addEventListener('change', load);
    const tpl = document.querySelector('select[name="template_id"]'), tf = document.getElementById('testimonialFields');
    if (tpl && tf) { tpl.addEventListener('change', function () { const k = this.options[this.selectedIndex]?.getAttribute('data-kind'); tf.classList.toggle('d-none', k !== 'testimonial'); }); }
})();
</script>
@endsection
