{{-- resources/views/subjectoperation/subjectinfo.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 fw-semibold">
                            <i class="ri-book-open-line me-2 text-primary"></i>
                            Subject Information
                            <span class="text-muted fw-normal fs-6 ms-2">— {{ $studentdata->first()->firstname ?? '' }} {{ $studentdata->first()->lastname ?? '' }}</span>
                        </h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('subjectoperation.index') }}">Subjects</a></li>
                                <li class="breadcrumb-item active">Subject Information</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">

                {{-- ── Student Profile Card ─────────────────────────────────── --}}
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
                        <div class="text-center p-4" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);">
                            @php
                                $avatarPath = !empty($studentpic->first()->avatar)
                                    ? asset('storage/student_avatars/' . $studentpic->first()->avatar)
                                    : asset('storage/student_avatars/unnamed.jpg');
                            @endphp
                            <div class="mb-3" style="position:relative;display:inline-block;">
                                <img src="{{ $avatarPath }}"
                                     class="rounded-circle border border-4 border-white shadow"
                                     style="width:110px;height:110px;object-fit:cover;"
                                     onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}'">
                                <span class="position-absolute bottom-0 end-0 rounded-circle bg-white d-flex align-items-center justify-content-center shadow-sm"
                                      style="width:28px;height:28px;">
                                    <i class="ri-user-line text-primary" style="font-size:13px;"></i>
                                </span>
                            </div>
                            <h5 class="text-white fw-bold mb-1">{{ $studentdata->first()->firstname ?? '' }} {{ $studentdata->first()->lastname ?? '' }}</h5>
                            <div class="d-flex justify-content-center gap-2 flex-wrap mt-2">
                                <span class="badge px-3 py-2 rounded-pill" style="background:rgba(255,255,255,0.2);color:#fff;font-size:11px;">
                                    <i class="ri-id-card-line me-1"></i>{{ $studentdata->first()->admissionno ?? 'N/A' }}
                                </span>
                                <span class="badge px-3 py-2 rounded-pill" style="background:rgba(255,255,255,0.2);color:#fff;font-size:11px;">
                                    <i class="ri-gender-line me-1"></i>{{ $studentdata->first()->gender ?? 'N/A' }}
                                </span>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            {{-- Registration Summary --}}
                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <div class="text-center p-2 rounded-3 border" style="background:#f0f4ff;">
                                        <div class="fw-bold text-primary fs-5 lh-1 mb-1">{{ $totalreg ?? 0 }}</div>
                                        <small class="text-muted" style="font-size:10px;">Total</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-center p-2 rounded-3 border" style="background:#f0fdf4;">
                                        <div class="fw-bold text-success fs-5 lh-1 mb-1">{{ $regcount ?? 0 }}</div>
                                        <small class="text-muted" style="font-size:10px;">Registered</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-center p-2 rounded-3 border" style="background:#fff5f5;">
                                        <div class="fw-bold text-danger fs-5 lh-1 mb-1">{{ $noregcount ?? 0 }}</div>
                                        <small class="text-muted" style="font-size:10px;">Unregistered</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            @php $percentage = $totalreg > 0 ? ($regcount / $totalreg) * 100 : 0; @endphp
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted fw-medium">Registration Progress</small>
                                    <small class="fw-semibold text-primary">{{ number_format($percentage, 1) }}%</small>
                                </div>
                                <div class="progress rounded-pill" style="height:8px;background:#e0e7ff;">
                                    <div class="progress-bar rounded-pill"
                                         style="width:{{ $percentage }}%;background:linear-gradient(90deg,#667eea,#764ba2);"
                                         role="progressbar"></div>
                                </div>
                            </div>

                            {{-- Class / Term info --}}
                            <hr class="my-3">
                            <div class="small text-muted">
                                <div class="mb-1">
                                    <i class="ri-school-line me-2 text-primary"></i>
                                    <strong>Class:</strong>
                                    @if($classname->isNotEmpty())
                                        {{ $classname->first()->schoolclass }} {{ $classname->first()->arm }}
                                    @else
                                        Unknown Class
                                    @endif
                                </div>
                                <div>
                                    <i class="ri-calendar-2-line me-2 text-primary"></i>
                                    <strong>Term:</strong>
                                    {{ $subjectclass->isNotEmpty() ? $subjectclass->first()->term : 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Subjects Table ───────────────────────────────────────── --}}
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
                        <div class="card-header border-0 px-4 py-3" style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 60%,#7c3aed 100%);">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width:40px;height:40px;background:rgba(255,255,255,0.15);">
                                        <i class="ri-graduation-cap-line text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-white fw-bold mb-0">Subjects for
                                            @if($classname->isNotEmpty())
                                                {{ $classname->first()->schoolclass }} {{ $classname->first()->arm }}
                                            @else
                                                Unknown Class
                                            @endif
                                        </h6>
                                        <small class="text-white opacity-75">
                                            Term: {{ $subjectclass->isNotEmpty() ? $subjectclass->first()->term : 'N/A' }}
                                        </small>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <span class="badge px-3 py-2 rounded-pill" style="background:rgba(255,255,255,0.2);color:#fff;">
                                        <i class="ri-book-open-line me-1"></i>{{ $totalreg ?? 0 }} Subjects
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                                    <thead style="background:#f0f4ff;">
                                        <tr>
                                            <th class="ps-3" width="40">#</th>
                                            <th><i class="ri-book-line me-1 text-primary"></i>Subject</th>
                                            <th><i class="ri-user-star-line me-1 text-primary"></i>Teacher</th>
                                            <th><i class="ri-checkbox-circle-line me-1 text-primary"></i>Status</th>
                                            <th><i class="ri-calendar-line me-1 text-primary"></i>Term</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($subjectclass as $index => $sc)
                                            @php
                                                $status = isset($subjectRegistrations[$sc->subjectid][$sc->staffid]['status']['status'])
                                                    ? $subjectRegistrations[$sc->subjectid][$sc->staffid]['status']['status']
                                                    : 'Not Registered';
                                                $isReg = $status === 'Registered';
                                            @endphp
                                            <tr>
                                                <td class="ps-3 text-muted small">{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-semibold">{{ $sc->subject ?? 'N/A' }}</div>
                                                    <small class="text-muted">{{ $sc->subjectcode ?? '' }}</small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        @php
                                                            $teacherPic = !empty($sc->picture)
                                                                ? asset('storage/staff_avatars/' . $sc->picture)
                                                                : asset('storage/staff_avatars/default.png');
                                                        @endphp
                                                        <img src="{{ $teacherPic }}"
                                                             class="rounded-circle border"
                                                             style="width:30px;height:30px;object-fit:cover;"
                                                             onerror="this.src='{{ asset('storage/staff_avatars/default.png') }}'">
                                                        <div>
                                                            <div class="fw-medium small">{{ $sc->title ?? '' }} {{ $sc->name ?? '' }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($isReg)
                                                        <span class="badge rounded-pill px-3 py-2" style="background:#dcfce7;color:#166534;font-size:11px;">
                                                            <i class="ri-check-line me-1"></i>Registered
                                                        </span>
                                                    @else
                                                        <span class="badge rounded-pill px-3 py-2" style="background:#fee2e2;color:#991b1b;font-size:11px;">
                                                            <i class="ri-close-line me-1"></i>Not Registered
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill px-3" style="background:#e0e7ff;color:#3730a3;font-size:11px;">
                                                        {{ $sc->term ?? 'N/A' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-5">
                                                    <i class="ri-information-line ri-2x mb-2 d-block text-primary opacity-50"></i>
                                                    No subjects found for this class, term, and session.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
