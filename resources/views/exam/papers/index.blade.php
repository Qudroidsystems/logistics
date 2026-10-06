{{-- resources/views/exam/papers/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Exam Papers" icon="ri-file-list-3-fill"
        subtitle="Build, tag to topics, submit for vetting, and print approved papers." :back="route('dashboard')" back-label="Dashboard">
        <x-slot:actions>
            <a href="{{ route('exam.papers.create') }}" class="action-btn btn-primary-cb"><i class="ri-add-line"></i>New paper</a>
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <x-cb.card title="Papers" icon="ri-file-list-3-line" :count="$papers->total()" :flush="true">
        <div class="p-3 d-flex flex-wrap gap-2 align-items-center">
            <form method="GET" class="d-flex gap-2">
                @if($canVetAll)
                <select name="scope" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <option value="">My papers</option>
                    <option value="all" @selected($scope==='all')>All teachers</option>
                </select>
                @endif
                <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach($statuses as $k=>$v)<option value="{{ $k }}" @selected($status===$k)>{{ $v[0] }}</option>@endforeach
                </select>
            </form>
        </div>

        @if($papers->isEmpty())
            <div class="empty-state"><i class="ri-file-list-3-line"></i><h6>No papers yet</h6><p>Create one to get started.</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr>
                    <th>Paper</th><th>Class</th><th>Type</th><th class="text-center">Qs</th>
                    <th class="text-center">Marks</th><th>Status</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                @foreach($papers as $p)
                    @php [$lbl,$cls] = $p->label(); @endphp
                    <tr>
                        <td><span class="fw-semibold">{{ $p->title }}</span></td>
                        <td class="small text-muted">{{ $labels[$p->subjectclass_id] ?? '—' }}</td>
                        <td class="small">{{ $p->typeLabel() }}</td>
                        <td class="text-center">{{ $p->questions()->count() }}</td>
                        <td class="text-center">{{ rtrim(rtrim(number_format($p->total_marks,2),'0'),'.') }}</td>
                        <td><span class="status-pill {{ $cls }}">{{ $lbl }}</span></td>
                        <td class="text-end" style="white-space:nowrap">
                            <a href="{{ route('exam.papers.show', $p) }}" class="action-btn btn-open" title="Preview"><i class="ri-eye-line"></i></a>
                            @if($p->isEditable() && (int)$p->teacher_id===auth()->id())
                                <a href="{{ route('exam.papers.edit', $p) }}" class="action-btn btn-open" title="Edit"><i class="ri-edit-line"></i></a>
                            @endif
                            @if(in_array($p->status,['approved','locked']))
                                <a href="{{ route('exam.papers.print', $p) }}" target="_blank" class="action-btn btn-open" title="Print"><i class="ri-printer-line"></i></a>
                                <a href="{{ route('exam.papers.word', $p) }}" class="action-btn btn-open" title="Download Word"><i class="ri-file-word-2-line"></i></a>
                            @endif
                            @if($p->subjectclass_id)
                                <a href="{{ route('exam.coverage.report', $p) }}" class="action-btn btn-open" title="Coverage"><i class="ri-bar-chart-2-line"></i></a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
    @if($papers->hasPages())<div class="mt-3">{{ $papers->links() }}</div>@endif
</div></div></div>
@endsection
