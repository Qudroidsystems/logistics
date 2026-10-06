@extends('layouts.master')
@section('content')

     <!-- Start Page title and tab -->
     <div class="section-body">
        <div class="container-fluid">

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

                    <div class="align-center">
                        <div class="align-center">
                            <h3 class="align-center"><div class="align-center">NO SUBJECT REGISTERED YET</div></h3>
                        </div>

                    </div>

                </div>


            </div>
        </div>
    </div>


    @endsection
