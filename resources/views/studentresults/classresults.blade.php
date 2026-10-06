@extends('layouts.master')
@section('content')



        <!--begin::Main-->
        <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <!--begin::Content wrapper-->
                <div class="d-flex flex-column flex-column-fluid">

                <!--begin::Toolbar-->
                <div id="kt_app_toolbar" class="app-toolbar  py-3 py-lg-6 ">

                            <!--begin::Toolbar container-->
                        <div id="kt_app_toolbar_container" class="app-container  container-xxl d-flex flex-stack ">

                            <!--begin::Page title-->
                            <div  class="page-title d-flex flex-column justify-content-center flex-wrap me-3 ">
                                <!--begin::Title-->
                                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                                Student Accademic Report
                                        </h1>
                                <!--end::Title-->


                                    <!--begin::Breadcrumb-->
                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                                        <!--begin::Item-->
                                                        <li class="breadcrumb-item text-muted">
                                                            <a href="{{ route('student.index') }}" class="text-muted text-hover-primary">Student's Result  </a>
                                                                        </li>
                                                            <!--end::Item-->
                                                                <!--begin::Item-->
                                                <li class="breadcrumb-item">
                                                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                                                </li>
                                                <!--end::Item-->

                                                        <!--begin::Item-->
                                                                <li class="breadcrumb-item text-muted">Student's Result</li>
                                                            <!--end::Item-->

                                                </ul>
                                    <!--end::Breadcrumb-->
                                </div>
                            <!--end::Page title-->
                                @if ($errors->any())
                                <div class="alert alert-danger">
                                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif

                                @if (\Session::has('status'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ \Session::get('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                @endif
                                @if (\Session::has('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ \Session::get('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                @endif

                            </div>
                            <!--end::Toolbar container-->
                        </div>
                    <!--end::Toolbar-->


                    <div id="kt_app_content" class="app-content  flex-column-fluid " >
                        <!--begin::Content container-->
                        <div id="kt_app_content_container" class="app-container  container-xxl ">

                   <!--begin::Toolbar-->
                        <div class="d-flex flex-wrap flex-stack my-5">
                            <!--begin::Heading-->
                            <h2 class="fs-2 fw-semibold my-2">
                                Student Management
                                <span class="fs-6 text-gray-400 ms-1">Database</span>
                            </h2>
                            <!--end::Heading-->


                        </div>
                    <!--end::Toolbar-->



        <!--begin::Card-->
    <div class="card">
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">

                  <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                              <!--begin::Toolbar-->
                                    <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                                                <!--begin::Add user-->
                                                <a href="{{ route('student.create') }}" type="button" class="btn btn-primary">
                                                    <i class="ki-duotone ki-plus fs-2"></i>     Add New Student
                                                </a>
                                                <!--end::Add user-->
                                    </div>





                                    </div>
                                    <!--end::Card toolbar-->
                                            <!--begin::Card toolbar-->
                                            <div class="card-toolbar">
                                                <!--begin::Search-->
                                                <div class="d-flex align-items-center position-relative my-1"  data-kt-view-roles-table-toolbar="base">
                                                    <i class="ki-duotone ki-magnifier fs-1 position-absolute ms-6"><span class="path1"></span><span class="path2"></span></i>                <input type="text" data-kt-roles-table-filter="search" class="form-control form-control-solid w-250px ps-15" placeholder="Search ..." />
                                                </div>
                                                <!--end::Search-->

                                                <!--begin::Group actions-->
                                                <div class="d-flex justify-content-end align-items-center d-none" data-kt-view-roles-table-toolbar="selected">
                                                    <div class="fw-bold me-5">
                                                        <span class="me-2" data-kt-view-roles-table-select="selected_count"></span> Selected
                                                    </div>

                                                    <button type="button" class="btn btn-danger" data-kt-view-roles-table-select="delete_selected">
                                                        Delete Selected
                                                    </button>
                                                </div>
                                                <!--end::Group actions-->
                                            </div>
                                            <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->

            @if (count($errors) > 0)
            <div class="row animated fadeInUp">
                @if (count($errors) > 0)
            <div class="alert alert-warning fade in">
            <a href="#" class="close" data-dismiss="alert">&times;</a>
                <strong>Opps!</strong> Something went wrong, please check below errors.<br><br>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            </div>
            @endif
            <!--begin::Card body-->
            <div class="card-body py-4">

            <?php
                                foreach ($schoolclass as $key => $value) {
                                    # code...
                                    $sclass =  $value->schoolclass;
                                    $arm = $value->arm;
                                }

                                foreach ($session as $key => $value) {
                                    # code...
                                    $session =  $value->session;

                                }


                                foreach ($term as $key => $value) {
                                    # code...
                                    $term =  $value->term;

                                }

                            ?>

             <!--begin::Table-->
        <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0" id="kt_roles_view_table">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_roles_view_table .form-check-input" value="1" />
                        </div>
                    </th>
                    <th>SN</th>
                    <th>Admission No</th>
                    <th>Name</th>
                    <th>Gender</th>
                    {{-- <th></th> --}}
                    <th>View Result</th>
            </tr>
            </thead>
            <tbody class="fw-semibold text-gray-600">
                                        <?php $sn = 1; ?>
                                        @foreach ($students as $student)

                                        <tr>
                                            <td>{{ $sn++ }}</td>
                                            <td>{{ $student->admissionno }}</td>
                                            <td>{{ $student->fname }}  {{ $student->lname }}</td>
                                            <td>{{ $student->gender }}</td>

                                            <?php

                                            if ($student->picture == NULL || $student->picture =="" || !isset($student->picture) ){

                                                  // $cimage =  $imageclass;
                                                   $image =  'unnamed.png';
                                            }else {

                                               $image =  $student->picture;
                                            }

                                            ?>
                                            <td><img  class = "avatar avatar-lg" src="{{ asset('images/studentpics/'.$image) }}"/></td>

                                            <td>  <a href="/viewresults/{{ $student->studentID }}/{{ $schoolclassid }}/{{ $student->termid }}/{{ $student->sessionid }}" class="btn btn-outline-success" data-toggle="tooltip"
                                                title="View all Students in  {{  $student->firstname}}  {{ $student->lastname  }} ">View Result</a></td>

                                        </tr>
                                        @endforeach
            </tbody>
        </table>
        <!--end::Table-->
            </div>
            <!--end::Card body-->
      </div>
            <!--end::Card-->
    </div>


<div class="card">

    <div class="print-body bg-light w-100 h-100">
        <div class="print-sect container-fluid border bg-white" style="width: 1200px;">

            <div class="row mb-2">


                <div class="col-md d-flex flex-column">
                    <div class="w-100 d-flex justify-content-center align-items-center pt-1">
                        <div class="school-logo">
                            <img src=" {{ asset('print-main/public/assets/tp.png') }}" class="w-100 h-100" alt="">
                        </div>
                        <div>
                            <p class="school-name1 m-0">TCC</p>
                        </div>
                    </div>

                    <div class="w-100 d-flex justify-content-center align-items-center">
                        <p class="school-name2 m-0">TOPCLASS COLLEGE</p>
                    </div>

                    <div class="w-100 d-flex flex-column justify-content-center align-items-center">
                        <p class="h4 m-0">Developing the total child</p>
                        <p class="h4 m-0">39, Okegbala Street off Odojomu Road, Ondo.</p>
                    </div>

                    <div class="header-divider"></div>
                    <div class="header-divider2"></div>

                    <div class="w-100 d-flex flex-column justify-content-center align-items-center">
                        <p class="h1 m-0 bg-black text-white px-1 rounded"
                            style="font-family: 'Times New Roman', Times, serif;">
                            TERMINAL PROGRESS REPORT FOR SSS 1-3
                        </p>
                    </div>

                </div>
            </div>


            <div class="row mb-2 d-flex flex-row">
                <div class="col-sm-10 bg-white d-flex flex-column justify-content-left gap-3">
                    <div class="d-flex flex-row flex-nowrap align-items-center gap-2">
                        <span class="result-details">Name of Student:</span><span class="rd1"></span>
                    </div>
                    <div class="d-flex flex-row align-items-center gap-2">
                        <span class="result-details">Session:</span><span class="rd2"></span>
                        <span class="result-details">Term:</span><span class="rd3"></span>
                        <span class="result-details">Class:</span><span class="rd4"></span>
                    </div>
                    <div class="d-flex flex-row align-items-center gap-2">
                        <span class="result-details">Date of Birth:</span><span class="rd5"></span>
                        <span class="result-details">Admission No:</span><span class="rd6"></span>
                        <span class="result-details">Sex:</span><span class="rd7"></span>
                    </div>
                    <div class="d-flex flex-row align-items-center gap-2">
                        <span class="result-details">No. of Times School Opened:</span><span class="rd8"></span>
                        <span class="result-details">No. of Times School Absent:</span><span class="rd9"></span>
                        <span class="result-details">No. of Student in Class:</span><span class="rd10"></span>
                    </div>
                </div>
                <div class="col bg-white d-flex justify-content-center align-items-center" >
                    <div class="h-100 bg-light" style="width: 90%; box-shadow: 50px">
                        <!-- profile image section -->
                        <img src=" {{ asset('print-main/public/assets/siji.jpg') }}" class="w-100 h-100" alt="">
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-sm bg-white">

                    <div class="mt-3 result-table">
                        <table class="table table-bordered table-hover table-responsive-sm"
                            style="border: 1px solid black;">
                            <thead>
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
                                    <th>l</th>
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
                                    <th>Sign</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td align="center" style="font-size: 16px; font-weight: bold;">1</td>
                                    <td align="center" style="font-size: 16px; font-weight: bold;">Mathematics</td>
                                    <td align="center" style="font-size: 16px; font-weight: bold;">12</td>
                                    <td align="center" style="font-size: 16px; font-weight: bold;">9</td>
                                    <td align="center" style="font-size: 16px; font-weight: bold;">23</td>
                                    <td align="center" style="font-size: 16px; font-weight: bold;">56</td>
                                    <td align="center" style="font-size: 16px; font-weight: bold;">23</td>
                                    <td align="center" style="font-size: 16px; font-weight: bold;"></td>
                                    <td align="center" style="font-size: 16px; font-weight: bold;"></td>
                                    <td align="center" style="font-size: 16px; font-weight: bold;"></td>
                                    <td align="center" style="font-size: 16px; font-weight: bold;"></td>
                                    <td align="center" style="font-size: 16px; font-weight: bold;"></td>
                                    <td align="center" style="font-size: 16px; font-weight: bold;"></td>
                                    <td align="center" style="font-size: 16px; font-weight: bold;"></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>English Language Language</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                </div>
            </div>


            <div class="row gap-2 mb-2 flex flex-row">
                <div class="col bg-white rounded">

                    <div class="mt-2">
                        <div class="h5">Character Assessment</div>
                        <table class="table table-bordered table-hover table-responsive-sm"
                            style="border: 1px solid black;">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Grade</th>
                                    <th>Sign</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Class Attendance</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Attentiveness in Class</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Class Participation</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Self Control</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Relationship with Others</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Doing Assignment</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Neatness</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
                <div class="col bg-white rounded">

                    <div class="mt-2">
                        <div class="h5">Skill Development</div>
                        <table class="table table-bordered table-hover table-responsive-sm"
                            style="border: 1px solid black;">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Grade</th>
                                    <th>Sign</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Writing Skill</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Reading Skill</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Spoken English/Communication</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Hand Writing</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Sports/Games</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Club</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Music</td>
                                    <td></td>
                                    <td></td>
                                </tr>
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
                                            <span class="text-space-on-dots">aaa</span>
                                        </div>
                                    </td>
                                    <td class="p-2 w-50">
                                        <div class="h6">Remark On Other Activities</div>
                                        <div class="">
                                            <span class="text-space-on-dots">aaa</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="w-50">
                                    <td class="p-2 w-50">
                                        <div class="h6">Guidance Counselor's Remark Signature/Date</div>
                                        <div class="">
                                            <span class="text-space-on-dots">aaa</span>
                                        </div>
                                    </td>
                                    <td class="p-2 w-50">
                                        <div class="h6">Principal's Remark Signature/Date</div>
                                        <div class="">
                                            <span class="text-space-on-dots">aaa</span>
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
                    <div class=" d-flex flex-row justify-content-left align-items-center p-2  gap-4">
                        <span>This Result was issued on<span class="m-2 text-dot-space2">abc</span></span>
                        <span>and collected by<span class="m-2 text-dot-space2">abc</span></span>
                    </div>
                    <div class=" d-flex flex-row justify-content-left align-items-center p-2  gap-4">
                        <span class="h6">NEXT TERM BEGINS<span class="m-2 text-dot-space2">abc</span></span>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>


            <!--end::Content container-->
    </div>
        <!--end::Content-->

@endsection
