{{-- resources/views/admin/branding/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="App Branding" icon="ri-palette-line" subtitle="The name, logo and colours the customer and driver apps show on their opening screen. Apps pick up changes the next time they open."></x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row">
    @foreach($apps as $key => $label)
        @php($r = $rows[$key])
        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <h5 class="mb-3">{{ $label }}</h5>
                <form method="POST" action="{{ route('branding.save', $key) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3"><label class="form-label">App name</label><input name="name" class="form-control" value="{{ old('name', $r->name) }}" required maxlength="60"></div>
                    <div class="mb-3"><label class="form-label">Tagline</label><input name="tagline" class="form-control" value="{{ old('tagline', $r->tagline) }}" maxlength="120"></div>
                    <div class="row">
                        <div class="col-6 mb-3"><label class="form-label">Brand colour</label><input type="color" name="primary_color" class="form-control form-control-color" value="{{ $r->primary_color }}"></div>
                        <div class="col-6 mb-3"><label class="form-label">Darker shade</label><input type="color" name="secondary_color" class="form-control form-control-color" value="{{ $r->secondary_color }}"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo (square PNG, up to 2 MB)</label>
                        @if($r->logo_path)<div class="mb-2"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($r->logo_path) }}" alt="" style="height:64px;border-radius:14px"></div>@endif
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        @if($r->logo_path)<div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="rm{{ $key }}"><label class="form-check-label" for="rm{{ $key }}">Remove logo</label></div>@endif
                    </div>
                    <button class="btn btn-primary">Save</button>
                </form>
            </div></div>
        </div>
    @endforeach
    </div>
</div>
</div>
</div>
@endsection
