{{-- resources/views/finance/payroll/profile-edit.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$s->name" icon="ri-bank-card-line" :subtitle="'Staff ID ' . ($s->employmentid ?: '—') . ' · pay profile'" :back="route('payroll.profiles')" back-label="Pay profiles">
        <x-slot:actions><a href="{{ route('payroll.profiles.preview', $s->id) }}" class="cb-hero-btn"><i class="ri-calculator-line"></i>See calculation</a></x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif
    @if($gaps = $p->gaps())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>Still missing: {{ implode(', ', $gaps) }}.</div></div>@endif

    <form method="POST" action="{{ route('payroll.profiles.save', $s->id) }}">@csrf @method('PUT')
    <div class="row g-3">
        <div class="col-lg-6">
            <x-cb.card title="Bank account" icon="ri-bank-line">
                @if(empty($banks))<div class="cb-banner warning mb-2"><i class="ri-error-warning-line"></i><div>Bank list unavailable — add Paystack keys under Payment Gateways to verify accounts.</div></div>@endif
                <label class="form-label small">Bank</label>
                <select name="bank_code" id="bankCode" class="form-select form-select-sm mb-2">
                    <option value="">Choose bank…</option>
                    @foreach($banks as $b)<option value="{{ $b['code'] }}" @selected(old('bank_code', $p->bank_code) == $b['code'])>{{ $b['name'] }}</option>@endforeach
                </select>
                <label class="form-label small">Account number (10 digits) @if($p->account_last4)<span class="text-muted">— current {{ $p->maskedAccount() }}, leave empty to keep</span>@endif</label>
                <div class="input-group input-group-sm mb-1">
                    <input name="account_number" id="acctNo" class="form-control" inputmode="numeric" maxlength="10" pattern="\d{10}" value="{{ old('account_number') }}" autocomplete="off">
                    <button type="button" class="btn btn-light" id="verifyBtn">Check name</button>
                </div>
                <div id="verifyOut" class="small mb-2">
                    @if($p->account_verified_at)<span class="text-success"><i class="ri-shield-check-line"></i> Verified: {{ $p->account_name }} ({{ $p->account_verified_at->format('d M Y') }})</span>
                    @elseif($p->account_last4)<span class="text-warning">Not verified yet</span>@endif
                </div>
                <div class="small text-muted">Changing the bank or number sends the staff member an SMS alert, and the account must be verified again before it can be paid automatically.</div>
            </x-cb.card>

            <x-cb.card title="Employment & pay status" icon="ri-briefcase-line">
                <div class="row g-2">
                    <div class="col-6"><label class="form-label small">Type</label>
                        <select name="employment_type" class="form-select form-select-sm">@foreach(\App\Models\StaffPayProfile::TYPES as $k => $l)<option value="{{ $k }}" @selected(old('employment_type', $p->employment_type ?? 'full_time') === $k)>{{ $l }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label small">Pay status</label>
                        <select name="pay_status" class="form-select form-select-sm" id="payStatus">@foreach(['active' => 'Active', 'hold' => 'Hold pay', 'exited' => 'Left the school'] as $k => $l)<option value="{{ $k }}" @selected(old('pay_status', $p->pay_status ?? 'active') === $k)>{{ $l }}</option>@endforeach</select></div>
                    <div class="col-8"><input name="hold_reason" class="form-control form-control-sm" placeholder="Reason for hold" value="{{ old('hold_reason', $p->hold_reason) }}" aria-label="Hold reason"></div>
                    <div class="col-4"><input name="exit_date" type="date" class="form-control form-control-sm" value="{{ old('exit_date', $p->exit_date?->toDateString()) }}" aria-label="Exit date" title="Last working day"></div>
                </div>
                <div class="small text-muted mt-2">Pay for someone who joins or leaves mid-month is worked out for the days employed (start date from the staff record, last day here).</div>
            </x-cb.card>
        </div>

        <div class="col-lg-6">
            <x-cb.card title="Tax (PAYE)" icon="ri-government-line">
                <label class="form-check mb-2"><input type="checkbox" class="form-check-input" name="paye_enabled" value="1" @checked(old('paye_enabled', $p->exists ? $p->paye_enabled : true))> <span class="form-check-label">Deduct PAYE</span></label>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label small">TIN</label><input name="tin" class="form-control form-control-sm" value="{{ old('tin', $p->tin) }}"></div>
                    <div class="col-6"><label class="form-label small">State of residence (tax office)</label>
                        <select name="tax_state" class="form-select form-select-sm"><option value="">—</option>@foreach($states as $st)<option @selected(old('tax_state', $p->tax_state) === $st)>{{ $st }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label small">Annual rent paid (₦)</label><input name="annual_rent" type="number" min="0" step="0.01" class="form-control form-control-sm" value="{{ old('annual_rent', $p->annual_rent ?? 0) }}"></div>
                    <div class="col-6"><label class="form-label small">Other allowed reliefs a year (₦)</label><input name="other_reliefs_annual" type="number" min="0" step="0.01" class="form-control form-control-sm" value="{{ old('other_reliefs_annual', $p->other_reliefs_annual ?? 0) }}" title="e.g. life assurance premium, mortgage interest"></div>
                </div>
                <div class="small text-muted mt-2">Rent relief = 20% of rent, up to ₦500,000 a year (keep the tenancy/receipt on file). Other reliefs: life assurance premiums, mortgage interest on owner-occupied home, with evidence.</div>
            </x-cb.card>

            <x-cb.card title="Pension, NHF & health insurance" icon="ri-shield-user-line">
                <label class="form-check mb-2"><input type="checkbox" class="form-check-input" name="pension_enabled" value="1" @checked(old('pension_enabled', $p->exists ? $p->pension_enabled : true))> <span class="form-check-label">Pension (staff 8% + school 10% of basic, housing & transport)</span></label>
                <div class="row g-2 mb-2">
                    <div class="col-7"><input name="pfa_name" list="pfaList" class="form-control form-control-sm" placeholder="Pension company (PFA)" value="{{ old('pfa_name', $p->pfa_name) }}" aria-label="PFA">
                        <datalist id="pfaList">@foreach($pfas as $f)<option value="{{ $f }}">@endforeach</datalist></div>
                    <div class="col-5"><input name="rsa_pin" class="form-control form-control-sm" placeholder="RSA PIN (PEN…)" value="{{ old('rsa_pin', $p->rsa_pin) }}" aria-label="RSA PIN"></div>
                </div>
                <label class="form-check mb-2"><input type="checkbox" class="form-check-input" name="nhf_enabled" value="1" @checked(old('nhf_enabled', $p->nhf_enabled))> <span class="form-check-label">National Housing Fund (2.5% of basic)</span></label>
                <input name="nhf_number" class="form-control form-control-sm mb-2" placeholder="NHF number" value="{{ old('nhf_number', $p->nhf_number) }}" aria-label="NHF number">
                <label class="form-check"><input type="checkbox" class="form-check-input" name="nhia_enabled" value="1" @checked(old('nhia_enabled', $p->nhia_enabled))> <span class="form-check-label">Health insurance (NHIA) — rate set under Rates & tax bands</span></label>
            </x-cb.card>

            <button class="action-btn btn-primary-cb w-100 justify-content-center py-2"><i class="ri-save-line"></i>Save pay profile</button>
        </div>
    </div>
    </form>

    @if(\App\Services\Payroll\SalaryScaleService::available())
    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <x-cb.card title="Salary" icon="ri-stack-line">
                <form method="POST" action="{{ route('payroll.profiles.placement', $s->id) }}">@csrf
                    <div class="d-flex gap-3 mb-2 small">
                        <label class="form-check"><input class="form-check-input sal-src" type="radio" name="salary_source" value="structure" @checked(($p->salary_source ?? 'structure') === 'structure')> <span class="form-check-label">Individual salary structure</span></label>
                        <label class="form-check"><input class="form-check-input sal-src" type="radio" name="salary_source" value="grade" @checked(($p->salary_source ?? '') === 'grade')> <span class="form-check-label">Salary scale (grade & step)</span></label>
                    </div>
                    <div id="srcStructure" class="small mb-2">
                        @if($structure)Current structure: ₦{{ number_format($structure->basic_salary + $structure->housing_allowance + $structure->transport_allowance + $structure->meal_allowance + $structure->medical_allowance + $structure->utility_allowance + $structure->other_allowances, 2) }} a month from {{ \Carbon\Carbon::parse($structure->effective_from)->format('d M Y') }}.
                        @else<span class="text-warning">No salary structure yet.</span>@endif
                        <a href="{{ route('payroll.salary-structures') }}">Manage structures</a>
                    </div>
                    <div id="srcGrade" class="row g-2 mb-2">
                        <div class="col-5"><select name="grade_id" class="form-select form-select-sm" aria-label="Grade"><option value="">Grade…</option>@foreach($grades as $g)<option value="{{ $g->id }}" @selected($placement && $placement->grade_id == $g->id)>{{ $g->code }} — {{ $g->name }}</option>@endforeach</select></div>
                        <div class="col-2"><input name="step" type="number" min="1" class="form-control form-control-sm" value="{{ $placement->step ?? 1 }}" aria-label="Step" title="Step"></div>
                        <div class="col-5"><input name="effective_from" type="date" class="form-control form-control-sm" value="{{ now()->startOfMonth()->toDateString() }}" aria-label="From" title="From"></div>
                        <div class="col-12"><select name="reason" class="form-select form-select-sm" aria-label="Reason"><option value="placement">First placement</option><option value="promotion">Promotion</option><option value="increment">Increment</option><option value="correction">Correction</option></select></div>
                        @if($placement)<div class="col-12 small text-muted">Now: {{ $placement->grade_code }} step {{ $placement->step }} (since {{ \Carbon\Carbon::parse($placement->effective_from)->format('d M Y') }})</div>@endif
                    </div>
                    <button class="action-btn btn-primary-cb"><i class="ri-save-line"></i>Save salary</button>
                </form>
                @if($gradeHistory->isNotEmpty())
                    <div class="small mt-3"><strong>History</strong>
                        @foreach($gradeHistory as $h)<div class="text-muted">{{ \Carbon\Carbon::parse($h->effective_from)->format('d M Y') }} — {{ $h->code }} step {{ $h->step }} ({{ $h->reason }})</div>@endforeach
                    </div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-lg-6">
            <x-cb.card title="Allowances & deductions for this staff member" icon="ri-list-settings-line" :count="$staffItems->count()">
                @forelse($staffItems as $it)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-1 small">
                        <div><span class="status-pill {{ $it->type === 'earning' ? 'st-paid' : 'st-danger' }}">{{ $it->type === 'earning' ? '+' : '−' }}</span> {{ $it->name }}
                            <strong>{{ $it->calc === 'fixed' ? '₦' . number_format($it->amount ?? $it->default_amount, 2) : rtrim(rtrim(number_format($it->rate ?? $it->default_rate, 4), '0'), '.') . '%' }}</strong>
                            <div class="text-muted">{{ \Carbon\Carbon::parse($it->from_month)->format('M Y') }}{{ $it->to_month ? ($it->to_month === $it->from_month ? ' only' : ' – ' . \Carbon\Carbon::parse($it->to_month)->format('M Y')) : ' onward' }}{{ $it->note ? ' · ' . $it->note : '' }}</div></div>
                        <form method="POST" action="{{ route('payroll.staff-items.remove', $it->id) }}" onsubmit="return confirm('Remove or end this item?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light" aria-label="Remove"><i class="ri-close-line"></i></button></form>
                    </div>
                @empty <div class="small text-muted mb-2">None.</div> @endforelse
                <form method="POST" action="{{ route('payroll.items.assign') }}" class="row g-2 mt-2">@csrf
                    <input type="hidden" name="staff_ids[]" value="{{ $s->id }}">
                    <div class="col-7"><select name="pay_item_id" class="form-select form-select-sm" required aria-label="Item"><option value="">Add item…</option>@foreach($payItems as $pi)<option value="{{ $pi->id }}">{{ $pi->name }}</option>@endforeach</select></div>
                    <div class="col-5"><input name="amount" type="number" step="0.01" min="0" class="form-control form-control-sm" placeholder="Amount ₦" aria-label="Amount"></div>
                    <div class="col-4"><select name="mode" class="form-select form-select-sm" aria-label="How often"><option value="once">One month</option><option value="ongoing">Every month</option></select></div>
                    <div class="col-4"><input name="from_month" type="month" class="form-control form-control-sm" value="{{ now()->format('Y-m') }}" required aria-label="Month"></div>
                    <div class="col-4"><button class="action-btn btn-go w-100 justify-content-center"><i class="ri-add-line"></i>Add</button></div>
                    <div class="col-12"><input name="note" class="form-control form-control-sm" placeholder="Note on payslip (optional)" maxlength="255"></div>
                </form>
            </x-cb.card>
        </div>
    </div>
    @endif
</div>
</div>
</div>
<script>
(function () {
    const f = () => { const g = document.querySelector('.sal-src:checked')?.value === 'grade';
        const a = document.getElementById('srcGrade'), b = document.getElementById('srcStructure'); if (a) a.style.display = g ? '' : 'none'; if (b) b.style.display = g ? 'none' : ''; };
    document.querySelectorAll('.sal-src').forEach(r => r.addEventListener('change', f)); f();
})();
document.getElementById('verifyBtn').addEventListener('click', async () => {
    const out = document.getElementById('verifyOut'), bank = document.getElementById('bankCode').value, no = document.getElementById('acctNo').value.trim();
    if (!bank || !/^\d{10}$/.test(no)) { out.innerHTML = '<span class="text-danger">Choose a bank and enter 10 digits.</span>'; return; }
    out.textContent = 'Checking…';
    try {
        const r = await fetch(@json(route('payroll.profiles.verify')), {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || @json(csrf_token())}, body: JSON.stringify({bank_code: bank, account_number: no})});
        const d = await r.json();
        out.innerHTML = d.ok ? '<span class="text-success"><i class="ri-shield-check-line"></i> ' + d.account_name.replace(/[<>&]/g, '') + ' — save to keep</span>' : '<span class="text-danger">' + (d.message || 'Not found').replace(/[<>&]/g, '') + '</span>';
    } catch (e) { out.innerHTML = '<span class="text-danger">Could not check right now.</span>'; }
});
</script>
@endsection
