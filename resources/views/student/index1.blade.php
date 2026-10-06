@extends('layouts.master')
@section('content')
<?php
use Spatie\Permission\Models\Role;
?>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Student Management</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">Students</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            <style>
                /* ====== MODERN CARD UI STYLES ====== */
                .dashboard-stats-card {
                    border: none;
                    border-radius: 16px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                    transition: all 0.3s ease;
                    margin-bottom: 24px;
                    position: relative;
                    overflow: hidden;
                }

                .dashboard-stats-card:hover {
                    transform: translateY(-8px);
                    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
                }

                .dashboard-stats-card::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 4px;
                    background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
                }

                .dashboard-stats-card .card-body {
                    padding: 24px;
                    position: relative;
                    z-index: 1;
                }

                .dashboard-stats-card .stats-icon {
                    width: 64px;
                    height: 64px;
                    border-radius: 16px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 20px;
                    font-size: 28px;
                    background: rgba(255, 255, 255, 0.2);
                    backdrop-filter: blur(10px);
                    color: white;
                }

                .dashboard-stats-card .stats-content {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }

                .dashboard-stats-card .stats-label {
                    font-size: 14px;
                    font-weight: 500;
                    color: rgba(255, 255, 255, 0.9);
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .dashboard-stats-card .stats-value {
                    font-size: 32px;
                    font-weight: 700;
                    color: white;
                    line-height: 1;
                }

                .dashboard-stats-card .stats-change {
                    font-size: 12px;
                    font-weight: 500;
                    display: flex;
                    align-items: center;
                    gap: 4px;
                    color: rgba(255, 255, 255, 0.8);
                }

                .dashboard-stats-card .stats-change.positive {
                    color: #10b981;
                }

                .dashboard-stats-card .stats-change.negative {
                    color: #ef4444;
                }

                /* Card color themes */
                .stats-primary {
                    --gradient-start: #4361ee;
                    --gradient-end: #3a0ca3;
                    background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
                }

                .stats-success {
                    --gradient-start: #10b981;
                    --gradient-end: #047857;
                    background: linear-gradient(135deg, #10b981 0%, #047857 100%);
                }

                .stats-warning {
                    --gradient-start: #f59e0b;
                    --gradient-end: #b45309;
                    background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);
                }

                .stats-info {
                    --gradient-start: #0ea5e9;
                    --gradient-end: #0369a1;
                    background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%);
                }

                .stats-purple {
                    --gradient-start: #8b5cf6;
                    --gradient-end: #7c3aed;
                    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
                }

                .stats-pink {
                    --gradient-start: #ec4899;
                    --gradient-end: #be185d;
                    background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
                }

                .stats-teal {
                    --gradient-start: #14b8a6;
                    --gradient-end: #0d9488;
                    background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
                }

                /* ====== PROFESSIONAL STUDENT CARD STYLES ====== */
                .student-profile-card {
                    border: 1px solid #e5e7eb;
                    border-radius: 16px;
                    overflow: hidden;
                    transition: all 0.3s ease;
                    background: white;
                    height: 100%;
                    position: relative;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
                }

                .student-profile-card:hover {
                    border-color: #3b82f6;
                    box-shadow: 0 8px 30px rgba(59, 130, 246, 0.15);
                    transform: translateY(-4px);
                }

                .student-profile-card.selected {
                    border-color: #3b82f6;
                    background-color: #f0f9ff;
                }

                .student-profile-card .card-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    padding: 20px;
                    position: relative;
                    min-height: 120px;
                }

                .student-profile-card .avatar-container {
                    position: absolute;
                    top: 20px;
                    right: 20px;
                    width: 80px;
                    height: 80px;
                    border-radius: 16px;
                    overflow: hidden;
                    border: 4px solid white;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    background: white;
                }

                .student-profile-card .avatar {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                .student-profile-card .avatar-initials {
                    width: 100%;
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 28px;
                    font-weight: 700;
                    color: #667eea;
                    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
                }

                .student-profile-card .header-content {
                    padding-right: 100px;
                }

                .student-profile-card .student-name {
                    font-size: 20px;
                    font-weight: 700;
                    color: white;
                    margin-bottom: 4px;
                    line-height: 1.2;
                }

                .student-profile-card .student-admission {
                    font-size: 13px;
                    color: rgba(255, 255, 255, 0.9);
                    background: rgba(255, 255, 255, 0.1);
                    padding: 4px 12px;
                    border-radius: 20px;
                    display: inline-block;
                    backdrop-filter: blur(10px);
                }

                .student-profile-card .card-body {
                    padding: 20px;
                }

                .student-profile-card .student-info-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 12px;
                    margin-bottom: 20px;
                }

                .student-profile-card .info-item {
                    display: flex;
                    flex-direction: column;
                    gap: 4px;
                }

                .student-profile-card .info-label {
                    font-size: 11px;
                    font-weight: 600;
                    color: #6b7280;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .student-profile-card .info-value {
                    font-size: 14px;
                    font-weight: 600;
                    color: #374151;
                }

                .student-profile-card .status-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 6px 12px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 600;
                    margin-bottom: 16px;
                }

                .student-profile-card .status-active {
                    background-color: #d1fae5;
                    color: #065f46;
                    border: 1px solid #a7f3d0;
                }

                .student-profile-card .status-inactive {
                    background-color: #fee2e2;
                    color: #991b1b;
                    border: 1px solid #fecaca;
                }

                .student-profile-card .status-new {
                    background-color: #dbeafe;
                    color: #1e40af;
                    border: 1px solid #bfdbfe;
                }

                .student-profile-card .status-old {
                    background-color: #fef3c7;
                    color: #92400e;
                    border: 1px solid #fde68a;
                }

                .student-profile-card .action-buttons {
                    display: flex;
                    gap: 8px;
                    padding-top: 16px;
                    border-top: 1px solid #e5e7eb;
                }

                .student-profile-card .action-btn {
                    flex: 1;
                    padding: 10px;
                    border-radius: 12px;
                    border: none;
                    font-size: 13px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 6px;
                }

                .student-profile-card .view-btn {
                    background-color: #3b82f6;
                    color: white;
                }

                .student-profile-card .view-btn:hover {
                    background-color: #2563eb;
                    transform: translateY(-2px);
                }

                .student-profile-card .edit-btn {
                    background-color: #f3f4f6;
                    color: #374151;
                    border: 1px solid #e5e7eb;
                }

                .student-profile-card .edit-btn:hover {
                    background-color: #e5e7eb;
                    transform: translateY(-2px);
                }

                .student-profile-card .delete-btn {
                    background-color: #fef2f2;
                    color: #dc2626;
                    border: 1px solid #fee2e2;
                }

                .student-profile-card .delete-btn:hover {
                    background-color: #fee2e2;
                    transform: translateY(-2px);
                }

                .student-profile-card .checkbox-container {
                    position: absolute;
                    top: 16px;
                    left: 16px;
                    z-index: 2;
                }

                .student-profile-card .form-check-input {
                    width: 20px;
                    height: 20px;
                    cursor: pointer;
                    border: 2px solid white;
                    background-color: rgba(255, 255, 255, 0.2);
                    backdrop-filter: blur(10px);
                }

                .student-profile-card .form-check-input:checked {
                    background-color: #3b82f6;
                    border-color: #3b82f6;
                }

                /* ====== BULK STATUS MODAL STYLES ====== */
                .student-term-card {
                    transition: all 0.3s ease;
                    border: 1px solid #e9ecef;
                    overflow: hidden;
                }

                .student-term-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
                    border-color: #4361ee;
                }

                .student-term-card .card-body {
                    position: relative;
                    padding: 1.5rem 1rem 1rem;
                }

                .avatar-xl {
                    width: 80px;
                    height: 80px;
                }

                .avatar-title {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 100%;
                    height: 100%;
                }

                .bg-soft-primary {
                    background-color: rgba(67, 97, 238, 0.1);
                    color: #4361ee;
                }

                .bg-soft-success {
                    background-color: rgba(40, 167, 69, 0.1);
                    color: #28a745;
                }

                .bg-soft-warning {
                    background-color: rgba(255, 193, 7, 0.1);
                    color: #ffc107;
                }

                .bg-soft-danger {
                    background-color: rgba(220, 53, 69, 0.1);
                    color: #dc3545;
                }

                /* Toggle buttons styling */
                .toggle-activity, .toggle-type {
                    padding: 0.2rem 0.4rem;
                    font-size: 0.7rem;
                }

                .toggle-activity:hover, .toggle-type:hover {
                    transform: scale(1.1);
                }

                /* Checkbox styling for cards */
                .term-student-checkbox {
                    width: 1.2rem;
                    height: 1.2rem;
                    cursor: pointer;
                }

                /* Bulk action toolbar */
                .bulk-action-toolbar {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 1rem;
                    border-radius: 12px;
                    margin-bottom: 1rem;
                }

                /* Animation for updates */
                @keyframes pulse-green {
                    0% { background-color: rgba(40, 167, 69, 0); }
                    50% { background-color: rgba(40, 167, 69, 0.2); }
                    100% { background-color: rgba(40, 167, 69, 0); }
                }

                .status-updated {
                    animation: pulse-green 1s ease;
                }

                /* ====== ENHANCED TABLE ACTION BUTTONS ====== */
                .btn-soft-info {
                    color: #0dcaf0;
                    background-color: rgba(13, 202, 240, 0.1);
                    border-color: transparent;
                    transition: all 0.2s ease;
                }

                .btn-soft-info:hover {
                    color: #fff;
                    background-color: #0dcaf0;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(13, 202, 240, 0.2);
                }

                .btn-soft-warning {
                    color: #ffc107;
                    background-color: rgba(255, 193, 7, 0.1);
                    border-color: transparent;
                    transition: all 0.2s ease;
                }

                .btn-soft-warning:hover {
                    color: #fff;
                    background-color: #ffc107;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.2);
                }

                .btn-soft-danger {
                    color: #dc3545;
                    background-color: rgba(220, 53, 69, 0.1);
                    border-color: transparent;
                    transition: all 0.2s ease;
                }

                .btn-soft-danger:hover {
                    color: #fff;
                    background-color: #dc3545;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
                }

                .btn-soft-secondary {
                    color: #6c757d;
                    background-color: rgba(108, 117, 125, 0.1);
                    border-color: transparent;
                    transition: all 0.2s ease;
                }

                .btn-soft-secondary:hover {
                    color: #fff;
                    background-color: #6c757d;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.2);
                }

                .btn-soft-success {
                    color: #198754;
                    background-color: rgba(25, 135, 84, 0.1);
                    border-color: transparent;
                    transition: all 0.2s ease;
                }

                .btn-soft-success:hover {
                    color: #fff;
                    background-color: #198754;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(25, 135, 84, 0.2);
                }

                .btn-soft-primary {
                    color: #0d6efd;
                    background-color: rgba(13, 110, 253, 0.1);
                    border-color: transparent;
                    transition: all 0.2s ease;
                }

                .btn-soft-primary:hover {
                    color: #fff;
                    background-color: #0d6efd;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
                }

                /* Button groups */
                .btn-group .btn {
                    padding: 0.4rem 0.8rem;
                    font-size: 0.875rem;
                }

                .btn-group .btn:first-child {
                    border-top-left-radius: 8px;
                    border-bottom-left-radius: 8px;
                }

                .btn-group .btn:last-child {
                    border-top-right-radius: 8px;
                    border-bottom-right-radius: 8px;
                }

                /* Dropdown menu styling */
                .dropdown-menu {
                    border: none;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
                    border-radius: 12px;
                    padding: 8px;
                    animation: fadeInDown 0.2s ease;
                }

                .dropdown-item {
                    border-radius: 8px;
                    padding: 8px 16px;
                    font-size: 0.875rem;
                    transition: all 0.2s ease;
                }

                .dropdown-item:hover {
                    background-color: #f8f9fa;
                    transform: translateX(4px);
                }

                .dropdown-item i {
                    width: 20px;
                    text-align: center;
                }

                .dropdown-divider {
                    margin: 8px 0;
                    opacity: 0.1;
                }

                /* Table row enhancements */
                .data-table tbody tr {
                    transition: all 0.25s ease;
                }

                .data-table tbody tr:hover {
                    background-color: #f8f9fa;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
                }

                .data-table tbody td {
                    padding: 16px 12px;
                    vertical-align: middle;
                }

                /* Badge enhancements */
                .badge {
                    font-weight: 500;
                    letter-spacing: 0.3px;
                }

                .badge.bg-gradient {
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                }

                /* Animations */
                @keyframes fadeInDown {
                    from {
                        opacity: 0;
                        transform: translateY(-10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                @keyframes pulse {
                    0% {
                        box-shadow: 0 0 0 0 rgba(67, 97, 238, 0.4);
                    }
                    70% {
                        box-shadow: 0 0 0 6px rgba(67, 97, 238, 0);
                    }
                    100% {
                        box-shadow: 0 0 0 0 rgba(67, 97, 238, 0);
                    }
                }

                /* Active status indicator */
                .position-absolute.bg-success,
                .position-absolute.bg-secondary {
                    animation: pulse 2s infinite;
                    box-shadow: 0 0 0 rgba(67, 97, 238, 0.4);
                    border: 2px solid white;
                }

                /* Responsive adjustments */
                @media (max-width: 768px) {
                    .btn-group .btn {
                        padding: 0.3rem 0.6rem;
                    }

                    .data-table tbody td {
                        padding: 12px 8px;
                    }
                }

                /* Color utilities */
                .text-pink {
                    color: #f72585;
                }

                .bg-pink {
                    background-color: #f72585;
                }

                /* Avatar enhancements */
                .avatar-circle {
                    position: relative;
                    display: inline-block;
                }

                .avatar-initials {
                    font-family: 'Inter', sans-serif;
                    font-weight: 600;
                    text-transform: uppercase;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    transition: all 0.3s ease;
                }

                tr:hover .avatar-initials {
                    transform: scale(1.05);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                }

                /* Quick action buttons */
                .quick-actions {
                    display: flex;
                    gap: 4px;
                }

                /* Status indicators */
                .status-dot {
                    display: inline-block;
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    margin-right: 6px;
                }

                .status-dot.active {
                    background-color: #10b981;
                    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
                }

                .status-dot.inactive {
                    background-color: #6c757d;
                    box-shadow: 0 0 0 2px rgba(108, 117, 125, 0.2);
                }

                /* Table action container */
                .table-actions-container {
                    display: flex;
                    gap: 8px;
                    justify-content: flex-end;
                }

                /* Student info in table */
                .student-info-wrapper {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }

                .student-details {
                    display: flex;
                    flex-direction: column;
                }

                .student-name {
                    font-weight: 600;
                    color: #1e293b;
                    margin-bottom: 4px;
                }

                .student-meta {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    flex-wrap: wrap;
                }

                .admission-badge {
                    background-color: #f1f5f9;
                    color: #475569;
                    padding: 2px 8px;
                    border-radius: 20px;
                    font-size: 11px;
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                }

                /* Compact status badges */
                .compact-badge {
                    padding: 4px 8px;
                    border-radius: 20px;
                    font-size: 11px;
                    font-weight: 500;
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                }

                /* ====== TABLE STYLES ====== */
                .data-table-container {
                    background: white;
                    border-radius: 16px;
                    overflow: hidden;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
                }

                .data-table {
                    margin-bottom: 0;
                }

                .data-table thead {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                }

                .data-table thead th {
                    border: none;
                    color: white;
                    font-weight: 600;
                    font-size: 13px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    padding: 16px 12px;
                }

                .data-table tbody tr {
                    transition: all 0.2s ease;
                    border-bottom: 1px solid #e5e7eb;
                }

                .data-table tbody tr:hover {
                    background-color: #f9fafb;
                }

                .data-table tbody tr.selected {
                    background-color: #f0f9ff;
                }

                /* ====== ACTION BUTTONS ====== */
                .btn-group-toggle .btn {
                    border-radius: 12px;
                    padding: 10px 20px;
                    font-weight: 600;
                    transition: all 0.3s ease;
                }

                .btn-group-toggle .btn.active {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border-color: #667eea;
                    color: white;
                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
                }

                .btn-primary-gradient {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border: none;
                    color: white;
                    padding: 12px 24px;
                    border-radius: 12px;
                    font-weight: 600;
                    transition: all 0.3s ease;
                }

                .btn-primary-gradient:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
                }

                /* ====== FILTER BAR ====== */
                .filter-bar {
                    background: white;
                    padding: 20px;
                    border-radius: 16px;
                    margin-bottom: 24px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
                }

                .search-box {
                    position: relative;
                }

                .search-box input {
                    padding-left: 44px;
                    padding-right: 40px;
                    border-radius: 12px;
                    border: 1px solid #e5e7eb;
                    height: 48px;
                    font-size: 14px;
                    transition: all 0.3s ease;
                }

                .search-box input:focus {
                    border-color: #667eea;
                    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
                }

                .search-box .search-icon {
                    position: absolute;
                    left: 16px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #9ca3af;
                    font-size: 18px;
                }

                .search-box .clear-search {
                    position: absolute;
                    right: 8px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: transparent;
                    border: none;
                    color: #6b7280;
                    font-size: 16px;
                    padding: 4px 8px;
                    cursor: pointer;
                    display: none;
                    z-index: 10;
                }

                .search-box .clear-search:hover {
                    color: #dc2626;
                }

                /* ====== PAGINATION ====== */
                .pagination-container {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px;
                    background: white;
                    border-top: 1px solid #e5e7eb;
                }

                .pagination .page-link {
                    border: none;
                    color: #374151;
                    margin: 0 4px;
                    border-radius: 10px;
                    width: 40px;
                    height: 40px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 600;
                    transition: all 0.3s ease;
                }

                .pagination .page-link:hover {
                    background-color: #f3f4f6;
                    color: #667eea;
                }

                .pagination .page-item.active .page-link {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                }

                /* ====== EMPTY STATE ====== */
                .empty-state {
                    padding: 60px 20px;
                    text-align: center;
                }

                .empty-state-icon {
                    font-size: 64px;
                    color: #d1d5db;
                    margin-bottom: 20px;
                }

                .empty-state-title {
                    font-size: 20px;
                    font-weight: 600;
                    color: #374151;
                    margin-bottom: 8px;
                }

                .empty-state-description {
                    color: #6b7280;
                    font-size: 14px;
                    max-width: 400px;
                    margin: 0 auto 24px;
                }

                /* ====== LOADING STATE ====== */
                .loading-state {
                    padding: 60px 20px;
                    text-align: center;
                }

                .spinner-container {
                    display: inline-block;
                    position: relative;
                    width: 80px;
                    height: 80px;
                }

                .spinner-ring {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    border: 4px solid #f3f4f6;
                    border-top-color: #667eea;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                }

                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }

                /* ====== MODAL STYLES ====== */
                .modal-xl .modal-content {
                    border-radius: 20px;
                    overflow: hidden;
                    border: none;
                }

                .modal-header-gradient {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 24px 32px;
                    border: none;
                }

                .modal-header-gradient .modal-title {
                    font-size: 20px;
                    font-weight: 700;
                }

                .modal-header-gradient .btn-close {
                    filter: brightness(0) invert(1);
                    opacity: 0.8;
                }

                .modal-header-gradient .btn-close:hover {
                    opacity: 1;
                }

                /* ====== PROGRESS STEPS ====== */
                .progress-steps {
                    display: flex;
                    justify-content: space-between;
                    position: relative;
                    margin-bottom: 30px;
                    counter-reset: step;
                }

                .progress-steps::before {
                    content: '';
                    position: absolute;
                    top: 50%;
                    left: 0;
                    right: 0;
                    height: 2px;
                    background: #e9ecef;
                    transform: translateY(-50%);
                    z-index: 1;
                }

                .progress-steps .step {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    background: #e9ecef;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    color: #6b7280;
                    position: relative;
                    z-index: 2;
                    border: 2px solid #e9ecef;
                }

                .progress-steps .step.active {
                    background: #405189;
                    color: white;
                    border-color: #405189;
                }

                /* ====== FORM SECTIONS ====== */
                .form-section {
                    padding: 20px 30px;
                    border-bottom: 1px solid #e5e7eb;
                }

                .section-header {
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #f0f0f0;
                }

                .section-header h5 {
                    color: #495057;
                    font-weight: 600;
                }

                .form-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                    gap: 20px;
                }

                .name-container {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                    gap: 15px;
                }

                .full-width {
                    grid-column: 1 / -1;
                }

                /* ====== DRAG AND DROP STYLES ====== */
                .cursor-move {
                    cursor: move !important;
                }

                .drag-handle {
                    cursor: grab !important;
                    color: #6c757d;
                    padding: 8px;
                    margin: -8px 8px -8px -8px;
                    border-radius: 4px;
                    transition: all 0.2s ease;
                    display: inline-flex;
                    align-items: center;
                }

                .drag-handle:hover {
                    color: #4361ee;
                    background: rgba(67, 97, 238, 0.1);
                }

                .drag-handle:active {
                    cursor: grabbing !important;
                }

                .draggable-item {
                    user-select: none;
                    transition: all 0.3s ease;
                    position: relative;
                    cursor: default !important;
                    background: white;
                    border: 1px solid #e9ecef;
                }

                .draggable-item.dragging {
                    opacity: 0.5;
                    transform: rotate(2deg);
                    background-color: #f8f9fa !important;
                }

                .draggable-item.drag-over {
                    background-color: #e9ecef !important;
                    border-color: #405189 !important;
                }

                /* Sortable.js specific classes */
                .sortable-ghost {
                    opacity: 0.4;
                    background-color: #e9ecef !important;
                    transform: rotate(2deg);
                    border: 2px dashed #4361ee !important;
                }

                .sortable-chosen {
                    background-color: #ffffff !important;
                    color: white !important;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                    transform: scale(1.02);
                    z-index: 1000;
                    border: 2px solid #4361ee !important;
                }

                .sortable-chosen .form-check-label {
                    color: #4361ee !important;
                }

                .sortable-chosen .drag-handle {
                    color: #4361ee !important;
                }

                .sortable-drag {
                    opacity: 0.8;
                    transform: rotate(2deg);
                    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
                }

                /* Order badges */
                .order-badge {
                    font-size: 10px;
                    padding: 2px 6px;
                    border-radius: 10px;
                    float: right;
                }

                /* Dragging state */
                body.sortable-dragging {
                    cursor: grabbing !important;
                }

                body.sortable-dragging .drag-handle {
                    cursor: grabbing !important;
                }

                /* Preview styling */
                #columnOrderPreview {
                    min-height: 38px;
                    padding: 8px;
                    background: #f8f9fa;
                    border-radius: 8px;
                    border: 1px solid #e9ecef;
                }

                #columnOrderPreview .badge {
                    font-size: 11px;
                    padding: 4px 8px;
                }


                /* ====================================================
   FIXES: MODAL SCROLLING / BUTTON VISIBILITY / ACTIONS
   ==================================================== */

#addStudentModal .modal-dialog,
#editStudentModal .modal-dialog,
#viewStudentModal .modal-dialog {
    max-height: calc(100vh - 1rem);
}

#addStudentModal .modal-content,
#editStudentModal .modal-content,
#viewStudentModal .modal-content {
    max-height: calc(100vh - 1rem);
}

#addStudentModal .modal-body,
#editStudentModal .modal-body,
#viewStudentModal .modal-body {
    overflow-y: auto;
    max-height: calc(100vh - 160px);
}

/* Make sure gradient/app buttons always show text */
.btn-pg,
.btn-pg:hover,
.btn-pg:focus,
.btn-pg:active {
    color: #fff !important;
    text-decoration: none;
}

.stu-card-view,
.stu-card-view:hover,
.stu-card-view:focus,
.stu-card-view:active {
    color: #fff !important;
}

.stu-card-edit,
.stu-card-edit:hover,
.stu-card-edit:focus,
.stu-card-edit:active {
    color: #374151 !important;
}

.stu-card-delete,
.stu-card-delete:hover,
.stu-card-delete:focus,
.stu-card-delete:active {
    color: var(--sm-danger) !important;
}

/* Bootstrap info/warning buttons can become low contrast in some themes */
.btn-info,
.btn-info:hover,
.btn-info:focus,
.btn-info:active {
    color: #fff !important;
}

.btn-warning,
.btn-warning:hover,
.btn-warning:focus,
.btn-warning:active {
    color: #111827 !important;
}

/* Dynamic modal dropdown/actions */
#bulkStatusUpdateModal .dropdown-item,
#termStudentsModal .dropdown-item {
    color: #111827 !important;
}

#bulkStatusUpdateModal .btn-outline-success:hover,
#bulkStatusUpdateModal .btn-outline-warning:hover,
#bulkStatusUpdateModal .btn-outline-primary:hover,
#termStudentsModal .btn-outline-danger:hover {
    color: #fff !important;
}

/* Disabled pagination buttons */
.page-link.disabled,
.page-item.disabled .page-link {
    pointer-events: none;
    opacity: .45;
}

/* Keep generated card buttons readable */
.stu-card-actions button i,
.act-btn i {
    pointer-events: none;
}

            </style>

            <!-- Dashboard Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="dashboard-stats-card stats-primary">
                        <div class="card-body">
                            <div class="stats-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stats-content">
                                <span class="stats-label">Total Students</span>
                                <span class="stats-value">{{ $total_population }}</span>
                                <span class="stats-change positive">
                                    <i class="fas fa-arrow-up"></i>
                                    12% from last term
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-stats-card stats-success">
                        <div class="card-body">
                            <div class="stats-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stats-content">
                                <span class="stats-label">Active Students</span>
                                <span class="stats-value">{{ $student_status_counts['Active'] ?? 0 }}</span>
                                <span class="stats-change positive">
                                    <i class="fas fa-arrow-up"></i>
                                    8% from last term
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-stats-card stats-warning">
                        <div class="card-body">
                            <div class="stats-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="stats-content">
                                <span class="stats-label">New Admissions</span>
                                <span class="stats-value">{{ $status_counts['New Student'] ?? 0 }}</span>
                                <span class="stats-change positive">
                                    <i class="fas fa-arrow-up"></i>
                                    15% from last term
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-stats-card stats-purple">
                        <div class="card-body">
                            <div class="stats-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="stats-content">
                                <span class="stats-label">Staff Count</span>
                                <span class="stats-value">{{ $staff_count }}</span>
                                <span class="stats-change positive">
                                    <i class="fas fa-arrow-up"></i>
                                    5% from last term
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gender and Religion Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="dashboard-stats-card stats-info">
                        <div class="card-body">
                            <div class="stats-icon">
                                <i class="fas fa-mars"></i>
                            </div>
                            <div class="stats-content">
                                <span class="stats-label">Male Students</span>
                                <span class="stats-value">{{ $gender_counts['Male'] ?? 0 }}</span>
                                <span class="stats-change">
                                    {{ $total_population > 0 ? number_format(($gender_counts['Male'] / $total_population) * 100, 1) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-stats-card stats-pink">
                        <div class="card-body">
                            <div class="stats-icon">
                                <i class="fas fa-venus"></i>
                            </div>
                            <div class="stats-content">
                                <span class="stats-label">Female Students</span>
                                <span class="stats-value">{{ $gender_counts['Female'] ?? 0 }}</span>
                                <span class="stats-change">
                                    {{ $total_population > 0 ? number_format(($gender_counts['Female'] / $total_population) * 100, 1) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-stats-card stats-teal">
                        <div class="card-body">
                            <div class="stats-icon">
                                <i class="fas fa-cross"></i>
                            </div>
                            <div class="stats-content">
                                <span class="stats-label">Christians</span>
                                <span class="stats-value">{{ $religion_counts['Christianity'] ?? 0 }}</span>
                                <span class="stats-change">
                                    {{ $total_population > 0 ? number_format(($religion_counts['Christianity'] / $total_population) * 100, 1) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-stats-card stats-warning">
                        <div class="card-body">
                            <div class="stats-icon">
                                <i class="fas fa-moon"></i>
                            </div>
                            <div class="stats-content">
                                <span class="stats-label">Muslims</span>
                                <span class="stats-value">{{ $religion_counts['Islam'] ?? 0 }}</span>
                                <span class="stats-change">
                                    {{ $total_population > 0 ? number_format(($religion_counts['Islam'] / $total_population) * 100, 1) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Validation Error!</strong> Please check the form for errors.
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Main Content Card -->
            <div class="data-table-container">
                <!-- Card Header -->
                <div class="card-header d-flex align-items-center justify-content-between py-3 px-4 border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="option" id="checkAll">
                            <label class="form-check-label" for="checkAll"></label>
                        </div>
                        <h5 class="mb-0 fw-bold">Student Records</h5>
                        <span class="badge bg-primary bg-gradient rounded-pill" id="totalStudents">0</span>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <!-- View Toggle -->
                        <div class="btn-group btn-group-toggle" role="group">
                            <button type="button" class="btn btn-outline-primary active" id="tableViewBtn">
                                <i class="fas fa-table me-2"></i>Table
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="cardViewBtn">
                                <i class="fas fa-th-large me-2"></i>Cards
                            </button>
                        </div>

                        <!-- Bulk Actions -->
                        @can('Delete student')
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" id="bulkActionsDropdown"
                                    data-bs-toggle="dropdown" aria-expanded="false" disabled>
                                <i class="fas fa-cog me-2"></i>Actions
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="bulkActionsDropdown">
                                <li>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);" id="deleteMultipleBtn">
                                        <i class="fas fa-trash me-2"></i>Delete Selected
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-primary" href="javascript:void(0);" id="updateCurrentTermBtn">
                                        <i class="fas fa-calendar-alt me-2"></i>Update Current Term
                                    </a>
                                </li>
                            </ul>
                        </div>
                        @endcan

                        <!-- Add Student Button -->
                        @can('Create student')
                        <button type="button" class="btn btn-primary-gradient" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="fas fa-user-plus me-2"></i>Add Student
                        </button>
                        @endcan

                        <!-- Export Button -->
                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#printStudentReportModal">
                            <i class="fas fa-file-export me-2"></i>Export
                        </button>
                    </div>
                </div>

                <!-- Filter Bar - WITH NEW ACTION BUTTONS -->
                <div class="filter-bar">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <div class="search-box">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" class="form-control" id="search-input"
                                       placeholder="Search name or admission...">
                                <button type="button" class="clear-search" id="clear-search" title="Clear search">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" id="schoolclass-filter">
                                <option value="all">All Classes</option>
                                @foreach ($schoolclasses as $class)
                                    <option value="{{ $class->id }}">{{ $class->schoolclass }} - {{ $class->arm }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" id="term-filter">
                                <option value="all">All Terms</option>
                                @foreach ($schoolterms as $term)
                                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" id="session-filter">
                                <option value="all">All Sessions</option>
                                @if(isset($schoolsessions) && count($schoolsessions) > 0)
                                    @foreach ($schoolsessions as $session)
                                        <option value="{{ $session->id }}">{{ $session->session ?? $session->name ?? 'Session ' . $session->id }}</option>
                                    @endforeach
                                @else
                                    <option value="" disabled>No sessions found</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-primary w-100" id="filterBtn">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-secondary w-100" id="resetFiltersBtn">
                                <i class="fas fa-redo-alt"></i>
                            </button>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-warning w-100" id="bulkStatusBtn" data-bs-toggle="tooltip" title="Update student status (Active/Inactive or Old/New)">
                                    <i class="fas fa-sync-alt me-2"></i>Status
                                </button>
                                <button type="button" class="btn btn-info w-100" id="manageTermBtn" data-bs-toggle="tooltip" title="Manage term registrations">
                                    <i class="fas fa-calendar-alt me-2"></i>Term
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table View -->
                <div id="tableView" class="view-container">
                    <div class="table-responsive">
                        <table class="table data-table" id="studentTable">
                            <thead>
                                <tr>
                                    <th width="50">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="option" id="checkAllTable">
                                        </div>
                                    </th>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                    <th>Gender</th>
                                    <th>Registered</th>
                                    <th width="250">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="studentTableBody">
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Card View -->
                <div id="cardView" class="view-container d-none p-4">
                    <div class="row" id="studentsCardsContainer">
                        <!-- Cards will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Empty/Loading States -->
                <div id="emptyState" class="empty-state d-none">
                    <div class="empty-state-icon">
                        <i class="fas fa-users-slash"></i>
                    </div>
                    <h5 class="empty-state-title">No Students Found</h5>
                    <p class="empty-state-description">
                        Try adjusting your search or filter to find what you're looking for.
                    </p>
                    <button class="btn btn-primary-gradient" id="resetFromEmptyBtn">
                        <i class="fas fa-redo me-2"></i>Reset Filters
                    </button>
                </div>

                <div id="loadingState" class="loading-state">
                    <div class="spinner-container">
                        <div class="spinner-ring"></div>
                    </div>
                    <p class="mt-3 text-muted">Loading students...</p>
                </div>

                <!-- Pagination -->
                <div class="pagination-container">
                    <div>
                        <span class="text-muted">
                            Showing <span class="fw-bold" id="showingCount">0</span> to
                            <span class="fw-bold" id="toCount">0</span> of
                            <span class="fw-bold" id="totalCount">0</span> students
                        </span>
                    </div>
                    <nav>
                        <ul class="pagination mb-0" id="pagination">
                            <li class="page-item" id="prevPageLi">
                                <a class="page-link" href="javascript:void(0);" id="prevPage">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <!-- Page numbers will be added here dynamically -->
                            <li class="page-item" id="nextPageLi">
                                <a class="page-link" href="javascript:void(0);" id="nextPage">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Update Current Term Modal -->
        <div id="updateCurrentTermModal" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header modal-header-gradient">
                        <h5 class="modal-title">
                            <i class="fas fa-calendar-alt me-2"></i>Register/Update Term
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <form id="updateCurrentTermForm">
                            @csrf
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Registering/updating term for <span id="selectedStudentsCount">0</span> selected student(s).
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Class</label>
                                <select class="form-control" name="schoolclassId" required>
                                    <option value="">Select Class</option>
                                    @foreach ($schoolclasses as $class)
                                        <option value="{{ $class->id }}">{{ $class->class_display }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Term</label>
                                <select class="form-control" name="termId" required>
                                    <option value="">Select Term</option>
                                    @foreach ($schoolterms as $term)
                                        <option value="{{ $term->id }}">{{ $term->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Session</label>
                                <select class="form-control" name="sessionId" required>
                                    <option value="">Select Session</option>
                                    @foreach ($schoolsessions as $session)
                                        <option value="{{ $session->id }}">{{ $session->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_current" id="is_current" value="1" checked>
                                    <label class="form-check-label" for="is_current">
                                        Mark as current term for student(s)
                                    </label>
                                </div>
                                <small class="text-muted">If checked, this will be marked as the current term. Previous current term will be unmarked.</small>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Note:</strong> Students can have multiple terms registered in the same session.
                                If a term already exists for a student in this session, it will be updated.
                                Otherwise, a new term registration will be created.
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary-gradient" id="confirmUpdateCurrentTerm">
                            <i class="fas fa-save me-2"></i>Register/Update Term
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print/Export Report Modal -->
        <div id="printStudentReportModal" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header modal-header-gradient">
                        <h5 class="modal-title">
                            <i class="fas fa-file-export me-2"></i>Generate Report
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <form id="printReportForm">
                            <!-- Filters Section -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Class</label>
                                    <select class="form-select" name="class_id">
                                        <option value="">— All Classes —</option>
                                        @foreach ($schoolclasses as $class)
                                            <option value="{{ $class->id }}">{{ $class->class_display }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="">— All —</option>
                                        <option value="1">Old Students</option>
                                        <option value="2">New Students</option>
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Term and Session Filters -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Term</label>
                                    <select class="form-select" name="term_id">
                                        <option value="">— All Terms —</option>
                                        @foreach ($schoolterms as $term)
                                            <option value="{{ $term->id }}">{{ $term->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Session</label>
                                    <select class="form-select" name="session_id">
                                        <option value="">— All Sessions —</option>
                                        @foreach ($schoolsessions as $session)
                                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Column Selection with Drag & Drop -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="ri-draggable me-1"></i> Select & Arrange Columns (Drag to reorder)
                                </label>
                                <div class="row g-3" id="columnsContainer">
                                    <input type="hidden" name="columns_order" id="columnsOrderInput" value="">
                                    @php
                                        $availableColumns = [
                                            'photo'          => 'Photo',
                                            'admissionNo'    => 'Admission No',
                                            'lastname'       => 'Last Name',
                                            'firstname'      => 'First Name',
                                            'othername'      => 'Other Name',
                                            'gender'         => 'Gender',
                                            'dateofbirth'    => 'Date of Birth',
                                            'age'            => 'Age',
                                            'class'          => 'Class / Arm',
                                            'status'         => 'Student Status',
                                            'admission_date' => 'Admission Date',
                                            'phone_number'   => 'Phone Number',
                                            'state'          => 'State of Origin',
                                            'local'          => 'LGA',
                                            'religion'       => 'Religion',
                                            'blood_group'    => 'Blood Group',
                                            'father_name'    => "Father's Name",
                                            'mother_name'    => "Mother's Name",
                                            'guardian_phone' => 'Guardian Phone',
                                            'term'           => 'Term',
                                            'session'        => 'Session',
                                        ];
                                    @endphp
                                    @foreach ($availableColumns as $key => $label)
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check border rounded p-2 mb-2 bg-light draggable-item" data-column="{{ $key }}">
                                                <div class="d-flex align-items-center">
                                                    <span class="drag-handle me-2">
                                                        <i class="ri-draggable"></i>
                                                    </span>
                                                    <input class="form-check-input column-checkbox" type="checkbox" name="columns[]" value="{{ $key }}" id="col_{{ $key }}"
                                                        {{ in_array($key, ['admissionNo','lastname','firstname','class','gender']) ? 'checked' : '' }}>
                                                    <label class="form-check-label w-100" for="col_{{ $key }}">
                                                        {{ $label }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted">Drag columns by the handle <i class="ri-draggable"></i> to arrange their order in the report</small>
                            </div>

                            <!-- Report Header Options -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="ri-file-info-line me-2"></i> Report Header Options</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" role="switch" name="include_header" id="includeHeader" checked>
                                                <label class="form-check-label" for="includeHeader">
                                                    <i class="ri-building-line me-1"></i> Include School Header
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" role="switch" name="include_logo" id="includeLogo" checked>
                                                <label class="form-check-label" for="includeLogo">
                                                    <i class="ri-image-line me-1"></i> Include School Logo
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="orientation" class="form-label">Page Orientation</label>
                                                <select class="form-select" name="orientation" id="orientation">
                                                    <option value="portrait">Portrait</option>
                                                    <option value="landscape">Landscape</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Export Format -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Export Format</label>
                                <div class="d-flex gap-3 flex-wrap">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="format" id="format_pdf" value="pdf" checked>
                                        <label class="form-check-label" for="format_pdf">
                                            <i class="ri-file-pdf-2-line text-danger me-1"></i> PDF
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="format" id="format_excel" value="excel">
                                        <label class="form-check-label" for="format_excel">
                                            <i class="ri-file-excel-2-line text-success me-1"></i> Excel
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview -->
                            <div class="alert alert-info small mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="ri-information-fill me-2"></i>
                                    <div>
                                        <strong>Preview:</strong>
                                        <span id="columnOrderPreview">admissionNo, lastname, firstname, class, gender</span>
                                        <br>
                                        <small>Only students matching the selected filters will be included.</small>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success" id="generateReportBtn">
                            <i class="ri-printer-line me-1"></i> Generate & Download
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Student Modal -->
        <div id="addStudentModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header modal-header-gradient">
                        <h5 class="modal-title">
                            <i class="fas fa-user-plus me-2"></i>
                            Student Registration
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form class="tablelist-form" id="addStudentForm" enctype="multipart/form-data" autocomplete="off" method="POST" action="{{ route('student.store') }}">
                        @csrf
                        <div class="modal-body p-4">
                            <!-- Progress Steps -->
                            <div class="progress-steps mb-4">
                                <div class="step active">1</div>
                                <div class="step">2</div>
                                <div class="step">3</div>
                                <div class="step">4</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Section A: Academic Details -->
                                    <div class="card">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Academic Details</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Admission Number Mode <span class="text-danger">*</span></label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="admissionMode" id="admissionAuto" value="auto" required onchange="toggleAdmissionInput()">
                                                        <label class="form-check-label" for="admissionAuto">
                                                            <i class="fas fa-magic me-1"></i>Auto Generate
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="admissionMode" id="admissionManual" value="manual" required onchange="toggleAdmissionInput()">
                                                        <label class="form-check-label" for="admissionManual">
                                                            <i class="fas fa-edit me-1"></i>Manual Entry
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="admissionNo" class="form-label">Admission Number <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <select class="form-control" id="admissionYear" name="admissionYear" required onchange="updateAdmissionNumber()">
                                                        @for ($year = date('Y'); $year >= date('Y') - 5; $year--)
                                                            <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                                                        @endfor
                                                    </select>
                                                    <input type="text" id="admissionNo" name="admissionNo" class="form-control" placeholder="TCC/YYYY/0001" required>
                                                    <small class="form-text text-muted w-100 mt-1">Format: TCC/YYYY/0001 (e.g., TCC/2024/0871)</small>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="admissionDate" class="form-label">Admission Date <span class="text-danger">*</span></label>
                                                <input type="date" id="admissionDate" name="admissionDate" class="form-control" required max="{{ date('Y-m-d') }}">
                                            </div>
                                            <div class="mb-3">
                                                <label for="schoolclassid" class="form-label">Class <span class="text-danger">*</span></label>
                                                <select id="schoolclassid" name="schoolclassid" class="form-control" required>
                                                    <option value="">Select Class</option>
                                                    @foreach ($schoolclasses as $class)
                                                        <option value="{{ $class->id }}">{{ $class->schoolclass }} - {{ $class->arm }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="termid" class="form-label">Term <span class="text-danger">*</span></label>
                                                        <select id="termid" name="termid" class="form-control" required>
                                                            <option value="">Select Term</option>
                                                            @foreach ($schoolterms as $term)
                                                                <option value="{{ $term->id }}">{{ $term->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="sessionid" class="form-label">Session <span class="text-danger">*</span></label>
                                                        <select id="sessionid" name="sessionid" class="form-control" required>
                                                            <option value="">Select Session</option>
                                                            @foreach ($schoolsessions as $session)
                                                                <option value="{{ $session->id }}">{{ $session->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Student Status <span class="text-danger">*</span></label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="statusId" id="statusOld" value="1" required>
                                                        <label class="form-check-label" for="statusOld">
                                                            <i class="fas fa-user-clock me-1"></i>Old Student
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="statusId" id="statusNew" value="2" required>
                                                        <label class="form-check-label" for="statusNew">
                                                            <i class="fas fa-user-plus me-1"></i>New Student
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Student Activity Status <span class="text-danger">*</span></label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="student_status" id="statusActive" value="Active" required>
                                                        <label class="form-check-label" for="statusActive">
                                                            <i class="fas fa-check-circle text-success me-1"></i>Active
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="student_status" id="statusInactive" value="Inactive" required>
                                                        <label class="form-check-label" for="statusInactive">
                                                            <i class="fas fa-pause-circle text-warning me-1"></i>Inactive
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="student_category" class="form-label">Student Category <span class="text-danger">*</span></label>
                                                <select id="student_category" name="student_category" class="form-control" required>
                                                    <option value="">Select Category</option>
                                                    <option value="Day">Day Student</option>
                                                    <option value="Boarding">Boarding Student</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Personal Details -->
                                <div class="col-md-6">
                                    <!-- Section B: Student's Personal Details -->
                                    <div class="card">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Personal Details</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3 text-center">
                                                <div class="upload-area border border-2 border-dashed border-primary rounded p-3">
                                                    <img id="addStudentAvatar" src="https://via.placeholder.com/120x120/667eea/ffffff?text=Photo" alt="Avatar Preview" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #667eea; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" />
                                                    <div>
                                                        <label for="avatar" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-camera me-1"></i>Choose Photo
                                                        </label>
                                                        <input type="file" id="avatar" name="avatar" class="d-none" accept=".png,.jpg,.jpeg" onchange="previewImage(this)">
                                                        <div class="form-text mt-2">Max 2MB (PNG, JPG, JPEG)</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="title" class="form-label">Title</label>
                                                    <select id="title" name="title" class="form-control">
                                                        <option value="">Select</option>
                                                        <option value="Master">Master</option>
                                                        <option value="Miss">Miss</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="lastname" class="form-label">Last Name <span class="text-danger">*</span></label>
                                                    <input type="text" id="lastname" name="lastname" class="form-control" placeholder="Last name" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="firstname" class="form-label">First Name <span class="text-danger">*</span></label>
                                                    <input type="text" id="firstname" name="firstname" class="form-control" placeholder="First name" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="othername" class="form-label">Other Names<span class="text-danger">*</span></label>
                                                <input type="text" id="othername" name="othername" class="form-control" placeholder="Middle name(s)">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="gender" id="genderMale" value="Male" required>
                                                        <label class="form-check-label" for="genderMale">
                                                            <i class="fas fa-male text-primary me-1"></i>Male
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="Female" required>
                                                        <label class="form-check-label" for="genderFemale">
                                                            <i class="fas fa-female text-danger me-1"></i>Female
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="dateofbirth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                                        <input type="date" id="addDOB" name="dateofbirth" class="form-control" required onchange="calculateAge(this.value, 'addAgeInput')">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="addAgeInput" class="form-label">Age <span class="text-danger">*</span></label>
                                                        <input type="number" id="addAgeInput" name="age" class="form-control" readonly required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="placeofbirth" class="form-label">Place of Birth <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-primary text-white">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                    </span>
                                                    <input type="text" id="placeofbirth" name="placeofbirth" class="form-control" placeholder="e.g., Lagos, Nigeria" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="phone_number" class="form-label">Phone Number</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-primary text-white">
                                                        <i class="fas fa-phone"></i>
                                                    </span>
                                                    <input type="text" id="phone_number" name="phone_number" class="form-control" placeholder="+234 xxx xxx xxxx">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-primary text-white">
                                                        <i class="fas fa-envelope"></i>
                                                    </span>
                                                    <input type="email" id="email" name="email" class="form-control" placeholder="student@example.com">
                                                </div>
                                            </div>
                                           <div class="mb-3">
                                                <label for="future_ambition" class="form-label">Future Ambition <span class="text-danger">*</span></label>
                                                <textarea id="future_ambition" name="future_ambition" class="form-control" rows="2" placeholder="Enter future ambition" required></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="permanent_address" class="form-label">Permanent Address <span class="text-danger">*</span></label>
                                                <textarea id="permanent_address" name="permanent_address" class="form-control" rows="2" placeholder="Enter permanent address" required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Additional Information, Parent/Guardian Details, and Previous School Details -->
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Section C: Additional Details -->
                                    <div class="card">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Additional Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-10">
                                                    <div class="mb-3">
                                                        <label for="nationality" class="form-label">Nationality <span class="text-danger">*</span></label>
                                                        <input type="text" id="nationality" name="nationality" class="form-control" placeholder="e.g., Nigerian" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="addState" class="form-label">State of Origin <span class="text-danger">*</span></label>
                                                        <select id="addState" name="state" class="form-control" required>
                                                            <option value="">Select State</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="addLocal" class="form-label">Local Government <span class="text-danger">*</span></label>
                                                        <select id="addLocal" name="local" class="form-control" required>
                                                            <option value="">Select LGA</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="city" class="form-label">City</label>
                                                        <input type="text" id="city" name="city" class="form-control" placeholder="Enter city">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="religion" class="form-label">Religion <span class="text-danger">*</span></label>
                                                        <select id="religion" name="religion" class="form-control" required>
                                                            <option value="">Select Religion</option>
                                                            <option value="Christianity">Christianity</option>
                                                            <option value="Islam">Islam</option>
                                                            <option value="Others">Others</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="blood_group" class="form-label">Blood Group</label>
                                                        <select id="blood_group" name="blood_group" class="form-control">
                                                            <option value="">Select Blood Group</option>
                                                            <option value="A+">A+</option>
                                                            <option value="A-">A-</option>
                                                            <option value="B+">B+</option>
                                                            <option value="B-">B-</option>
                                                            <option value="AB+">AB+</option>
                                                            <option value="AB-">AB-</option>
                                                            <option value="O+">O+</option>
                                                            <option value="O-">O-</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="mother_tongue" class="form-label">Mother Tongue</label>
                                                        <input type="text" id="mother_tongue" name="mother_tongue" class="form-control" placeholder="Native language">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="nin_number" class="form-label">NIN Number</label>
                                                        <input type="text" id="nin_number" name="nin_number" class="form-control" placeholder="11-digit NIN" maxlength="11">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="school_house" class="form-label">School House <span class="text-danger">*</span></label>
                                                        <select id="school_house" name="schoolhouseid" class="form-control" required>
                                                            <option value="">Select School House</option>
                                                            @foreach ($schoolhouses as $schoolhouse)
                                                                <option value="{{ $schoolhouse->id }}">{{ $schoolhouse->house }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <!-- Section D: Parent/Guardian Details -->
                                    <div class="card">
                                        <div class="card-header bg-warning text-dark">
                                            <h6 class="mb-0"><i class="fas fa-users me-2"></i>Parent/Guardian Details</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="father_name" class="form-label">Father's Name</label>
                                                <input type="text" id="father_name" name="father_name" class="form-control" placeholder="Father's full name">
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="father_phone" class="form-label">Father's Phone</label>
                                                        <input type="text" id="father_phone" name="father_phone" class="form-control" placeholder="+234 xxx xxx xxxx">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="father_occupation" class="form-label">Father's Occupation</label>
                                                        <input type="text" id="father_occupation" name="father_occupation" class="form-control" placeholder="Occupation">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="father_city" class="form-label">Father's City</label>
                                                <input type="text" id="father_city" name="father_city" class="form-control" placeholder="City of residence">
                                            </div>
                                            <div class="mb-3">
                                                <label for="mother_name" class="form-label">Mother's Name</label>
                                                <input type="text" id="mother_name" name="mother_name" class="form-control" placeholder="Mother's full name">
                                            </div>
                                            <div class="mb-3">
                                                <label for="mother_phone" class="form-label">Mother's Phone</label>
                                                <input type="text" id="mother_phone" name="mother_phone" class="form-control" placeholder="+234 xxx xxx xxxx">
                                            </div>
                                            <div class="mb-3">
                                                <label for="parent_email" class="form-label">Parent's Email</label>
                                                <input type="email" id="parent_email" name="parent_email" class="form-control" placeholder="parent@example.com">
                                            </div>
                                            <div class="mb-3">
                                                <label for="parent_address" class="form-label">Parent's Address</label>
                                                <textarea id="parent_address" name="parent_address" class="form-control" rows="2" placeholder="Parent's address"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Section E: Previous School Details -->
                                    <div class="card">
                                        <div class="card-header bg-secondary text-white">
                                            <h6 class="mb-0"><i class="fas fa-school me-2"></i>Previous School Details</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="last_school" class="form-label">Last School Attended</label>
                                                <input type="text" id="last_school" name="last_school" class="form-control" placeholder="Previous school name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="last_class" class="form-label">Last Class Attended</label>
                                                <input type="text" id="last_class" name="last_class" class="form-control" placeholder="e.g., JSS 2" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="reason_for_leaving" class="form-label">Reason for Leaving</label>
                                                <textarea id="reason_for_leaving" name="reason_for_leaving" class="form-control" rows="2" placeholder="Reason for leaving previous school" required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-danger d-none" id="alert-error-msg"></div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" id="add-btn">
                                <i class="fas fa-save me-1"></i>Register Student
                            </button>
                            <button type="button" class="btn btn-success" onclick="printStudentDetails()">
                                <i class="fas fa-print me-1"></i>Print PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Student Modal -->
        <div id="editStudentModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header modal-header-gradient">
                        <h5 class="modal-title">
                            <i class="fas fa-user-edit me-2"></i>Edit Student
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    {{-- <form class="tablelist-form" id="editStudentForm" enctype="multipart/form-data" autocomplete="off" method="POST" action="{{ route('student.update', ':id') }}"> --}}
                        <form class="tablelist-form" id="editStudentForm" enctype="multipart/form-data" autocomplete="off" method="POST"
                                        action="{{ route('student.update', ':id') }}"
                                        data-base-action="{{ route('student.update', ':id') }}">
                        @csrf
                        @method('PATCH')
                        <div class="modal-body p-4">
                            <input type="hidden" id="editStudentId" name="id">

                            <!-- Progress Steps - Fixed: No active steps by default -->
                            <div class="progress-steps mb-4">
                                <div class="step">1</div>
                                <div class="step">2</div>
                                <div class="step">3</div>
                                <div class="step">4</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                   <!-- Academic Details section -->
                                    <div class="card">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Academic Details</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Admission Number Mode <span class="text-danger">*</span></label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="admissionMode" id="editAdmissionAuto" value="auto" required onchange="toggleAdmissionInput('edit')">
                                                        <label class="form-check-label" for="editAdmissionAuto">
                                                            <i class="fas fa-magic me-1"></i>Auto Generate
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="admissionMode" id="editAdmissionManual" value="manual" required onchange="toggleAdmissionInput('edit')">
                                                        <label class="form-check-label" for="editAdmissionManual">
                                                            <i class="fas fa-edit me-1"></i>Manual Entry
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="editAdmissionNo" class="form-label">Admission Number <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <select class="form-control" id="editAdmissionYear" name="admissionYear" required onchange="updateAdmissionNumber('edit')">
                                                        @for ($year = date('Y'); $year >= date('Y') - 5; $year--)
                                                            <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                                                        @endfor
                                                    </select>
                                                    <input type="text" id="editAdmissionNo" name="admissionNo" class="form-control" placeholder="TCC/YYYY/0001" required>
                                                    <small class="form-text text-muted w-100 mt-1">Format: TCC/YYYY/0001 (e.g., TCC/2024/0871)</small>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="editAdmissionDate" class="form-label">Admission Date <span class="text-danger">*</span></label>
                                                <input type="date" id="editAdmissionDate" name="admissionDate" class="form-control" required max="{{ date('Y-m-d') }}">
                                            </div>
                                            <div class="mb-3">
                                                <label for="editSchoolclassid" class="form-label">Class <span class="text-danger">*</span></label>
                                                <select id="editSchoolclassid" name="schoolclassid" class="form-control" required>
                                                    <option value="">Select Class</option>
                                                    @foreach ($schoolclasses as $class)
                                                        <option value="{{ $class->id }}">{{ $class->schoolclass }} - {{ $class->arm }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="editTermid" class="form-label">Term <span class="text-danger">*</span></label>
                                                        <select id="editTermid" name="termid" class="form-control" required>
                                                            <option value="">Select Term</option>
                                                            @foreach ($schoolterms as $term)
                                                                <option value="{{ $term->id }}">{{ $term->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="editSessionid" class="form-label">Session <span class="text-danger">*</span></label>
                                                        <select id="editSessionid" name="sessionid" class="form-control" required>
                                                            <option value="">Select Session</option>
                                                            @foreach ($schoolsessions as $session)
                                                                <option value="{{ $session->id }}">{{ $session->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Student Status <span class="text-danger">*</span></label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="statusId" id="editStatusOld" value="1" required>
                                                        <label class="form-check-label" for="editStatusOld">
                                                            <i class="fas fa-user-clock me-1"></i>Old Student
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="statusId" id="editStatusNew" value="2" required>
                                                        <label class="form-check-label" for="editStatusNew">
                                                            <i class="fas fa-user-plus me-1"></i>New Student
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Student Activity Status <span class="text-danger">*</span></label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="student_status" id="editStatusActive" value="Active" required>
                                                        <label class="form-check-label" for="editStatusActive">
                                                            <i class="fas fa-check-circle text-success me-1"></i>Active
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="student_status" id="editStatusInactive" value="Inactive" required>
                                                        <label class="form-check-label" for="editStatusInactive">
                                                            <i class="fas fa-pause-circle text-warning me-1"></i>Inactive
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="editStudentCategory" class="form-label">Student Category <span class="text-danger">*</span></label>
                                                <select id="editStudentCategory" name="student_category" class="form-control" required>
                                                    <option value="">Select Category</option>
                                                    <option value="Day">Day Student</option>
                                                    <option value="Boarding">Boarding Student</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Personal Details -->
                                <div class="col-md-6">
                                 <!-- Personal Details section -->
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Personal Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3 text-center">
                                            <div class="upload-area border border-2 border-dashed border-primary rounded p-3">
                                                <img id="editStudentAvatar" src="{{ asset('theme/layouts/assets/media/avatars/blank.png') }}" alt="Avatar Preview" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #667eea; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" />
                                                <div>
                                                    <label for="editAvatar" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-camera me-1"></i>Choose Photo
                                                    </label>
                                                    <input type="file" id="editAvatar" name="avatar" class="d-none" accept=".png,.jpg,.jpeg" onchange="previewImage(this, 'editStudentAvatar')">
                                                    <div class="form-text mt-2">Max 2MB (PNG, JPG, JPEG)</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="editTitle" class="form-label">Title</label>
                                                    <select id="editTitle" name="title" class="form-control">
                                                        <option value="">Select</option>
                                                        <option value="Master">Master</option>
                                                        <option value="Miss">Miss</option>
                                                    </select>
                                                </div>
                                            </div>
                                             <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="editLastname" class="form-label">Last Name <span class="text-danger">*</span></label>
                                                    <input type="text" id="editLastname" name="lastname" class="form-control" placeholder="Last name" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="editFirstname" class="form-label">First Name <span class="text-danger">*</span></label>
                                                    <input type="text" id="editFirstname" name="firstname" class="form-control" placeholder="First name" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="editOthername" class="form-label">Other Names</label>
                                            <input type="text" id="editOthername" name="othername" class="form-control" placeholder="Middle name(s)" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                                            <div class="d-flex gap-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="gender" id="editGenderMale" value="Male" required>
                                                    <label class="form-check-label" for="editGenderMale">
                                                        <i class="fas fa-male text-primary me-1"></i>Male
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="gender" id="editGenderFemale" value="Female" required>
                                                    <label class="form-check-label" for="editGenderFemale">
                                                        <i class="fas fa-female text-danger me-1"></i>Female
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="editDOB" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                                    <input type="date" id="editDOB" name="dateofbirth" class="form-control" required onchange="calculateAge(this.value, 'editAgeInput')">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="editAgeInput" class="form-label">Age <span class="text-danger">*</span></label>
                                                    <input type="number" id="editAgeInput" name="age" class="form-control" readonly required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="editPlaceofbirth" class="form-label">Place of Birth <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-primary text-white">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </span>
                                                <input type="text" id="editPlaceofbirth" name="placeofbirth" class="form-control" placeholder="e.g., Lagos, Nigeria" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="editPhoneNumber" class="form-label">Phone Number</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-primary text-white">
                                                    <i class="fas fa-phone"></i>
                                                </span>
                                                <input type="text" id="editPhoneNumber" name="phone_number" class="form-control" placeholder="+234 xxx xxx xxxx">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="editEmail" class="form-label">Email</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-primary text-white">
                                                    <i class="fas fa-envelope"></i>
                                                </span>
                                                <input type="email" id="editEmail" name="email" class="form-control" placeholder="student@example.com">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="editFutureAmbition" class="form-label">Future Ambition <span class="text-danger">*</span></label>
                                            <textarea id="editFutureAmbition" name="future_ambition" class="form-control" rows="2" placeholder="Enter future ambition" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="editPermanentAddress" class="form-label">Permanent Address <span class="text-danger">*</span></label>
                                            <textarea id="editPermanentAddress" name="permanent_address" class="form-control" rows="2" placeholder="Enter permanent address" required></textarea>
                                        </div>
                                    </div>
                                </div>
                                </div>
                                <div class="col-md-6">
                            <!-- Additional Information section -->
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Additional Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-10">
                                                <div class="mb-3">
                                                    <label for="editNationality" class="form-label">Nationality <span class="text-danger">*</span></label>
                                                    <input type="text" id="editNationality" name="nationality" class="form-control" placeholder="e.g., Nigerian" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="editState" class="form-label">State of Origin <span class="text-danger">*</span></label>
                                                    <select id="editState" name="state" class="form-control" required>
                                                        <option value="">Select State</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="editLocal" class="form-label">Local Government <span class="text-danger">*</span></label>
                                                    <select id="editLocal" name="local" class="form-control" required>
                                                        <option value="">Select LGA</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="editCity" class="form-label">City</label>
                                                    <input type="text" id="editCity" name="city" class="form-control" placeholder="Enter city">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="editReligion" class="form-label">Religion <span class="text-danger">*</span></label>
                                                    <select id="editReligion" name="religion" class="form-control" required>
                                                        <option value="">Select Religion</option>
                                                        <option value="Christianity">Christianity</option>
                                                        <option value="Islam">Islam</option>
                                                        <option value="Others">Others</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="editBloodGroup" class="form-label">Blood Group</label>
                                                    <select id="editBloodGroup" name="blood_group" class="form-control">
                                                        <option value="">Select Blood Group</option>
                                                        <option value="A+">A+</option>
                                                        <option value="A-">A-</option>
                                                        <option value="B+">B+</option>
                                                        <option value="B-">B-</option>
                                                        <option value="AB+">AB+</option>
                                                        <option value="AB-">AB-</option>
                                                        <option value="O+">O+</option>
                                                        <option value="O-">O-</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="editMotherTongue" class="form-label">Mother Tongue</label>
                                                    <input type="text" id="editMotherTongue" name="mother_tongue" class="form-control" placeholder="Native language">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="editNinNumber" class="form-label">NIN Number</label>
                                                    <input type="text" id="editNinNumber" name="nin_number" class="form-control" placeholder="11-digit NIN" maxlength="11">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="editSchoolHouse" class="form-label">School House <span class="text-danger">*</span></label>
                                                    <select id="editSchoolHouse" name="schoolhouseid" class="form-control" required>
                                                        <option value="">Select School House</option>
                                                        @foreach ($schoolhouses as $schoolhouse)
                                                            <option value="{{ $schoolhouse->id }}">{{ $schoolhouse->house }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    <!-- Section D: Parent/Guardian Details -->
                                    <div class="card">
                                        <div class="card-header bg-warning text-dark">
                                            <h6 class="mb-0"><i class="fas fa-users me-2"></i>Parent/Guardian Details</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="editFatherName" class="form-label">Father's Name</label>
                                                <input type="text" id="editFatherName" name="father_name" class="form-control" placeholder="Father's full name">
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="editFatherPhone" class="form-label">Father's Phone</label>
                                                        <input type="text" id="editFatherPhone" name="father_phone" class="form-control" placeholder="+234 xxx xxx xxxx">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="editFatherOccupation" class="form-label">Father's Occupation</label>
                                                        <input type="text" id="editFatherOccupation" name="father_occupation" class="form-control" placeholder="Occupation">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="editFatherCity" class="form-label">Father's City</label>
                                                <input type="text" id="editFatherCity" name="father_city" class="form-control" placeholder="City of residence">
                                            </div>
                                            <div class="mb-3">
                                                <label for="editMotherName" class="form-label">Mother's Name</label>
                                                <input type="text" id="editMotherName" name="mother_name" class="form-control" placeholder="Mother's full name">
                                            </div>
                                            <div class="mb-3">
                                                <label for="editMotherPhone" class="form-label">Mother's Phone</label>
                                                <input type="text" id="editMotherPhone" name="mother_phone" class="form-control" placeholder="+234 xxx xxx xxxx">
                                            </div>
                                            <div class="mb-3">
                                                <label for="editParentEmail" class="form-label">Parent's Email</label>
                                                <input type="email" id="editParentEmail" name="parent_email" class="form-control" placeholder="parent@example.com">
                                            </div>
                                            <div class="mb-3">
                                                <label for="editParentAddress" class="form-label">Parent's Address</label>
                                                <textarea id="editParentAddress" name="parent_address" class="form-control" rows="2" placeholder="Parent's address"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Section E: Previous School Details -->
                                    <div class="card">
                                        <div class="card-header bg-secondary text-white">
                                            <h6 class="mb-0"><i class="fas fa-school me-2"></i>Previous School Details</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="editLastSchool" class="form-label">Last School Attended</label>
                                                <input type="text" id="editLastSchool" name="last_school" class="form-control" placeholder="Previous school name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="editLastClass" class="form-label">Last Class Attended</label>
                                                <input type="text" id="editLastClass" name="last_class" class="form-control" placeholder="e.g., JSS 2" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="editReasonForLeaving" class="form-label">Reason for Leaving</label>
                                                <textarea id="editReasonForLeaving" name="reason_for_leaving" class="form-control" rows="2" placeholder="Reason for leaving previous school" required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-danger d-none" id="edit-alert-error-msg"></div>
                        </div>

                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" id="edit-btn">
                                <i class="fas fa-save me-1"></i>Update Student
                            </button>
                            <button type="button" class="btn btn-success" onclick="printStudentDetails('edit')">
                                <i class="fas fa-print me-1"></i>Print PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== ENHANCED VIEW STUDENT MODAL WITH COMPLETE PARENT INFORMATION ===== -->
        <div id="viewStudentModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <!-- Modern Gradient Header -->
                    <div class="modal-header modal-header-gradient">
                        <div class="d-flex align-items-center">
                            <div class="header-icon-wrapper me-3">
                                <i class="fas fa-graduation-cap fa-2x"></i>
                            </div>
                            <div>
                                <h4 class="modal-title mb-1">Student Profile</h4>
                                <p class="text-white-50 mb-0">Complete student information and records</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-0">
                        <!-- Student Header with Profile Image and Basic Info -->
                        <div class="student-profile-header bg-light p-4 border-bottom">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="position-relative">
                                        <div class="profile-avatar" id="viewStudentAvatarContainer">
                                            <img id="viewStudentPhoto"
                                                 src="{{ asset('theme/layouts/assets/media/avatars/blank.png') }}"
                                                 alt="Student Photo"
                                                 class="rounded-circle border border-4 border-white shadow"
                                                 style="width: 120px; height: 120px; object-fit: cover;">
                                            <span class="position-absolute bottom-0 end-0 bg-success rounded-circle p-2 border border-2 border-white"
                                                  style="width: 20px; height: 20px;"
                                                  id="studentStatusIndicator"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="d-flex flex-column">
                                        <h2 class="mb-2 fw-bold" id="viewFullName">-</h2>
                                        <div class="d-flex flex-wrap gap-3 mb-2">
                                            <span class="badge bg-primary bg-gradient px-3 py-2">
                                                <i class="fas fa-id-card me-1"></i>
                                                <span id="viewAdmissionNumber">-</span>
                                            </span>
                                            <span class="badge bg-info bg-gradient px-3 py-2" id="viewClassBadge">
                                                <i class="fas fa-school me-1"></i>
                                                <span id="viewClassDisplay">-</span>
                                            </span>
                                            <span class="badge bg-success bg-gradient px-3 py-2" id="viewStudentTypeBadge">
                                                <i class="fas fa-user-tag me-1"></i>
                                                <span id="viewStudentType">-</span>
                                            </span>
                                        </div>
                                        <div class="d-flex gap-4 text-muted">
                                            <div><i class="fas fa-calendar-alt me-1"></i> Admitted: <span id="viewAdmittedDate">-</span></div>
                                            <div><i class="fas fa-venus-mars me-1"></i> <span id="viewGenderText">-</span></div>
                                            <div><i class="fas fa-birthday-cake me-1"></i> Age: <span id="viewAge">-</span> years</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modern Tab Navigation -->
                        <div class="px-4 pt-4">
                            <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#personalInfo" role="tab">
                                        <i class="fas fa-user-circle me-2"></i>Personal Details
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#academicInfo" role="tab">
                                        <i class="fas fa-graduation-cap me-2"></i>Academic Info
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#familyInfo" role="tab">
                                        <i class="fas fa-users me-2"></i>Family & Guardian
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#additionalInfo" role="tab">
                                        <i class="fas fa-info-circle me-2"></i>Additional Info
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#termHistory" role="tab">
                                        <i class="fas fa-history me-2"></i>Term History
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Tab Content -->
                        <div class="tab-content p-4">
                            <!-- 1. PERSONAL DETAILS TAB -->
                            <div class="tab-pane fade show active" id="personalInfo" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-card mb-3">
                                            <div class="info-card-header">
                                                <h6 class="mb-0"><i class="fas fa-id-badge me-2 text-primary"></i>Basic Information</h6>
                                            </div>
                                            <div class="info-card-body">
                                                <table class="table table-borderless table-sm mb-0">
                                                    <tr>
                                                        <th width="40%">Full Name:</th>
                                                        <td class="fw-semibold" id="viewFullNameDetail">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Title:</th>
                                                        <td id="viewTitle">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Date of Birth:</th>
                                                        <td><span id="viewDOB">-</span> (<span id="viewAgeDetail">-</span> years)</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Place of Birth:</th>
                                                        <td id="viewPlaceOfBirth">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Gender:</th>
                                                        <td><span id="viewGenderDetail">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Blood Group:</th>
                                                        <td><span class="badge bg-danger-light" id="viewBloodGroupDetail">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Religion:</th>
                                                        <td id="viewReligionDetail">-</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-card mb-3">
                                            <div class="info-card-header">
                                                <h6 class="mb-0"><i class="fas fa-address-card me-2 text-primary"></i>Contact Information</h6>
                                            </div>
                                            <div class="info-card-body">
                                                <table class="table table-borderless table-sm mb-0">
                                                    <tr>
                                                        <th width="40%">Phone Number:</th>
                                                        <td><i class="fas fa-phone-alt me-1 text-muted"></i> <span id="viewPhoneNumber">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Email Address:</th>
                                                        <td><i class="fas fa-envelope me-1 text-muted"></i> <span id="viewEmailAddress">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Permanent Address:</th>
                                                        <td><i class="fas fa-map-marker-alt me-1 text-muted"></i> <span id="viewPermanentAddress">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <th>City:</th>
                                                        <td id="viewCity">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>State of Origin:</th>
                                                        <td id="viewStateOrigin">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>LGA:</th>
                                                        <td id="viewLGA">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Nationality:</th>
                                                        <td id="viewNationality">-</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <div class="info-card">
                                            <div class="info-card-header">
                                                <h6 class="mb-0"><i class="fas fa-rocket me-2 text-primary"></i>Future Ambition</h6>
                                            </div>
                                            <div class="info-card-body">
                                                <p class="mb-0 fst-italic" id="viewFutureAmbition">-</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. ACADEMIC INFORMATION TAB -->
                            <div class="tab-pane fade" id="academicInfo" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-card mb-3">
                                            <div class="info-card-header">
                                                <h6 class="mb-0"><i class="fas fa-graduation-cap me-2 text-success"></i>Current Academic Status</h6>
                                            </div>
                                            <div class="info-card-body">
                                                <table class="table table-borderless table-sm mb-0">
                                                    <tr>
                                                        <th width="40%">Admission No:</th>
                                                        <td class="fw-bold text-primary" id="viewAdmissionNo">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Admission Date:</th>
                                                        <td id="viewAdmissionDate">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Class:</th>
                                                        <td><span class="badge bg-info" id="viewCurrentClass">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Arm:</th>
                                                        <td id="viewArm">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Student Category:</th>
                                                        <td><span class="badge bg-secondary" id="viewStudentCategory">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Student Status:</th>
                                                        <td><span id="viewStudentStatus">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <th>School House:</th>
                                                        <td id="viewSchoolHouse">-</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-card mb-3">
                                            <div class="info-card-header">
                                                <h6 class="mb-0"><i class="fas fa-calendar-alt me-2 text-success"></i>Current Term Information</h6>
                                            </div>
                                            <div class="info-card-body">
                                                <div class="text-center mb-3" id="currentTermAlert">
                                                    <!-- Will be populated by JS -->
                                                </div>
                                                <table class="table table-borderless table-sm mb-0">
                                                    <tr>
                                                        <th width="40%">Current Term:</th>
                                                        <td><span id="viewCurrentTerm">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Current Session:</th>
                                                        <td id="viewCurrentSession">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Status in Current Term:</th>
                                                        <td><span id="viewCurrentTermStatus">-</span></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <div class="info-card-header">
                                                <h6 class="mb-0"><i class="fas fa-school me-2 text-success"></i>Previous School</h6>
                                            </div>
                                            <div class="info-card-body">
                                                <table class="table table-borderless table-sm mb-0">
                                                    <tr>
                                                        <th width="40%">Last School:</th>
                                                        <td id="viewLastSchool">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Last Class:</th>
                                                        <td id="viewLastClass">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Reason for Leaving:</th>
                                                        <td><em id="viewReasonForLeaving">-</em></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. FAMILY & GUARDIAN INFORMATION TAB - ENHANCED -->
                            <div class="tab-pane fade" id="familyInfo" role="tabpanel">
                                <div class="row">
                                    <!-- Father's Information -->
                                    <div class="col-md-6">
                                        <div class="info-card mb-3">
                                            <div class="info-card-header bg-soft-primary">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-user-tie me-2 text-primary"></i>Father's Information
                                                    <span class="badge bg-primary ms-2" id="fatherStatusBadge"></span>
                                                </h6>
                                            </div>
                                            <div class="info-card-body">
                                                <div class="text-center mb-3" id="fatherPhotoSection" style="display: none;">
                                                    <img id="viewFatherPhoto" src="" alt="Father" class="rounded-circle border" style="width: 80px; height: 80px; object-fit: cover;">
                                                </div>
                                                <table class="table table-borderless table-sm mb-0">
                                                    <tr>
                                                        <th width="40%"><i class="fas fa-user me-1 text-muted"></i>Full Name:</th>
                                                        <td class="fw-semibold" id="viewFatherFullName">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th><i class="fas fa-phone me-1 text-muted"></i>Phone Number:</th>
                                                        <td>
                                                            <span id="viewFatherPhone">-</span>
                                                            <a href="javascript:void(0)" onclick="callNumber('viewFatherPhone')" class="ms-2 text-success" title="Call">
                                                                <i class="fas fa-phone-alt"></i>
                                                            </a>
                                                            <a href="javascript:void(0)" onclick="sendSMS('viewFatherPhone')" class="ms-2 text-info" title="SMS">
                                                                <i class="fas fa-comment"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><i class="fas fa-briefcase me-1 text-muted"></i>Occupation:</th>
                                                        <td><span class="badge bg-soft-success" id="viewFatherOccupation">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <th><i class="fas fa-building me-1 text-muted"></i>Employer:</th>
                                                        <td id="viewFatherEmployer">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th><i class="fas fa-map-marker-alt me-1 text-muted"></i>City/State:</th>
                                                        <td id="viewFatherCityState">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th><i class="fas fa-envelope me-1 text-muted"></i>Email:</th>
                                                        <td>
                                                            <span id="viewFatherEmail">-</span>
                                                            <a href="javascript:void(0)" onclick="sendEmail('viewFatherEmail')" class="ms-2 text-info" title="Send Email">
                                                                <i class="fas fa-envelope"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><i class="fas fa-address-card me-1 text-muted"></i>Address:</th>
                                                        <td id="viewFatherAddress">-</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mother's Information -->
                                    <div class="col-md-6">
                                        <div class="info-card mb-3">
                                            <div class="info-card-header bg-soft-pink">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-user-tie me-2 text-danger"></i>Mother's Information
                                                    <span class="badge bg-danger ms-2" id="motherStatusBadge"></span>
                                                </h6>
                                            </div>
                                            <div class="info-card-body">
                                                <div class="text-center mb-3" id="motherPhotoSection" style="display: none;">
                                                    <img id="viewMotherPhoto" src="" alt="Mother" class="rounded-circle border" style="width: 80px; height: 80px; object-fit: cover;">
                                                </div>
                                                <table class="table table-borderless table-sm mb-0">
                                                    <tr>
                                                        <th width="40%"><i class="fas fa-user me-1 text-muted"></i>Full Name:</th>
                                                        <td class="fw-semibold" id="viewMotherFullName">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th><i class="fas fa-phone me-1 text-muted"></i>Phone Number:</th>
                                                        <td>
                                                            <span id="viewMotherPhone">-</span>
                                                            <a href="javascript:void(0)" onclick="callNumber('viewMotherPhone')" class="ms-2 text-success" title="Call">
                                                                <i class="fas fa-phone-alt"></i>
                                                            </a>
                                                            <a href="javascript:void(0)" onclick="sendSMS('viewMotherPhone')" class="ms-2 text-info" title="SMS">
                                                                <i class="fas fa-comment"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><i class="fas fa-briefcase me-1 text-muted"></i>Occupation:</th>
                                                        <td><span class="badge bg-soft-success" id="viewMotherOccupation">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <th><i class="fas fa-building me-1 text-muted"></i>Employer:</th>
                                                        <td id="viewMotherEmployer">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th><i class="fas fa-map-marker-alt me-1 text-muted"></i>City/State:</th>
                                                        <td id="viewMotherCityState">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th><i class="fas fa-envelope me-1 text-muted"></i>Email:</th>
                                                        <td>
                                                            <span id="viewMotherEmail">-</span>
                                                            <a href="javascript:void(0)" onclick="sendEmail('viewMotherEmail')" class="ms-2 text-info" title="Send Email">
                                                                <i class="fas fa-envelope"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><i class="fas fa-address-card me-1 text-muted"></i>Address:</th>
                                                        <td id="viewMotherAddress">-</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Guardian Information (if different from parents) -->
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <div class="info-card">
                                            <div class="info-card-header bg-soft-warning">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-user-shield me-2 text-warning"></i>Emergency Contact / Guardian
                                                </h6>
                                            </div>
                                            <div class="info-card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <table class="table table-borderless table-sm mb-0">
                                                            <tr>
                                                                <th width="40%">Guardian Name:</th>
                                                                <td class="fw-semibold" id="viewGuardianName">-</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Relationship:</th>
                                                                <td id="viewGuardianRelation">-</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Phone Number:</th>
                                                                <td>
                                                                    <span id="viewGuardianPhone">-</span>
                                                                    <a href="javascript:void(0)" onclick="callNumber('viewGuardianPhone')" class="ms-2 text-success" title="Call">
                                                                        <i class="fas fa-phone-alt"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <table class="table table-borderless table-sm mb-0">
                                                            <tr>
                                                                <th width="40%">Parent's Email:</th>
                                                                <td id="viewParentEmail">-</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Parent's Address:</th>
                                                                <td id="viewParentAddress">-</td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. ADDITIONAL INFORMATION TAB -->
                            <div class="tab-pane fade" id="additionalInfo" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-card mb-3">
                                            <div class="info-card-header">
                                                <h6 class="mb-0"><i class="fas fa-notes-medical me-2 text-info"></i>Medical & Personal</h6>
                                            </div>
                                            <div class="info-card-body">
                                                <table class="table table-borderless table-sm mb-0">
                                                    <tr>
                                                        <th width="40%">Blood Group:</th>
                                                        <td><span class="badge bg-danger-light" id="viewBloodGroupAdditional">-</span></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Genotype:</th>
                                                        <td id="viewGenotype">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Allergies:</th>
                                                        <td id="viewAllergies">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Medical Conditions:</th>
                                                        <td id="viewMedicalConditions">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Disabilities:</th>
                                                        <td id="viewDisabilities">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>NIN Number:</th>
                                                        <td id="viewNIN">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Mother Tongue:</th>
                                                        <td id="viewMotherTongue">-</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-card mb-3">
                                            <div class="info-card-header">
                                                <h6 class="mb-0"><i class="fas fa-certificate me-2 text-info"></i>Identification</h6>
                                            </div>
                                            <div class="info-card-body">
                                                <table class="table table-borderless table-sm mb-0">
                                                    <tr>
                                                        <th width="40%">Birth Certificate:</th>
                                                        <td><span id="viewBirthCertificate">Not Uploaded</span></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Previous School Report:</th>
                                                        <td><span id="viewPreviousReport">Not Uploaded</span></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Health Report:</th>
                                                        <td><span id="viewHealthReport">Not Uploaded</span></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Immunization Record:</th>
                                                        <td><span id="viewImmunization">Not Uploaded</span></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 5. TERM HISTORY TAB -->
                            <div class="tab-pane fade" id="termHistory" role="tabpanel">
                                <div class="info-card">
                                    <div class="info-card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>Term Registration History</h6>
                                        <button class="btn btn-sm btn-outline-primary" onclick="refreshTermHistory()">
                                            <i class="fas fa-sync-alt me-1"></i> Refresh
                                        </button>
                                    </div>
                                    <div class="info-card-body">
                                        <div id="termHistoryLoading" class="text-center py-4">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2 text-muted">Loading term history...</p>
                                        </div>
                                        <div id="termHistoryContent" style="display: none;">
                                            <!-- Term history table will be inserted here by JavaScript -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <span class="text-muted" id="studentProfileLastUpdated"></span>
                            </div>
                            <div>
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i> Close
                                </button>
                                <button type="button" class="btn btn-primary" onclick="editStudentFromView()">
                                    <i class="fas fa-edit me-1"></i> Edit Student
                                </button>
                                <button type="button" class="btn btn-success" onclick="printStudentProfile()">
                                    <i class="fas fa-print me-1"></i> Print Profile
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include required libraries -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
// ============================================================================
// STUDENT MANAGEMENT SYSTEM - COMPLETE FIXED VERSION WITH NEW FEATURES
// ============================================================================

(function() {
    'use strict';

    // ============================================================================
    // GLOBAL CONFIGURATION
    // ============================================================================
    const CONFIG = {
        DEFAULT_PER_PAGE: 25,
        PER_PAGE_OPTIONS: [25, 50, 100, 250, 500],
        SEARCH_DEBOUNCE_DELAY: 500,
        MAX_API_RETRIES: 3,
        CACHE_DURATION: 300000,
        LAZY_LOAD_IMAGES: true,
        ENABLE_LOGGING: true
    };

    // ============================================================================
    // STATE MANAGEMENT
    // ============================================================================
    const AppState = {
        pagination: {
            currentPage: 1,
            perPage: CONFIG.DEFAULT_PER_PAGE,
            total: 0,
            lastPage: 1,
            from: 0,
            to: 0,
            data: []
        },
        filters: {
            search: '',
            class: 'all',
            status: 'all',
            gender: 'all',
            session: 'all',
            term: 'all'
        },
        ui: {
            currentView: 'table',
            isLoading: false,
            selectedStudents: new Set(),
            lastUpdated: null
        },
        cache: {
            students: new Map(),
            stats: null,
            classes: null
        },
        bulkStatusFilters: null,
        termFilters: null,
        bulkStatusData: null
    };

    // ============================================================================
    // NIGERIAN STATES AND LGAS
    // ============================================================================
    const NIGERIAN_STATES = [
        { name: "Abia", lgas: ["Aba North", "Aba South", "Arochukwu", "Bende", "Ikwuano", "Isiala Ngwa North", "Isiala Ngwa South", "Isuikwuato", "Obi Ngwa", "Ohafia", "Osisioma", "Ugwunagbo", "Ukwa East", "Ukwa West", "Umuahia North", "Umuahia South", "Umu Nneochi"] },
        { name: "Adamawa", lgas: ["Demsa", "Fufure", "Ganye", "Gayuk", "Gombi", "Grie", "Hong", "Jada", "Lamurde", "Madagali", "Maiha", "Mayo Belwa", "Michika", "Mubi North", "Mubi South", "Numan", "Shelleng", "Song", "Toungo", "Yola North", "Yola South"] },
        { name: "Akwa Ibom", lgas: ["Abak", "Eastern Obolo", "Eket", "Esit Eket", "Essien Udim", "Etim Ekpo", "Etinan", "Ibeno", "Ibesikpo Asutan", "Ibiono-Ibom", "Ika", "Ikono", "Ikot Abasi", "Ikot Ekpene", "Ini", "Itu", "Mbo", "Mkpat-Enin", "Nsit-Atai", "Nsit-Ibom", "Nsit-Ubium", "Obot Akara", "Okobo", "Onna", "Oron", "Oruk Anam", "Udung-Uko", "Ukanafun", "Uruan", "Urue-Offong/Oruko", "Uyo"] },
        { name: "Anambra", lgas: ["Aguata", "Anambra East", "Anambra West", "Anaocha", "Awka North", "Awka South", "Ayamelum", "Dunukofia", "Ekwusigo", "Idemili North", "Idemili South", "Ihiala", "Njikoka", "Nnewi North", "Nnewi South", "Ogbaru", "Onitsha North", "Onitsha South", "Orumba North", "Orumba South", "Oyi"] },
        { name: "Bauchi", lgas: ["Alkaleri", "Bauchi", "Bogoro", "Damban", "Darazo", "Dass", "Gamawa", "Ganjuwa", "Giade", "Itas/Gadau", "Jama'are", "Katagum", "Kirfi", "Misau", "Ningi", "Shira", "Tafawa Balewa", "Toro", "Warji", "Zaki"] },
        { name: "Bayelsa", lgas: ["Brass", "Ekeremor", "Kolokuma/Opokuma", "Nembe", "Ogbia", "Sagbama", "Southern Ijaw", "Yenagoa"] },
        { name: "Benue", lgas: ["Ado", "Agatu", "Apa", "Buruku", "Gboko", "Guma", "Gwer East", "Gwer West", "Katsina-Ala", "Konshisha", "Kwande", "Logo", "Makurdi", "Obi", "Ogbadibo", "Ohimini", "Oju", "Okpokwu", "Oturkpo", "Tarka", "Ukum", "Ushongo", "Vandeikya"] },
        { name: "Borno", lgas: ["Abadam", "Askira/Uba", "Bama", "Bayo", "Biu", "Chibok", "Damboa", "Dikwa", "Gubio", "Guzamala", "Gwoza", "Hawul", "Jere", "Kaga", "Kala/Balge", "Konduga", "Kukawa", "Kwaya Kusar", "Mafa", "Magumeri", "Maiduguri", "Marte", "Mobbar", "Monguno", "Ngala", "Nganzai", "Shani"] },
        { name: "Cross River", lgas: ["Abi", "Akamkpa", "Akpabuyo", "Bakassi", "Bekwarra", "Biase", "Boki", "Calabar Municipal", "Calabar South", "Etung", "Ikom", "Obanliku", "Obubra", "Obudu", "Odukpani", "Ogoja", "Yakuur", "Yala"] },
        { name: "Delta", lgas: ["Aniocha North", "Aniocha South", "Bomadi", "Burutu", "Ethiope East", "Ethiope West", "Ika North East", "Ika South", "Isoko North", "Isoko South", "Ndokwa East", "Ndokwa West", "Okpe", "Oshimili North", "Oshimili South", "Patani", "Sapele", "Udu", "Ughelli North", "Ughelli South", "Ukwuani", "Uvwie", "Warri North", "Warri South", "Warri South West"] },
        { name: "Ebonyi", lgas: ["Abakaliki", "Afikpo North", "Afikpo South", "Ebonyi", "Ezza North", "Ezza South", "Ikwo", "Ishielu", "Ivo", "Izzi", "Ohaozara", "Ohaukwu", "Onicha"] },
        { name: "Edo", lgas: ["Akoko-Edo", "Egor", "Esan Central", "Esan North-East", "Esan South-East", "Esan West", "Etsako Central", "Etsako East", "Etsako West", "Igueben", "Ikpoba Okha", "Orhionmwon", "Oredo", "Ovia North-East", "Ovia South-West", "Owan East", "Owan West", "Uhunmwonde"] },
        { name: "Ekiti", lgas: ["Ado Ekiti", "Efon", "Ekiti East", "Ekiti South-West", "Ekiti West", "Emure", "Gbonyin", "Ido Osi", "Ijero", "Ikere", "Ilejemeje", "Irepodun/Ifelodun", "Ise/Orun", "Moba", "Oye"] },
        { name: "Enugu", lgas: ["Aninri", "Awgu", "Enugu East", "Enugu North", "Enugu South", "Ezeagu", "Igbo Etiti", "Igbo Eze North", "Igbo Eze South", "Isi Uzo", "Nkanu East", "Nkanu West", "Nsukka", "Oji River", "Udenu", "Udi", "Uzo Uwani"] },
        { name: "FCT", lgas: ["Abaji", "Bwari", "Gwagwalada", "Kuje", "Kwali", "Municipal Area Council"] },
        { name: "Gombe", lgas: ["Akko", "Balanga", "Billiri", "Dukku", "Funakaye", "Gombe", "Kaltungo", "Kwami", "Nafada", "Shongom", "Yamaltu/Deba"] },
        { name: "Imo", lgas: ["Aboh Mbaise", "Ahiazu Mbaise", "Ehime Mbano", "Ezinihitte", "Ideato North", "Ideato South", "Ihitte/Uboma", "Ikeduru", "Isiala Mbano", "Isu", "Mbaitoli", "Ngor Okpala", "Njaba", "Nkwerre", "Nwangele", "Obowo", "Oguta", "Ohaji/Egbema", "Okigwe", "Orlu", "Orsu", "Oru East", "Oru West", "Owerri Municipal", "Owerri North", "Owerri West", "Unuimo"] },
        { name: "Jigawa", lgas: ["Auyo", "Babura", "Biriniwa", "Birnin Kudu", "Buji", "Dutse", "Gagarawa", "Garki", "Gumel", "Guri", "Gwaram", "Gwiwa", "Hadejia", "Jahun", "Kafin Hausa", "Kazaure", "Kiri Kasama", "Kiyawa", "Kaugama", "Maigatari", "Malam Madori", "Miga", "Ringim", "Roni", "Sule Tankarkar", "Taura", "Yankwashi"] },
        { name: "Kaduna", lgas: ["Birnin Gwari", "Chikun", "Giwa", "Igabi", "Ikara", "Jaba", "Jema'a", "Kachia", "Kaduna North", "Kaduna South", "Kagarko", "Kajuru", "Kaura", "Kauru", "Kubau", "Kudan", "Lere", "Makarfi", "Sabon Gari", "Sanga", "Soba", "Zangon Kataf", "Zaria"] },
        { name: "Kano", lgas: ["Ajingi", "Albasu", "Bagwai", "Bebeji", "Bichi", "Bunkure", "Dala", "Dambatta", "Dawakin Kudu", "Dawakin Tofa", "Doguwa", "Fagge", "Gabasawa", "Garko", "Garun Mallam", "Gaya", "Gezawa", "Gwale", "Gwarzo", "Kabo", "Kano Municipal", "Karaye", "Kibiya", "Kiru", "Kumbotso", "Kunchi", "Kura", "Madobi", "Makoda", "Minjibir", "Nasarawa", "Rano", "Rimin Gado", "Rogo", "Shanono", "Sumaila", "Takai", "Tarauni", "Tofa", "Tsanyawa", "Tudun Wada", "Ungogo", "Warawa", "Wudil"] },
        { name: "Katsina", lgas: ["Bakori", "Batagarawa", "Batsari", "Baure", "Bindawa", "Charanchi", "Dan Musa", "Dandume", "Danja", "Daura", "Dutsi", "Dutsin Ma", "Faskari", "Funtua", "Ingawa", "Jibia", "Kafur", "Kaita", "Kankara", "Kankia", "Katsina", "Kurfi", "Kusada", "Mai'Adua", "Malumfashi", "Mani", "Mashi", "Matazu", "Musawa", "Rimi", "Sabuwa", "Safana", "Sandamu", "Zango"] },
        { name: "Kebbi", lgas: ["Aleiro", "Arewa Dandi", "Argungu", "Augie", "Bagudo", "Birnin Kebbi", "Bunza", "Dandi", "Fakai", "Gwandu", "Jega", "Kalgo", "Koko/Besse", "Maiyama", "Ngaski", "Sakaba", "Shanga", "Suru", "Danko/Wasagu", "Yauri", "Zuru"] },
        { name: "Kogi", lgas: ["Adavi", "Ajaokuta", "Ankpa", "Bassa", "Dekina", "Ibaji", "Idah", "Igalamela Odolu", "Ijumu", "Kabba/Bunu", "Kogi", "Lokoja", "Mopa Muro", "Ofu", "Ogori/Magongo", "Okehi", "Okene", "Olamaboro", "Omala", "Yagba East", "Yagba West"] },
        { name: "Kwara", lgas: ["Asa", "Baruten", "Edu", "Ekiti", "Ifelodun", "Ilorin East", "Ilorin South", "Ilorin West", "Irepodun", "Isin", "Kaiama", "Moro", "Offa", "Oke Ero", "Oyun", "Pategi"] },
        { name: "Lagos", lgas: ["Agege", "Ajeromi-Ifelodun", "Alimosho", "Amuwo-Odofin", "Apapa", "Badagry", "Epe", "Eti Osa", "Ibeju-Lekki", "Ifako-Ijaiye", "Ikeja", "Ikorodu", "Kosofe", "Lagos Island", "Lagos Mainland", "Mushin", "Ojo", "Oshodi-Isolo", "Shomolu", "Surulere"] },
        { name: "Nasarawa", lgas: ["Akwanga", "Awe", "Doma", "Karu", "Keana", "Keffi", "Kokona", "Lafia", "Nasarawa", "Nasarawa Egon", "Obi", "Toto", "Wamba"] },
        { name: "Niger", lgas: ["Agaie", "Agwara", "Bida", "Borgu", "Bosso", "Chanchaga", "Edati", "Gbako", "Gurara", "Katcha", "Kontagora", "Lapai", "Lavun", "Magama", "Mariga", "Mashegu", "Mokwa", "Moya", "Paikoro", "Rafi", "Rijau", "Shiroro", "Suleja", "Tafa", "Wushishi"] },
        { name: "Ogun", lgas: ["Abeokuta North", "Abeokuta South", "Ado-Odo/Ota", "Egbado North", "Egbado South", "Ewekoro", "Ifo", "Ijebu East", "Ijebu North", "Ijebu North East", "Ijebu Ode", "Ikenne", "Imeko Afon", "Ipokia", "Obafemi Owode", "Odeda", "Odogbolu", "Ogun Waterside", "Remo North", "Shagamu"] },
        { name: "Ondo", lgas: ["Akoko North-East", "Akoko North-West", "Akoko South-East", "Akoko South-West", "Akure North", "Akure South", "Ese Odo", "Idanre", "Ifedore", "Ilaje", "Ile Oluji/Okeigbo", "Irele", "Odigbo", "Okitipupa", "Ondo East", "Ondo West", "Ose", "Owo"] },
        { name: "Osun", lgas: ["Aiyedade", "Aiyedire", "Atakunmosa East", "Atakunmosa West", "Boluwaduro", "Boripe", "Ede North", "Ede South", "Egbedore", "Ejigbo", "Ife Central", "Ife East", "Ife North", "Ife South", "Ifedayo", "Ifelodun", "Ila", "Ilesa East", "Ilesa West", "Irepodun", "Irewole", "Isokan", "Iwo", "Obokun", "Odo Otin", "Ola Oluwa", "Olorunda", "Oriade", "Orolu", "Osogbo"] },
        { name: "Oyo", lgas: ["Afijio", "Akinyele", "Atiba", "Atisbo", "Egbeda", "Ibadan North", "Ibadan North-East", "Ibadan North-West", "Ibadan South-East", "Ibadan South-West", "Ibarapa Central", "Ibarapa East", "Ibarapa North", "Ido", "Irepo", "Iseyin", "Itesiwaju", "Iwajowa", "Kajola", "Lagelu", "Ogbomosho North", "Ogbomosho South", "Ogo Oluwa", "Olorunsogo", "Oluyole", "Ona Ara", "Orelope", "Ori Ire", "Oyo East", "Oyo West", "Saki East", "Saki West", "Surulere"] },
        { name: "Plateau", lgas: ["Bokkos", "Barkin Ladi", "Bassa", "Jos East", "Jos North", "Jos South", "Kanam", "Kanke", "Langtang North", "Langtang South", "Mangu", "Mikang", "Pankshin", "Qua'an Pan", "Riyom", "Shendam", "Wase"] },
        { name: "Rivers", lgas: ["Abua/Odual", "Ahoada East", "Ahoada West", "Akuku-Toru", "Andoni", "Asari-Toru", "Bonny", "Degema", "Eleme", "Emohua", "Etche", "Gokana", "Ikwerre", "Khana", "Obio/Akpor", "Ogba/Egbema/Ndoni", "Ogu/Bolo", "Okrika", "Omuma", "Opobo/Nkoro", "Oyigbo", "Port Harcourt", "Tai"] },
        { name: "Sokoto", lgas: ["Binji", "Bodinga", "Dange Shuni", "Gada", "Goronyo", "Gudu", "Gwadabawa", "Illela", "Isa", "Kebbe", "Kware", "Rabah", "Sabon Birni", "Shagari", "Silame", "Sokoto North", "Sokoto South", "Tambuwal", "Tangaza", "Tureta", "Wamako", "Wurno", "Yabo"] },
        { name: "Taraba", lgas: ["Ardo Kola", "Bali", "Donga", "Gashaka", "Gassol", "Ibi", "Jalingo", "Karim Lamido", "Kumi", "Lau", "Sardauna", "Takum", "Ussa", "Wukari", "Yorro", "Zing"] },
        { name: "Yobe", lgas: ["Bade", "Bursari", "Damaturu", "Fika", "Fune", "Geidam", "Gujba", "Gulani", "Jakusko", "Karasuwa", "Machina", "Nangere", "Nguru", "Potiskum", "Tarmuwa", "Yunusari", "Yusufari"] },
        { name: "Zamfara", lgas: ["Anka", "Bakura", "Birnin Magaji/Kiyaw", "Bukkuyum", "Bungudu", "Gummi", "Gusau", "Kaura Namoda", "Maradun", "Maru", "Shinkafi", "Talata Mafara", "Chafe", "Zurmi"] }
    ];

    // ============================================================================
    // UTILITY FUNCTIONS
    // ============================================================================
    const Utils = {
        log: function(message, data = null, level = 'info') {
            if (!CONFIG.ENABLE_LOGGING) return;
            const timestamp = new Date().toISOString();
            const logFn = level === 'error' ? console.error : console.log;
            if (data) {
                logFn(`[${timestamp}] ${message}:`, data);
            } else {
                logFn(`[${timestamp}] ${message}`);
            }
        },

        escapeHtml: function(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        formatDate: function(dateString, format = 'short') {
            if (!dateString) return 'N/A';
            try {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return 'N/A';
                if (format === 'short') {
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                } else if (format === 'long') {
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                }
                return date.toLocaleDateString();
            } catch (e) {
                return 'N/A';
            }
        },

        calculateAge: function(dateOfBirth) {
            if (!dateOfBirth) return 'N/A';
            try {
                const dob = new Date(dateOfBirth);
                if (isNaN(dob.getTime())) return 'N/A';
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                return age;
            } catch (e) {
                return 'N/A';
            }
        },

        getInitials: function(firstName, lastName) {
            const first = firstName && firstName.length > 0 ? firstName.charAt(0).toUpperCase() : '';
            const last = lastName && lastName.length > 0 ? lastName.charAt(0).toUpperCase() : '';
            return (first + last) || 'ST';
        },

        debounce: function(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        },

        showLoading: function() {
            const loadingEl = document.getElementById('loadingState');
            const tableView = document.getElementById('tableView');
            const cardView = document.getElementById('cardView');
            const emptyState = document.getElementById('emptyState');

            if (loadingEl) loadingEl.classList.remove('d-none');
            if (tableView) tableView.classList.add('d-none');
            if (cardView) cardView.classList.add('d-none');
            if (emptyState) emptyState.classList.add('d-none');

            AppState.ui.isLoading = true;
        },

        hideLoading: function() {
            const loadingEl = document.getElementById('loadingState');
            if (loadingEl) loadingEl.classList.add('d-none');

            const tableView = document.getElementById('tableView');
            const cardView = document.getElementById('cardView');
            const emptyState = document.getElementById('emptyState');

            if (AppState.pagination.data && AppState.pagination.data.length > 0) {
                if (tableView && AppState.ui.currentView === 'table') {
                    tableView.classList.remove('d-none');
                }
                if (cardView && AppState.ui.currentView === 'card') {
                    cardView.classList.remove('d-none');
                }
                if (emptyState) emptyState.classList.add('d-none');
            } else {
                if (tableView) tableView.classList.add('d-none');
                if (cardView) cardView.classList.add('d-none');
                if (emptyState) emptyState.classList.remove('d-none');
            }

            AppState.ui.isLoading = false;
        },

        showError: function(message, title = 'Error') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: title,
                    text: message,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: { confirmButton: 'btn btn-primary' }
                });
            } else {
                alert(`${title}: ${message}`);
            }
        },

        showSuccess: function(message, title = 'Success') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: title,
                    text: message,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    timer: 2000,
                    timerProgressBar: true
                });
            } else {
                alert(`${title}: ${message}`);
            }
        },

        showConfirm: async function(title, text, confirmText = 'Yes', cancelText = 'Cancel') {
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: title,
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: cancelText,
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-light'
                    }
                });
                return result.isConfirmed;
            }
            return confirm(`${title}: ${text}`);
        },

        initializeTooltips: function() {
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        },

        ensureAxios: function() {
            if (typeof axios === 'undefined') {
                this.showError('Axios library is missing. Please refresh the page or contact support.');
                return false;
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                this.showError('CSRF token not found. Please refresh the page.');
                return false;
            }
            axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
            axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            return true;
        }
    };

    // ============================================================================
    // API SERVICE
    // ============================================================================
    const ApiService = {
        async getStudents(page = 1, perPage = null, filters = null) {
            if (!Utils.ensureAxios()) {
                throw new Error('Axios not available');
            }

            const params = new URLSearchParams();
            params.append('page', page);

            const itemsPerPage = perPage || AppState.pagination.perPage || CONFIG.DEFAULT_PER_PAGE;
            params.append('per_page', itemsPerPage);

            const currentFilters = filters || AppState.filters;

            if (currentFilters.search && currentFilters.search.trim() !== '') {
                params.append('search', currentFilters.search.trim());
            }

            if (currentFilters.class && currentFilters.class !== 'all') {
                params.append('class_id', currentFilters.class);
            }

            if (currentFilters.status && currentFilters.status !== 'all') {
                params.append('status', currentFilters.status);
            }

            if (currentFilters.gender && currentFilters.gender !== 'all') {
                params.append('gender', currentFilters.gender);
            }

            if (currentFilters.session && currentFilters.session !== 'all') {
                params.append('session_id', currentFilters.session);
            }

            try {
                Utils.log('Fetching students with params:', {
                    page,
                    perPage: itemsPerPage,
                    search: currentFilters.search,
                    params: params.toString()
                });

                const response = await axios.get(`/students/optimized?${params.toString()}`);

                if (response.data.success) {
                    return response.data.data;
                } else {
                    throw new Error(response.data.message || 'Failed to fetch students');
                }
            } catch (error) {
                Utils.log('API Error - getStudents', error, 'error');
                throw error;
            }
        },

        async getStudent(id) {
            if (!Utils.ensureAxios()) {
                throw new Error('Axios not available');
            }
            try {
                Utils.log('Fetching student by ID:', id);
                const response = await axios.get(`/student/${id}/edit`);

                if (response.data.success && response.data.student) {
                    return response.data.student;
                } else {
                    throw new Error(response.data.message || 'Failed to fetch student');
                }
            } catch (error) {
                Utils.log('API Error - getStudent', error, 'error');
                throw error;
            }
        },

        async deleteStudent(id) {
            if (!Utils.ensureAxios()) {
                throw new Error('Axios not available');
            }
            try {
                const response = await axios.delete(`/student/${id}/destroy`);
                return response.data;
            } catch (error) {
                Utils.log('API Error - deleteStudent', error, 'error');
                throw error;
            }
        },

        async deleteMultipleStudents(ids) {
            if (!Utils.ensureAxios()) {
                throw new Error('Axios not available');
            }
            try {
                const response = await axios.post('/students/destroy-multiple', { ids });
                return response.data;
            } catch (error) {
                Utils.log('API Error - deleteMultipleStudents', error, 'error');
                throw error;
            }
        },

        async getStudentActiveTerm(studentId) {
            if (!Utils.ensureAxios()) {
                throw new Error('Axios not available');
            }
            try {
                const response = await axios.get(`/student-current-term/student/${studentId}/active`);
                return response.data;
            } catch (error) {
                Utils.log('API Error - getStudentActiveTerm', error, 'error');
                return { success: false, data: null };
            }
        },

        async getStudentAllTerms(studentId) {
            if (!Utils.ensureAxios()) {
                throw new Error('Axios not available');
            }
            try {
                const response = await axios.get(`/student/${studentId}/all-terms`);
                return response.data;
            } catch (error) {
                Utils.log('API Error - getStudentAllTerms', error, 'error');
                return { success: false, data: [] };
            }
        },

        async updateBulkCurrentTerm(data) {
            if (!Utils.ensureAxios()) {
                throw new Error('Axios not available');
            }
            try {
                const response = await axios.post('/student-current-term/bulk-update', data);
                return response.data;
            } catch (error) {
                Utils.log('API Error - updateBulkCurrentTerm', error, 'error');
                throw error;
            }
        },

        async generateReport(params) {
            if (!Utils.ensureAxios()) {
                throw new Error('Axios not available');
            }
            try {
                const response = await axios({
                    method: 'GET',
                    url: '/students/report',
                    params: params,
                    responseType: 'blob',
                    timeout: 120000
                });
                return response;
            } catch (error) {
                Utils.log('API Error - generateReport', error, 'error');
                throw error;
            }
        },

        // ===== NEW API METHODS =====
        async getStudentsByClassAndSession(classId, sessionId, termId = null) {
            if (!Utils.ensureAxios()) {
                throw new Error('Axios not available');
            }
            try {
                const params = {
                    class_id: classId,
                    session_id: sessionId
                };
                if (termId) {
                    params.term_id = termId;
                }
                const response = await axios.get('/students/by-class-session', { params });
                return response.data;
            } catch (error) {
                Utils.log('API Error - getStudentsByClassAndSession', error, 'error');
                throw error;
            }
        },

        async bulkUpdateStatus(data) {
            if (!Utils.ensureAxios()) {
                throw new Error('Axios not available');
            }
            try {
                const response = await axios.post('/students/bulk-update-status', data);
                return response.data;
            } catch (error) {
                Utils.log('API Error - bulkUpdateStatus', error, 'error');
                throw error;
            }
        },

        async getStudentsInTerm(params) {
            if (!Utils.ensureAxios()) {
                throw new Error('Axios not available');
            }
            try {
                const response = await axios.get('/students-in-term', { params });
                return response.data;
            } catch (error) {
                Utils.log('API Error - getStudentsInTerm', error, 'error');
                throw error;
            }
        },

        async removeStudentFromTerm(registrationId) {
            if (!Utils.ensureAxios()) {
                throw new Error('Axios not available');
            }
            try {
                const response = await axios.post('/students/remove-from-term', {
                    registration_id: registrationId
                });
                return response.data;
            } catch (error) {
                Utils.log('API Error - removeStudentFromTerm', error, 'error');
                throw error;
            }
        },

        async bulkRemoveFromTerm(registrationIds) {
            if (!Utils.ensureAxios()) {
                throw new Error('Axios not available');
            }
            try {
                const response = await axios.post('/students/bulk-remove-from-term', {
                    registration_ids: registrationIds
                });
                return response.data;
            } catch (error) {
                Utils.log('API Error - bulkRemoveFromTerm', error, 'error');
                throw error;
            }
        }
    };

    // ============================================================================
    // FILTER MANAGER
    // ============================================================================
    const FilterManager = {
        searchTimeout: null,

        initializeFilters: function() {
            const searchInput = document.getElementById('search-input');
            const classFilter = document.getElementById('schoolclass-filter');
            const statusFilter = document.getElementById('status-filter');
            const genderFilter = document.getElementById('gender-filter');
            const sessionFilter = document.getElementById('session-filter');
            const termFilter = document.getElementById('term-filter');
            const filterBtn = document.getElementById('filterBtn');
            const resetBtn = document.getElementById('resetFiltersBtn');
            const clearSearchBtn = document.getElementById('clear-search');
            const resetFromEmptyBtn = document.getElementById('resetFromEmptyBtn');

            if (searchInput) {
                searchInput.removeEventListener('input', this.handleSearchInput);
                searchInput.addEventListener('input', (e) => this.handleSearchInput(e));
                searchInput.removeEventListener('keypress', this.handleSearchEnter);
                searchInput.addEventListener('keypress', (e) => this.handleSearchEnter(e));
            }

            if (clearSearchBtn) {
                clearSearchBtn.removeEventListener('click', this.clearSearch);
                clearSearchBtn.addEventListener('click', () => this.clearSearch());
            }

            if (classFilter) {
                classFilter.removeEventListener('change', this.handleFilterChange);
                classFilter.addEventListener('change', (e) => this.handleFilterChange(e));
            }

            if (termFilter) {
                termFilter.removeEventListener('change', this.handleFilterChange);
                termFilter.addEventListener('change', (e) => this.handleFilterChange(e));
            }

            if (statusFilter) {
                statusFilter.removeEventListener('change', this.handleFilterChange);
                statusFilter.addEventListener('change', (e) => this.handleFilterChange(e));
            }

            if (genderFilter) {
                genderFilter.removeEventListener('change', this.handleFilterChange);
                genderFilter.addEventListener('change', (e) => this.handleFilterChange(e));
            }

            if (sessionFilter) {
                sessionFilter.removeEventListener('change', this.handleFilterChange);
                sessionFilter.addEventListener('change', (e) => this.handleFilterChange(e));
            }

            if (filterBtn) {
                filterBtn.removeEventListener('click', this.applyFilters);
                filterBtn.addEventListener('click', () => this.applyFilters());
            }

            if (resetBtn) {
                resetBtn.removeEventListener('click', this.resetFilters);
                resetBtn.addEventListener('click', () => this.resetFilters());
            }

            if (resetFromEmptyBtn) {
                resetFromEmptyBtn.removeEventListener('click', this.resetFilters);
                resetFromEmptyBtn.addEventListener('click', () => this.resetFilters());
            }

            if (searchInput && clearSearchBtn) {
                clearSearchBtn.style.display = searchInput.value.length > 0 ? 'block' : 'none';
            }
        },

        handleSearchInput: function(e) {
            const searchInput = e.target;
            const clearSearchBtn = document.getElementById('clear-search');

            if (clearSearchBtn) {
                clearSearchBtn.style.display = searchInput.value.length > 0 ? 'block' : 'none';
            }

            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }

            this.searchTimeout = setTimeout(() => {
                this.applyFilters();
            }, CONFIG.SEARCH_DEBOUNCE_DELAY);
        },

        handleSearchEnter: function(e) {
            if (e.key === 'Enter') {
                if (this.searchTimeout) {
                    clearTimeout(this.searchTimeout);
                }
                this.applyFilters();
            }
        },

        handleFilterChange: function(e) {
            this.applyFilters();
        },

        clearSearch: function() {
            const searchInput = document.getElementById('search-input');
            const clearSearchBtn = document.getElementById('clear-search');

            if (searchInput) {
                searchInput.value = '';
                if (clearSearchBtn) {
                    clearSearchBtn.style.display = 'none';
                }

                if (this.searchTimeout) {
                    clearTimeout(this.searchTimeout);
                }
                this.applyFilters();
            }
        },

        applyFilters: function() {
            const searchInput = document.getElementById('search-input');
            const classFilter = document.getElementById('schoolclass-filter');
            const statusFilter = document.getElementById('status-filter');
            const genderFilter = document.getElementById('gender-filter');
            const sessionFilter = document.getElementById('session-filter');
            const termFilter = document.getElementById('term-filter');

            AppState.filters = {
                search: searchInput ? searchInput.value.trim() : '',
                class: classFilter ? classFilter.value : 'all',
                status: statusFilter ? statusFilter.value : 'all',
                gender: genderFilter ? genderFilter.value : 'all',
                session: sessionFilter ? sessionFilter.value : 'all',
                term: termFilter ? termFilter.value : 'all'
            };

            AppState.pagination.currentPage = 1;
            StudentManager.fetchStudents();

            Utils.log('Filters applied:', AppState.filters);
        },

        resetFilters: function() {
            const searchInput = document.getElementById('search-input');
            const classFilter = document.getElementById('schoolclass-filter');
            const statusFilter = document.getElementById('status-filter');
            const genderFilter = document.getElementById('gender-filter');
            const sessionFilter = document.getElementById('session-filter');
            const termFilter = document.getElementById('term-filter');
            const clearSearchBtn = document.getElementById('clear-search');

            if (searchInput) {
                searchInput.value = '';
                if (clearSearchBtn) {
                    clearSearchBtn.style.display = 'none';
                }
            }
            if (classFilter) classFilter.value = 'all';
            if (termFilter) termFilter.value = 'all';
            if (statusFilter) statusFilter.value = 'all';
            if (genderFilter) genderFilter.value = 'all';
            if (sessionFilter) sessionFilter.value = 'all';

            AppState.filters = {
                search: '',
                class: 'all',
                status: 'all',
                gender: 'all',
                session: 'all',
                term: 'all'
            };

            AppState.pagination.currentPage = 1;
            StudentManager.fetchStudents();

            Utils.log('Filters reset');
        }
    };

    // ============================================================================
    // STATE AND LGA MANAGER
    // ============================================================================
    const StateLGAManager = {
        initializeAddStateDropdown: function() {
            const stateSelect = document.getElementById('addState');
            const lgaSelect = document.getElementById('addLocal');

            if (!stateSelect || !lgaSelect) return;

            stateSelect.innerHTML = '<option value="">Select State</option>';
            lgaSelect.innerHTML = '<option value="">Select LGA</option>';
            lgaSelect.disabled = true;

            NIGERIAN_STATES.forEach(state => {
                const option = document.createElement('option');
                option.value = state.name;
                option.textContent = state.name;
                stateSelect.appendChild(option);
            });

            stateSelect.removeEventListener('change', this.handleAddStateChange);
            stateSelect.addEventListener('change', (e) => this.handleAddStateChange(e));
        },

        handleAddStateChange: function(event) {
            const selectedState = event.target.value;
            const lgaSelect = document.getElementById('addLocal');

            if (!lgaSelect) return;

            lgaSelect.innerHTML = '<option value="">Select LGA</option>';

            if (selectedState) {
                const state = NIGERIAN_STATES.find(s => s.name === selectedState);
                lgaSelect.disabled = false;

                if (state) {
                    state.lgas.forEach(lga => {
                        const option = document.createElement('option');
                        option.value = lga;
                        option.textContent = lga;
                        lgaSelect.appendChild(option);
                    });
                }
            } else {
                lgaSelect.disabled = true;
            }
        },

        initializeEditStateDropdown: function() {
            const stateSelect = document.getElementById('editState');
            const lgaSelect = document.getElementById('editLocal');

            if (!stateSelect || !lgaSelect) return;

            stateSelect.innerHTML = '<option value="">Select State</option>';

            NIGERIAN_STATES.forEach(state => {
                const option = document.createElement('option');
                option.value = state.name;
                option.textContent = state.name;
                stateSelect.appendChild(option);
            });

            lgaSelect.innerHTML = '<option value="">Select LGA</option>';
            lgaSelect.disabled = true;

            stateSelect.removeEventListener('change', this.handleEditStateChange);
            stateSelect.addEventListener('change', (e) => this.handleEditStateChange(e));
        },

        handleEditStateChange: function(event) {
            const selectedState = event.target.value;
            const lgaSelect = document.getElementById('editLocal');

            if (!lgaSelect) return;

            lgaSelect.innerHTML = '<option value="">Select LGA</option>';

            if (selectedState) {
                const state = NIGERIAN_STATES.find(s => s.name === selectedState);
                lgaSelect.disabled = false;

                if (state) {
                    state.lgas.forEach(lga => {
                        const option = document.createElement('option');
                        option.value = lga;
                        option.textContent = lga;
                        lgaSelect.appendChild(option);
                    });
                }
            } else {
                lgaSelect.disabled = true;
            }
        },

        setEditStateAndLGA: function(stateName, lgaName) {
            const stateSelect = document.getElementById('editState');
            const lgaSelect = document.getElementById('editLocal');

            if (!stateSelect || !lgaSelect) return false;

            if (stateSelect.options.length <= 1) {
                NIGERIAN_STATES.forEach(state => {
                    const option = document.createElement('option');
                    option.value = state.name;
                    option.textContent = state.name;
                    stateSelect.appendChild(option);
                });
            }

            if (stateName && stateName !== '') {
                let stateFound = false;
                for (let i = 0; i < stateSelect.options.length; i++) {
                    if (stateSelect.options[i].value.toLowerCase() === stateName.toLowerCase()) {
                        stateSelect.selectedIndex = i;
                        stateFound = true;
                        break;
                    }
                }

                if (!stateFound) {
                    try {
                        stateSelect.value = stateName;
                    } catch (e) {}
                }

                const changeEvent = new Event('change', { bubbles: true });
                stateSelect.dispatchEvent(changeEvent);

                setTimeout(() => {
                    if (lgaName && lgaName !== '') {
                        for (let i = 0; i < lgaSelect.options.length; i++) {
                            if (lgaSelect.options[i].value.toLowerCase() === lgaName.toLowerCase()) {
                                lgaSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
                }, 300);
            }

            return true;
        }
    };

    // ============================================================================
    // ADMISSION NUMBER MANAGER
    // ============================================================================
    const AdmissionNumberManager = {
        async updateAdmissionNumber(prefix = '') {
            const yearSelect = document.getElementById(`${prefix}admissionYear`);
            const admissionNoInput = document.getElementById(`${prefix}admissionNo`);
            const admissionMode = document.querySelector(`input[name="admissionMode"]:checked${prefix ? `[id^="${prefix}"]` : ''}`);

            if (!yearSelect || !admissionNoInput) return;

            const year = yearSelect.value;
            const baseFormat = `TCC/${year}/`;

            if (admissionMode && admissionMode.value === 'auto') {
                admissionNoInput.readOnly = true;
                try {
                    const response = await axios.get(`/students/last-admission-number?year=${year}`);
                    if (response.data.success) {
                        admissionNoInput.value = response.data.admissionNo;
                    } else {
                        admissionNoInput.value = `${baseFormat}0871`;
                    }
                } catch (error) {
                    admissionNoInput.value = `${baseFormat}0871`;
                }
            } else {
                admissionNoInput.readOnly = false;
                if (!admissionNoInput.value || admissionNoInput.value === `${baseFormat}AUTO`) {
                    admissionNoInput.value = `${baseFormat}0871`;
                } else if (!admissionNoInput.value.startsWith(baseFormat)) {
                    const numericPart = admissionNoInput.value.split('/').pop() || '0871';
                    const numericValue = Math.max(871, parseInt(numericPart) || 871);
                    admissionNoInput.value = `${baseFormat}${numericValue.toString().padStart(4, '0')}`;
                }
            }
        },

        toggleAdmissionInput: function(prefix = '') {
            const admissionMode = document.querySelector(`input[name="admissionMode"]:checked${prefix ? `[id^="${prefix}"]` : ''}`);
            const admissionNoInput = document.getElementById(`${prefix}admissionNo`);
            const yearSelect = document.getElementById(`${prefix}admissionYear`);

            if (!admissionMode || !admissionNoInput || !yearSelect) return;

            const year = yearSelect.value;
            const baseFormat = `TCC/${year}/`;

            if (admissionMode.value === 'auto') {
                admissionNoInput.readOnly = true;
                this.updateAdmissionNumber(prefix);
            } else {
                admissionNoInput.readOnly = false;
                if (!admissionNoInput.value || admissionNoInput.value === `${baseFormat}AUTO`) {
                    admissionNoInput.value = `${baseFormat}0871`;
                } else if (!admissionNoInput.value.startsWith(baseFormat)) {
                    const numericPart = admissionNoInput.value.split('/').pop() || '0871';
                    const numericValue = Math.max(871, parseInt(numericPart) || 871);
                    admissionNoInput.value = `${baseFormat}${numericValue.toString().padStart(4, '0')}`;
                }
            }
        }
    };

    // ============================================================================
    // EDIT FORM MANAGER - COMPLETE FIXED VERSION
    // ============================================================================
    const EditFormManager = {
        populateEditForm: function(student) {
            Utils.log('Populating edit form', student);

            // Set student ID
            const studentIdField = document.getElementById('editStudentId');
            if (studentIdField) studentIdField.value = student.id || '';

            // ===== ACADEMIC DETAILS =====
            const admissionNoInput = document.getElementById('editAdmissionNo');
            const admissionYearSelect = document.getElementById('editAdmissionYear');
            const admissionDateInput = document.getElementById('editAdmissionDate');

            if (admissionNoInput) admissionNoInput.value = student.admissionNo || '';
            if (admissionYearSelect) admissionYearSelect.value = student.admissionYear || new Date().getFullYear();
            if (admissionDateInput) {
                const admissionDate = student.admissionDate || student.admission_date || '';
                if (admissionDate) {
                    admissionDateInput.value = admissionDate.split(' ')[0];
                }
            }

            // Set admission mode
            const admissionAuto = document.getElementById('editAdmissionAuto');
            const admissionManual = document.getElementById('editAdmissionManual');

            if (student.admissionNo && student.admissionNo.includes('AUTO')) {
                if (admissionAuto) {
                    admissionAuto.checked = true;
                    admissionAuto.required = false;
                }
                if (admissionManual) admissionManual.checked = false;
                if (admissionNoInput) admissionNoInput.readOnly = true;
            } else {
                if (admissionAuto) admissionAuto.checked = false;
                if (admissionManual) {
                    admissionManual.checked = true;
                    admissionManual.required = false;
                }
                if (admissionNoInput) admissionNoInput.readOnly = false;
            }

            // Class, Term, Session
            const classSelect = document.getElementById('editSchoolclassid');
            if (classSelect && student.schoolclassid) {
                classSelect.value = student.schoolclassid;
            }

            const termSelect = document.getElementById('editTermid');
            if (termSelect && student.termid) {
                termSelect.value = student.termid;
            }

            const sessionSelect = document.getElementById('editSessionid');
            if (sessionSelect && student.sessionid) {
                sessionSelect.value = student.sessionid;
            }

            // Status radio buttons
            if (student.statusId == 1) {
                document.getElementById('editStatusOld').checked = true;
            } else if (student.statusId == 2) {
                document.getElementById('editStatusNew').checked = true;
            }

            // Activity Status
            if (student.student_status === 'Active') {
                document.getElementById('editStatusActive').checked = true;
            } else if (student.student_status === 'Inactive') {
                document.getElementById('editStatusInactive').checked = true;
            }

            // Student Category
            const categorySelect = document.getElementById('editStudentCategory');
            if (categorySelect && student.student_category) {
                categorySelect.value = student.student_category;
            }

            // ===== PERSONAL DETAILS =====
            const titleSelect = document.getElementById('editTitle');
            if (titleSelect && student.title) {
                titleSelect.value = student.title;
            }

            const lastnameInput = document.getElementById('editLastname');
            if (lastnameInput) lastnameInput.value = student.lastname || '';

            const firstnameInput = document.getElementById('editFirstname');
            if (firstnameInput) firstnameInput.value = student.firstname || '';

            const othernameInput = document.getElementById('editOthername');
            if (othernameInput) othernameInput.value = student.othername || '';

            // Gender radio buttons
            if (student.gender === 'Male') {
                document.getElementById('editGenderMale').checked = true;
            } else if (student.gender === 'Female') {
                document.getElementById('editGenderFemale').checked = true;
            }

            // Date of Birth
            const dobInput = document.getElementById('editDOB');
            if (dobInput) {
                const dobValue = student.dateofbirth || '';
                if (dobValue) {
                    let formattedDate = dobValue;
                    if (dobValue.includes(' ')) {
                        formattedDate = dobValue.split(' ')[0];
                    } else if (dobValue.includes('T')) {
                        formattedDate = dobValue.split('T')[0];
                    }
                    dobInput.value = formattedDate;

                    const ageInput = document.getElementById('editAgeInput');
                    if (ageInput) {
                        const age = Utils.calculateAge(formattedDate);
                        ageInput.value = age || student.age || '';
                    }
                }
            }

            const placeOfBirthInput = document.getElementById('editPlaceofbirth');
            if (placeOfBirthInput) placeOfBirthInput.value = student.placeofbirth || '';

            const phoneInput = document.getElementById('editPhoneNumber');
            if (phoneInput) phoneInput.value = student.phone_number || '';

            const emailInput = document.getElementById('editEmail');
            if (emailInput) emailInput.value = student.email || '';

            const futureAmbitionInput = document.getElementById('editFutureAmbition');
            if (futureAmbitionInput) futureAmbitionInput.value = student.future_ambition || '';

            const permanentAddressInput = document.getElementById('editPermanentAddress');
            if (permanentAddressInput) permanentAddressInput.value = student.permanent_address || '';

            // ===== ADDITIONAL INFORMATION =====
            const nationalityInput = document.getElementById('editNationality');
            if (nationalityInput) nationalityInput.value = student.nationality || '';

            const bloodGroupSelect = document.getElementById('editBloodGroup');
            if (bloodGroupSelect && student.blood_group) {
                bloodGroupSelect.value = student.blood_group;
            }

            const houseSelect = document.getElementById('editSchoolHouse');
            if (houseSelect) {
                let houseValue = student.schoolhouseid || student.schoolhouse || student.school_house || null;
                if (houseValue) {
                    for (let i = 0; i < houseSelect.options.length; i++) {
                        if (houseSelect.options[i].value == houseValue) {
                            houseSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
            }

            // State and LGA
            if (student.state) {
                StateLGAManager.setEditStateAndLGA(student.state, student.local);
            }

            const cityInput = document.getElementById('editCity');
            if (cityInput) cityInput.value = student.city || '';

            const religionSelect = document.getElementById('editReligion');
            if (religionSelect && student.religion) {
                religionSelect.value = student.religion;
            }

            const motherTongueInput = document.getElementById('editMotherTongue');
            if (motherTongueInput) motherTongueInput.value = student.mother_tongue || '';

            const ninInput = document.getElementById('editNinNumber');
            if (ninInput) ninInput.value = student.nin_number || '';

            // ===== PARENT DETAILS =====
            const fatherNameInput = document.getElementById('editFatherName');
            if (fatherNameInput) fatherNameInput.value = student.father_name || '';

            const fatherPhoneInput = document.getElementById('editFatherPhone');
            if (fatherPhoneInput) fatherPhoneInput.value = student.father_phone || '';

            const fatherOccupationInput = document.getElementById('editFatherOccupation');
            if (fatherOccupationInput) fatherOccupationInput.value = student.father_occupation || '';

            const fatherCityInput = document.getElementById('editFatherCity');
            if (fatherCityInput) fatherCityInput.value = student.father_city || '';

            const motherNameInput = document.getElementById('editMotherName');
            if (motherNameInput) motherNameInput.value = student.mother_name || '';

            const motherPhoneInput = document.getElementById('editMotherPhone');
            if (motherPhoneInput) motherPhoneInput.value = student.mother_phone || '';

            const parentEmailInput = document.getElementById('editParentEmail');
            if (parentEmailInput) parentEmailInput.value = student.parent_email || '';

            const parentAddressInput = document.getElementById('editParentAddress');
            if (parentAddressInput) parentAddressInput.value = student.parent_address || '';

            // ===== PREVIOUS SCHOOL =====
            const lastSchoolInput = document.getElementById('editLastSchool');
            if (lastSchoolInput) lastSchoolInput.value = student.last_school || '';

            const lastClassInput = document.getElementById('editLastClass');
            if (lastClassInput) lastClassInput.value = student.last_class || '';

            const reasonLeavingInput = document.getElementById('editReasonForLeaving');
            if (reasonLeavingInput) reasonLeavingInput.value = student.reason_for_leaving || '';

            // ===== PHOTO =====
            const avatarImg = document.getElementById('editStudentAvatar');
            if (avatarImg) {
                if (student.picture && student.picture !== 'unnamed.jpg') {
                    avatarImg.src = `/storage/images/student_avatars/${student.picture}`;
                } else {
                    avatarImg.src = 'https://via.placeholder.com/120x120/667eea/ffffff?text=Photo';
                }
            }

            // Update form action URL
            // const form = document.getElementById('editStudentForm');
            // if (form && student.id) {
            //     form.action = form.action.replace(':id', student.id);
            // }




            // Update form action URL — always rebuild from the stored base pattern
            // so that opening the modal a second time doesn't use a stale URL
            const form = document.getElementById('editStudentForm');
            if (form && student.id) {
                const baseAction = form.dataset.baseAction; // always has ':id' literal
                if (baseAction) {
                    form.action = baseAction.replace(':id', student.id);
                } else {
                    // Fallback: strip any numeric segment and re-substitute
                    form.action = form.action.replace(/\/\d+$/, '/' + student.id)
                                            .replace(/\/\d+\/([^\/]+)$/, '/' + student.id + '/$1');
                }
            }



            Utils.log('Edit form populated successfully');
        }
    };

    // ============================================================================
    // VIEW MODAL MANAGER
    // ============================================================================
    const ViewModalManager = {
        currentStudentId: null,

        populateEnhancedViewModal: function(student) {
            Utils.log('Populating enhanced view modal', student);
            this.currentStudentId = student.id;

            // Basic Information
            this.safeSetText('viewFullName', `${student.lastname || ''} ${student.firstname || ''} ${student.othername || ''}`.trim() || '-');
            this.safeSetText('viewFullNameDetail', `${student.lastname || ''} ${student.firstname || ''} ${student.othername || ''}`.trim() || '-');
            this.safeSetText('viewAdmissionNumber', student.admissionNo || '-');
            this.safeSetText('viewAdmissionNo', student.admissionNo || '-');
            this.safeSetText('viewTitle', student.title || '-');
            this.safeSetText('viewDOB', Utils.formatDate(student.dateofbirth, 'long'));
            this.safeSetText('viewAge', student.age || Utils.calculateAge(student.dateofbirth));
            this.safeSetText('viewAgeDetail', student.age || Utils.calculateAge(student.dateofbirth));
            this.safeSetText('viewPlaceOfBirth', student.placeofbirth || '-');
            this.safeSetText('viewGenderDetail', student.gender || '-');
            this.safeSetText('viewGenderText', student.gender || '-');
            this.safeSetText('viewBloodGroupDetail', student.blood_group || 'Not Specified');
            this.safeSetText('viewBloodGroupAdditional', student.blood_group || 'Not Specified');
            this.safeSetText('viewReligionDetail', student.religion || '-');

            // Contact Information
            this.safeSetText('viewPhoneNumber', student.phone_number || '-');
            this.safeSetText('viewEmailAddress', student.email || '-');
            this.safeSetText('viewPermanentAddress', student.permanent_address || '-');
            this.safeSetText('viewCity', student.city || '-');
            this.safeSetText('viewStateOrigin', student.state || '-');
            this.safeSetText('viewLGA', student.local || '-');
            this.safeSetText('viewNationality', student.nationality || '-');

            // Future Ambition
            this.safeSetText('viewFutureAmbition', student.future_ambition || 'Not specified');

            // Academic Information
            this.safeSetText('viewAdmissionDate', Utils.formatDate(student.admission_date, 'long'));

            const classDisplay = `${student.schoolclass || ''} ${student.arm || ''}`.trim() || '-';
            this.safeSetText('viewCurrentClass', classDisplay);
            this.safeSetText('viewClassDisplay', classDisplay);

            const classBadge = document.getElementById('viewClassBadge');
            if (classBadge) {
                classBadge.innerHTML = `<i class="fas fa-school me-1"></i> ${classDisplay}`;
            }

            this.safeSetText('viewArm', student.arm || '-');
            this.safeSetText('viewStudentCategory', student.student_category || '-');

            const studentType = student.statusId == 2 ? 'New Student' : student.statusId == 1 ? 'Old Student' : '-';
            this.safeSetText('viewStudentType', studentType);

            const studentTypeBadge = document.getElementById('viewStudentTypeBadge');
            if (studentTypeBadge) {
                if (student.statusId == 2) {
                    studentTypeBadge.className = 'badge bg-warning bg-gradient px-3 py-2';
                    studentTypeBadge.innerHTML = `<i class="fas fa-star me-1"></i> New Student`;
                } else if (student.statusId == 1) {
                    studentTypeBadge.className = 'badge bg-secondary bg-gradient px-3 py-2';
                    studentTypeBadge.innerHTML = `<i class="fas fa-history me-1"></i> Old Student`;
                }
            }

            this.safeSetText('viewStudentStatus', student.student_status || '-');
            this.safeSetText('viewSchoolHouse', student.school_house || '-');
            this.safeSetText('viewAdmittedDate', Utils.formatDate(student.admission_date, 'short'));

            // Student Status Indicator
            const statusIndicator = document.getElementById('studentStatusIndicator');
            if (statusIndicator) {
                if (student.student_status === 'Active') {
                    statusIndicator.className = 'position-absolute bottom-0 end-0 bg-success rounded-circle p-2 border border-2 border-white';
                } else {
                    statusIndicator.className = 'position-absolute bottom-0 end-0 bg-secondary rounded-circle p-2 border border-2 border-white';
                }
            }

            // Previous School
            this.safeSetText('viewLastSchool', student.last_school || '-');
            this.safeSetText('viewLastClass', student.last_class || '-');
            this.safeSetText('viewReasonForLeaving', student.reason_for_leaving || '-');

            // Photo
            const photoElement = document.getElementById('viewStudentPhoto');
            if (photoElement) {
                if (student.picture && student.picture !== 'unnamed.jpg') {
                    photoElement.src = `/storage/images/student_avatars/${student.picture}`;
                    photoElement.style.display = 'inline';
                } else {
                    photoElement.src = 'https://via.placeholder.com/120x120/667eea/ffffff?text=Photo';
                }
            }

            // Parent Information
            this.safeSetText('viewFatherFullName', student.father_name || '-');
            this.safeSetText('viewFatherPhone', student.father_phone || '-');
            this.safeSetText('viewFatherOccupation', student.father_occupation || '-');
            this.safeSetText('viewFatherCityState', student.father_city || '-');
            this.safeSetText('viewFatherEmail', student.parent_email || '-');
            this.safeSetText('viewFatherAddress', student.parent_address || '-');

            this.safeSetText('viewMotherFullName', student.mother_name || '-');
            this.safeSetText('viewMotherPhone', student.mother_phone || '-');
            this.safeSetText('viewMotherOccupation', student.mother_occupation || '-');
            this.safeSetText('viewMotherEmail', student.parent_email || '-');
            this.safeSetText('viewMotherAddress', student.parent_address || '-');

            this.safeSetText('viewParentEmail', student.parent_email || '-');
            this.safeSetText('viewParentAddress', student.parent_address || '-');

            // Father Status Badge
            const fatherBadge = document.getElementById('fatherStatusBadge');
            if (fatherBadge) {
                if (student.father_name) {
                    fatherBadge.textContent = 'Available';
                    fatherBadge.className = 'badge bg-success ms-2';
                } else {
                    fatherBadge.textContent = 'Not Provided';
                    fatherBadge.className = 'badge bg-secondary ms-2';
                }
            }

            // Mother Status Badge
            const motherBadge = document.getElementById('motherStatusBadge');
            if (motherBadge) {
                if (student.mother_name) {
                    motherBadge.textContent = 'Available';
                    motherBadge.className = 'badge bg-success ms-2';
                } else {
                    motherBadge.textContent = 'Not Provided';
                    motherBadge.className = 'badge bg-secondary ms-2';
                }
            }

            // Additional Information
            this.safeSetText('viewNIN', student.nin_number || '-');
            this.safeSetText('viewMotherTongue', student.mother_tongue || '-');

            // Fetch term info
            this.fetchStudentTermInfo(student.id);
        },

        safeSetText: function(elementId, text) {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = text;
            }
        },

        async fetchStudentTermInfo(studentId) {
            try {
                const response = await ApiService.getStudentActiveTerm(studentId);
                const currentTermAlert = document.getElementById('currentTermAlert');

                if (response.success && response.data) {
                    const data = response.data;

                    this.safeSetText('viewCurrentTerm', data.term?.term || '-');
                    this.safeSetText('viewCurrentSession', data.session?.session || '-');

                    const statusHtml = data.is_current
                        ? '<span class="badge bg-success">Current Active Term</span>'
                        : '<span class="badge bg-warning text-dark">Registered (Not Current)</span>';

                    const currentTermStatus = document.getElementById('viewCurrentTermStatus');
                    if (currentTermStatus) currentTermStatus.innerHTML = statusHtml;

                    if (currentTermAlert) {
                        currentTermAlert.innerHTML = `
                            <div class="alert alert-success mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Currently enrolled in:</strong> ${data.schoolClass?.schoolclass || ''} ${data.schoolClass?.armRelation?.arm || ''}
                                (${data.term?.term || ''} Term, ${data.session?.session || ''} Session)
                            </div>
                        `;
                    }
                } else {
                    this.safeSetText('viewCurrentTerm', '-');
                    this.safeSetText('viewCurrentSession', '-');

                    const currentTermStatus = document.getElementById('viewCurrentTermStatus');
                    if (currentTermStatus) currentTermStatus.innerHTML = '<span class="badge bg-secondary">Not Registered</span>';

                    if (currentTermAlert) {
                        currentTermAlert.innerHTML = `
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>No active term registration found.</strong> Please update the student's current term.
                            </div>
                        `;
                    }
                }
            } catch (error) {
                Utils.log('Error fetching student term info', error, 'error');
            }
        }
    };

    // ============================================================================
    // PAGINATION MANAGER
    // ============================================================================
    const PaginationManager = {
        initializePerPageSelector: function() {
            const container = document.querySelector('.pagination-container');
            if (!container) return;

            if (document.getElementById('perPageSelector')) return;

            const perPageHtml = `
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted">Show:</span>
                    <select id="perPageSelector" class="form-select form-select-sm" style="width: auto;">
                        ${CONFIG.PER_PAGE_OPTIONS.map(option =>
                            `<option value="${option}" ${AppState.pagination.perPage === option ? 'selected' : ''}>${option}</option>`
                        ).join('')}
                    </select>
                    <span class="text-muted">entries</span>
                </div>
            `;

            const paginationNav = container.querySelector('nav');
            if (paginationNav) {
                container.insertAdjacentHTML('afterbegin', perPageHtml);
            } else {
                container.insertAdjacentHTML('beforeend', perPageHtml);
            }

            const selector = document.getElementById('perPageSelector');
            if (selector) {
                selector.addEventListener('change', function(e) {
                    const newPerPage = parseInt(e.target.value, 10);
                    AppState.pagination.perPage = newPerPage;
                    AppState.pagination.currentPage = 1;
                    StudentManager.fetchStudents();
                });
            }
        },

        updatePaginationUI: function(pagination) {
            const paginationContainer = document.getElementById('pagination');
            if (!paginationContainer) return;

            const showingCount = document.getElementById('showingCount');
            const toCount = document.getElementById('toCount');
            const totalCount = document.getElementById('totalCount');

            if (showingCount) showingCount.textContent = pagination.from || 0;
            if (toCount) toCount.textContent = pagination.to || 0;
            if (totalCount) totalCount.textContent = pagination.total || 0;

            const totalStudentsEl = document.getElementById('totalStudents');
            if (totalStudentsEl) totalStudentsEl.textContent = pagination.total || 0;

            // Clear existing page numbers except prev and next
            const pageItems = paginationContainer.querySelectorAll('.page-item:not(#prevPageLi):not(#nextPageLi)');
            pageItems.forEach(item => item.remove());

            if (!pagination.last_page || pagination.last_page <= 1) {
                return;
            }

            // Generate page numbers
            let startPage = Math.max(1, pagination.current_page - 2);
            let endPage = Math.min(pagination.last_page, pagination.current_page + 2);

            // Add first page and ellipsis if needed
            if (startPage > 1) {
                this.addPageItem(paginationContainer, 1, pagination.current_page);
                if (startPage > 2) {
                    this.addEllipsis(paginationContainer);
                }
            }

            // Add page numbers
            for (let i = startPage; i <= endPage; i++) {
                this.addPageItem(paginationContainer, i, pagination.current_page);
            }

            // Add last page and ellipsis if needed
            if (endPage < pagination.last_page) {
                if (endPage < pagination.last_page - 1) {
                    this.addEllipsis(paginationContainer);
                }
                this.addPageItem(paginationContainer, pagination.last_page, pagination.current_page);
            }

            // Update prev/next buttons
            const prevPageBtn = document.getElementById('prevPage');
            if (prevPageBtn) {
                if (pagination.current_page > 1) {
                    prevPageBtn.classList.remove('disabled');
                    prevPageBtn.onclick = (e) => {
                        e.preventDefault();
                        AppState.pagination.currentPage = pagination.current_page - 1;
                        StudentManager.fetchStudents();
                    };
                } else {
                    prevPageBtn.classList.add('disabled');
                    prevPageBtn.onclick = null;
                }
            }

            const nextPageBtn = document.getElementById('nextPage');
            if (nextPageBtn) {
                if (pagination.current_page < pagination.last_page) {
                    nextPageBtn.classList.remove('disabled');
                    nextPageBtn.onclick = (e) => {
                        e.preventDefault();
                        AppState.pagination.currentPage = pagination.current_page + 1;
                        StudentManager.fetchStudents();
                    };
                } else {
                    nextPageBtn.classList.add('disabled');
                    nextPageBtn.onclick = null;
                }
            }
        },

        addPageItem: function(container, pageNum, currentPage) {
            const li = document.createElement('li');
            li.className = `page-item ${pageNum === currentPage ? 'active' : ''}`;

            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = 'javascript:void(0);';
            a.textContent = pageNum;
            a.onclick = (e) => {
                e.preventDefault();
                AppState.pagination.currentPage = pageNum;
                StudentManager.fetchStudents();
            };

            li.appendChild(a);

            // Insert before next button
            const nextPageLi = document.getElementById('nextPageLi');
            container.insertBefore(li, nextPageLi);
        },

        addEllipsis: function(container) {
            const li = document.createElement('li');
            li.className = 'page-item disabled';
            li.innerHTML = '<span class="page-link">...</span>';

            const nextPageLi = document.getElementById('nextPageLi');
            container.insertBefore(li, nextPageLi);
        }
    };

    // ============================================================================
    // RENDER MANAGER - WITH COMPLETE ACTION BUTTONS UI
    // ============================================================================
    const RenderManager = {
        renderTableView: function(students) {
            const tbody = document.getElementById('studentTableBody');
            if (!tbody) return;

            if (!students || students.length === 0) {
                tbody.innerHTML = '';
                return;
            }

            const fragment = document.createDocumentFragment();

            students.forEach(student => {
                const row = document.createElement('tr');
                row.className = 'align-middle';
                row.dataset.id = student.id;

                // Status badge
                const statusBadge = student.student_status === 'Active'
                    ? '<span class="badge bg-success bg-gradient px-2 py-1 rounded-pill"><span class="status-dot active"></span>Active</span>'
                    : '<span class="badge bg-secondary bg-gradient px-2 py-1 rounded-pill"><span class="status-dot inactive"></span>Inactive</span>';

                // Type badge (New/Old)
                const typeBadge = student.statusId == 2
                    ? '<span class="badge bg-warning bg-gradient text-dark px-2 py-1 rounded-pill ms-1"><i class="fas fa-star me-1" style="font-size: 10px;"></i>New</span>'
                    : student.statusId == 1
                    ? '<span class="badge bg-secondary bg-gradient px-2 py-1 rounded-pill ms-1"><i class="fas fa-history me-1" style="font-size: 10px;"></i>Old</span>'
                    : '';

                // Avatar with initials
                const initials = Utils.getInitials(student.firstname, student.lastname);
                const avatarHtml = student.picture && student.picture !== 'unnamed.jpg'
                    ? `<img src="/storage/images/student_avatars/${student.picture}" alt="Avatar" class="rounded-circle border border-2 border-white shadow-sm" style="width: 45px; height: 45px; object-fit: cover;">`
                    : `<div class="avatar-initials rounded-circle border border-2 border-white shadow-sm" style="width: 45px; height: 45px; background: #4361ee; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px;">${initials}</div>`;

                row.innerHTML = `
                    <td>
                        <div class="form-check">
                            <input class="form-check-input student-checkbox" type="checkbox"
                                   value="${student.id}">
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="position-relative">
                                ${avatarHtml}
                                <span class="position-absolute bottom-0 end-0 ${student.student_status === 'Active' ? 'bg-success' : 'bg-secondary'} rounded-circle p-1 border border-2 border-white"
                                      style="width: 12px; height: 12px;"></span>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-semibold">${Utils.escapeHtml(student.lastname || '')} ${Utils.escapeHtml(student.firstname || '')}</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-light text-dark px-2 py-1 rounded-pill">
                                        <i class="fas fa-id-card me-1 text-muted" style="font-size: 10px;"></i>
                                        ${Utils.escapeHtml(student.admissionNo || 'N/A')}
                                    </span>
                                    ${typeBadge}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-medium">${Utils.escapeHtml(student.schoolclass || '')} ${Utils.escapeHtml(student.arm || '')}</span>
                            <small class="text-muted">${Utils.escapeHtml(student.student_category || '')}</small>
                        </div>
                    </td>
                    <td>
                        ${statusBadge}
                    </td>
                    <td>
                        <span class="d-flex align-items-center gap-1">
                            <i class="fas fa-${student.gender === 'Male' ? 'mars text-primary' : 'venus text-pink'}"></i>
                            ${Utils.escapeHtml(student.gender || 'N/A')}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1">
                            <i class="fas fa-calendar-alt text-muted" style="font-size: 12px;"></i>
                            <span>${Utils.formatDate(student.created_at, 'short')}</span>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex gap-2 justify-content-end">
                            <div class="btn-group" role="group">
                                <button type="button"
                                        class="btn btn-sm btn-soft-info rounded-start view-student-btn"
                                        data-student-id="${student.id}"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="View Student Details">
                                    <i class="fas fa-eye"></i>
                                    <span class="d-none d-xl-inline-block ms-1">View</span>
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-soft-warning edit-student-btn"
                                        data-student-id="${student.id}"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Edit Student">
                                    <i class="fas fa-edit"></i>
                                    <span class="d-none d-xl-inline-block ms-1">Edit</span>
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-soft-danger rounded-end delete-student-btn"
                                        data-student-id="${student.id}"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Delete Student">
                                    <i class="fas fa-trash-alt"></i>
                                    <span class="d-none d-xl-inline-block ms-1">Delete</span>
                                </button>
                            </div>
                        </div>
                    </td>
                `;

                fragment.appendChild(row);
            });

            tbody.innerHTML = '';
            tbody.appendChild(fragment);

            Utils.initializeTooltips();
            this.updateCheckAllState();
        },

        renderCardView: function(students) {
            const container = document.getElementById('studentsCardsContainer');
            if (!container) return;

            if (!students || students.length === 0) {
                container.innerHTML = '';
                return;
            }

            const fragment = document.createDocumentFragment();

            students.forEach(student => {
                const col = document.createElement('div');
                col.className = 'col-xl-3 col-lg-4 col-md-6 mb-4';

                const initials = Utils.getInitials(student.firstname, student.lastname);
                const avatarHtml = student.picture && student.picture !== 'unnamed.jpg'
                    ? `<img src="/storage/images/student_avatars/${student.picture}" alt="Avatar" class="avatar">`
                    : `<div class="avatar-initials">${initials}</div>`;

                col.innerHTML = `
                    <div class="student-profile-card" data-id="${student.id}">
                        <div class="checkbox-container">
                            <div class="form-check">
                                <input class="form-check-input student-checkbox" type="checkbox"
                                       value="${student.id}">
                            </div>
                        </div>
                        <div class="card-header">
                            <div class="header-content">
                                <h5 class="student-name">${Utils.escapeHtml(student.lastname || '')} ${Utils.escapeHtml(student.firstname || '')}</h5>
                                <span class="student-admission">${Utils.escapeHtml(student.admissionNo || 'N/A')}</span>
                            </div>
                            <div class="avatar-container">
                                ${avatarHtml}
                            </div>
                        </div>
                        <div class="card-body">
                            ${this.getStatusBadge(student)}
                            <div class="student-info-grid">
                                <div class="info-item">
                                    <span class="info-label">Class</span>
                                    <span class="info-value">${Utils.escapeHtml(student.schoolclass || '')} ${Utils.escapeHtml(student.arm || '')}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Gender</span>
                                    <span class="info-value">${Utils.escapeHtml(student.gender || 'N/A')}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Age</span>
                                    <span class="info-value">${Utils.escapeHtml(student.age || 'N/A')}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Registered</span>
                                    <span class="info-value">${Utils.formatDate(student.created_at, 'short')}</span>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <button class="action-btn view-btn view-student-btn" data-student-id="${student.id}">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <button class="action-btn edit-btn edit-student-btn" data-student-id="${student.id}">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn delete-btn delete-student-btn" data-student-id="${student.id}">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                fragment.appendChild(col);
            });

            container.innerHTML = '';
            container.appendChild(fragment);

            this.updateCheckAllState();
        },

        getStatusBadge: function(student) {
            let badges = '';

            if (student.student_status === 'Active') {
                badges += `<span class="status-badge status-active">
                            <i class="fas fa-check-circle"></i> Active
                        </span>`;
            } else if (student.student_status === 'Inactive') {
                badges += `<span class="status-badge status-inactive">
                            <i class="fas fa-pause-circle"></i> Inactive
                        </span>`;
            }

            if (student.statusId == 2) {
                badges += `<span class="status-badge status-new ms-2">
                            <i class="fas fa-star"></i> New Student
                        </span>`;
            } else if (student.statusId == 1) {
                badges += `<span class="status-badge status-old ms-2">
                            <i class="fas fa-history"></i> Old Student
                        </span>`;
            }

            return badges;
        },

        updateCheckAllState: function() {
            const checkAll = document.getElementById('checkAll');
            const checkAllTable = document.getElementById('checkAllTable');

            const totalCheckboxes = document.querySelectorAll('.student-checkbox').length;
            const checkedCheckboxes = document.querySelectorAll('.student-checkbox:checked').length;

            if (checkAll) {
                checkAll.checked = totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes;
                checkAll.indeterminate = checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes;
            }

            if (checkAllTable) {
                checkAllTable.checked = totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes;
                checkAllTable.indeterminate = checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes;
            }

            // Update bulk actions button
            const bulkActionsDropdown = document.getElementById('bulkActionsDropdown');
            if (bulkActionsDropdown) {
                if (checkedCheckboxes > 0) {
                    bulkActionsDropdown.disabled = false;
                    bulkActionsDropdown.innerHTML = `<i class="fas fa-cog me-2"></i>Actions (${checkedCheckboxes})`;
                } else {
                    bulkActionsDropdown.disabled = true;
                    bulkActionsDropdown.innerHTML = `<i class="fas fa-cog me-2"></i>Actions`;
                }
            }
        },

        toggleView: function(viewType) {
            AppState.ui.currentView = viewType;

            const tableView = document.getElementById('tableView');
            const cardView = document.getElementById('cardView');
            const tableViewBtn = document.getElementById('tableViewBtn');
            const cardViewBtn = document.getElementById('cardViewBtn');

            if (!tableView || !cardView || !tableViewBtn || !cardViewBtn) return;

            if (viewType === 'table') {
                tableView.classList.remove('d-none');
                cardView.classList.add('d-none');
                tableViewBtn.classList.add('active');
                cardViewBtn.classList.remove('active');

                if (AppState.pagination.data.length > 0) {
                    this.renderTableView(AppState.pagination.data);
                }
            } else {
                tableView.classList.add('d-none');
                cardView.classList.remove('d-none');
                tableViewBtn.classList.remove('active');
                cardViewBtn.classList.add('active');

                if (AppState.pagination.data.length > 0) {
                    this.renderCardView(AppState.pagination.data);
                }
            }
        }
    };

    // ============================================================================
    // SELECTION MANAGER
    // ============================================================================
    const SelectionManager = {
        initializeCheckboxes: function() {
            const checkAll = document.getElementById('checkAll');
            const checkAllTable = document.getElementById('checkAllTable');

            if (checkAll) {
                checkAll.removeEventListener('change', this.handleSelectAll);
                checkAll.addEventListener('change', (e) => this.handleSelectAll(e));
            }

            if (checkAllTable) {
                checkAllTable.removeEventListener('change', this.handleSelectAll);
                checkAllTable.addEventListener('change', (e) => this.handleSelectAll(e));
            }

            document.removeEventListener('change', this.handleCheckboxChange);
            document.addEventListener('change', (e) => this.handleCheckboxChange(e));
        },

        handleSelectAll: function(e) {
            const isChecked = e.target.checked;
            const checkboxes = document.querySelectorAll('.student-checkbox');

            checkboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
                const parent = checkbox.closest('.student-profile-card, tr');
                if (parent) {
                    parent.classList.toggle('selected', isChecked);
                }

                if (isChecked) {
                    AppState.ui.selectedStudents.add(checkbox.value);
                } else {
                    AppState.ui.selectedStudents.delete(checkbox.value);
                }
            });

            RenderManager.updateCheckAllState();
        },

        handleCheckboxChange: function(e) {
            if (e.target.classList.contains('student-checkbox')) {
                const checkbox = e.target;
                const parent = checkbox.closest('.student-profile-card, tr');

                if (parent) {
                    parent.classList.toggle('selected', checkbox.checked);
                }

                if (checkbox.checked) {
                    AppState.ui.selectedStudents.add(checkbox.value);
                } else {
                    AppState.ui.selectedStudents.delete(checkbox.value);
                }

                RenderManager.updateCheckAllState();
            }
        },

        getSelectedStudentIds: function() {
            return Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);
        },

        clearAllSelections: function() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
                const parent = checkbox.closest('.student-profile-card, tr');
                if (parent) {
                    parent.classList.remove('selected');
                }
            });

            AppState.ui.selectedStudents.clear();

            const checkAll = document.getElementById('checkAll');
            const checkAllTable = document.getElementById('checkAllTable');
            if (checkAll) checkAll.checked = false;
            if (checkAllTable) checkAllTable.checked = false;

            RenderManager.updateCheckAllState();
        }
    };

    // ============================================================================
    // STUDENT MANAGER
    // ============================================================================
    const StudentManager = {
        async fetchStudents() {
            Utils.showLoading();

            try {
                const paginationData = await ApiService.getStudents(
                    AppState.pagination.currentPage,
                    AppState.pagination.perPage,
                    AppState.filters
                );

                AppState.pagination = {
                    currentPage: paginationData.current_page,
                    lastPage: paginationData.last_page,
                    total: paginationData.total,
                    from: paginationData.from,
                    to: paginationData.to,
                    data: paginationData.data
                };

                if (AppState.ui.currentView === 'table') {
                    RenderManager.renderTableView(paginationData.data);
                } else {
                    RenderManager.renderCardView(paginationData.data);
                }

                PaginationManager.updatePaginationUI(paginationData);
                SelectionManager.clearAllSelections();

                paginationData.data.forEach(student => {
                    AppState.cache.students.set(student.id.toString(), student);
                });

                Utils.log('Students fetched successfully', {
                    total: paginationData.total,
                    showing: paginationData.data.length,
                    search: AppState.filters.search
                });

            } catch (error) {
                Utils.log('Error fetching students', error, 'error');
                Utils.showError('Failed to load students. Please try again.');

            } finally {
                Utils.hideLoading();
            }
        },

        async viewStudent(id) {
            try {
                Utils.showLoading();

                let student = AppState.cache.students.get(id.toString());
                if (!student) {
                    student = await ApiService.getStudent(id);
                    if (student && student.id) {
                        AppState.cache.students.set(id.toString(), student);
                    }
                }

                Utils.hideLoading();

                if (student) {
                    ViewModalManager.populateEnhancedViewModal(student);

                    const viewModalElement = document.getElementById('viewStudentModal');
                    if (viewModalElement) {
                        const viewModal = new bootstrap.Modal(viewModalElement);
                        viewModal.show();
                    }
                } else {
                    Utils.showError('Student data not found.');
                }
            } catch (error) {
                Utils.hideLoading();
                Utils.log('Error viewing student', error, 'error');
                Utils.showError('Failed to load student data.');
            }
        },

        async editStudent(id) {
            try {
                Utils.showLoading();

                // Ensure state dropdown is initialized
                StateLGAManager.initializeEditStateDropdown();

                const student = await ApiService.getStudent(id);

                if (!student || !student.id) {
                    throw new Error('Invalid student data received');
                }

                Utils.hideLoading();

                // Populate the edit form
                EditFormManager.populateEditForm(student);

                // Show the modal
                const editModalElement = document.getElementById('editStudentModal');
                if (editModalElement) {
                    const editModal = new bootstrap.Modal(editModalElement);
                    editModal.show();
                }
            } catch (error) {
                Utils.hideLoading();
                Utils.log('Error editing student', error, 'error');
                Utils.showError('Failed to load student for editing: ' + (error.message || 'Unknown error'));
            }
        },

        async deleteStudent(id) {
            const confirmed = await Utils.showConfirm(
                'Delete Student',
                'You won\'t be able to revert this!',
                'Yes, delete it!'
            );

            if (confirmed) {
                try {
                    await ApiService.deleteStudent(id);
                    AppState.cache.students.delete(id.toString());
                    await this.fetchStudents();
                    Utils.showSuccess('Student has been deleted.');
                } catch (error) {
                    Utils.log('Error deleting student', error, 'error');
                    Utils.showError('Failed to delete student.');
                }
            }
        },

        async deleteMultiple() {
            const selectedIds = SelectionManager.getSelectedStudentIds();

            if (selectedIds.length === 0) {
                Utils.showError('Please select at least one student to delete.', 'No Selection');
                return;
            }

            const confirmed = await Utils.showConfirm(
                `Delete ${selectedIds.length} Students?`,
                "This action cannot be undone!",
                'Yes, delete them!'
            );

            if (confirmed) {
                try {
                    await ApiService.deleteMultipleStudents(selectedIds);
                    selectedIds.forEach(id => AppState.cache.students.delete(id.toString()));
                    await this.fetchStudents();
                    Utils.showSuccess(`${selectedIds.length} student(s) have been deleted.`);
                    SelectionManager.clearAllSelections();
                } catch (error) {
                    Utils.log('Error deleting multiple students', error, 'error');
                    Utils.showError('Failed to delete selected students.');
                }
            }
        }
    };

    // ============================================================================
    // CURRENT TERM MANAGER
    // ============================================================================
    const CurrentTermManager = {
        showUpdateCurrentTermModal: function(studentId = null) {
            let selectedIds = [];

            if (studentId) {
                selectedIds = [studentId];
            } else {
                selectedIds = SelectionManager.getSelectedStudentIds();
            }

            if (selectedIds.length === 0) {
                Utils.showError('Please select at least one student.', 'No Selection');
                return;
            }

            const form = document.getElementById('updateCurrentTermForm');
            if (form) {
                form.reset();
            }

            const selectedCountEl = document.getElementById('selectedStudentsCount');
            if (selectedCountEl) {
                selectedCountEl.textContent = selectedIds.length;
            }

            const modal = new bootstrap.Modal(document.getElementById('updateCurrentTermModal'));
            modal.show();
        },

        async updateCurrentTerm() {
            const selectedIds = SelectionManager.getSelectedStudentIds();
            const form = document.getElementById('updateCurrentTermForm');
            if (!form) return;

            const classId = form.querySelector('[name="schoolclassId"]')?.value;
            const termId = form.querySelector('[name="termId"]')?.value;
            const sessionId = form.querySelector('[name="sessionId"]')?.value;

            if (!classId || !termId || !sessionId) {
                Utils.showError('Please select class, term, and session.', 'Missing Fields');
                return;
            }

            try {
                Swal.fire({
                    title: 'Updating...',
                    text: 'Please wait while updating current term.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const data = {
                    student_ids: selectedIds,
                    schoolclassId: classId,
                    termId: termId,
                    sessionId: sessionId,
                    is_current: true
                };

                const response = await ApiService.updateBulkCurrentTerm(data);

                const modal = bootstrap.Modal.getInstance(document.getElementById('updateCurrentTermModal'));
                if (modal) modal.hide();

                Swal.close();
                Utils.showSuccess(response.message || `Current term updated for ${selectedIds.length} student(s).`);
                await StudentManager.fetchStudents();

            } catch (error) {
                Swal.close();
                Utils.log('Error updating current term', error, 'error');
                let errorMessage = 'Failed to update current term.';
                if (error.response?.data?.message) {
                    errorMessage = error.response.data.message;
                } else if (error.message) {
                    errorMessage = error.message;
                }
                Utils.showError(errorMessage);
            }
        }
    };

    // ============================================================================
    // BULK STATUS UPDATE FEATURE
    // ============================================================================
    const BulkStatusManager = {
        showUpdateStatusModal: function() {
            const classId = document.getElementById('schoolclass-filter').value;
            const sessionId = document.getElementById('session-filter').value;

            if (classId === 'all' || sessionId === 'all') {
                Utils.showError('Please select both a class and a session to use this feature.', 'Selection Required');
                return;
            }

            // Store current filters for later use
            AppState.bulkStatusFilters = {
                class_id: classId,
                session_id: sessionId
            };

            // Show loading
            Swal.fire({
                title: 'Loading Students',
                html: 'Fetching students in this class and session...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            // Fetch students
            ApiService.getStudentsByClassAndSession(classId, sessionId)
            .then(response => {
                Swal.close();

                if (response.success) {
                    AppState.bulkStatusData = response;
                    this.renderStatusUpdateModal(response.students, response.stats);
                } else {
                    Utils.showError('Failed to load students: ' + (response.message || 'Unknown error'));
                }
            })
            .catch(error => {
                Swal.close();
                Utils.showError('Error loading students: ' + (error.response?.data?.message || error.message));
            });
        },

        renderStatusUpdateModal: function(students, stats) {
            // Remove existing modal if any
            const existingModal = document.getElementById('bulkStatusUpdateModal');
            if (existingModal) existingModal.remove();

            const modalHtml = `
                <div class="modal fade" id="bulkStatusUpdateModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <div class="modal-content">
                            <div class="modal-header modal-header-gradient">
                                <h5 class="modal-title">
                                    <i class="fas fa-sync-alt me-2"></i>
                                    Bulk Update Student Status
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <!-- Summary Cards -->
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <div class="card bg-primary text-white">
                                            <div class="card-body">
                                                <h6 class="card-title">Total Students</h6>
                                                <h2 class="mb-0">${stats.total}</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-success text-white">
                                            <div class="card-body">
                                                <h6 class="card-title">Active</h6>
                                                <h2 class="mb-0">${stats.active}</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-secondary text-white">
                                            <div class="card-body">
                                                <h6 class="card-title">Inactive</h6>
                                                <h2 class="mb-0">${stats.inactive}</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-warning text-dark">
                                            <div class="card-body">
                                                <h6 class="card-title">New Students</h6>
                                                <h2 class="mb-0">${stats.new_students}</h2>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bulk Action Toolbar -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAllStatusStudents">
                                                    <label class="form-check-label fw-semibold" for="selectAllStatusStudents">
                                                        Select All Students
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <div class="btn-group me-2">
                                                        <button class="btn btn-outline-success dropdown-toggle" type="button"
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-user-check me-1"></i>Set Activity Status
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="#" onclick="BulkStatusManager.bulkUpdateStatus('activity_status', 'Active')">
                                                                <i class="fas fa-check-circle text-success me-2"></i>Active
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="#" onclick="BulkStatusManager.bulkUpdateStatus('activity_status', 'Inactive')">
                                                                <i class="fas fa-pause-circle text-secondary me-2"></i>Inactive
                                                            </a></li>
                                                        </ul>
                                                    </div>
                                                    <div class="btn-group">
                                                        <button class="btn btn-outline-warning dropdown-toggle" type="button"
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-user-tag me-1"></i>Set Student Type
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="#" onclick="BulkStatusManager.bulkUpdateStatus('student_type', 'old')">
                                                                <i class="fas fa-history text-secondary me-2"></i>Old Student
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="#" onclick="BulkStatusManager.bulkUpdateStatus('student_type', 'new')">
                                                                <i class="fas fa-star text-warning me-2"></i>New Student
                                                            </a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Students Table -->
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="50">
                                                    <div class="form-check">
                                                        <input class="form-check-input student-status-checkbox" type="checkbox" id="selectAllCheckbox">
                                                    </div>
                                                </th>
                                                <th>Student</th>
                                                <th>Admission No</th>
                                                <th>Class</th>
                                                <th>Current Status</th>
                                                <th>Student Type</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="statusUpdateTableBody">
                                            ${this.renderStudentRows(students)}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>Close
                                </button>
                                <button type="button" class="btn btn-primary" onclick="BulkStatusManager.refreshData()">
                                    <i class="fas fa-sync-alt me-1"></i>Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Add to DOM
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Initialize checkboxes
            this.initializeCheckboxes();

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('bulkStatusUpdateModal'));
            modal.show();
        },

        renderStudentRows: function(students) {
            if (!students || students.length === 0) {
                return '<tr><td colspan="7" class="text-center py-4">No students found</td></tr>';
            }

            return students.map(student => {
                // Ensure student.id is valid and is a number
                const studentId = student.id ? parseInt(student.id) : null;

                // Skip if no valid ID
                if (!studentId) {
                    console.warn('Student has no valid ID:', student);
                    return '';
                }

                // Safely handle potentially null/undefined values
                const firstName = student.firstname || '';
                const lastName = student.lastname || '';
                const otherName = student.othername || '';
                const admissionNo = student.admissionNo || 'N/A';
                const schoolClass = student.schoolclass || '';
                const arm = student.arm || '';

                const activityBadge = student.student_status === 'Active'
                    ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active</span>'
                    : '<span class="badge bg-secondary"><i class="fas fa-pause-circle me-1"></i>Inactive</span>';

                const typeBadge = student.statusId == 2
                    ? '<span class="badge bg-warning text-dark"><i class="fas fa-star me-1"></i>New</span>'
                    : '<span class="badge bg-secondary"><i class="fas fa-history me-1"></i>Old</span>';

                return `
                    <tr data-student-id="${studentId}">
                        <td>
                            <div class="form-check">
                                <input class="form-check-input student-status-checkbox" type="checkbox"
                                       value="${studentId}" data-student-id="${studentId}">
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    <span class="avatar-title rounded-circle bg-primary text-white">
                                        ${firstName.charAt(0) || ''}${lastName.charAt(0) || ''}
                                    </span>
                                </div>
                                <div>
                                    <h6 class="mb-0">${Utils.escapeHtml(lastName)} ${Utils.escapeHtml(firstName)}</h6>
                                    <small class="text-muted">${Utils.escapeHtml(otherName)}</small>
                                </div>
                            </div>
                        </td>
                        <td><span class="fw-semibold">${Utils.escapeHtml(admissionNo)}</span></td>
                        <td>${Utils.escapeHtml(schoolClass)} ${Utils.escapeHtml(arm)}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                ${activityBadge}
                                <button class="btn btn-sm btn-outline-success toggle-activity"
                                        data-student-id="${studentId}"
                                        data-current="${student.student_status || 'Inactive'}"
                                        onclick="BulkStatusManager.toggleIndividualStatus(this, 'activity')">
                                    <i class="fas fa-exchange-alt"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                ${typeBadge}
                                <button class="btn btn-sm btn-outline-warning toggle-type"
                                        data-student-id="${studentId}"
                                        data-current="${student.statusId || 1}"
                                        onclick="BulkStatusManager.toggleIndividualStatus(this, 'type')">
                                    <i class="fas fa-exchange-alt"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info view-student-btn"
                                    data-student-id="${studentId}"
                                    onclick="StudentManager.viewStudent(${studentId})">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        },

        initializeCheckboxes: function() {
            const selectAll = document.getElementById('selectAllCheckbox');
            if (selectAll) {
                selectAll.removeEventListener('change', this.handleSelectAll);
                selectAll.addEventListener('change', (e) => this.handleSelectAll(e));
            }

            const selectAllStatus = document.getElementById('selectAllStatusStudents');
            if (selectAllStatus) {
                selectAllStatus.removeEventListener('change', this.handleSelectAll);
                selectAllStatus.addEventListener('change', (e) => this.handleSelectAll(e));
            }

            // Individual checkbox change
            document.querySelectorAll('.student-status-checkbox').forEach(cb => {
                cb.removeEventListener('change', () => this.updateSelectedCount());
                cb.addEventListener('change', () => this.updateSelectedCount());
            });
        },

        handleSelectAll: function(e) {
            const isChecked = e.target.checked;
            // Only select checkboxes with valid numeric values
            const checkboxes = document.querySelectorAll('.student-status-checkbox');

            checkboxes.forEach(checkbox => {
                const value = checkbox.value;
                // Only check if it has a valid numeric value
                if (value && value !== 'on' && !isNaN(parseInt(value))) {
                    checkbox.checked = isChecked;
                }
            });

            this.updateSelectedCount();
        },

        updateSelectedCount: function() {
            const selected = document.querySelectorAll('.student-status-checkbox:checked').length;
            const selectAll = document.getElementById('selectAllCheckbox');
            const selectAllStatus = document.getElementById('selectAllStatusStudents');
            const total = document.querySelectorAll('.student-status-checkbox:not([disabled])').length;

            if (selectAll) {
                selectAll.checked = selected === total && total > 0;
                selectAll.indeterminate = selected > 0 && selected < total;
            }

            if (selectAllStatus) {
                selectAllStatus.checked = selected === total && total > 0;
                selectAllStatus.indeterminate = selected > 0 && selected < total;
            }

            // Update the bulk actions button text if needed
            const bulkActionsDropdown = document.getElementById('bulkActionsDropdown');
            if (bulkActionsDropdown) {
                if (selected > 0) {
                    bulkActionsDropdown.innerHTML = `<i class="fas fa-cog me-2"></i>Actions (${selected})`;
                } else {
                    bulkActionsDropdown.innerHTML = `<i class="fas fa-cog me-2"></i>Actions`;
                }
            }
        },

        getSelectedStudentIds: function() {
            const checkedBoxes = document.querySelectorAll('.student-status-checkbox:checked');
            const ids = [];

            checkedBoxes.forEach(cb => {
                const value = cb.value;
                // Only include valid numeric IDs (not "on" or empty)
                if (value && value !== 'on' && !isNaN(parseInt(value))) {
                    ids.push(parseInt(value));
                } else {
                    console.warn('Invalid checkbox value:', value, cb);
                }
            });

            return ids;
        },

        async toggleIndividualStatus(button, type) {
            const studentId = button.dataset.studentId;
            const current = button.dataset.current;

            let newValue, updateType;

            if (type === 'activity') {
                updateType = 'activity_status';
                newValue = current === 'Active' ? 'Inactive' : 'Active';
            } else {
                updateType = 'student_type';
                newValue = current == 1 ? 'new' : 'old';
            }

            try {
                const result = await Swal.fire({
                    title: 'Confirm Update',
                    text: `Change status to ${newValue === 'new' ? 'New Student' : newValue === 'old' ? 'Old Student' : newValue}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, update',
                    cancelButtonText: 'Cancel'
                });

                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Updating...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const response = await ApiService.bulkUpdateStatus({
                        student_ids: [studentId],
                        update_type: updateType,
                        value: newValue
                    });

                    Swal.close();

                    if (response.success) {
                        Utils.showSuccess('Status updated successfully');
                        this.refreshData();
                    }
                }
            } catch (error) {
                Swal.close();
                Utils.showError('Failed to update status');
            }
        },

        async bulkUpdateStatus(updateType, value) {
            const selectedIds = this.getSelectedStudentIds();

            if (selectedIds.length === 0) {
                Utils.showError('Please select at least one student', 'No Selection');
                return;
            }

            let displayValue = value;
            if (updateType === 'student_type') {
                displayValue = value === 'old' ? 'Old Student' : 'New Student';
            }

            // Show the correct count in the confirmation dialog
            const confirmed = await Utils.showConfirm(
                'Confirm Bulk Update',
                `Update ${selectedIds.length} student(s) to "${displayValue}"?`,
                'Yes, update'
            );

            if (confirmed) {
                try {
                    Swal.fire({
                        title: 'Updating...',
                        html: `Updating ${selectedIds.length} student(s)`,
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const response = await ApiService.bulkUpdateStatus({
                        student_ids: selectedIds,
                        update_type: updateType,
                        value: value
                    });

                    Swal.close();

                    if (response.success) {
                        Utils.showSuccess(response.message);
                        this.refreshData();
                    }
                } catch (error) {
                    Swal.close();
                    console.error('Bulk update error:', error);
                    Utils.showError('Failed to update students: ' + (error.response?.data?.message || error.message));
                }
            }
        },

        async refreshData() {
            if (!AppState.bulkStatusFilters) return;

            Swal.fire({
                title: 'Refreshing',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await ApiService.getStudentsByClassAndSession(
                    AppState.bulkStatusFilters.class_id,
                    AppState.bulkStatusFilters.session_id
                );

                if (response.success) {
                    const tbody = document.getElementById('statusUpdateTableBody');
                    if (tbody) {
                        tbody.innerHTML = this.renderStudentRows(response.students);
                        this.initializeCheckboxes();
                    }

                    // Update stats
                    const stats = response.stats;
                    const cards = document.querySelectorAll('#bulkStatusUpdateModal .card .h2');
                    if (cards.length >= 4) {
                        cards[0].textContent = stats.total;
                        cards[1].textContent = stats.active;
                        cards[2].textContent = stats.inactive;
                        cards[3].textContent = stats.new_students;
                    }
                }

                Swal.close();
            } catch (error) {
                Swal.close();
                Utils.showError('Failed to refresh data');
            }
        }
    };

    // ============================================================================
    // TERM REGISTRATION MANAGEMENT FEATURE
    // ============================================================================
    const TermRegistrationManager = {
        showTermStudentsModal: function() {
            const termId = document.getElementById('term-filter')?.value;
            const sessionId = document.getElementById('session-filter').value;

            if (!termId || termId === 'all' || !sessionId || sessionId === 'all') {
                Utils.showError('Please select both a term and a session to use this feature.', 'Selection Required');
                return;
            }

            AppState.termFilters = {
                term_id: termId,
                session_id: sessionId,
                class_id: document.getElementById('schoolclass-filter').value !== 'all'
                    ? document.getElementById('schoolclass-filter').value
                    : null
            };

            Swal.fire({
                title: 'Loading Registered Students',
                html: 'Fetching term registration data...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            ApiService.getStudentsInTerm(AppState.termFilters)
            .then(response => {
                Swal.close();

                if (response.success) {
                    this.renderTermStudentsModal(response.students, response.total);
                } else {
                    Utils.showError('Failed to load students: ' + response.message);
                }
            })
            .catch(error => {
                Swal.close();
                Utils.showError('Error loading students: ' + (error.response?.data?.message || error.message));
            });
        },

        renderTermStudentsModal: function(students, total) {
            // Remove existing modal if any
            const existingModal = document.getElementById('termStudentsModal');
            if (existingModal) existingModal.remove();

            const termName = students.length > 0 ? students[0]?.term : 'Selected';
            const sessionName = students.length > 0 ? students[0]?.session : 'Selected';

            const modalHtml = `
                <div class="modal fade" id="termStudentsModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <div class="modal-content">
                            <div class="modal-header modal-header-gradient">
                                <h5 class="modal-title">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    Term Registration Management
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <!-- Header Info -->
                                <div class="alert alert-info d-flex align-items-center mb-4">
                                    <i class="fas fa-info-circle fa-2x me-3"></i>
                                    <div>
                                        <strong>${termName} Term - ${sessionName} Session</strong>
                                        <br>
                                        <span>Total Registered Students: <strong>${total}</strong></span>
                                        ${students.length > 0 && students[0]?.class ? `<br><span>Class: <strong>${students[0]?.class} ${students[0]?.arm || ''}</strong></span>` : ''}
                                    </div>
                                </div>

                                <!-- Bulk Actions -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAllTermStudents">
                                                    <label class="form-check-label fw-semibold" for="selectAllTermStudents">
                                                        Select All Students
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <button class="btn btn-danger" onclick="TermRegistrationManager.bulkRemoveFromTerm()">
                                                        <i class="fas fa-user-minus me-2"></i>
                                                        Remove Selected from Term
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Students Grid/Table -->
                                <div class="row" id="termStudentsContainer">
                                    ${this.renderStudentCards(students)}
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>Close
                                </button>
                                <button type="button" class="btn btn-primary" onclick="TermRegistrationManager.refreshData()">
                                    <i class="fas fa-sync-alt me-1"></i>Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            this.initializeTermCheckboxes();

            const modal = new bootstrap.Modal(document.getElementById('termStudentsModal'));
            modal.show();
        },

        renderStudentCards: function(students) {
            if (!students || students.length === 0) {
                return '<div class="col-12"><div class="alert alert-warning text-center">No students registered for this term</div></div>';
            }

            return students.map(student => {
                const initials = (student.firstname?.charAt(0) || '') + (student.lastname?.charAt(0) || '');
                const currentBadge = student.is_current
                    ? '<span class="badge bg-success position-absolute top-0 end-0 m-2">Current</span>'
                    : '';

                return `
                    <div class="col-md-4 col-lg-3 mb-3">
                        <div class="card h-100 shadow-sm student-term-card" data-registration-id="${student.registration_id}">
                            <div class="card-body">
                                <div class="position-relative">
                                    ${currentBadge}
                                    <div class="form-check position-absolute top-0 start-0 m-2">
                                        <input class="form-check-input term-student-checkbox" type="checkbox"
                                               value="${student.registration_id}" data-student-id="${student.student_id}">
                                    </div>
                                    <div class="text-center mb-3">
                                        <div class="avatar-xl mx-auto mb-2">
                                            <div class="avatar-title bg-soft-primary text-primary rounded-circle" style="width: 80px; height: 80px; line-height: 80px; font-size: 32px;">
                                                ${initials || 'ST'}
                                            </div>
                                        </div>
                                        <h6 class="mb-1 fw-semibold">${Utils.escapeHtml(student.fullname || '')}</h6>
                                        <p class="text-muted small mb-2">${Utils.escapeHtml(student.admissionNo || '')}</p>
                                    </div>
                                    <div class="d-flex flex-column gap-1 mb-3">
                                        <div><i class="fas fa-school text-muted me-2"></i>${Utils.escapeHtml(student.class || '')} ${Utils.escapeHtml(student.arm || '')}</div>
                                        <div><i class="fas fa-venus-mars text-muted me-2"></i>${Utils.escapeHtml(student.gender || '')}</div>
                                        <div><i class="fas fa-calendar text-muted me-2"></i>Reg: ${student.registered_at || ''}</div>
                                    </div>
                                    <button class="btn btn-outline-danger btn-sm w-100"
                                            onclick="TermRegistrationManager.removeSingleStudent(${student.registration_id}, '${Utils.escapeHtml(student.fullname)}')">
                                        <i class="fas fa-user-minus me-1"></i>Remove from Term
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        },

        initializeTermCheckboxes: function() {
            const selectAll = document.getElementById('selectAllTermStudents');
            if (selectAll) {
                selectAll.removeEventListener('change', this.handleSelectAll);
                selectAll.addEventListener('change', (e) => this.handleSelectAll(e));
            }
        },

        handleSelectAll: function(e) {
            document.querySelectorAll('.term-student-checkbox').forEach(cb => {
                cb.checked = e.target.checked;
            });
        },

        getSelectedRegistrationIds: function() {
            return Array.from(document.querySelectorAll('.term-student-checkbox:checked'))
                .map(cb => cb.value);
        },

        async removeSingleStudent(registrationId, studentName) {
            const confirmed = await Utils.showConfirm(
                'Confirm Removal',
                `Remove ${studentName} from this term registration?`,
                'Yes, remove'
            );

            if (confirmed) {
                try {
                    Swal.fire({
                        title: 'Removing...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const response = await ApiService.removeStudentFromTerm(registrationId);

                    Swal.close();

                    if (response.success) {
                        Utils.showSuccess(response.message);

                        // Remove card from UI
                        const card = document.querySelector(`.student-term-card[data-registration-id="${registrationId}"]`);
                        if (card) {
                            const cardCol = card.closest('.col-md-4');
                            if (cardCol) {
                                cardCol.remove();

                                // Update total count
                                const remaining = document.querySelectorAll('.student-term-card').length;
                                const totalEl = document.querySelector('#termStudentsModal .alert-info strong:last-child');
                                if (totalEl) {
                                    totalEl.textContent = remaining;
                                }

                                if (remaining === 0) {
                                    document.getElementById('termStudentsContainer').innerHTML =
                                        '<div class="col-12"><div class="alert alert-warning text-center">No students registered for this term</div></div>';
                                }
                            }
                        }
                    }
                } catch (error) {
                    Swal.close();
                    Utils.showError('Failed to remove student');
                }
            }
        },

        async bulkRemoveFromTerm() {
            const selectedIds = this.getSelectedRegistrationIds();

            if (selectedIds.length === 0) {
                Utils.showError('Please select at least one student to remove.', 'No Selection');
                return;
            }

            const confirmed = await Utils.showConfirm(
                'Confirm Bulk Removal',
                `Remove ${selectedIds.length} student(s) from this term registration?`,
                'Yes, remove all'
            );

            if (confirmed) {
                try {
                    Swal.fire({
                        title: 'Removing...',
                        html: `Removing ${selectedIds.length} student(s)`,
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const response = await ApiService.bulkRemoveFromTerm(selectedIds);

                    Swal.close();

                    if (response.success) {
                        Utils.showSuccess(response.message);
                        this.refreshData();
                    }
                } catch (error) {
                    Swal.close();
                    Utils.showError('Failed to remove students');
                }
            }
        },

        async refreshData() {
            if (!AppState.termFilters) return;

            Swal.fire({
                title: 'Refreshing',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await ApiService.getStudentsInTerm(AppState.termFilters);

                if (response.success) {
                    document.getElementById('termStudentsContainer').innerHTML =
                        this.renderStudentCards(response.students);
                    this.initializeTermCheckboxes();
                }

                Swal.close();
            } catch (error) {
                Swal.close();
                Utils.showError('Failed to refresh data');
            }
        }
    };

    // ============================================================================
    // REPORT MANAGER - FIXED DRAG AND DROP
    // ============================================================================
    const ReportManager = {
        sortableInstance: null,
        columnOrder: [],

        initializeReportModal: function() {
            console.log('Initializing report modal with drag and drop...');

            // Initialize column order array
            this.columnOrder = [];

            // Make sure default columns are checked
            const defaultColumns = ['admissionNo', 'lastname', 'firstname', 'class', 'gender'];
            defaultColumns.forEach(col => {
                const checkbox = document.getElementById(`col_${col}`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });

            // Initialize Sortable after a small delay to ensure DOM is ready
            setTimeout(() => {
                this.initSortable();
                this.updateColumnOrder();
                this.updatePreview();
            }, 100);
        },

        initSortable: function() {
            const container = document.getElementById('columnsContainer');
            if (!container) {
                console.error('Columns container not found!');
                return;
            }

            // Check if Sortable is available
            if (typeof Sortable === 'undefined') {
                console.error('Sortable library not loaded!');
                // Try to load Sortable dynamically
                this.loadSortableLibrary();
                return;
            }

            // Destroy existing instance if any
            if (this.sortableInstance) {
                this.sortableInstance.destroy();
            }

            console.log('Initializing Sortable on container:', container);

            // Make all draggable items actually draggable
            document.querySelectorAll('.draggable-item').forEach(item => {
                item.style.cursor = 'grab';
                item.setAttribute('draggable', 'false'); // Let Sortable handle dragging
            });

            // Create new Sortable instance
            this.sortableInstance = new Sortable(container, {
                animation: 250,
                handle: '.drag-handle',
                draggable: '.draggable-item',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                forceFallback: false,
                fallbackClass: 'sortable-fallback',
                fallbackOnBody: true,

                onStart: function(evt) {
                    console.log('Drag started', evt);
                    document.body.style.cursor = 'grabbing';
                },

                onEnd: (evt) => {
                    console.log('Drag ended', evt);
                    document.body.style.cursor = '';
                    this.updateColumnOrder();
                    this.updatePreview();
                },

                onSort: (evt) => {
                    console.log('Sorted', evt);
                }
            });

            // Add event listeners to checkboxes
            document.querySelectorAll('.column-checkbox').forEach(checkbox => {
                checkbox.removeEventListener('change', (e) => this.handleCheckboxChange(e));
                checkbox.addEventListener('change', (e) => this.handleCheckboxChange(e));
            });

            console.log('Sortable initialized successfully');
        },

        handleCheckboxChange: function(e) {
            this.updateColumnOrder();
            this.updatePreview();
        },

        loadSortableLibrary: function() {
            // Dynamically load Sortable if not available
            if (document.querySelector('script[src*="Sortable"]')) return;

            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js';
            script.onload = () => {
                console.log('Sortable library loaded dynamically');
                setTimeout(() => this.initSortable(), 100);
            };
            document.head.appendChild(script);
        },

        updateColumnOrder: function() {
            const container = document.getElementById('columnsContainer');
            if (!container) return;

            const items = container.querySelectorAll('.draggable-item');
            const orderInput = document.getElementById('columnsOrderInput');

            // Get the current order from DOM
            this.columnOrder = Array.from(items).map(item => {
                return item.dataset.column;
            });

            // Filter to only include checked columns
            const checkedInOrder = this.columnOrder.filter(col => {
                const checkbox = document.getElementById(`col_${col}`);
                return checkbox && checkbox.checked;
            });

            // Update hidden input with the order
            if (orderInput) {
                orderInput.value = checkedInOrder.join(',');
                console.log('Column order updated:', orderInput.value);
            }

            // Update visual indicators
            items.forEach((item, index) => {
                const numberBadge = item.querySelector('.order-badge');
                if (!numberBadge) {
                    const badge = document.createElement('span');
                    badge.className = 'order-badge badge bg-primary ms-2';
                    badge.style.cssText = 'float: right; font-size: 10px;';
                    badge.textContent = index + 1;
                    item.querySelector('.drag-handle').after(badge);
                } else {
                    numberBadge.textContent = index + 1;
                }
            });
        },

        updatePreview: function() {
            const previewEl = document.getElementById('columnOrderPreview');
            if (!previewEl) return;

            const checkedColumns = [];
            const checkboxes = document.querySelectorAll('.column-checkbox:checked');

            checkboxes.forEach(cb => {
                const label = document.querySelector(`label[for="${cb.id}"]`);
                if (label) {
                    checkedColumns.push(label.innerText.trim());
                }
            });

            if (checkedColumns.length === 0) {
                previewEl.innerHTML = '<span class="text-danger">⚠️ No columns selected</span>';
            } else {
                previewEl.innerHTML = checkedColumns.map(col =>
                    `<span class="badge bg-light text-dark me-1">${col}</span>`
                ).join(' <span class="text-muted">→</span> ');
            }
        },

        async generateReport() {
            const form = document.getElementById('printReportForm');
            if (!form) {
                Utils.showError('Report form not found');
                return;
            }

            // Get selected columns
            const selectedColumns = Array.from(form.querySelectorAll('.column-checkbox:checked'))
                .map(cb => cb.value);

            if (selectedColumns.length === 0) {
                Utils.showError('Please select at least one column to display in the report', 'No Columns Selected');
                return;
            }

            // Build params
            const formData = new FormData(form);
            const params = {};

            for (let [key, value] of formData.entries()) {
                if (key === 'columns[]') {
                    if (!params.columns) {
                        params.columns = [];
                    }
                    params.columns.push(value);
                } else if (key === 'columns_order') {
                    // Get the latest order from hidden input
                    const orderInput = document.getElementById('columnsOrderInput');
                    if (orderInput && orderInput.value) {
                        params.columns_order = orderInput.value;
                    }
                } else if (value) {
                    params[key] = value;
                }
            }

            // Format columns as comma-separated string
            if (params.columns && Array.isArray(params.columns)) {
                params.columns = params.columns.join(',');
            }

            // Add format and orientation
            if (!params.format) {
                const formatRadio = form.querySelector('input[name="format"]:checked');
                params.format = formatRadio ? formatRadio.value : 'pdf';
            }

            if (!params.orientation) {
                const orientationSelect = form.querySelector('#orientation');
                params.orientation = orientationSelect ? orientationSelect.value : 'portrait';
            }

            // Add header options
            params.include_header = form.querySelector('input[name="include_header"]')?.checked ? '1' : '0';
            params.include_logo = form.querySelector('input[name="include_logo"]')?.checked ? '1' : '0';
            params.exclude_photos = '0';

            console.log('Report params:', params);

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('printStudentReportModal'));
            if (modal) modal.hide();

            // Show loading
            Swal.fire({
                title: 'Generating Report',
                html: 'Please wait while your report is being generated...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await axios({
                    method: 'GET',
                    url: '/students/report',
                    params: params,
                    responseType: 'blob',
                    timeout: 120000
                });

                Swal.close();

                // Create download link
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;

                // Get filename
                let filename = 'student-report.pdf';
                const contentDisposition = response.headers['content-disposition'];
                if (contentDisposition) {
                    const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                    if (filenameMatch && filenameMatch[1]) {
                        filename = filenameMatch[1].replace(/['"]/g, '');
                    }
                } else {
                    const date = new Date();
                    const formattedDate = date.toISOString().split('T')[0];
                    filename = `student-report-${formattedDate}.${params.format === 'excel' ? 'xlsx' : 'pdf'}`;
                }

                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);

                Utils.showSuccess(`Report generated successfully: ${filename}`, 'Success');

            } catch (error) {
                Swal.close();
                console.error('Error generating report:', error);

                let errorMessage = 'Failed to generate report.';
                if (error.response?.data instanceof Blob) {
                    try {
                        const text = await error.response.data.text();
                        const json = JSON.parse(text);
                        errorMessage = json.message || errorMessage;
                    } catch (e) {}
                } else if (error.response?.data?.message) {
                    errorMessage = error.response.data.message;
                }

                Utils.showError(errorMessage, 'Report Generation Failed');
            }
        }
    };

    // ============================================================================
    // EVENT DELEGATION
    // ============================================================================
    const EventDelegationManager = {
        initialize: function() {
            document.addEventListener('click', this.handleClick);
            this.initializeGlobalButtons();
        },

        handleClick: function(e) {
            const viewBtn = e.target.closest('.view-student-btn');
            if (viewBtn) {
                e.preventDefault();
                const studentId = viewBtn.dataset.studentId;
                if (studentId) {
                    StudentManager.viewStudent(studentId);
                }
                return;
            }

            const editBtn = e.target.closest('.edit-student-btn');
            if (editBtn) {
                e.preventDefault();
                const studentId = editBtn.dataset.studentId;
                if (studentId) {
                    StudentManager.editStudent(studentId);
                }
                return;
            }

            const deleteBtn = e.target.closest('.delete-student-btn');
            if (deleteBtn) {
                e.preventDefault();
                const studentId = deleteBtn.dataset.studentId;
                if (studentId) {
                    StudentManager.deleteStudent(studentId);
                }
                return;
            }
        },

        initializeGlobalButtons: function() {
            const deleteMultipleBtn = document.getElementById('deleteMultipleBtn');
            if (deleteMultipleBtn) {
                deleteMultipleBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    StudentManager.deleteMultiple();
                });
            }

            const updateTermBtn = document.getElementById('updateCurrentTermBtn');
            if (updateTermBtn) {
                updateTermBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    CurrentTermManager.showUpdateCurrentTermModal();
                });
            }

            const confirmUpdateBtn = document.getElementById('confirmUpdateCurrentTerm');
            if (confirmUpdateBtn) {
                confirmUpdateBtn.addEventListener('click', () => CurrentTermManager.updateCurrentTerm());
            }

            const generateReportBtn = document.getElementById('generateReportBtn');
            if (generateReportBtn) {
                generateReportBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    ReportManager.generateReport();
                });
            }

            const tableViewBtn = document.getElementById('tableViewBtn');
            if (tableViewBtn) {
                tableViewBtn.addEventListener('click', () => RenderManager.toggleView('table'));
            }

            const cardViewBtn = document.getElementById('cardViewBtn');
            if (cardViewBtn) {
                cardViewBtn.addEventListener('click', () => RenderManager.toggleView('card'));
            }

            // NEW BUTTONS
            const bulkStatusBtn = document.getElementById('bulkStatusBtn');
            if (bulkStatusBtn) {
                bulkStatusBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    BulkStatusManager.showUpdateStatusModal();
                });
            }

            const manageTermBtn = document.getElementById('manageTermBtn');
            if (manageTermBtn) {
                manageTermBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    TermRegistrationManager.showTermStudentsModal();
                });
            }
        }
    };

    // ============================================================================
    // FORM SUBMISSION MANAGER
    // ============================================================================
    const FormSubmissionManager = {
        initializeAddForm: function() {
            const addForm = document.getElementById('addStudentForm');
            if (!addForm) return;

            addForm.removeEventListener('submit', this.handleAddSubmit);
            addForm.addEventListener('submit', (e) => this.handleAddSubmit(e));
        },

        async handleAddSubmit(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            try {
                Swal.fire({
                    title: 'Saving...',
                    text: 'Please wait while student is being registered.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const response = await axios.post(form.action, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });

                Swal.close();

                if (response.data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addStudentModal'));
                    if (modal) modal.hide();
                    await StudentManager.fetchStudents();
                    Utils.showSuccess(response.data.message || 'Student registered successfully.');

                    form.reset();
                    const avatarImg = document.getElementById('addStudentAvatar');
                    if (avatarImg) {
                        avatarImg.src = 'https://via.placeholder.com/120x120/667eea/ffffff?text=Photo';
                    }
                }
            } catch (error) {
                Swal.close();
                Utils.showError('Failed to save student.');
            }
        },

        initializeEditForm: function() {
            const editForm = document.getElementById('editStudentForm');
            if (!editForm) return;

            editForm.removeEventListener('submit', this.handleEditSubmit);
            editForm.addEventListener('submit', (e) => this.handleEditSubmit(e));
        },

        async handleEditSubmit(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            formData.append('_method', 'PATCH');

            try {
                Swal.fire({
                    title: 'Updating...',
                    text: 'Please wait while student is being updated.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const response = await axios.post(form.action, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });

                Swal.close();

                if (response.data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editStudentModal'));
                    if (modal) modal.hide();
                    await StudentManager.fetchStudents();
                    Utils.showSuccess(response.data.message || 'Student updated successfully.');
                }
            } catch (error) {
                Swal.close();
                Utils.showError('Failed to update student.');
            }
        }
    };

    // ============================================================================
    // INITIALIZATION
    // ============================================================================
    function initializeApplication() {
        Utils.log('Initializing Student Management System...');

        if (!Utils.ensureAxios()) {
            Utils.showError('Failed to initialize application. Please refresh the page.');
            return;
        }

        // Initialize all managers
        EventDelegationManager.initialize();
        FilterManager.initializeFilters();
        StateLGAManager.initializeAddStateDropdown();
        StateLGAManager.initializeEditStateDropdown();
        AdmissionNumberManager.updateAdmissionNumber('');
        AdmissionNumberManager.updateAdmissionNumber('edit');
        SelectionManager.initializeCheckboxes();
        PaginationManager.initializePerPageSelector();
        FormSubmissionManager.initializeAddForm();
        FormSubmissionManager.initializeEditForm();

        const reportModal = document.getElementById('printStudentReportModal');
        if (reportModal) {
            reportModal.addEventListener('show.bs.modal', () => {
                setTimeout(() => ReportManager.initializeReportModal(), 100);
            });
        }

        StudentManager.fetchStudents();

        // Expose managers to window for onclick handlers
        window.BulkStatusManager = BulkStatusManager;
        window.TermRegistrationManager = TermRegistrationManager;
        window.StudentManager = StudentManager;

        Utils.log('Student Management System initialized successfully');
    }

    // ============================================================================
    // EXPORT GLOBAL FUNCTIONS
    // ============================================================================
    window.fetchStudents = () => StudentManager.fetchStudents();
    window.filterData = () => FilterManager.applyFilters();
    window.resetFilters = () => FilterManager.resetFilters();
    window.clearSearch = () => FilterManager.clearSearch();
    window.viewStudent = (id) => StudentManager.viewStudent(id);
    window.editStudent = (id) => StudentManager.editStudent(id);
    window.deleteStudent = (id) => StudentManager.deleteStudent(id);
    window.deleteMultiple = () => StudentManager.deleteMultiple();
    window.toggleView = (view) => RenderManager.toggleView(view);
    window.updateAdmissionNumber = (prefix) => AdmissionNumberManager.updateAdmissionNumber(prefix);
    window.toggleAdmissionInput = (prefix) => AdmissionNumberManager.toggleAdmissionInput(prefix);
    window.previewImage = function(input, targetId = 'addStudentAvatar') {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            const target = document.getElementById(targetId);
            if (target) {
                target.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);
    };
    window.calculateAge = function(dateOfBirth, ageInputId) {
        const age = Utils.calculateAge(dateOfBirth);
        const ageInput = document.getElementById(ageInputId);
        if (ageInput) ageInput.value = age;
    };
    window.generateReport = () => ReportManager.generateReport();
    window.showUpdateCurrentTermModal = (id) => CurrentTermManager.showUpdateCurrentTermModal(id);
    window.updateCurrentTerm = () => CurrentTermManager.updateCurrentTerm();
    window.getSelectedStudentIds = () => SelectionManager.getSelectedStudentIds();
    window.refreshTermHistory = () => {
        if (ViewModalManager.currentStudentId) {
            ViewModalManager.fetchStudentTermInfo(ViewModalManager.currentStudentId);
        }
    };
    window.callNumber = function(phoneElementId) {
        const phone = document.getElementById(phoneElementId)?.textContent;
        if (phone && phone !== '-') {
            window.location.href = `tel:${phone}`;
        }
    };
    window.sendSMS = function(phoneElementId) {
        const phone = document.getElementById(phoneElementId)?.textContent;
        if (phone && phone !== '-') {
            window.location.href = `sms:${phone}`;
        }
    };
    window.sendEmail = function(emailElementId) {
        const email = document.getElementById(emailElementId)?.textContent;
        if (email && email !== '-') {
            window.location.href = `mailto:${email}`;
        }
    };
    window.editStudentFromView = function() {
        if (ViewModalManager.currentStudentId) {
            const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewStudentModal'));
            if (viewModal) viewModal.hide();
            StudentManager.editStudent(ViewModalManager.currentStudentId);
        }
    };
    window.printStudentProfile = function() {
        window.print();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeApplication);
    } else {
        initializeApplication();
    }

})();
</script>
@endsection
