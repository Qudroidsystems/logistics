{{-- resources/views/parent-contacts/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php $report = session('import_report'); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Parent Contacts" icon="ri-contacts-book-2-line"
               subtitle="Find and fix missing or invalid parent phone numbers and emails. Notices, results and receipts depend on them.">
        <x-slot:actions>
            <a class="cb-hero-btn" href="{{ route('parent-contacts.export', request()->query()) }}"><i class="ri-download-2-line"></i>Download CSV</a>
            <button type="button" class="cb-hero-btn" data-bs-toggle="modal" data-bs-target="#importModal"><i class="ri-upload-2-line"></i>Import CSV</button>
        </x-slot:actions>
    </x-cb.hero>

    @foreach(['success' => 'info', 'error' => 'warning'] as $f => $cls)
        @if(session($f))<div class="cb-banner {{ $cls }}"><i class="ri-information-line"></i><div>{{ session($f) }}</div></div>@endif
    @endforeach
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ implode(' ', $errors->all()) }}</div></div>@endif

    @if($report)
        <x-cb.card :title="$report['dry'] ? 'Import preview (dry run — nothing saved)' : 'Import result'" icon="ri-file-list-3-line" :flush="true">
            <div class="p-3 d-flex flex-wrap gap-2">
                <span class="status-pill st-paid">{{ $report['counts']['updated'] }} {{ $report['dry'] ? 'would update' : 'updated' }}</span>
                <span class="status-pill st-muted">{{ $report['counts']['unchanged'] }} unchanged</span>
                <span class="status-pill st-warning">{{ $report['counts']['invalid'] }} with invalid values</span>
                <span class="status-pill st-danger">{{ $report['counts']['not_found'] }} not found</span>
            </div>
            @if($report['rows'])
                <div class="table-responsive" style="max-height:300px;overflow:auto">
                    <table class="cb-table mb-0"><thead><tr><th>Line</th><th>Admission no</th><th>Result</th><th>Details</th></tr></thead><tbody>
                        @foreach($report['rows'] as $r)
                            <tr><td>{{ $r['line'] }}</td><td>{{ $r['adm'] }}</td>
                                <td><span class="status-pill {{ ['updated' => 'st-paid', 'invalid' => 'st-warning', 'not_found' => 'st-danger'][$r['status']] ?? 'st-muted' }}">{{ ['updated' => $report['dry'] ? 'Would update' : 'Updated', 'invalid' => 'Invalid', 'not_found' => 'Not found'][$r['status']] ?? $r['status'] }}</span></td>
                                <td><small>{{ $r['note'] }}</small></td></tr>
                        @endforeach
                    </tbody></table>
                </div>
            @endif
        </x-cb.card>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-lg-2 col-md-4 col-6"><x-cb.stat label="Active students" :value="number_format($stats['students'])" icon="ri-group-line" accent="sky" /></div>
        <div class="col-lg-2 col-md-4 col-6"><x-cb.stat label="Valid phone" :value="number_format($stats['phone'])" icon="ri-phone-line" accent="teal" :hint="$stats['students'] ? round($stats['phone'] / $stats['students'] * 100) . '%' : null" /></div>
        <div class="col-lg-2 col-md-4 col-6"><x-cb.stat label="WhatsApp" :value="number_format($stats['whatsapp'])" icon="ri-whatsapp-line" accent="green" /></div>
        <div class="col-lg-2 col-md-4 col-6"><x-cb.stat label="Valid email" :value="number_format($stats['email'])" icon="ri-mail-line" accent="violet" :hint="$stats['students'] ? round($stats['email'] / $stats['students'] * 100) . '%' : null" /></div>
        <div class="col-lg-2 col-md-4 col-6"><x-cb.stat label="Invalid entries" :value="number_format($stats['invalid'])" icon="ri-error-warning-line" accent="amber" /></div>
        <div class="col-lg-2 col-md-4 col-6"><x-cb.stat label="No contact at all" :value="number_format($stats['none'])" icon="ri-user-unfollow-line" accent="rose" /></div>
    </div>

    <x-cb.card title="Students" icon="ri-list-check" :count="$rows->total()" :flush="true">
        <form method="GET" class="cb-toolbar">
            <div class="cb-search"><i class="ri-search-line"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Name or admission no" aria-label="Search"></div>
            <select name="class_id" class="cb-select" onchange="this.form.submit()" aria-label="Class">
                <option value="">All classes</option>
                @foreach($classes as $c)<option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->name }}</option>@endforeach
            </select>
            <select name="issue" class="cb-select" onchange="this.form.submit()" aria-label="Problem">
                @foreach($issues as $k => $l)<option value="{{ $k }}" @selected(request('issue', '') === $k)>{{ $l }}</option>@endforeach
            </select>
        </form>
        @if($rows->isEmpty())
            <div class="empty-state"><i class="ri-checkbox-circle-line"></i><h6>Nothing to fix here</h6><p>No students match this filter.</p></div>
        @else
            <div class="table-responsive">
                <table class="cb-table mb-0">
                    <thead><tr><th>Student</th><th>Father</th><th>Mother</th><th>Guardian</th><th>WhatsApp / Email</th><th>Problems</th><th></th></tr></thead>
                    <tbody>
                    @foreach($rows as $r)
                        <tr data-row='@json($r)'>
                            <td><div class="fw-semibold">{{ $r->student_name }}</div><small class="text-muted">{{ $r->admission_no }} · {{ $r->class_name }}</small></td>
                            <td><small>{{ $r->father_name ?: '—' }}<br><span class="{{ $r->father_phone && !\App\Services\Messaging\MessagingService::normalizePhone($r->father_phone) ? 'text-danger' : 'text-muted' }}">{{ $r->father_phone ?: '' }}</span></small></td>
                            <td><small>{{ $r->mother_name ?: '—' }}<br><span class="{{ $r->mother_phone && !\App\Services\Messaging\MessagingService::normalizePhone($r->mother_phone) ? 'text-danger' : 'text-muted' }}">{{ $r->mother_phone ?: '' }}</span></small></td>
                            <td><small>{{ $r->guardian_name ?: '—' }}<br><span class="{{ $r->guardian_phone && !\App\Services\Messaging\MessagingService::normalizePhone($r->guardian_phone) ? 'text-danger' : 'text-muted' }}">{{ $r->guardian_phone ?: '' }}</span></small></td>
                            <td><small><span class="{{ $r->whatsapp_number && !\App\Services\Messaging\MessagingService::normalizePhone($r->whatsapp_number) ? 'text-danger' : '' }}">{{ $r->whatsapp_number ?: '—' }}</span><br>
                                <span class="{{ $r->parent_email && !$r->valid_email ? 'text-danger' : 'text-muted' }}">{{ $r->parent_email ?: '' }}</span></small></td>
                            <td>
                                @forelse(array_diff($r->issues, ['no_whatsapp']) as $i)
                                    <span class="status-pill {{ in_array($i, ['no_contact', 'invalid_phone', 'invalid_email']) ? 'st-danger' : 'st-warning' }} mb-1">{{ $issues[$i] ?? $i }}</span>
                                @empty
                                    <span class="status-pill st-paid">OK</span>
                                @endforelse
                            </td>
                            <td class="text-end"><button type="button" class="action-btn btn-go pc-edit"><i class="ri-edit-line"></i>Edit</button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $rows->links() }}</div>
        @endif
    </x-cb.card>
</div>
</div>
</div>

{{-- Edit modal --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" id="editForm" autocomplete="off">
            <div class="modal-header"><h5 class="modal-title" id="editTitle">Edit contacts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    @foreach($fields as $key => $f)
                        <div class="col-md-6">
                            <label class="form-label" for="f-{{ $key }}">{{ $f['label'] }}</label>
                            <input class="form-control" id="f-{{ $key }}" name="{{ $key }}" type="{{ $f['type'] === 'email' ? 'email' : ($f['type'] === 'phone' ? 'tel' : 'text') }}" maxlength="190">
                        </div>
                    @endforeach
                </div>
                <small class="text-muted d-block mt-2">Phone numbers are saved in local format (0803…). Leave a box empty to remove that value.</small>
                <div id="editError" class="cb-banner warning d-none mt-3 mb-0"><i class="ri-error-warning-line"></i><div></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="editSave">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Import modal --}}
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('parent-contacts.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Import parent contacts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <ol class="small ps-3">
                    <li>Click <strong>Download CSV</strong> (filter first, e.g. "No valid phone").</li>
                    <li>Fill in the missing numbers/emails in Excel. Keep the <code>admission_no</code> column.</li>
                    <li>Save as CSV and upload here. Empty cells keep the current value.</li>
                </ol>
                <input type="file" class="form-control" name="file" accept=".csv,text/csv" required>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="dry_run" value="1" id="dry_run" checked>
                    <label class="form-check-label" for="dry_run">Preview only (don't save yet)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary"><i class="ri-upload-2-line me-1"></i>Upload</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const urlTpl = @json(route('parent-contacts.update', ['student' => '__ID__']));
    const FIELDS = @json(array_keys($fields));
    const form = document.getElementById('editForm');
    let currentId = null, currentTr = null;

    document.querySelectorAll('.pc-edit').forEach(btn => btn.addEventListener('click', () => {
        currentTr = btn.closest('tr');
        const r = JSON.parse(currentTr.dataset.row);
        currentId = r.id;
        document.getElementById('editTitle').textContent = r.student_name + ' (' + r.admission_no + ')';
        FIELDS.forEach(k => { form.elements[k].value = r[k] || ''; });
        document.getElementById('editError').classList.add('d-none');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
    }));

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const btn = document.getElementById('editSave'); btn.disabled = true;
        const body = {}; FIELDS.forEach(k => { body[k] = form.elements[k].value; });
        try {
            const res = await fetch(urlTpl.replace('__ID__', currentId), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(body),
            });
            const j = await res.json();
            if (!res.ok || !j.success) throw new Error(j.message || 'Could not save.');
            window.location.reload();
        } catch (err) {
            const box = document.getElementById('editError');
            box.querySelector('div').textContent = err.message; box.classList.remove('d-none');
        } finally { btn.disabled = false; }
    });
})();
</script>
@endsection
