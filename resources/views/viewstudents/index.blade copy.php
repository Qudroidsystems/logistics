@extends('layouts.master')
@section('content')

<div class="content">
    <div class="content-header">
        <div class="leftside-content-header">
            <ul class="breadcrumbs">
                <li><i class="fa fa-table" aria-hidden="true"></i><a href="{{ route('staff.index') }}">Staff</a></li>
                <li><a>Staff Management</a></li>
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
    <div class="alert alert-warning fade in">
     <a href="#" class="close" data-dismiss="alert">&times;</a>
         <p>{{ \Session::get('success') }}</p>
     </div>
 @endif

    <span class="float-right">

    </span>

<div class="row animated fadeInRight">
        <div class="col-sm-12">
            <h4 class="section-subtitle">Registered Staff</h4>
            <div class="panel">
                <div class="panel-content">
                    <table id="responsive-table" class="data-table table table-striped table-hover responsive nowrap" cellspacing="0" width="100%">
                        <thead>
                        <tr>
                            <th>SN</th>
                            <th>Name</th>
                            <th>Picture</th>
                            <th>Employment ID</th>
                            <th>Email</th>
                            <th>Profile</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                           <?php $sn = 1; ?>
                        @foreach ($users as $user)

                        <tr>
                            <td>{{ $sn++ }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->pic }}</td>
                            <td>{{ $user->employmentid  }}</td>
                            <td>{{ $user->email }} </td>
                            <td>View Profile </td>
                            <td><div class="btn-group">

                                @can('edit-staff')
                                <a href="{{ route('staff.edit',$user->userid) }}" class="btn fa fa-pencil" data-toggle="tooltip" title="Edit Staff"></a>
                                @endcan
                                @can('delete-staff')
                                {!! Form::open(['method' => 'DELETE','route' => ['staff.destroy', $user->userid],]) !!}
                                <button class="btn  fa fa-trash" data-toggle="tooltip" title="Delete Staff"></button>
                                {!! Form::close() !!}
                            @endcan

                            </div></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>


@endsection
