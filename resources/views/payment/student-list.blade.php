{{-- resources/views/payment/student-list.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
.pay-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: 12px;
    padding: 28px 32px;
    margin-bottom: 24px;
}
.pay-hero h1 { font-size: 22px; font-weight: 700; color: white; margin: 0 0 6px; }
.pay-hero p { font-size: 13px; color: rgba(255,255,255,.75); margin: 0; }

.student-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    transition: all 0.2s ease;
    cursor: pointer;
}
.student-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,.1);
    border-color: #2563eb;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="pay-hero">
        <h1><i class="ri-wallet-line me-2"></i>{{ $pagetitle }}</h1>
        <p>Select a student to view payment details, make payments, or generate invoices.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="search-box">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by name or admission number...">
                <i class="ri-search-line search-icon"></i>
            </div>
        </div>
        <div class="col-md-3">
            <select id="classFilter" class="form-select">
                <option value="">All Classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->arm }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select id="savingsFilter" class="form-select">
                <option value="">All Students</option>
                <option value="has_scholarship">Has Scholarship</option>
                <option value="has_discount">Has Discount</option>
                <option value="no_savings">No Savings Applied</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary w-100" id="resetFilters">
                <i class="ri-refresh-line me-1"></i>Reset
            </button>
        </div>
    </div>

    <div class="row g-3" id="studentsGrid">
        @forelse($students as $student)
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="student-card" data-student-id="{{ $student->id }}"
                     data-name="{{ strtolower($student->firstname . ' ' . $student->lastname) }}"
                     data-admission="{{ $student->admissionNo }}"
                     data-class="{{ $student->schoolclassid }}">
                    <div class="d-flex align-items-start gap-3">
                        <img src="{{ $student->avatar ?? asset('assets/images/default-avatar.png') }}" alt="Student" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-bold">{{ $student->firstname }} {{ $student->lastname }}</h6>
                            <small class="text-muted d-block">{{ $student->admissionNo }}</small>
                            <small class="text-muted d-block">{{ $student->schoolclass }} {{ $student->arm }}</small>
                            <div class="mt-2">
                                @if($student->has_scholarship)
                                    <span class="badge bg-success"><i class="ri-graduation-cap-line me-1"></i>Scholarship</span>
                                @endif
                                @if($student->has_discount)
                                    <span class="badge bg-info"><i class="ri-discount-line me-1"></i>Discount</span>
                                @endif
                            </div>
                        </div>
                        <i class="ri-arrow-right-s-line text-muted"></i>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center py-5">
                    <i class="ri-inbox-line ri-2x d-block mb-2"></i>
                    No students found.
                </div>
            </div>
        @endforelse
    </div>

</div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.student-card');

    cards.forEach(card => {
        card.addEventListener('click', function() {
            const studentId = this.dataset.studentId;
            // You'll need to implement term/session selection
            Swal.fire({
                title: 'Select Term & Session',
                html: `
                    <select id="termSelect" class="form-select mb-3">
                        <option value="">Select Term</option>
                        @foreach($terms ?? [] as $term)
                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                        @endforeach
                    </select>
                    <select id="sessionSelect" class="form-select">
                        <option value="">Select Session</option>
                        @foreach($sessions ?? [] as $session)
                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                        @endforeach
                    </select>
                `,
                preConfirm: () => {
                    const termId = document.getElementById('termSelect').value;
                    const sessionId = document.getElementById('sessionSelect').value;
                    if (!termId || !sessionId) {
                        Swal.showValidationMessage('Please select both term and session');
                        return false;
                    }
                    return { termId, sessionId };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `/payment/student/${studentId}/class/${classId}/term/${result.value.termId}/session/${result.value.sessionId}`;
                }
            });
        });
    });
});
</script>
@endsection
