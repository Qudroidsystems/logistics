@extends('layouts.master')
@section('content')
<?php
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Spatie\Permission\Models\Role;
?>


<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Permission</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Permission Management</a></li>
                                <li class="breadcrumb-item active">Permission</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

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

            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div id="permissionList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">Permissions <span class="badge bg-dark-subtle text-dark ms-1"></span></h5>
                                </div>
                               
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0" id="userList">
                                        <thead class="table-active">
                                            <tr>
                                                <th><div class="form-check"><input class="form-check-input" type="checkbox" value="option" id="checkAll"><label class="form-check-label" for="checkAll"></label></div></th>
                                                <th class="sort cursor-pointer" data-sort="name">Name</th>
                                                <th class="sort cursor-pointer" data-sort="role">Role</th>
                                                <th class="sort cursor-pointer" data-sort="datereg">Date Registered</th>
                                         
                                            </tr>
                                        </thead>
                                        <tbody class="list form-check-all">
                                            @forelse ($data as $permission)
                                                    @php
                                                        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.permission_id",$permission->id)
                                                        ->leftJoin('roles', 'roles.id','=','role_has_permissions.role_id')
                                                        ->get(['roles.name as name','roles.badge as badge']);
                                                    @endphp

                                         
                                                <tr>
                                                    <td class="id" data-id="{{ $permission->id }}">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="chk_child">
                                                            <label class="form-check-label"></label>
                                                        </div>
                                                    </td>
                                                    {{-- <td class="id" style="display:none;"><a href="javascript:void(0);" class="fw-medium link-primary">{{ $user->id }}</a></td> --}}
                                                    <td class="name" data-name="{{ $permission->name }}">
                                                        <div class="d-flex align-items-center">
                                                            <div>
                                                                <h6 class="mb-0"><a href="" class="text-reset products">{{ $permission->name }}</a></h6>
                                                                <p>{{ $permission->title }}</p>
                                                            </div>
                                                            
                                                        </div>
                                                    </td>
                                                    <td class="role" data-roles="">
                                                        <div>
                                                            @foreach ($rolePermissions as $r)
                                                                  <a href="{{ route('roles.index',) }}" class="{{ $r->badge }} fs-7 m-1">{{ $r->name }}</a>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                    <td class="datereg">{{ $permission->created_at->format('Y-m-d') }}</td>
                                                    
                                                </tr>
                                                @empty
                                                      <tr>
                                                        <td colspan="7" class="noresult" style="display: block;">No results found</td>
                                                    </tr>
                                            
                                            @endforelse
                                          
                                        </tbody>
                                    </table>
                                </div>
                                {{-- <div class="row mt-3 align-items-center" id="pagination-element">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing <span class="fw-semibold">{{ $data->count() }}</span> of <span class="fw-semibold">{{ $data->total() }}</span> Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        <div class="pagination-wrap hstack gap-2 justify-content-center">
                                            <a class="page-item pagination-prev {{ $data->onFirstPage() ? 'disabled' : '' }}" href="{{ $data->previousPageUrl() }}">
                                                <i class="mdi mdi-chevron-left align-middle"></i>
                                            </a>
                                            <ul class="pagination listjs-pagination mb-0">
                                                @foreach ($data->links()->elements[0] as $page => $url)
                                                    <li class="page-item {{ $data->currentPage() == $page ? 'active' : '' }}">
                                                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <a class="page-item pagination-next {{ $data->hasMorePages() ? '' : 'disabled' }}" href="{{ $data->nextPageUrl() }}">
                                                <i class="mdi mdi-chevron-right align-middle"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div> --}}
                               
                               
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- End Page-content -->

    <!-- Scripts -->
    {{-- <script src="{{ asset('theme/layouts/assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('theme/layouts/assets/js/list.min.js') }}"></script>
    <script src="{{ asset('theme/layouts/assets/js/choices.min.js') }}" defer></script>
    <script src="{{ asset('theme/layouts/assets/js/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('js/user-list.init.js') }}"></script> --}}
</div>
@endsection