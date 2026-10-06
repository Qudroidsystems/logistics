@extends('layouts.master')

@section('content')
<style>
.report-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: 12px;
    padding: 28px 32px;
    margin-bottom: 24px;
}
.report-hero h1 {
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    margin: 0;
}
.report-hero p {
    font-size: 13px;
    color: rgba(255,255,255,.75);
    margin: 5px 0 0;
}
.filter-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.filter-label {
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 8px;
    color: #1e3a5f;
}
.filter-label .required {
    color: #dc2626;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="report-hero">
        <h1><i class="ri-bar-chart-line me-2"></i>{{ $pagetitle }}</h1>
        <p>Analyze fee collection by class, term, and session</p>
    </div>

    <div class="filter-card">
        <form action="{{ route('reports.analysis.class-details') }}" method="GET">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="filter-label">Class <span class="required">*</span></label>
                    <select name="class_id" class="form-select" required>
                        <option value="">-- Select Class --</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->schoolarm }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="filter-label">Term <span class="required">*</span></label>
                    <select name="termid_id" class="form-select" required>
                        <option value="">-- Select Term --</option>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="filter-label">Session <span class="required">*</span></label>
                    <select name="session_id" class="form-select" required>
                        <option value="">-- Select Session --</option>
                        @foreach($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-search-line me-1"></i>View Analysis
                    </button>
                    <a href="{{ route('reports.analysis.school-wide') }}" class="btn btn-outline-primary ms-2">
                        <i class="ri-bar-chart-2-line me-1"></i>School Wide Analysis
                    </a>
                    <a href="{{ route('reports.analysis.scholarship-impact') }}" class="btn btn-outline-success ms-2">
                        <i class="ri-gift-line me-1"></i>Scholarship Impact
                    </a>
                </div>
            </div>
        </form>
    </div>

</div>
</div>
</div>
@endsection
