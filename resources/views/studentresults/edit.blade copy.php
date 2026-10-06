@extends('layouts.master')
@section('content')
<div class="content">
            <div class="content-header">

                <div class="leftside-content-header">
                    <ul class="breadcrumbs">
                        <li><i class="fa fa-columns" aria-hidden="true"></i><a href="{{ route('classteacher.index') }}">Class Teacher Management</a></li>
                        <li><a>Edit Class Teacher</a></li>
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
            <div class="alert alert-success fade in">
             <a href="#" class="close" data-dismiss="alert">&times;</a>
                 <p>{{ \Session::get('success') }}</p>
             </div>
         @endif
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
                <div class="col-sm-12 col-md-8 col-md-offset-2">
                   <span class="float-right">
                    <a class="btn btn-primary" href="{{ route('classteacher.index') }}"> Back to Class Teacher Management</a>
                </span>
                    <div class="panel">
                        <div class="panel-content">
                            <div class="row">
                                <div class="col-md-12">
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
                                     <div class="form-group">
                                        <label for="password" class="col-sm-3 control-label">Select Teacher<span class="required">*</span></label>
                                        <div class="col-sm-9">
                                    <select name ="staffid" id="staffid"  class="form-control" required>
                                        <option value="" selected>Select staff</option>
                                        @foreach ($teachers as $staffname => $name )
                                         <option value="{{$name->userid}}">{{ $name->name }} </option>
                                        @endforeach
                                    </select>[ {{ $cteachers->staffname }}]
                                        </div>
                                </div>


                                        <div class="form-group">
                                            <label for="password" class="col-sm-3 control-label">Select Class<span class="required">*</span></label>
                                            <div class="col-sm-9">
                                        <select name ="schoolclassid" id="schoolclassid"  class="form-control" required>
                                            <option value="" selected>Select Class</option>
                                            @foreach ($schoolclasses as $schoolclass => $name )
                                             <option value="{{$name->id}}">{{ $name->schoolclass }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $name->arm}} </option>
                                            @endforeach
                                        </select>[ {{ $cteachers->schoolclass }} &nbsp;&nbsp;&nbsp;&nbsp; {{ $cteachers->schoolarm }}]
                                            </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="password" class="col-sm-3 control-label">Select Term<span class="required">*</span></label>
                                        <div class="col-sm-9">
                                    <select name ="termid" id="termid"  class="form-control" required>
                                        <option value="" selected>Select Term </option>
                                        @foreach ($schoolterms as $schoolterm => $name )
                                         <option value="{{$name->id}}">{{ $name->term}}</option>
                                        @endforeach
                                    </select>[ {{ $cteachers->term }}]
                                        </div>
                                </div>

                                <div class="form-group">
                                    <label for="password" class="col-sm-3 control-label">Select Session<span class="required">*</span></label>
                                    <div class="col-sm-9">
                                <select name ="sessionid" id="sessiionid"  class="form-control" required>
                                    <option value="" selected>Select Session </option>
                                    @foreach ($schoolsessions as $schoolsession => $name )
                                     <option value="{{$name->id}}">{{ $name->session}}</option>
                                    @endforeach
                                </select>[ {{ $cteachers->session }}]
                                    </div>
                            </div>

                                        <div class="form-group">
                                            <div class="col-sm-offset-3 col-sm-9">
                                                <button type="submit" class="btn btn-primary">Assign Teacher to Class</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        @endsection
