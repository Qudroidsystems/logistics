@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Book a delivery" icon="ri-flashlight-line" subtitle="A fixed price straight away. Your business plan covers it when it can; otherwise pay from your wallet or by card." :back="route('account.dashboard')" back-label="Home">
        <x-slot:pills>
            <span class="cb-meta-pill"><a class="text-reset" href="{{ route('account.recurring') }}">Same trip every week? Set up a recurring delivery</a></span>
        </x-slot:pills>
    </x-cb.hero>
    @include('account._flash')
    <div class="row justify-content-center"><div class="col-xl-6 col-lg-8">
        @include('public._quote-form')
    </div></div>
</div></div></div>
@endsection
