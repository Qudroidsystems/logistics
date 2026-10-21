@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Team invitations" icon="ri-team-line" subtitle="A delivery company can invite you to drive or work for them. Accept an invitation here, or type the code from the invitation email." :back="route('account.dashboard')" back-label="Home" />
    @include('account._flash')
    <div class="row g-3">
        <div class="col-lg-7">
            <x-cb.card title="Sent to {{ $email }}" icon="ri-mail-open-line" :count="count($invitations)" :flush="true">
                <table class="table align-middle mb-0"><tbody>
                @forelse($invitations as $i)
                    <tr>
                        <td><div class="fw-semibold">{{ $i->provider }}</div>
                            <div class="small text-muted">As {{ str_replace('_', ' ', $i->role) }} · expires {{ \Illuminate\Support\Carbon::parse($i->expires_at)->diffForHumans() }}</div></td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('account.invitations.accept') }}">@csrf
                                <input type="hidden" name="invitation" value="{{ $i->public_id }}">
                                <button class="btn btn-sm btn-primary">Accept</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="text-center text-muted py-4">No invitations for {{ $email }} right now. If a company invited a different email address, sign in with that one, or use the code from their email.</td></tr>
                @endforelse
                </tbody></table>
            </x-cb.card>
        </div>
        <div class="col-lg-5">
            <x-cb.card title="Have a code?" icon="ri-key-2-line">
                <form method="POST" action="{{ route('account.invitations.accept') }}" class="row g-2">@csrf
                    <div class="col-12"><input name="token" class="form-control" placeholder="inv_..." maxlength="60" required value="{{ old('token') }}" autocomplete="off"></div>
                    <div class="col-12 form-text">The code only works for the email address the invitation was sent to.</div>
                    <div class="col-12"><button class="btn btn-primary">Join</button></div>
                </form>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
