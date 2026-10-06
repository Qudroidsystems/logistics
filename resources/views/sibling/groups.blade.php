{{-- resources/views/sibling/groups.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
.group-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.2s;
}
.group-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,.1);
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-bold">Family Groups</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="search-box">
                <input type="text" class="form-control" id="searchInput" placeholder="Search families...">
                <i class="ri-search-line search-icon"></i>
            </div>
        </div>
        <div class="col-md-8 text-end">
            <a href="{{ route('sibling.create') }}" class="btn btn-primary">
                <i class="ri-add-line me-1"></i>Create Family Group
            </a>
        </div>
    </div>

    <div id="groupsContainer"></div>

</div>
</div>
</div>

<script>
function loadGroups() {
    $.ajax({
        url: '{{ route("sibling.index") }}',
        type: 'GET',
        data: { ajax: true },
        success: function(response) {
            if (response.success) {
                renderGroups(response.data);
            }
        }
    });
}

function renderGroups(groups) {
    let html = '<div class="row">';
    groups.forEach(group => {
        html += `
            <div class="col-md-6 col-lg-4">
                <div class="group-card">
                    <h5>${group.family_name}</h5>
                    <p>Group #: ${group.group_no}</p>
                    <p>Children: ${group.total_children}</p>
                    <a href="/sibling/${group.id}/edit" class="btn btn-sm btn-primary">Edit</a>
                    <button class="btn btn-sm btn-danger delete-group" data-id="${group.id}">Delete</button>
                </div>
            </div>
        `;
    });
    html += '</div>';
    $('#groupsContainer').html(html);
}

$(document).ready(function() {
    loadGroups();
});
</script>
@endsection
