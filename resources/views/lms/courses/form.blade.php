{{-- resources/views/lms/courses/form.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$course->exists ? 'Edit course' : 'New course'" icon="ri-book-2-fill"
        subtitle="Course details, class assignment and enrolment settings."
        :back="route('lms.courses.index')" back-label="Courses" />

    @if($errors->any())
        <div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>
    @endif

    <form method="POST" action="{{ $course->exists ? route('lms.courses.update', $course) : route('lms.courses.store') }}" enctype="multipart/form-data">
        @csrf
        @if($course->exists)@method('PUT')@endif

        <div class="row g-3">
            <div class="col-lg-8">
                <x-cb.card title="Details" icon="ri-information-line">
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label small">Title *</label>
                            <input name="title" value="{{ old('title', $course->title) }}" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label small">Course code</label>
                            <input name="code" value="{{ old('code', $course->code) }}" class="form-control" placeholder="e.g. MTH-101"></div>
                        <div class="col-12"><label class="form-label small">Description</label>
                            <textarea name="description" rows="4" class="form-control">{{ old('description', $course->description) }}</textarea></div>
                    </div>
                </x-cb.card>

                <x-cb.card title="Placement" icon="ri-node-tree">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small">Subject</label>
                            <select name="subject_id" class="form-select"><option value="">—</option>
                                @foreach($subjects as $s)<option value="{{ $s->id }}" @selected(old('subject_id',$course->subject_id)==$s->id)>{{ $s->subject }}</option>@endforeach
                            </select></div>
                        <div class="col-md-6"><label class="form-label small">Class</label>
                            <select name="schoolclass_id" class="form-select"><option value="">—</option>
                                @foreach($classes as $c)<option value="{{ $c->id }}" @selected(old('schoolclass_id',$course->schoolclass_id)==$c->id)>{{ $c->name }}</option>@endforeach
                            </select></div>
                        <div class="col-md-6"><label class="form-label small">Session</label>
                            <select name="session_id" class="form-select"><option value="">Current</option>
                                @foreach($sessions as $s)<option value="{{ $s->id }}" @selected(old('session_id',$course->session_id)==$s->id)>{{ $s->session }}</option>@endforeach
                            </select></div>
                        <div class="col-md-6"><label class="form-label small">Term</label>
                            <select name="term_id" class="form-select"><option value="">Any</option>
                                @foreach($terms as $t)<option value="{{ $t->id }}" @selected(old('term_id',$course->term_id)==$t->id)>{{ $t->term }}</option>@endforeach
                            </select></div>
                        <div class="col-md-6"><label class="form-label small">Teacher</label>
                            <select name="teacher_id" class="form-select"><option value="">—</option>
                                @foreach($teachers as $t)<option value="{{ $t->id }}" @selected(old('teacher_id',$course->teacher_id)==$t->id)>{{ $t->name }}</option>@endforeach
                            </select></div>
                        <div class="col-md-6"><label class="form-label small">Completion certificate</label>
                            <select name="completion_cert_template_id" class="form-select"><option value="">None</option>
                                @foreach($certTemplates as $t)<option value="{{ $t->id }}" @selected(old('completion_cert_template_id',$course->completion_cert_template_id)==$t->id)>{{ $t->name }}</option>@endforeach
                            </select>
                            <div class="form-text">Drafted automatically when a learner reaches 100%.</div></div>
                    </div>
                </x-cb.card>
            </div>

            <div class="col-lg-4">
                <x-cb.card title="Enrolment" icon="ri-user-add-line">
                    <label class="form-label small">Mode</label>
                    <select name="enrollment_mode" class="form-select mb-3">
                        @foreach(['both'=>'Auto (class) + manual','auto'=>'Auto by class','manual'=>'Manual only'] as $k=>$v)
                            <option value="{{ $k }}" @selected(old('enrollment_mode',$course->enrollment_mode)===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="allow_self_enroll" value="0">
                        <input class="form-check-input" type="checkbox" name="allow_self_enroll" value="1" id="selfEnroll" @checked(old('allow_self_enroll',$course->allow_self_enroll))>
                        <label class="form-check-label small" for="selfEnroll">Allow student self-enrolment</label>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="fees_gate" value="0">
                        <input class="form-check-input" type="checkbox" name="fees_gate" value="1" id="feesGate" @checked(old('fees_gate', $course->exists ? $course->feesGateOn() : false))>
                        <label class="form-check-label small" for="feesGate">Block access for students who owe fees</label>
                    </div>
                </x-cb.card>

                <x-cb.card title="Grade weighting" icon="ri-scales-3-line">
                    @php $gw = $course->exists ? $course->gradeWeights() : ['quiz'=>50,'assignment'=>50]; @endphp
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label small">Quizzes %</label><input type="number" min="0" max="100" name="quiz_weight" value="{{ old('quiz_weight', $gw['quiz']) }}" class="form-control"></div>
                        <div class="col-6"><label class="form-label small">Assignments %</label><input type="number" min="0" max="100" name="assignment_weight" value="{{ old('assignment_weight', $gw['assignment']) }}" class="form-control"></div>
                    </div>
                    <div class="form-text">Used for each learner's overall course grade. Auto-normalised to 100%.</div>
                </x-cb.card>

                <x-cb.card title="Publish" icon="ri-global-line">
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="is_published" value="0">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1" id="pub" @checked(old('is_published',$course->is_published))>
                        <label class="form-check-label small" for="pub">Published (visible to learners)</label>
                    </div>
                    <label class="form-label small">Cover image</label>
                    <input type="file" name="cover" accept="image/*" class="form-control">
                    @if($course->exists && $course->coverUrl())
                        <img src="{{ $course->coverUrl() }}" class="img-fluid rounded mt-2" alt="cover">
                    @endif
                </x-cb.card>

                <button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-save-line"></i>{{ $course->exists ? 'Save changes' : 'Create course' }}</button>
            </div>
        </div>
    </form>
</div></div></div>
@endsection
