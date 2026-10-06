@extends('layouts.master')
@section('content')

     <!-- Start Page title and tab -->
     <div class="section-body">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center ">
                <div class="header-action">
                    <h1 class="page-title">School</h1>
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Class Teacher</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit Class Teacher</li>
                    </ol>
                </div>
                <ul class="nav nav-tabs page-header-tab">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#edit-class-teacher">Edit Class Teacher</a></li>

                </ul>
            </div>
        </div>
        @if (\Session::has('status'))
           <div class="alert alert-warning fade in">
            <a href="#" class="close" data-dismiss="alert">&times;</a>
                <p>{{ \Session::get('status') }}</p>
            </div>
        @endif
            @if (\Session::has('success'))
           <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert"></button>
                <p>{{ \Session::get('success') }}</p>
            </div>
        @endif

    </div>
    <div class="section-body mt-4">
        <div class="container-fluid">
            <div class="tab-content">
                <div class="tab-pane active" id="edit-arm">
                    <div class="card">
                        <div class="card-header">
                            <a class="btn btn-primary" href="{{ route('classteacher.index') }}"> Back to Class Teacher</a>
                            @if (count($errors) > 0)
                            <div class="alert alert-success alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert"></button>
                                <strong>Opps!</strong> Something went wrong, please check below errors.<br><br>
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                            <div class="card-options ">
                                <a href="#" class="card-options-collapse" data-toggle="card-collapse"><i class="fe fe-chevron-up"></i></a>
                                <a href="#" class="card-options-remove" data-toggle="card-remove"><i class="fe fe-x"></i></a>
                            </div>
                        </div>
                        <?php
                        foreach($classteachers as $cteachers) {
                            $cteachers->ctid;
                            $cteachers->staffname;
                            $cteachers->schoolclass;
                            $cteachers->schoolarm;
                            $cteachers->schoolterm;
                            $cteachers->schoolsession;
                        }

                        ?>
                        {!! Form::model($classteachers, ['route' => ['classteacher.update', $cteachers->ctid], 'method'=>'PATCH','class'=>'form-horizontal form-stripe']) !!}
                        @csrf

                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">Select Staff <span class="text-danger">*</span></label>
                            <div class="col-md-7">
                                <select name ="staffid" id="staffid"  class="form-control">


                                    @if ($cteachers->userid == "")
                                        <option value="">Select Staff </option>
                                    @else
                                        <option value="{{ $cteachers->userid }}">Select  </option>
                                    @endif


                                @foreach ($teachers as $staffname => $name )
                                 <option value="{{$name->userid}}">{{ $name->name }} </option>
                                @endforeach
                            </select>[ {{ $cteachers->staffname }}]
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">Select Class <span class="text-danger">*</span></label>
                            <div class="col-md-7">
                                <select name ="schoolclassid" id="schoolclassid"  class="form-control" >

                                    @if ($cteachers->classid == "")
                                         <option value="">Select Class </option>
                                    @else
                                        <option value="{{ $cteachers->classid }}">Select  Class </option>
                                    @endif

                                    @foreach ($schoolclasses as $schoolclass => $name )
                                     <option value="{{$name->id}}">{{ $name->schoolclass }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $name->arm}} </option>
                                    @endforeach
                                </select>[ {{ $cteachers->schoolclass }} &nbsp;&nbsp;&nbsp;&nbsp; {{ $cteachers->schoolarm }}]
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">Select Term <span class="text-danger">*</span></label>
                            <div class="col-md-7">
                                <select name ="termid" id="termid"  class="form-control" >

                                    @if ($cteachers->termid == "")
                                    <option value="{{ $cteachers->termid }}">Select Term </option>
                                    @else
                                        <option value="{{ $cteachers->termid }}" selected>Select  Term </option>
                                    @endif



                                    @foreach ($schoolterms as $schoolterm => $name )
                                     <option value="{{$name->id}}">{{ $name->term}}</option>
                                    @endforeach
                                </select>[ {{ $cteachers->term }}]
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">Select Session <span class="text-danger">*</span></label>
                            <div class="col-md-7">
                                <select name ="sessionid" id="sessiionid"  class="form-control">

                                    @if ($cteachers->sessionid == "")
                                    <option value="">Select Session </option>
                                    @else
                                        <option value="{{ $cteachers->sessionid }}">Select  Session </option>
                                    @endif

                                    @foreach ($schoolsessions as $schoolsession => $name )
                                     <option value="{{$name->id}}">{{ $name->session}}</option>
                                    @endforeach
                                </select>[ {{ $cteachers->session }}]
                            </div>
                        </div>


                            <div class="form-group row">
                                <label class="col-md-3 col-form-label"></label>
                                <div class="col-md-7">
                                    <button type="submit" class="btn btn-primary">Submit</button>

                                </div>
                            </div>
                            {!! Form::close() !!}


                    </div>
                </div>


            </div>
        </div>
    </div>
    <script>
        function chk(){
      //  var p = document.getElementById('ternid').value;
       // var opt = p.options[p.selectedIndex];
            alert();
        }
    </script>
    @endsection
