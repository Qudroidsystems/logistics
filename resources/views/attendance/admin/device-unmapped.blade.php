{{-- resources/views/attendance/admin/device-unmapped.blade.php --}}
@extends('layouts.master')
@section('content')
<style>
/* ── Consistent with device-mappings ── */
:root {
    --du-primary: #1e3a5f;
    --du-accent:  #2563eb;
    --du-success: #16a34a;
    --du-danger:  #dc2626;
    --du-border:  #e2e8f0;
    --du-radius:  10px;
    --du-shadow:  0 1px 4px rgba(0,0,0,.08);
}

.du-hero {
    background: linear-gradient(135deg, var(--du-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--du-radius);
    padding: 24px 28px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.du-hero::before {
    content:'';
    position:absolute;
    top:-60px;
    right:-60px;
    width:220px;
    height:220px;
    background:rgba(255,255,255,.06);
    border-radius:50%;
}
.du-hero h4 {
    color:#fff;
    font-weight:700;
    margin:0;
    position:relative;
}
.du-hero p {
    color:rgba(255,255,255,.75);
    margin:0;
    font-size:13px;
    position:relative;
}

.du-table th {
    background:var(--du-primary);
    color:#fff;
    padding:12px 16px;
    font-weight:600;
    font-size:13px;
    border:none;
}
.du-table td {
    padding:12px 16px;
    vertical-align:middle;
    border-bottom:1px solid var(--du-border);
    font-size:13px;
}
.du-table tr:hover td {
    background:#eff6ff;
}

.du-card {
    background:#fff;
    border:1px solid var(--du-border);
    border-radius:var(--du-radius);
    box-shadow:var(--du-shadow);
    overflow:hidden;
}

/* Form controls - labels above */
.du-form-label {
    font-size:12px;
    font-weight:600;
    color:#374151;
    margin-bottom:4px;
    display:block;
}
.du-form-control {
    border:1.5px solid var(--du-border);
    border-radius:8px;
    font-size:13px;
    padding:6px 10px;
    transition:border .15s;
    width:100%;
}
.du-form-control:focus {
    border-color:var(--du-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
    outline:none;
}

/* Select2 overrides */
.select2-container .select2-selection--single {
    border:1.5px solid var(--du-border) !important;
    border-radius:8px !important;
    min-height:36px;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height:34px;
    font-size:13px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height:34px;
}
.swal2-container {
    z-index: 2000 !important;
}

.du-toast {
    position:fixed;
    bottom:20px;
    right:20px;
    z-index:99999;
    padding:14px 20px;
    border-radius:10px;
    background:#fff;
    box-shadow:0 4px 20px rgba(0,0,0,.12);
    font-weight:600;
    font-size:13px;
    animation: duToastIn .3s ease;
}
@keyframes duToastIn {
    from { opacity:0; transform:translateY(20px); }
    to { opacity:1; transform:translateY(0); }
}
</style>

<div class="main-content"><div class="page-content"><div class="container-fluid">

    {{-- ══ HERO ═══════════════════════════════════════════════════════════ --}}
    <div class="du-hero d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h4><i class="ri-alert-line me-2"></i>Unmapped Device PINs</h4>
            <p>{{ $unmapped->count() }} PINs found without an assigned person</p>
        </div>
        <a href="{{ route('device-mappings.index') }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i>Back to Mappings
        </a>
    </div>

    {{-- ══ TABLE ════════════════════════════════════════════════════════ --}}
    <div class="du-card">
        <div class="table-responsive">
            <table class="table du-table mb-0">
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>PIN</th>
                        <th>Punches</th>
                        <th>Last Seen</th>
                        <th style="min-width:380px;">Assign To</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($unmapped as $row)
                <tr>
                    <td class="text-muted">{{ $row->device_serial }}</td>
                    <td class="fw-semibold">{{ $row->device_pin }}</td>
                    <td><span class="badge bg-warning-subtle text-warning">{{ $row->punch_count }}</span></td>
                    <td>{{ \Carbon\Carbon::parse($row->last_seen)->diffForHumans() }}</td>
                    <td>
                        <div class="row g-2">
                            <div class="col-3">
                                <label class="du-form-label">Type</label>
                                <select class="du-form-control assign-type" style="width:100%;">
                                    <option value="student">Student</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="du-form-label">Person</label>
                                <select class="assign-person" style="width:100%;"></select>
                            </div>
                            <div class="col-3" style="display:flex;align-items:flex-end;">
                                <button class="btn btn-primary btn-sm assign-btn w-100"
                                        data-device="{{ $row->device_serial }}" data-pin="{{ $row->device_pin }}">
                                    <i class="ri-check-line me-1"></i>Assign
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-5 text-muted">
                    <i class="ri-check-double-line ri-2x d-block mb-2 text-success"></i>
                    All PINs are mapped!
                </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div></div></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function duToast(msg, type = 'success') {
    const colors = { success:'#16a34a', danger:'#dc2626', warning:'#d97706', info:'#2563eb' };
    const id = 'du_toast_' + Date.now();
    document.body.insertAdjacentHTML('beforeend',
        `<div id="${id}" class="du-toast" style="background:${colors[type] || colors.success};color:#fff;min-width:220px;border-radius:10px;padding:14px 20px;box-shadow:0 4px 20px rgba(0,0,0,.12);font-weight:600;font-size:13px;animation:duToastIn .3s ease;">
            ${msg}
            <button onclick="document.getElementById('${id}').remove()" style="background:none;border:none;color:#fff;float:right;margin-left:12px;font-size:16px;cursor:pointer;">×</button>
        </div>`
    );
    setTimeout(() => document.getElementById(id)?.remove(), 4500);
}

function jsonHeaders(extra = {}) {
    return Object.assign({
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
    }, extra);
}

async function handleJsonResponse(r) {
    if (r.redirected) {
        await Swal.fire({
            icon: 'warning',
            title: 'Session expired',
            text: 'Your session or security token expired. Please refresh the page and try again.',
            confirmButtonColor: '#1e3a5f',
        });
        throw new Error('Redirected — session expired');
    }
    if (r.status === 401 || r.status === 419) {
        await Swal.fire({
            icon: 'warning',
            title: 'Session expired',
            text: 'Please refresh the page and log in again.',
            confirmButtonColor: '#1e3a5f',
        });
        throw new Error('Session expired');
    }
    if (r.status === 422) {
        const err = await r.json();
        const msgs = Object.values(err.errors || {}).flat().join('<br>');
        await Swal.fire({
            icon: 'error',
            title: 'Please fix the following',
            html: msgs || err.message || 'Validation failed.',
            confirmButtonColor: '#1e3a5f',
        });
        throw new Error('Validation failed');
    }
    if (!r.ok) {
        const text = await r.text();
        console.error('Request failed:', r.status, text);
        await Swal.fire({
            icon: 'error',
            title: 'Something went wrong',
            text: 'Server responded with ' + r.status + '. Check the console for details.',
            confirmButtonColor: '#1e3a5f',
        });
        throw new Error('Server error (' + r.status + ')');
    }
    return r.json();
}

function personTemplate(item) {
    if (!item.id) return item.text;
    const photo = item.photo
        ? `<img src="${item.photo}" style="width:26px;height:26px;border-radius:50%;object-fit:cover;margin-right:6px;">`
        : `<div style="width:26px;height:26px;border-radius:50%;background:#e2e8f0;display:inline-flex;align-items:center;justify-content:center;margin-right:6px;font-size:10px;color:#64748b;">${item.text.slice(0,2).toUpperCase()}</div>`;
    const meta = item.meta ? Object.entries(item.meta).map(([k, v]) => `${k}: ${v}`).join(' · ') : '';
    return $(`
        <div style="display:flex;align-items:center;padding:1px 0;">
            ${photo}
            <div>
                <div style="font-weight:600;font-size:12px;">${item.text}</div>
                <div style="font-size:10px;color:#6b7280;">${item.subtitle || ''}${meta ? ' · ' + meta : ''}</div>
            </div>
        </div>
    `);
}

document.querySelectorAll('tbody tr').forEach(row => {
    const typeSel   = row.querySelector('.assign-type');
    const personSel = row.querySelector('.assign-person');
    if (!typeSel || !personSel) return;

    $(personSel).select2({
        ajax: {
            url: "{{ route('device-mappings.search') }}",
            dataType: 'json',
            delay: 300,
            data: params => ({ q: params.term || '', type: $(typeSel).val(), page: params.page || 1 }),
            processResults: data => ({ results: data.results, pagination: data.pagination }),
            cache: true,
        },
        minimumInputLength: 0,
        placeholder: 'Search…',
        templateResult: personTemplate,
        templateSelection: item => item.text || item.id,
        width: '100%',
        dropdownAutoWidth: true,
    });

    $(typeSel).on('change', () => $(personSel).val(null).trigger('change'));
});

document.querySelectorAll('.assign-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const row        = this.closest('tr');
        const personId   = $(row.querySelector('.assign-person')).val();
        const personType = row.querySelector('.assign-type').value;

        if (!personId) {
            Swal.fire({ icon: 'warning', title: 'Select a person first.', confirmButtonColor: '#1e3a5f' });
            return;
        }

        const originalHtml = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch('{{ route('device-mappings.quick-assign') }}', {
            method: 'POST',
            headers: jsonHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({
                device_serial: this.dataset.device,
                device_pin: this.dataset.pin,
                person_type: personType,
                person_id: personId,
            }),
        })
        .then(handleJsonResponse)
        .then(d => {
            if (d.success) {
                duToast(d.message || 'Assigned.', 'success');
                row.remove();
            } else {
                Swal.fire({ icon: 'error', title: 'Failed', text: d.message, confirmButtonColor: '#1e3a5f' });
                this.disabled = false;
                this.innerHTML = originalHtml;
            }
        })
        .catch(e => {
            console.error(e);
            this.disabled = false;
            this.innerHTML = originalHtml;
        });
    });
});
</script>
@endsection