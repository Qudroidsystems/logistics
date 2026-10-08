@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Become a provider" icon="ri-store-2-line" subtitle="Deliver goods, run errands or shop for customers in your city." />
    @include('provider._flash')
    <div class="row justify-content-center"><div class="col-lg-6">
        <x-cb.card title="Tell us who you are" icon="ri-user-add-line">
            <form method="POST" action="{{ route('provider.register') }}">@csrf
                <label class="form-label small">I am</label>
                <select name="type" class="form-select mb-3" required>
                    @foreach($types as $k => $v)<option value="{{ $k }}" @selected(old('type') === $k)>{{ $v }}</option>@endforeach
                </select>
                <label class="form-label small">Legal name <span class="text-muted">(your name, or the registered company name)</span></label>
                <input name="legal_name" class="form-control mb-3" value="{{ old('legal_name') }}" maxlength="160" required>
                <label class="form-label small">Name customers will see</label>
                <input name="display_name" class="form-control mb-3" value="{{ old('display_name') }}" maxlength="120" required>
                <label class="form-label small">Your city</label>
                <select name="city_id" class="form-select mb-3">
                    <option value="">Choose a city</option>
                    @foreach($cities as $c)<option value="{{ $c->id }}" @selected(old('city_id') == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
                <button class="btn btn-primary w-100">Create my provider account</button>
                <div class="form-text">Next you add your documents, a vehicle if you ride or drive, your prices and where you work. Our team reviews it before you go live.</div>
            </form>
        </x-cb.card>
    </div></div>
</div></div></div>
@endsection
