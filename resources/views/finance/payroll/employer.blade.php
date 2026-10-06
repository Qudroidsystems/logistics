{{-- resources/views/finance/payroll/employer.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Employer Details" icon="ri-building-4-line" subtitle="Shown on payslips, tax certificates and government schedules." />
    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif
    <div class="row"><div class="col-lg-7">
        <x-cb.card title="Details" icon="ri-edit-line">
            <form method="POST" action="{{ route('payroll.employer.save') }}" class="row g-2">@csrf
                <div class="col-12"><label class="small">Employer (legal) name</label><input name="employer_name" class="form-control form-control-sm" value="{{ old('employer_name', $e['employer_name']) }}" required></div>
                <div class="col-6"><label class="small">Employer TIN</label><input name="tin" class="form-control form-control-sm" value="{{ old('tin', $e['tin']) }}"></div>
                <div class="col-6"><label class="small">Tax office</label><input name="tax_office" class="form-control form-control-sm" value="{{ old('tax_office', $e['tax_office']) }}" placeholder="e.g. Kogi State IRS, Kabba"></div>
                <div class="col-6"><label class="small">Pension employer code (PenCom)</label><input name="pension_employer_code" class="form-control form-control-sm" value="{{ old('pension_employer_code', $e['pension_employer_code']) }}"></div>
                <div class="col-6"><label class="small">NHF employer code</label><input name="nhf_employer_code" class="form-control form-control-sm" value="{{ old('nhf_employer_code', $e['nhf_employer_code']) }}"></div>
                <div class="col-6"><label class="small">Signatory name</label><input name="signatory_name" class="form-control form-control-sm" value="{{ old('signatory_name', $e['signatory_name']) }}"></div>
                <div class="col-6"><label class="small">Signatory title</label><input name="signatory_title" class="form-control form-control-sm" value="{{ old('signatory_title', $e['signatory_title']) }}"></div>
                <div class="col-12"><label class="small">Password on emailed payslips</label>
                    <select name="payslip_password" class="form-select form-select-sm">
                        <option value="phone4" @selected($e['payslip_password'] === 'phone4')>Last 4 digits of the staff member's phone</option>
                        <option value="staffid" @selected($e['payslip_password'] === 'staffid')>Staff ID</option>
                        <option value="none" @selected($e['payslip_password'] === 'none')>No password</option>
                    </select></div>
                <div class="col-12 mt-3"><strong class="small">Remittance due dates</strong> <span class="small text-muted">— check these with your state tax office, PFA and FMBN</span></div>
                <div class="col-6"><label class="small">PAYE: day of next month</label><input name="due_paye_day" type="number" min="1" max="31" class="form-control form-control-sm" value="{{ old('due_paye_day', $e['due_paye_day']) }}"></div>
                <div class="col-6"><label class="small">Pension: working days after pay day</label><input name="due_pension_working_days" type="number" min="1" max="31" class="form-control form-control-sm" value="{{ old('due_pension_working_days', $e['due_pension_working_days']) }}"></div>
                <div class="col-6"><label class="small">NHF: day of next month</label><input name="due_nhf_day" type="number" min="1" max="31" class="form-control form-control-sm" value="{{ old('due_nhf_day', $e['due_nhf_day']) }}"></div>
                <div class="col-6"><label class="small">NHIA / NSITF / ITF: day of next month</label><input name="due_other_day" type="number" min="1" max="31" class="form-control form-control-sm" value="{{ old('due_other_day', $e['due_other_day']) }}"></div>
                <div class="col-12"><button class="action-btn btn-primary-cb"><i class="ri-save-line"></i>Save</button></div>
            </form>
        </x-cb.card>
    </div></div>
</div>
</div>
</div>
@endsection
