@extends('layouts.master')

@section('content')
<style>
    .fraction {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        font-family: Arial, sans-serif;
        font-size: 10px;
    }
    .fraction .numerator {
        border-bottom: 2px solid black;
        padding: 0 5px;
    }
    .fraction .denominator {
        padding-top: 5px;
    }
    tr.rt>th,
    tr.rt>td {
        text-align: center;
    }
    div.grade>span {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 14px;
    }
    span.text-space-on-dots {
        position: relative;
        width: 500px;
        border-bottom-style: dotted;
    }
    span.text-dot-space2 {
        position: relative;
        width: 300px;
        border-bottom-style: dotted;
    }
    .highlight-red {
        color: red;
    }
    @media print {
        div.print-body {
            background-color: white;
        }
        @page {
            size: 940px;
            margin: 0px;
        }
        html,
        body {
            width: 940px;
        }
        body {
            margin: 0;
        }
        nav {
            display: none;
        }
    }
    p.school-name1 {
        font-family: 'Times New Roman', Times, serif;
        font-size: 40px;
        font-weight: 500;
    }
    p.school-name2 {
        font-family: 'Times New Roman', Times, serif;
        font-size: 30px;
        font-weight: bolder;
    }
    div.school-logo {
        width: 80px;
        height: 60px;
    }
    div.header-divider {
        width: 100%;
        height: 3px;
        background-color: black;
        margin-bottom: 3px;
    }
    div.header-divider2 {
        width: 100%;
        height: 1px;
        background-color: black;
    }
    span.result-details {
        font-size: 14px;
        font-family: 'Times New Roman', Times, serif;
        font-weight: lighter;
        font-style: italic;
    }
    span.rd1 {
        position: relative;
        width: 86.1%;
        border-bottom-style: dotted;
    }
    span.rd2, span.rd3, span.rd4, span.rd5, span.rd6, span.rd7, span.rd8, span.rd9, span.rd10 {
        position: relative;
        border-bottom-style: dotted;
    }
    span.rd2, span.rd3, span.rd4 { width: 30%; }
    span.rd5 { width: 25%; }
    span.rd6 { width: 28%; }
    span.rd7 { width: 17.2%; }
    span.rd8 { width: 12%; }
    span.rd9, span.rd10 { width: 11%; }
</style>

<div class="app-main flex-column flex-row-fluid" id="kt_app_main">
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse" data-bs-target="#kt_account_profile_details" aria-expanded="true" aria-controls="kt_account_profile_details">
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">Student Profile for {{ $students[0]->fname }} {{ $students[0]->lastname }}</h3>
                    </div>
                </div>
                
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Whoops!</strong> There were some problems with your input.<br>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('status') || session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') ?: session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($students->isNotEmpty())
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="d-flex flex-wrap flex-stack mb-4">
                                            <!-- Student Avatar -->
                                            <div class="me-6 mb-3">
                                                <?php
                                                $picture = $students[0]->picture ? basename($students[0]->picture) : 'unnamed.jpg';
                                                $imagePath = asset('storage/student_avatars/' . $picture);
                                                $fileExists = file_exists(storage_path('app/public/student_avatars/' . $picture));
                                                $defaultImageExists = file_exists(storage_path('app/public/student_avatars/unnamed.jpg'));
                                                ?>
                                                <img src="{{ $students[0]->picture ? asset('storage/student_avatars/' . basename($students[0]->picture)) : asset('storage/student_avatars/unnamed.jpg') }}"
                                                     alt="{{ $students[0]->fname }} {{ $students[0]->lastname }}"
                                                     class="rounded avatar-xl student-image"
                                                     data-bs-toggle="modal"
                                                     data-bs-target="#imageViewModal"
                                                     data-image="{{ $students[0]->picture ? asset('storage/student_avatars/' . basename($students[0]->picture)) : asset('storage/student_avatars/unnamed.jpg') }}"
                                                     data-picture="{{ $students[0]->picture ?? 'none' }}"
                                                     data-admissionno="{{ $students[0]->admissionNo }}"
                                                     data-file-exists="{{ $fileExists ? 'true' : 'false' }}"
                                                     data-default-exists="{{ $defaultImageExists ? 'true' : 'false' }}"
                                                     onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}'; console.log('Image failed to load for admissionno: {{ $students[0]->admissionNo ?? 'unknown' }}, picture: {{ $students[0]->picture ?? 'none' }}');" />
                                            </div>
                                            <!-- Student Information -->
                                            <div class="d-flex flex-column flex-grow-1 pe-8">
                                                <div class="d-flex flex-wrap">
                                                    <!-- Student Name Card -->
                                                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-person fs-3 text-primary me-2"></i>
                                                            <div class="fs-2 fw-bold text-success">{{ $students[0]->lastname }} {{ $students[0]->fname }} {{ $students[0]->othername }}</div>
                                                        </div>
                                                        <div class="fw-semibold fs-6 text-gray-400">Student Name</div>
                                                    </div>
                                                    <!-- Admission No Card -->
                                                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-card-text fs-3 text-success me-2"></i>
                                                            <div class="fs-2 fw-bold text-success">{{ $students[0]->admissionNo }}</div>
                                                        </div>
                                                        <div class="fw-semibold fs-6 text-gray-400">Admission No</div>
                                                    </div>
                                                    <!-- Class Card -->
                                                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-building fs-3 text-success me-2"></i>
                                                            <div class="fs-2 fw-bold">{{ $schoolclass ? $schoolclass->schoolclass . ' ' . $schoolclass->armRelation->arm : 'N/A' }}</div>
                                                        </div>
                                                        <div class="fw-semibold fs-6 text-gray-400">Class</div>
                                                    </div>
                                                    <!-- Term | Session Card -->
                                                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-calendar fs-3 text-success me-2"></i>
                                                            <div class="fs-2 fw-bold text-success">{{ $schoolterm }} | {{ $schoolsession }}</div>
                                                        </div>
                                                        <div class="fw-semibold fs-6 text-gray-400">Term | Session</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Personality Profile -->
                    <div class="row">
                        <form action="{{ route('studentpersonalityprofile.save') }}" method="POST">
                            @csrf
                            <input type="hidden" name="studentid" value="{{ $studentid }}">
                            <input type="hidden" name="schoolclassid" value="{{ $schoolclassid }}">
                            <input type="hidden" name="staffid" value="{{ $staffid }}">
                            <input type="hidden" name="termid" value="{{ $termid }}">
                            <input type="hidden" name="sessionid" value="{{ $sessionid }}">
                            <div class="col-lg-12">
                                <div class="card">
                                    <div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse" data-bs-target="#kt_account_profile_details" aria-expanded="true" aria-controls="kt_account_profile_details">
                                        <div class="card-title m-0">
                                            <h3 class="fw-bold m-0">Student Profile for {{ $students[0]->fname }} {{ $students[0]->lastname }}</h3>
                                        </div>
                                    </div>
                                    <div id="kt_account_settings_profile_details" class="collapse show">
                                        <div class="card-body py-4">
                                            <div class="table-responsive">
                                                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0" id="kt_roles_view_table">
                                                    <thead>
                                                        <tr class="table-light">
                                                            <th scope="col" style="width: 50px;">#</th>
                                                            <th scope="col">Trait</th>
                                                            <th scope="col">Remark</th>
                                                            <th scope="col">Current Value</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($studentpp as $s)
                                                            <tr>
                                                                <td>1</td>
                                                                <td>Punctuality</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="punctuality" required>
                                                                        <option value="" {{ $s->punctuality == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->punctuality == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->punctuality == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->punctuality == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->punctuality == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->punctuality == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->punctuality }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>2</td>
                                                                <td>Neatness</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="neatness" required>
                                                                        <option value="" {{ $s->neatness == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->neatness == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->neatness == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->neatness == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->neatness == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->neatness == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->neatness }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>3</td>
                                                                <td>Leadership</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="leadership" required>
                                                                        <option value="" {{ $s->leadership == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->leadership == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->leadership == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->leadership == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->leadership == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->leadership == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->leadership }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>4</td>
                                                                <td>Attitude</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="attitude" required>
                                                                        <option value="" {{ $s->attitude == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->attitude == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->attitude == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->attitude == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->attitude == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->attitude == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->attitude }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>5</td>
                                                                <td>Reading</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="reading" required>
                                                                        <option value="" {{ $s->reading == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->reading == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->reading == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->reading == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->reading == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->reading == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->reading }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>6</td>
                                                                <td>Honesty</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="honesty" required>
                                                                        <option value="" {{ $s->honesty == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->honesty == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->honesty == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->honesty == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->honesty == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->honesty == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->honesty }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>7</td>
                                                                <td>Cooperation</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="cooperation" required>
                                                                        <option value="" {{ $s->cooperation == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->cooperation == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->cooperation == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->cooperation == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->cooperation == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->cooperation == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->cooperation }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>8</td>
                                                                <td>Self-control</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="selfcontrol" required>
                                                                        <option value="" {{ $s->selfcontrol == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->selfcontrol == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->selfcontrol == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->selfcontrol == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->selfcontrol == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->selfcontrol == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->selfcontrol }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>9</td>
                                                                <td>Politeness</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="politeness" required>
                                                                        <option value="" {{ $s->politeness == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->politeness == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->politeness == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->politeness == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->politeness == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->politeness == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->politeness }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>10</td>
                                                                <td>Physical Health</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="physicalhealth" required>
                                                                        <option value="" {{ $s->physicalhealth == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->physicalhealth == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->physicalhealth == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->physicalhealth == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->physicalhealth == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->physicalhealth == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->physicalhealth }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>11</td>
                                                                <td>Stability</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="stability" required>
                                                                        <option value="" {{ $s->stability == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->stability == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->stability == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->stability == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->stability == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->stability == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->stability }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>12</td>
                                                                <td>Games and Sports</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="gamesandsports" required>
                                                                        <option value="" {{ $s->gamesandsports == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->gamesandsports == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->gamesandsports == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->gamesandsports == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->gamesandsports == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->gamesandsports == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->gamesandsports }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>13</td>
                                                                <td>Attentiveness in Class</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="attentiveness_in_class" required>
                                                                        <option value="" {{ $s->attentiveness_in_class == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->attentiveness_in_class == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->attentiveness_in_class == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->attentiveness_in_class == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->attentiveness_in_class == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->attentiveness_in_class == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->attentiveness_in_class }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>14</td>
                                                                <td>Class Participation</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="class_participation" required>
                                                                        <option value="" {{ $s->class_participation == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->class_participation == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->class_participation == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->class_participation == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->class_participation == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->class_participation == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->class_participation }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>15</td>
                                                                <td>Relationship with Others</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="relationship_with_others" required>
                                                                        <option value="" {{ $s->relationship_with_others == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->relationship_with_others == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->relationship_with_others == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->relationship_with_others == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->relationship_with_others == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->relationship_with_others == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->relationship_with_others }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>16</td>
                                                                <td>Doing Assignment</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="doing_assignment" required>
                                                                        <option value="" {{ $s->doing_assignment == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->doing_assignment == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->doing_assignment == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->doing_assignment == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->doing_assignment == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->doing_assignment == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->doing_assignment }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>17</td>
                                                                <td>Writing Skill</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="writing_skill" required>
                                                                        <option value="" {{ $s->writing_skill == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->writing_skill == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->writing_skill == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->writing_skill == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->writing_skill == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->writing_skill == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->writing_skill }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>18</td>
                                                                <td>Reading Skill</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="reading_skill" required>
                                                                        <option value="" {{ $s->reading_skill == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->reading_skill == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->reading_skill == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->reading_skill == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->reading_skill == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->reading_skill == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->reading_skill }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>19</td>
                                                                <td>Spoken English/Communication</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="spoken_english_communication" required>
                                                                        <option value="" {{ $s->spoken_english_communication == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->spoken_english_communication == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->spoken_english_communication == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->spoken_english_communication == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->spoken_english_communication == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->spoken_english_communication == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->spoken_english_communication }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>20</td>
                                                                <td>Hand Writing</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="hand_writing" required>
                                                                        <option value="" {{ $s->hand_writing == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->hand_writing == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->hand_writing == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->hand_writing == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->hand_writing == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->hand_writing == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->hand_writing }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>21</td>
                                                                <td>Club</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="club" required>
                                                                        <option value="" {{ $s->club == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->club == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->club == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->club == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->club == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->club == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->club }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>22</td>
                                                                <td>Music</td>
                                                                <td>
                                                                    <select class="form-control col-md-8" name="music" required>
                                                                        <option value="" {{ $s->music == '' ? 'selected' : '' }}>Select Remark</option>
                                                                        <option value="Excellent" {{ $s->music == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                                        <option value="Very Good" {{ $s->music == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                                                        <option value="Good" {{ $s->music == 'Good' ? 'selected' : '' }}>Good</option>
                                                                        <option value="Fairly Good" {{ $s->music == 'Fairly Good' ? 'selected' : '' }}>Fairly Good</option>
                                                                        <option value="Poor" {{ $s->music == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control" value="{{ $s->music }}" readonly required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>23</td>
                                                                <td>School Attendance</td>
                                                                <td>
                                                                    <input type="number" name="attendance" value="{{ $s->attendance }}" class="form-control">
                                                                </td>
                                                                <td></td>
                                                            </tr>
                                                            <tr>
                                                                <td>24</td>
                                                                <td>Teacher's Comment</td>
                                                                <td>
                                                                    <input type="text" name="classteachercomment" value="{{ $s->classteachercomment }}" class="form-control">
                                                                </td>
                                                                <td></td>
                                                            </tr>
                                                            <tr>
                                                                <td>25</td>
                                                                <td>Principal's Comment</td>
                                                                <td>
                                                                    <input type="text" name="principalscomment" value="{{ $s->principalscomment }}" class="form-control" readonly>
                                                                </td>
                                                                <td></td>
                                                            </tr>
                                                            <tr>
                                                                <td>26</td>
                                                                <td>Remark on Other Activities</td>
                                                                <td>
                                                                    <input type="text" name="remark_on_other_activities" value="{{ $s->remark_on_other_activities }}" class="form-control">
                                                                </td>
                                                                <td></td>
                                                            </tr>
                                                            <tr>
                                                                <td></td>
                                                                <td></td>
                                                                <td>
                                                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                                                </td>
                                                                <td></td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Accordions for Reports -->
                    <div class="accordion custom-accordionwithicon-plus" id="studentReportsAccordion">
                        <!-- Terminal Report -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="terminalReportHeader">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#terminalReportCollapse" aria-expanded="true" aria-controls="terminalReportCollapse">
                                    Terminal Report
                                </button>
                            </h2>
                            <div id="terminalReportCollapse" class="accordion-collapse collapse show" aria-labelledby="terminalReportHeader" data-bs-parent="#studentReportsAccordion">
                                <div class="accordion-body">
                                    <div class="print-body bg-light w-100 h-100">
                                        <div class="print-sect container-fluid border bg-white" style="width: 1200px;">
                                            <div class="card-title m-0">
                                                <h3 class="fw-bold m-0">TERMINAL REPORT</h3>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm bg-white">
                                                    <div class="mt-3 result-table">
                                                        <table class="table table-bordered table-hover table-responsive-sm" style="border: 1px solid black;">
                                                            <thead style="border: 1px solid black;">
                                                                <tr class="rt">
                                                                    <th></th>
                                                                    <th>Subjects</th>
                                                                    <th>a</th>
                                                                    <th>b</th>
                                                                    <th>c</th>
                                                                    <th>d</th>
                                                                    <th>e</th>
                                                                    <th>f</th>
                                                                    <th>g</th>
                                                                    <th>h</th>
                                                                    <th>i</th>
                                                                    <th>j</th>
                                                                    <th>k</th>
                                                                </tr>
                                                                <tr class="rt">
                                                                    <th>S/N</th>
                                                                    <th></th>
                                                                    <th>T1</th>
                                                                    <th>T2</th>
                                                                    <th>T3</th>
                                                                    <th>
                                                                        <div class="fraction">
                                                                            <div class="numerator">a + b + c</div>
                                                                            <div class="denominator">3</div>
                                                                        </div>
                                                                    </th>
                                                                    <th>Term Exams</th>
                                                                    <th>
                                                                        <div class="fraction">
                                                                            <div class="numerator">d + f</div>
                                                                            <div class="denominator">2</div>
                                                                        </div>
                                                                    </th>
                                                                    <th>B/F</th>
                                                                    <th><span class="d-block">Cum</span> (f/g)/2</th>
                                                                    <th>Grade</th>
                                                                    <th>PSN</th>
                                                                    <th>Class Average</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @forelse ($scores as $index => $score)
                                                                    <tr>
                                                                        <td align="center" style="font-size: 14px;">{{ $index + 1 }}</td>
                                                                        <td align="center" style="font-size: 14px;">{{ $score->subject_name }}</td>
                                                                        <td align="center" style="font-size: 14px;" @if ($score->ca1 <= 50 && is_numeric($score->ca1)) class="highlight-red" @endif>{{ $score->ca1 ?? '-' }}</td>
                                                                        <td align="center" style="font-size: 14px;" @if ($score->ca2 <= 50 && is_numeric($score->ca2)) class="highlight-red" @endif>{{ $score->ca2 ?? '-' }}</td>
                                                                        <td align="center" style="font-size: 14px;" @if ($score->ca3 <= 50 && is_numeric($score->ca3)) class="highlight-red" @endif>{{ $score->ca3 ?? '-' }}</td>
                                                                        <td align="center" style="font-size: 14px;" @if ($score->ca1 && $score->ca2 && $score->ca3 && round(($score->ca1 + $score->ca2 + $score->ca3) / 3, 1) <= 50) class="highlight-red" @endif>
                                                                            {{ $score->ca1 && $score->ca2 && $score->ca3 ? round(($score->ca1 + $score->ca2 + $score->ca3) / 3, 1) : '-' }}
                                                                        </td>
                                                                        <td align="center" style="font-size: 14px;" @if ($score->exam <= 50 && is_numeric($score->exam)) class="highlight-red" @endif>{{ $score->exam ?? '-' }}</td>
                                                                        <td align="center" style="font-size: 14px;" @if ($score->total <= 50 && is_numeric($score->total)) class="highlight-red" @endif>{{ $score->total ?? '-' }}</td>
                                                                        <td align="center" style="font-size: 14px;" @if ($score->bf <= 50 && is_numeric($score->bf)) class="highlight-red" @endif>{{ $score->bf ?? '-' }}</td>
                                                                        <td align="center" style="font-size: 14px;" @if ($score->cum <= 50 && is_numeric($score->cum)) class="highlight-red" @endif>{{ $score->cum ?? '-' }}</td>
                                                                        <td align="center" style="font-size: 14px;" @if (in_array($score->grade, ['F', 'F9', 'E', 'E8'])) class="highlight-red" @endif>{{ $score->grade ?? '-' }}</td>
                                                                        <td align="center" style="font-size: 14px;">{{ $score->position ?? '-' }}</td>
                                                                        <td align="center" style="font-size: 14px;" @if ($score->class_average <= 50 && is_numeric($score->class_average)) class="highlight-red" @endif>{{ $score->class_average ?? '-' }}</td>
                                                                    </tr>
                                                                @empty
                                                                    <tr>
                                                                        <td colspan="14" align="center">No scores available for this student.</td>
                                                                    </tr>
                                                                @endforelse
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row gap-2 mb-2 d-flex flex-row">
                                                <div class="col bg-white rounded">
                                                    <div class="mt-2">
                                                        <div class="h5">Character Assessment</div>
                                                        <table class="table table-bordered table-hover table-responsive-sm" style="border: 1px solid black;">
                                                            <thead style="border: 1px solid black;">
                                                                <tr>
                                                                    <th></th>
                                                                    <th>Grade</th>
                                                                    <th>Sign</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($studentpp as $s)
                                                                    <tr>
                                                                        <td>Class Attendance</td>
                                                                        <td>{{ $s->attendance ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Attentiveness in Class</td>
                                                                        <td>{{ $s->attentiveness_in_class ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Class Participation</td>
                                                                        <td>{{ $s->class_participation ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Self Control</td>
                                                                        <td>{{ $s->selfcontrol ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Relationship with Others</td>
                                                                        <td>{{ $s->relationship_with_others ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Doing Assignment</td>
                                                                        <td>{{ $s->doing_assignment ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Neatness</td>
                                                                        <td>{{ $s->neatness ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="col bg-white rounded">
                                                    <div class="mt-2">
                                                        <div class="h5">Skill Development</div>
                                                        <table class="table table-bordered table-hover table-responsive-sm" style="border: 1px solid black;">
                                                            <thead style="border: 1px solid black;">
                                                                <tr>
                                                                    <th></th>
                                                                    <th>Grade</th>
                                                                    <th>Sign</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($studentpp as $s)
                                                                    <tr>
                                                                        <td>Writing Skill</td>
                                                                        <td>{{ $s->writing_skill ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Reading Skill</td>
                                                                        <td>{{ $s->reading_skill ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Spoken English/Communication</td>
                                                                        <td>{{ $s->spoken_english_communication ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Hand Writing</td>
                                                                        <td>{{ $s->hand_writing ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Sports/Games</td>
                                                                        <td>{{ $s->gamesandsports ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Club</td>
                                                                        <td>{{ $s->club ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Music</td>
                                                                        <td>{{ $s->music ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md bg-white rounded grade d-flex justify-content-around align-items-center">
                                                    <span>Grade: V.Good {VG}</span>
                                                    <span>Good {G}</span>
                                                    <span>Average {AVG}</span>
                                                    <span>Below Average {BA}</span>
                                                    <span>Poor {P}</span>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md bg-white rounded">
                                                    <div class="m-2">
                                                        <table class="w-100 table-bordered" style="border: 1px solid black;">
                                                            <tbody class="w-100">
                                                                <tr class="w-100">
                                                                    <td class="p-2 w-50">
                                                                        <div class="h6">Class Teacher's Remark Signature/Date</div>
                                                                        <div class="w-100">
                                                                            <span class="text-space-on-dots">{{ $studentpp[0]->classteachercomment ?? 'N/A' }}</span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="p-2 w-50">
                                                                        <div class="h6">Remark On Other Activities</div>
                                                                        <div class="">
                                                                            <span class="text-space-on-dots">{{ $studentpp[0]->remark_on_other_activities ?? 'N/A' }}</span>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <tr class="w-50">
                                                                    <td class="p-2 w-50">
                                                                        <div class="h6">Guidance Counselor's Remark Signature/Date</div>
                                                                        <div class="">
                                                                            <span class="text-space-on-dots">{{ $studentpp[0]->guidancescomment ?? 'N/A' }}</span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="p-2 w-50">
                                                                        <div class="h6">Principal's Remark Signature/Date</div>
                                                                        <div class="">
                                                                            <span class="text-space-on-dots">{{ $studentpp[0]->principalscomment ?? 'N/A' }}</span>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md bg-white rounded px-4">
                                                    <div class="d-flex flex-row justify-content-left align-items-center p-2 gap-4">
                                                        <span>This Result was issued on<span class="m-2 text-dot-space2">N/A</span></span>
                                                        <span>and collected by<span class="m-2 text-dot-space2">N/A</span></span>
                                                    </div>
                                                    <div class="d-flex flex-row justify-content-left align-items-center p-2 gap-4">
                                                        <span class="h6">NEXT TERM BEGINS<span class="m-2 text-dot-space2">N/A</span></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mock Report -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="mockReportHeader">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mockReportCollapse" aria-expanded="false" aria-controls="mockReportCollapse">
                                    Mock Report
                                </button>
                            </h2>
                            <div id="mockReportCollapse" class="accordion-collapse collapse" aria-labelledby="mockReportHeader" data-bs-parent="#studentReportsAccordion">
                                <div class="accordion-body">
                                    <div class="print-body bg-light w-100 h-100">
                                        <div class="print-sect container-fluid border bg-white" style="width: 1200px;">
                                            <div class="card-title m-0">
                                                <h3 class="fw-bold m-0">MOCK REPORT</h3>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm bg-white">
                                                    <div class="mt-3 result-table">
                                                        <table class="table table-bordered table-hover table-responsive-sm" style="border: 1px solid black;">
                                                            <thead style="border: 1px solid black;">
                                                                <tr class="rt">
                                                                    <th>S/N</th>
                                                                    <th>Subjects</th>
                                                                    <th>Term Exam</th>
                                                                    <th>Grade</th>
                                                                    <th>Position</th>
                                                                    <th>Class Average</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @forelse ($mockScores as $index => $score)
                                                                    <tr>
                                                                        <td align="center" style="font-size: 14px;">{{ $index + 1 }}</td>
                                                                        <td align="center" style="font-size: 14px;">{{ $score->subject_name }}</td>
                                                                        <td align="center" style="font-size: 14px;" @if ($score->exam <= 50 && is_numeric($score->exam)) class="highlight-red" @endif>{{ $score->exam ?? '-' }}</td>
                                                                        <td align="center" style="font-size: 14px;" @if (in_array($score->grade, ['F', 'F9', 'E', 'E8'])) class="highlight-red" @endif>{{ $score->grade ?? '-' }}</td>
                                                                        <td align="center" style="font-size: 14px;">{{ $score->position ?? '-' }}</td>
                                                                        <td align="center" style="font-size: 14px;" @if ($score->class_average <= 50 && is_numeric($score->class_average)) class="highlight-red" @endif>{{ $score->class_average ?? '-' }}</td>
                                                                    </tr>
                                                                @empty
                                                                    <tr>
                                                                        <td colspan="7" align="center">No mock scores available for this student.</td>
                                                                    </tr>
                                                                @endforelse
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row gap-2 mb-2 d-flex flex-row">
                                                <div class="col bg-white rounded">
                                                    <div class="mt-2">
                                                        <div class="h5">Character Assessment</div>
                                                        <table class="table table-bordered table-hover table-responsive-sm" style="border: 1px solid black;">
                                                            <thead style="border: 1px solid black;">
                                                                <tr>
                                                                    <th></th>
                                                                    <th>Grade</th>
                                                                    <th>Sign</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($studentpp as $s)
                                                                    <tr>
                                                                        <td>Class Attendance</td>
                                                                        <td>{{ $s->attendance ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Attentiveness in Class</td>
                                                                        <td>{{ $s->attentiveness_in_class ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Class Participation</td>
                                                                        <td>{{ $s->class_participation ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Self Control</td>
                                                                        <td>{{ $s->selfcontrol ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Relationship with Others</td>
                                                                        <td>{{ $s->relationship_with_others ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Doing Assignment</td>
                                                                        <td>{{ $s->doing_assignment ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Neatness</td>
                                                                        <td>{{ $s->neatness ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="col bg-white rounded">
                                                    <div class="mt-2">
                                                        <div class="h5">Skill Development</div>
                                                        <table class="table table-bordered table-hover table-responsive-sm" style="border: 1px solid black;">
                                                            <thead style="border: 1px solid black;">
                                                                <tr>
                                                                    <th></th>
                                                                    <th>Grade</th>
                                                                    <th>Sign</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($studentpp as $s)
                                                                    <tr>
                                                                        <td>Writing Skill</td>
                                                                        <td>{{ $s->writing_skill ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Reading Skill</td>
                                                                        <td>{{ $s->reading_skill ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Spoken English/Communication</td>
                                                                        <td>{{ $s->spoken_english_communication ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Hand Writing</td>
                                                                        <td>{{ $s->hand_writing ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Sports/Games</td>
                                                                        <td>{{ $s->gamesandsports ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Club</td>
                                                                        <td>{{ $s->club ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Music</td>
                                                                        <td>{{ $s->music ?? '-' }}</td>
                                                                        <td></td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md bg-white rounded grade d-flex justify-content-around align-items-center">
                                                    <span>Grade: V.Good {VG}</span>
                                                    <span>Good {G}</span>
                                                    <span>Average {AVG}</span>
                                                    <span>Below Average {BA}</span>
                                                    <span>Poor {P}</span>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md bg-white rounded">
                                                    <div class="m-2">
                                                        <table class="w-100 table-bordered" style="border: 1px solid black;">
                                                            <tbody class="w-100">
                                                                <tr class="w-100">
                                                                    <td class="p-2 w-50">
                                                                        <div class="h6">Class Teacher's Remark Signature/Date</div>
                                                                        <div class="w-100">
                                                                            <span class="text-space-on-dots">{{ $studentpp[0]->classteachercomment ?? 'N/A' }}</span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="p-2 w-50">
                                                                        <div class="h6">Remark On Other Activities</div>
                                                                        <div class="">
                                                                            <span class="text-space-on-dots">{{ $studentpp[0]->remark_on_other_activities ?? 'N/A' }}</span>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <tr class="w-50">
                                                                    <td class="p-2 w-50">
                                                                        <div class="h6">Guidance Counselor's Remark Signature/Date</div>
                                                                        <div class="">
                                                                            <span class="text-space-on-dots">{{ $studentpp[0]->guidancescomment ?? 'N/A' }}</span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="p-2 w-50">
                                                                        <div class="h6">Principal's Remark Signature/Date</div>
                                                                        <div class="">
                                                                            <span class="text-space-on-dots">{{ $studentpp[0]->principalscomment ?? 'N/A' }}</span>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md bg-white rounded px-4">
                                                    <div class="d-flex flex-row justify-content-left align-items-center p-2 gap-4">
                                                        <span>This Result was issued on<span class="m-2 text-dot-space2">N/A</span></span>
                                                        <span>and collected by<span class="m-2 text-dot-space2">N/A</span></span>
                                                    </div>
                                                    <div class="d-flex flex-row justify-content-left align-items-center p-2 gap-4">
                                                        <span class="h6">NEXT TERM BEGINS<span class="m-2 text-dot-space2">N/A</span></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="kt_app" class="toolbar py-3 py-lg-6">
                            <div id="kt_app" class="toolbar_container">
                                <div class="hstack gap-2 d-print-none">
                                    <a href="{{ route('viewstudent', [$schoolclassid, $termid, $sessionid]) }}" class="btn btn-success"><i class="ri-pr bi-pr-arrow-left"></i> << Back</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        No student data found.
                    </div>
                @endif

                <!-- Image View Modal -->
                <div id="imageViewModal" class="modal fade" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Student Picture</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img id="enlargedImage" src="" alt="Student Picture" class="img-fluid" />
                                <div class="placeholder-text">No image available</div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Handle image view modal
        const imageViewModal = document.getElementById('imageViewModal');
        if (imageViewModal) {
            imageViewModal.addEventListener('show.bs.modal', async function (event) {
                const button = event.relatedTarget;
                const imageSrc = button.getAttribute('data-image') || '{{ asset('storage/student_avatars/unnamed.jpg') }}';
                const pictureName = button.getAttribute('data-picture') || 'none';
                const admissionNo = button.getAttribute('data-admissionno') || 'unknown';
                const fileExists = button.getAttribute('data-file-exists') === 'true';
                const defaultImageExists = button.getAttribute('data-default-exists') === 'true';
                const modalImage = this.querySelector('#enlargedImage');
                const placeholderText = this.querySelector('.placeholder-text');

                console.log(`Opening image modal for admissionNo: ${admissionNo}, picture: ${pictureName}, src: ${imageSrc}, fileExists: ${fileExists}, defaultImageExists: ${defaultImageExists}`);

                // Reset modal content
                modalImage.src = '';
                modalImage.style.display = 'none';
                placeholderText.style.display = 'none';

                // Use server-side file existence check
                if (!fileExists) {
                    console.log(`Server-side check indicates image does not exist for admissionNo: ${admissionNo}`);
                    modalImage.src = '{{ asset('storage/student_avatars/unnamed.jpg') }}';
                    if (defaultImageExists) {
                        modalImage.style.display = 'block';
                    } else {
                        console.error(`Default image /storage/student_avatars/unnamed.jpg does not exist for admissionNo: ${admissionNo}`);
                        placeholderText.textContent = `No image available for ${admissionNo}`;
                        placeholderText.style.display = 'block';
                    }
                } else {
                    // Verify image accessibility client-side
                    const imageExists = await checkImageExists(imageSrc);
                    if (imageExists) {
                        modalImage.src = imageSrc;
                        modalImage.style.display = 'block';
                        console.log(`Set enlarged image for admissionNo: ${admissionNo}, src: ${imageSrc}`);
                    } else {
                        console.error(`Image does not exist for admissionNo: ${admissionNo}, picture: ${pictureName}, attempted URL: ${imageSrc}`);
                        modalImage.src = '{{ asset('storage/student_avatars/unnamed.jpg') }}';
                        if (defaultImageExists) {
                            modalImage.style.display = 'block';
                        } else {
                            console.error(`Default image /storage/student_avatars/unnamed.jpg does not exist for admissionNo: ${admissionNo}`);
                            placeholderText.textContent = `No image available for ${admissionNo}`;
                            placeholderText.style.display = 'block';
                        }
                    }
                }

                // Handle image load success
                modalImage.onload = () => {
                    console.log(`Successfully loaded enlarged image for admissionNo: ${admissionNo}, src: ${modalImage.src}`);
                    modalImage.style.display = 'block';
                    placeholderText.style.display = 'none';
                };

                // Handle image load failure
                modalImage.onerror = () => {
                    console.error(`Failed to load enlarged image for admissionNo: ${admissionNo}, picture: ${pictureName}, attempted URL: ${imageSrc}`);
                    modalImage.src = '{{ asset('storage/student_avatars/unnamed.jpg') }}';
                    if (defaultImageExists) {
                        modalImage.style.display = 'block';
                    } else {
                        console.error(`Default image /storage/student_avatars/unnamed.jpg failed to load for admissionNo: ${admissionNo}`);
                        placeholderText.textContent = `No image available for ${admissionNo}`;
                        placeholderText.style.display = 'block';
                    }
                };
            });

            // Clear image when modal is hidden
            imageViewModal.addEventListener('hidden.bs.modal', function () {
                const modalImage = this.querySelector('#enlargedImage');
                const placeholderText = this.querySelector('.placeholder-text');
                modalImage.src = '';
                modalImage.style.display = 'none';
                placeholderText.style.display = 'none';
                console.log('imageViewModal closed, cleared enlargedImage src and placeholder');
            });
        } else {
            console.warn('imageViewModal not found in DOM');
        }

        // Function to test if an image exists
        function checkImageExists(url) {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => {
                    console.log(`Image check succeeded for URL: ${url}`);
                    resolve(true);
                };
                img.onerror = () => {
                    console.log(`Image check failed for URL: ${url}`);
                    resolve(false);
                };
                img.src = url;
            });
        }

        // Debug form submission
        const form = document.querySelector('form[action="{{ route('studentpersonalityprofile.save') }}"]');
        if (form) {
            form.addEventListener('submit', function (event) {
                console.log('Form submission triggered');
                const formData = new FormData(form);
                for (let [key, value] of formData.entries()) {
                    console.log(`${key}: ${value}`);
                }
            });
        }
    });
</script>
@endsection