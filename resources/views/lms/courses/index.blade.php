{{-- resources/views/lms/courses/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Courses" icon="ri-book-open-fill" subtitle="Build and manage e-learning courses, lessons and coursework.">
        <x-slot name="actions">
            <a href="{{ route('lms.courses.create') }}" class="action-btn btn-primary-cb"><i class="ri-add-line"></i>New course</a>
        </x-slot>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3 mb-1">
        <div class="col-6 col-lg-4"><x-cb.stat label="Courses" :value="$stats['total']" icon="ri-book-2-line" accent="info" /></div>
        <div class="col-6 col-lg-4"><x-cb.stat label="Published" :value="$stats['published']" icon="ri-global-line" accent="teal" /></div>
        <div class="col-6 col-lg-4"><x-cb.stat label="Learners" :value="$stats['learners']" icon="ri-group-line" accent="violet" /></div>
    </div>

    <x-cb.card title="Filter" icon="ri-filter-3-line">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5"><label class="form-label small">Search</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Course title"></div>
            <div class="col-md-3"><label class="form-label small">Class</label>
                <select name="class" class="form-select"><option value="">All</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected(request('class')==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label small">Status</label>
                <select name="status" class="form-select"><option value="">Any</option><option value="published" @selected(request('status')==='published')>Published</option><option value="draft" @selected(request('status')==='draft')>Draft</option></select></div>
            <div class="col-md-2"><button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-search-line"></i></button></div>
        </form>
    </x-cb.card>

    <x-cb.card title="All courses" icon="ri-book-open-line" :count="$rows->total()" :flush="true">
        @if($rows->isEmpty())
            <div class="empty-state"><i class="ri-book-open-line"></i><h6>No courses yet</h6><p>Create your first course to get started.</p></div>
        @else
            <form method="POST" action="{{ route('lms.courses.bulk-publish') }}" id="bulkForm">@csrf
                <input type="hidden" name="action" id="bulkAction">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <span class="small text-muted"><span id="bulkCount">0</span> selected</span>
                    <div class="d-flex gap-1">
                        <button type="button" class="action-btn btn-open" onclick="lmsBulk('publish')"><i class="ri-global-line"></i>Publish</button>
                        <button type="button" class="action-btn btn-open" onclick="lmsBulk('unpublish')"><i class="ri-eye-off-line"></i>Unpublish</button>
                    </div>
                </div>
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th style="width:34px"><input type="checkbox" class="form-check-input" id="bulkAll" onclick="lmsBulkAll(this)"></th><th>Course</th><th>Class</th><th class="text-end">Lessons</th><th class="text-end">Learners</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @foreach($rows as $c)
                        <tr>
                            <td><input type="checkbox" class="form-check-input bulkbox" name="ids[]" value="{{ $c->id }}" onclick="lmsBulkCount()"></td>
                            <td>
                                <a href="{{ route('lms.courses.show', $c) }}" class="fw-semibold text-decoration-none">{{ $c->title }}</a>
                                <div class="small text-muted">{{ $c->code ?: '—' }}</div>
                            </td>
                            <td class="small">{{ optional($c->schoolclass)->schoolclass ?? '—' }}</td>
                            <td class="text-end">{{ $c->lessons_count }}</td>
                            <td class="text-end">{{ $c->enrollments_count }}</td>
                            <td><span class="status-pill {{ $c->is_published ? 'st-paid' : 'st-muted' }}">{{ $c->is_published ? 'Published' : 'Draft' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('lms.courses.show', $c) }}" class="action-btn btn-open" title="Manage"><i class="ri-settings-3-line"></i></a>
                                <a href="{{ route('lms.enrollments.index', $c) }}" class="action-btn btn-open" title="Learners"><i class="ri-group-line"></i></a>
                                <a href="{{ route('lms.gradebook.show', $c) }}" class="action-btn btn-open" title="Gradebook"><i class="ri-bar-chart-box-line"></i></a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            </form>
            <script>
            function lmsBulkCount(){ document.getElementById('bulkCount').textContent = document.querySelectorAll('.bulkbox:checked').length; }
            function lmsBulkAll(cb){ document.querySelectorAll('.bulkbox').forEach(function(b){b.checked=cb.checked;}); lmsBulkCount(); }
            function lmsBulk(a){
                if(!document.querySelectorAll('.bulkbox:checked').length){ alert('Select at least one course.'); return; }
                document.getElementById('bulkAction').value=a;
                document.getElementById('bulkForm').submit();
            }
            </script>
        @endif
    </x-cb.card>
    @if($rows->hasPages())<div class="mt-3">{{ $rows->links() }}</div>@endif
</div></div></div>
@endsection
