@extends('layouts.master')
@section('content')

     <!-- Start Page title and tab -->
     <div class="section-body">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center ">
                <div class="header-action">
                    <h1 class="page-title">School</h1>
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Student Results </a></li>

                    </ol>
                </div>

            </div>
        </div>
        @if (\Session::has('status'))
           <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert"></button>
                <p>{{ \Session::get('status') }}</p>
            </div>
        @endif
        @if (\Session::has('success'))
           <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert"></button>
                <p>{{ \Session::get('success') }}</p>
            </div>
        @endif
        @if (\Session::has('danger'))
        <div class="alert alert-danger alert-dismissible">
         <button type="button" class="close" data-dismiss="alert"></button>
             <p>{{ \Session::get('danger') }}</p>
         </div>
     @endif


    </div>
    <div class="section-body mt-4">
        <div class="container-fluid">
            <div class="tab-content">
                <div class="tab-pane active" id="classteacher">
                    <div class="card">

                        <div class="card-header">

                            <h3 class="card-title">Select Session and Term to proceed</h3>

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

                            {!! Form::model($schoolclassid, ['route' => ['studentresults.update',$schoolclassid], 'method'=>'PATCH','class'=>'form-horizontal form-stripe','onSubmit'=>'return validateForm();']) !!}
                            @csrf

                            <div class="form-group row">
                                <label class="col-md-3 col-form-label">Select Term <span class="text-danger">*</span></label>
                                <div class="col-md-7">
                                    <select name ="termid" id="termid"  class="form-control" >
                                        <option value="" selected>Select Term </option>
                                        @foreach ($schoolterms as $term => $name )
                                         <option value="{{$name->id}}">{{ $name->term}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-3 col-form-label">Select Session <span class="text-danger">*</span></label>
                                <div class="col-md-7">
                                    <select name ="sessionid" id="sessiionid"  class="form-control" >
                                        <option value="" selected>Select Session </option>
                                        @foreach ($schoolsessions as $schoolsession => $name )
                                         <option value="{{$name->id}}">{{ $name->session}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-3 col-form-label"></label>
                                <div class="col-md-7">
                                    <button type="submit" class="btn btn-primary">Proceed</button>

                                </div>
                            </div>
                            {!! Form::close() !!}
                    </div>
                </div>


            </div>
        </div>
    </div>


    @endsection
