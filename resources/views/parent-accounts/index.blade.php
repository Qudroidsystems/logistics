{{-- resources/views/parent-accounts/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Parent Portal Accounts" icon="ri-parent-line"
               subtitle="Parents sign in with their phone number. Accounts are created from the phone numbers on student records.">
        <x-slot:actions>
            <form method="POST" action="{{ route('parent-accounts.sync') }}" onsubmit="this.querySelector('button').disabled=true">@csrf
                <button class="cb-hero-btn"><i class="ri-refresh-line"></i>Create / update from records</button></form>
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif
    @if($channels->isEmpty())
        <div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>No SMS, WhatsApp or email channel is switched on, so login details can't be sent. Set them up under Notification Settings — or hand out the password shown after “New password”.</div></div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Parent accounts" :value="number_format($stats['total'])" icon="ri-group-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Login not sent" :value="number_format($stats['never_sent'])" icon="ri-mail-close-line" accent="amber" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Have signed in" :value="number_format($stats['logged_in'])" icon="ri-login-circle-line" accent="green" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Disabled" :value="number_format($stats['disabled'])" icon="ri-forbid-line" accent="rose" /></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <x-cb.card title="Send login details" icon="ri-send-plane-line">
                <form method="POST" action="{{ route('parent-accounts.send-all') }}" onsubmit="return confirm('Send a temporary password to every parent who has not received one?')">
                    @csrf
                    <p class="small text-muted mb-2">Sends a temporary password to the {{ number_format($stats['never_sent']) }} parents who haven't received one. They must change it at first sign-in.</p>
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        @foreach($channels as $ch)
                            <label class="form-check"><input class="form-check-input" type="checkbox" name="channels[]" value="{{ $ch }}" {{ $ch === 'sms' || $channels->count() === 1 ? 'checked' : '' }}> <span class="form-check-label">{{ $ch === 'sms' ? 'SMS' : ucfirst($ch) }}</span></label>
                        @endforeach
                    </div>
                    <button class="action-btn btn-primary-cb" @disabled($channels->isEmpty() || !$stats['never_sent'])><i class="ri-send-plane-line"></i>Send to all not yet sent</button>
                </form>
            </x-cb.card>
        </div>
        <div class="col-lg-6">
            <x-cb.card title="Add a parent manually" icon="ri-user-add-line">
                <form method="POST" action="{{ route('parent-accounts.store') }}" class="row g-2">
                    @csrf
                    <div class="col-sm-6"><input name="name" class="form-control form-control-sm" placeholder="Parent name" value="{{ old('name') }}" required></div>
                    <div class="col-sm-6"><input name="phone" class="form-control form-control-sm" placeholder="Phone" value="{{ old('phone') }}" required></div>
                    <div class="col-sm-6"><input name="email" type="email" class="form-control form-control-sm" placeholder="Email (optional)" value="{{ old('email') }}"></div>
                    <div class="col-sm-3"><input name="admission_no" class="form-control form-control-sm" placeholder="Child adm. no" value="{{ old('admission_no') }}" required></div>
                    <div class="col-sm-3"><select name="relationship" class="form-select form-select-sm"><option value="">Relation</option><option value="father">Father</option><option value="mother">Mother</option><option value="guardian">Guardian</option></select></div>
                    <div class="col-12"><button class="action-btn btn-go"><i class="ri-add-line"></i>Add &amp; link</button>
                        <span class="small text-muted ms-2">If the phone already has an account, the child is added to it.</span></div>
                </form>
            </x-cb.card>
        </div>
    </div>

    <x-cb.card title="Accounts" icon="ri-list-check-2" :count="$accounts->total()" :flush="true">
        <form class="cb-toolbar gap-2" method="GET">
            <input type="search" name="search" class="cb-search" value="{{ request('search') }}" placeholder="Name, phone, email, child or adm. no">
            <select name="status" class="cb-select" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="never_sent" @selected(request('status') === 'never_sent')>Login not sent</option>
                <option value="never_login" @selected(request('status') === 'never_login')>Never signed in</option>
                <option value="active" @selected(request('status') === 'active')>Have signed in</option>
                <option value="disabled" @selected(request('status') === 'disabled')>Disabled</option>
            </select>
            <button class="action-btn btn-open"><i class="ri-search-line"></i>Search</button>
        </form>

        @if($accounts->isEmpty())
            <div class="empty-state"><i class="ri-parent-line"></i><h6>No parent accounts</h6><p>Click “Create / update from records” to make accounts from the phone numbers on student records.</p></div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Parent</th><th>Children</th><th>Login</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @foreach($accounts as $a)
                        <tr class="{{ $a->is_disabled ? 'opacity-50' : '' }}">
                            <td><strong>{{ $a->name }}</strong>
                                <div class="small text-muted">{{ $a->phone_number }}@unless(str_contains($a->email, '@parents.')) · {{ $a->email }}@endunless</div></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($kids[$a->id] ?? [] as $k)
                                        <span class="status-pill st-info d-inline-flex align-items-center gap-1">
                                            {{ $k->firstname }} {{ $k->lastname }} <small>({{ $k->admissionNo }}{{ $k->relationship ? ', ' . $k->relationship : '' }})</small>
                                            <form method="POST" action="{{ route('parent-accounts.unlink', [$a->id, $k->student_id]) }}" class="d-inline" onsubmit="return confirm('Unlink this child?')">@csrf @method('DELETE')
                                                <button class="btn btn-link btn-sm p-0 text-danger" title="Unlink" aria-label="Unlink"><i class="ri-close-line"></i></button></form>
                                        </span>
                                    @endforeach
                                </div>
                                <form method="POST" action="{{ route('parent-accounts.link', $a->id) }}" class="d-flex gap-1 mt-1">@csrf
                                    <input name="admission_no" class="form-control form-control-sm" style="max-width:130px" placeholder="Adm. no" required aria-label="Admission number">
                                    <button class="btn btn-sm btn-light" title="Link child"><i class="ri-link"></i></button>
                                </form>
                            </td>
                            <td class="small">
                                <div>Sent: {{ $a->credentials_sent_at?->format('d M Y') ?? '—' }}</div>
                                <div>Last in: {{ $a->last_login_at?->diffForHumans() ?? 'never' }}</div>
                                @if($a->must_change_password)<span class="status-pill st-pending">Temp password</span>@endif
                                @if($a->is_disabled)<span class="status-pill st-danger">Disabled</span>@endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                                    <form method="POST" action="{{ route('parent-accounts.send', $a->id) }}" onsubmit="return confirm('Create a new temporary password for {{ addslashes($a->name) }} and send it?')">@csrf
                                        @foreach($channels as $ch)<input type="hidden" name="channels[]" value="{{ $ch }}">@endforeach
                                        <button class="action-btn btn-go"><i class="ri-key-2-line"></i>{{ $a->credentials_sent_at ? 'New password' : 'Send login' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('parent-accounts.toggle', $a->id) }}">@csrf
                                        <button class="action-btn btn-open">{!! $a->is_disabled ? '<i class="ri-checkbox-circle-line"></i>Enable' : '<i class="ri-forbid-line"></i>Disable' !!}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $accounts->links() }}</div>
        @endif
    </x-cb.card>
</div>
</div>
</div>
@endsection
