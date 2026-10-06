{{-- resources/views/curriculum/methods/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Teaching Methods" icon="ri-lightbulb-fill" subtitle="The library teachers choose from in lesson notes." :back="route('dashboard')" back-label="Dashboard" />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3">
        <div class="col-lg-5">
            <x-cb.card title="Add method" icon="ri-add-line">
                <form method="POST" action="{{ route('curriculum.methods.store') }}">@csrf
                    <label class="form-label small">Name *</label><input name="name" class="form-control mb-2" required>
                    <label class="form-label small">Description</label><input name="description" class="form-control mb-2">
                    <button class="action-btn btn-primary-cb"><i class="ri-add-line"></i>Add</button>
                </form>
            </x-cb.card>
        </div>
        <div class="col-lg-7">
            <x-cb.card title="Methods" icon="ri-lightbulb-line" :count="$methods->count()" :flush="true">
                @if($methods->isEmpty())
                    <div class="empty-state"><i class="ri-lightbulb-line"></i><p>No methods yet.</p></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0"><tbody>
                    @foreach($methods as $m)
                        <tr>
                            <td><span class="fw-semibold">{{ $m->name }}</span>@unless($m->is_active)<span class="status-pill st-muted ms-2">inactive</span>@endunless
                                @if($m->description)<div class="small text-muted">{{ $m->description }}</div>@endif</td>
                            <td class="text-end" style="white-space:nowrap">
                                <form method="POST" action="{{ route('curriculum.methods.update', $m) }}" class="d-inline">@csrf @method('PUT')
                                    <input type="hidden" name="name" value="{{ $m->name }}"><input type="hidden" name="description" value="{{ $m->description }}">
                                    <input type="hidden" name="is_active" value="{{ $m->is_active ? 0 : 1 }}">
                                    <button class="action-btn btn-open" title="{{ $m->is_active ? 'Deactivate' : 'Activate' }}"><i class="ri-{{ $m->is_active ? 'eye-off' : 'eye' }}-line"></i></button>
                                </form>
                                <form method="POST" action="{{ route('curriculum.methods.destroy', $m) }}" class="d-inline" onsubmit="return confirm('Remove method?')">@csrf @method('DELETE')<button class="action-btn btn-open"><i class="ri-delete-bin-line"></i></button></form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody></table></div>
                @endif
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
