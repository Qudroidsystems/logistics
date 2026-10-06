@extends('layouts.master')
@section('content')
<div class="content">
            <div class="content-header">

                <div class="leftside-content-header">
                    <ul class="breadcrumbs">
                        <li><i class="fa fa-columns" aria-hidden="true"></i><a href="{{ route('staff.index') }}">Staff Management</a></li>
                        <li><a>Add New Staff</a></li>
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
                    <a class="btn btn-primary" href="{{ route('staff.index') }}"> Back to Staff Management</a>
                </span>


                </div>
                <div class="row animated fadeInUp">
                    <div class="col-sm-12">

                        <div class="panel">
                            <div class="panel-content">
                                <div class="row">

                                    <div class="col-md-12">

                                        <div class="tabs">
                                            <ul class="nav nav-tabs nav-justified">
                                                <li class="active"><a href="#home2" data-toggle="tab">Home</a></li>
                                                <li><a href="#profile2" data-toggle="tab">Profile</a></li>
                                                <li><a href="#messages2" data-toggle="tab">Messages</a></li>
                                                <li><a href="#settings2" data-toggle="tab"><i class="fa fa-cog" aria-hidden="true"></i> Settings</a></li>
                                            </ul>
                                            <div class="tab-content">
                                                <div class="tab-pane fade in active" id="home2">



                                                        <form class="form-horizontal">
                                                            <h6 class="mb-xlg text-center"><b>Create Staff BioData Info</b></h6>
                                                            <div class="form-group">
                                                                <label for="name" class="col-sm-2 control-label">Name</label>
                                                                <div class="col-sm-10">
                                                                    <input type="text" class="form-control" id="name"  placeholder="name" {{ $user->name }}>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="name" class="col-sm-2 control-label">Employment ID</label>
                                                                <div class="col-sm-10">
                                                                    <input type="text" class="form-control" id="employmentid" required placeholder="Employment Id">
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="username" class="col-sm-2 control-label">Phone Number</label>
                                                                <div class="col-sm-10">
                                                                    <input type="text" class="form-control" id="phonenumber" required placeholder="Phone Number">
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="email3" class="col-sm-2 control-label">Email</label>
                                                                <div class="col-sm-10">
                                                                    <input type="email" class="form-control" id="email" required placeholder="Email">
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="password3" class="col-sm-2 control-label">Password</label>
                                                                <div class="col-sm-10">
                                                                    <input type="password" class="form-control" id="password3" placeholder="Password">
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <div class="col-sm-offset-2 col-sm-10">
                                                                    <div class="checkbox">
                                                                        <label>
                                                                            <input type="checkbox"> Remember me
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <div class="col-sm-offset-2 col-sm-10">
                                                                    <button type="submit" class="btn btn-primary">Sign in</button>
                                                                </div>
                                                            </div>
                                                        </form>




                                                </div>
                                                <div class="tab-pane fade" id="profile2">
                                                    <p><b>Profile</b> content</p>
                                                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas vitae tellus tincidunt, mattis odio eu, accumsan quam. Duis ultricies, erat nec suscipit mattis, risus est efficitur enim, sed finibus lacus nisi et mauris. Ut sed accumsan ipsum. Aliquam vel nibh et turpis euismod porttitor. In diam odio, cursus eget faucibus quis, efficitur id erat. Aliquam euismod in justo sit amet ornare. Quisque eu fringilla libero. Donec iaculis sit amet nibh non laoreet.
                                                    </p>
                                                </div>
                                                <div class="tab-pane fade" id="messages2">
                                                    <p><b>Message</b> content</p>
                                                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas vitae tellus tincidunt, mattis odio eu, accumsan quam. Duis ultricies, erat nec suscipit mattis, risus est efficitur enim, sed finibus lacus nisi et mauris. Ut sed accumsan ipsum. Aliquam vel nibh et turpis euismod porttitor. In diam odio, cursus eget faucibus quis, efficitur id erat. Aliquam euismod in justo sit amet ornare. Quisque eu fringilla libero. Donec iaculis sit amet nibh non laoreet.
                                                    </p>
                                                </div>
                                                <div class="tab-pane fade" id="settings2">
                                                    <p><b>Settings</b> content</p>
                                                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas vitae tellus tincidunt, mattis odio eu, accumsan quam. Duis ultricies, erat nec suscipit mattis, risus est efficitur enim, sed finibus lacus nisi et mauris. Ut sed accumsan ipsum. Aliquam vel nibh et turpis euismod porttitor. In diam odio, cursus eget faucibus quis, efficitur id erat. Aliquam euismod in justo sit amet ornare. Quisque eu fringilla libero. Donec iaculis sit amet nibh non laoreet.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        @endsection
