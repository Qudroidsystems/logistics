@extends('layouts.master')
@section('content')
<?php
use Spatie\Permission\Models\Role;
?>


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Students by Status Chart
            var ctx = document.getElementById("studentsByStatusChart").getContext("2d");
            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: ["Old Student", "New Student"],
                    datasets: [{
                        label: "Students by Status",
                        data: @json(array_values($status_counts)),
                        backgroundColor: ["#4e73df", "#1cc88a"],
                        borderColor: ["#4e73df", "#1cc88a"],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: "Number of Students"
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: "Status"
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: "top"
                        }
                    }
                }
            });

            // Image Preview for Add Student Modal
            document.getElementById('avatar').addEventListener('change', function(event) {
                const file = event.target.files[0];
                const preview = document.getElementById('addStudentAvatar');
                if (file) {
                    // Check file size (2MB = 2 * 1024 * 1024 bytes)
                    if (file.size > 2 * 1024 * 1024) {
                        Swal.fire({
                            title: "Error!",
                            text: "File size exceeds 2MB limit.",
                            icon: "error",
                            confirmButtonClass: "btn btn-info",
                            buttonsStyling: false
                        });
                        event.target.value = ''; // Clear the input
                        preview.style.display = 'none';
                        return;
                    }
                    // Check file type
                    const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
                    if (!allowedTypes.includes(file.type)) {
                        Swal.fire({
                            title: "Error!",
                            text: "Only PNG, JPG, and JPEG files are allowed.",
                            icon: "error",
                            confirmButtonClass: "btn btn-info",
                            buttonsStyling: false
                        });
                        event.target.value = ''; // Clear the input
                        preview.style.display = 'none';
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.src = '{{ asset('theme/layouts/assets/media/avatars/blank.png') }}';
                    preview.style.display = 'none';
                }
            });

            // Image Preview for Edit Student Modal
            document.getElementById('editAvatar').addEventListener('change', function(event) {
                const file = event.target.files[0];
                const preview = document.getElementById('editStudentAvatar');
                if (file) {
                    // Check file size (2MB = 2 * 1024 * 1024 bytes)
                    if (file.size > 2 * 1024 * 1024) {
                        Swal.fire({
                            title: "Error!",
                            text: "File size exceeds 2MB limit.",
                            icon: "error",
                            confirmButtonClass: "btn btn-info",
                            buttonsStyling: false
                        });
                        event.target.value = ''; // Clear the input
                        preview.src = '{{ asset('theme/layouts/assets/media/avatars/blank.png') }}';
                        return;
                    }
                    // Check file type
                    const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
                    if (!allowedTypes.includes(file.type)) {
                        Swal.fire({
                            title: "Error!",
                            text: "Only PNG, JPG, and JPEG files are allowed.",
                            icon: "error",
                            confirmButtonClass: "btn btn-info",
                            buttonsStyling: false
                        });
                        event.target.value = ''; // Clear the input
                        preview.src = '{{ asset('theme/layouts/assets/media/avatars/blank.png') }}';
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Reset to the original avatar or blank if no new file is selected
                    preview.src = preview.getAttribute('data-original-src') || '{{ asset('theme/layouts/assets/media/avatars/blank.png') }}';
                }
            });

            // Store original avatar src when edit modal is opened
            document.querySelectorAll('.edit-item-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const avatarImg = document.getElementById('editStudentAvatar');
                    avatarImg.setAttribute('data-original-src', avatarImg.src);
                });
            });
        });
    </script>
@endsection
