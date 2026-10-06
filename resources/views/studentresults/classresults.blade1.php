@extends('layouts.master')
@section('content')

     <!-- Start Page title and tab -->
     <div class="section-body">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center ">
                <div class="header-action">
                    <h1 class="page-title">School</h1>
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Student Result</a></li>
                        <li class="breadcrumb-item active" aria-current="page">View Results</li>
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
                <div class="tab-pane active" id="mysubjects">
                    <div class="card">
                        <div class="card-header">


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


<h5 class="card-title">Results for {{ $sclass }}  {{ $arm }} for {{  $term }} in  curent session ({{ $session }})</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">

                                <table class="table table-hover js-basic-example dataTable table-striped table_custom   ">
                                    <thead>
                                        <tr>
                                            <tr>
                                                <th>SN</th>
                                                <th>Admission No</th>
                                                <th>Name</th>
                                                <th>Gender</th>
                                                <th></th>
                                                <th>View Result</th>

                                            </tr>
                                        </tr>
                                    </thead>
                                    <tbody>
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

                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane" id="mysubjectshistory">
                    <div class="card">
                        <div class="card-header">

                            <h3 class="card-title">My Subjects History</h3>


                        </div>

                        <div class="card-body">
                            <div class="table-responsive">



                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script>

        function check(id){

            var id = id;
            var spinner = $('#loader');

          Swal.fire({
          title: 'Are you sure?',
          text: "Deleting this record will affect other associated records (e.g Any Record where this Class Teacher is featured)",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            spinner.show();
            $.ajax({

                url: 'ajaxclassteacher/'+id,
                async: false,
                type: "DELETE",
                cache: false,
                data:{
                    _token:'{{ csrf_token() }}',
                   id: id
                },
                dataType: 'JSON',

            }).done(function(resp) {
          spinner.hide();

            });
            Swal.fire(
              'Deleted!',
              'This Record is now Deleted. You can Check Other Records to make neccessary Editing!',
              'success'
            )

            var myobj = document.getElementById("sid"+id);
             myobj.remove();

          }
        })

        }
        </script>
    @endsection
