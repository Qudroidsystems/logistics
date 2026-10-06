console.log("mocksubjectvetting.init.js is loaded and executing!");

// Debug counter for chart initialization
let chartInitCount = 0;

// Verify dependencies
try {
    if (typeof axios === 'undefined') throw new Error("Axios is not loaded");
    if (typeof Swal === 'undefined') throw new Error("SweetAlert2 is not loaded");
    if (typeof bootstrap === 'undefined') throw new Error("Bootstrap is not loaded");
    if (typeof List === 'undefined') throw new Error("List.js is not loaded");
    if (typeof Chart === 'undefined') throw new Error("Chart.js is not loaded");
    console.log("All dependencies loaded successfully");
} catch (error) {
    console.error("Dependency check failed:", error);
}

// Set Axios CSRF token globally
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
if (!csrfToken) console.warn("CSRF token not found");

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Check all checkbox
const checkAll = document.getElementById("checkAll");
if (checkAll) {
    checkAll.addEventListener("click", function () {
        console.log("CheckAll clicked");
        const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
        checkboxes.forEach((checkbox) => {
            checkbox.checked = this.checked;
            const row = checkbox.closest("tr");
            row.classList.toggle("table-active", this.checked);
        });
        const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
        const removeActions = document.getElementById("remove-actions");
        if (removeActions) {
            removeActions.classList.toggle("d-none", checkedCount === 0);
        }
    });
}

// Form fields
const addIdField = document.getElementById("add-id-field");
const addUserIdField = document.getElementById("userid");
const addSessionIdField = document.getElementById("sessionid");
const editIdField = document.getElementById("edit-id-field");
const editUserIdField = document.getElementById("edit-userid");
const editTermIdField = document.getElementById("edit-termid");
const editSessionIdField = document.getElementById("edit-sessionid");
const editSubjectClassIdField = document.getElementById("edit-subjectclassid");
const editStatusField = document.getElementById("edit-status");

// Initialize Chart.js bar chart
let mockVettingStatusChart;

function initializeMockVettingStatusChart() {
    chartInitCount++;
    console.log(`Attempting to initialize mock chart (attempt #${chartInitCount}) with data:`, window.mockVettingStatusCounts);
    
    const ctx = document.getElementById('mockVettingStatusChart')?.getContext('2d');
    if (!ctx) {
        console.error("Mock chart canvas not found");
        return;
    }

    if (!window.mockVettingStatusCounts) {
        console.warn("mockVettingStatusCounts is undefined, using default data");
        window.mockVettingStatusCounts = { pending: 0, completed: 0, rejected: 0 };
    }

    if (mockVettingStatusChart) {
        console.log("Destroying existing mock chart instance");
        mockVettingStatusChart.destroy();
    }

    try {
        mockVettingStatusChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Pending', 'Completed', 'Rejected'],
                datasets: [{
                    label: 'Mock Vetting Assignments',
                    data: [
                        window.mockVettingStatusCounts.pending || 0,
                        window.mockVettingStatusCounts.completed || 0,
                        window.mockVettingStatusCounts.rejected || 0
                    ],
                    backgroundColor: ['#dc3545', '#28a745', '#ffc107'],
                    borderColor: ['#c82333', '#218838', '#e0a800'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: Math.max(10, (window.mockVettingStatusCounts.pending || 0) + 2),
                        ticks: { stepSize: 1 },
                        title: { display: true, text: 'Number of Mock Assignments' }
                    },
                    x: { title: { display: true, text: 'Status' } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}`;
                            }
                        }
                    }
                }
            }
        });
        console.log("Mock vetting status chart initialized successfully");
    } catch (error) {
        console.error("Failed to initialize mock chart:", error);
    }
}

// Checkbox handling
function initializeCheckboxes() {
    const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
    checkboxes.forEach((checkbox) => {
        checkbox.removeEventListener("change", handleCheckboxChange);
        checkbox.addEventListener("change", handleCheckboxChange);
    });
}

function handleCheckboxChange(e) {
    console.log("Checkbox changed:", e.target.checked);
    const row = e.target.closest("tr");
    row.classList.toggle("table-active", e.target.checked);
    const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
    const removeActions = document.getElementById("remove-actions");
    if (removeActions) {
        removeActions.classList.toggle("d-none", checkedCount === 0);
    }
    const allCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]');
    if (checkAll) {
        checkAll.checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCount;
    }
}

// Create button
const createButton = document.querySelector('.add-btn');
if (createButton) {
    createButton.addEventListener('click', function (e) {
        e.preventDefault();
        console.log("Create Mock Subject Vetting button clicked");
        try {
            const modal = new bootstrap.Modal(document.getElementById("addMockSubjectVettingModal"));
            modal.show();
            console.log("Add mock modal opened");
        } catch (error) {
            console.error("Error opening add mock modal:", error);
            Swal.fire({
                position: "center",
                icon: "error",
                title: "Error opening modal",
                text: "Please ensure Bootstrap is loaded and try again.",
                showConfirmButton: true
            });
        }
    });
}

// Event delegation
document.addEventListener('click', function (e) {
    const editBtn = e.target.closest('.edit-item-btn');
    const removeBtn = e.target.closest('.remove-item-btn');
    const image = e.target.closest('.staff-image');
    
    if (editBtn) {
        handleEditClick(e, editBtn);
    } else if (removeBtn) {
        handleRemoveClick(e, removeBtn);
    } else if (image) {
        handleImageClick(e, image);
    }
});

// Image preview
function handleImageClick(e, image) {
    e.preventDefault();
    console.log("Image clicked, attributes:", {
        imageUrl: image.getAttribute('data-image'),
        teacherName: image.getAttribute('data-teachername'),
        fileExists: image.getAttribute('data-file-exists'),
        defaultExists: image.getAttribute('data-default-exists'),
        picture: image.getAttribute('data-picture')
    });
    
    const imageUrl = image.getAttribute('data-image');
    const teacherName = image.getAttribute('data-teachername');
    const fileExists = image.getAttribute('data-file-exists') === 'true';
    const defaultExists = image.getAttribute('data-default-exists') === 'true';
    
    const previewImage = document.getElementById('preview-image');
    const previewTeacherName = document.getElementById('preview-teachername');
    
    if (previewImage && previewTeacherName) {
        if (fileExists || (image.getAttribute('data-picture') === 'none' && defaultExists)) {
            previewImage.src = imageUrl;
            previewTeacherName.textContent = teacherName || 'Unknown Staff';
        } else {
            previewImage.src = '/storage/staff_avatars/unnamed.jpg';
            previewTeacherName.textContent = teacherName || 'Unknown Staff';
            console.warn('Image not found, using default');
        }
        
        previewImage.onerror = function() {
            this.src = '/storage/staff_avatars/unnamed.jpg';
            console.error('Preview image failed to load, falling back to default');
        };
        
        try {
            const modal = new bootstrap.Modal(document.getElementById('imageViewModal'));
            modal.show();
            console.log("Image preview modal opened");
        } catch (error) {
            console.error("Error opening image preview modal:", error);
            Swal.fire({
                position: "center",
                icon: "error",
                title: "Error opening image preview",
                text: "Failed to open modal. Please ensure Bootstrap is loaded.",
                showConfirmButton: true
            });
        }
    } else {
        console.error("Preview elements not found");
        Swal.fire({
            position: "center",
            icon: "error",
            title: "Error opening image preview",
            text: "Preview elements not found.",
            showConfirmButton: true
        });
    }
}

// Delete single assignment
function handleRemoveClick(e, button) {
    e.preventDefault();
    console.log("Remove button clicked");
    
    const itemId = button.closest("tr").querySelector(".id")?.getAttribute("data-id");
    const deleteUrl = button.closest("tr").getAttribute("data-url");
    
    if (!itemId || !deleteUrl) {
        console.error("Item ID or delete URL not found");
        return;
    }
    
    const modal = new bootstrap.Modal(document.getElementById("deleteRecordModal"));
    modal.show();
    console.log("Delete mock modal opened");

    const deleteButton = document.getElementById("delete-record");
    if (deleteButton) {
        deleteButton.onclick = null;
        deleteButton.onclick = function () {
            console.log("Deleting mock subject vetting assignment:", itemId);
            
            axios.delete(deleteUrl)
                .then(function (response) {
                    console.log("Delete success:", response.data);
                    Swal.fire({
                        position: "center",
                        icon: "success",
                        title: response.data.message || "Mock Subject Vetting assignment deleted successfully!",
                        showConfirmButton: false,
                        timer: 2000,
                        showCloseButton: true
                    });
                    modal.hide();
                    setTimeout(() => refreshTable(), 500);
                })
                .catch(function (error) {
                    console.error("Delete error:", error.response?.data || error);
                    Swal.fire({
                        position: "center",
                        icon: "error",
                        title: "Error deleting mock subject vetting assignment",
                        text: error.response?.data?.message || "An error occurred",
                        showConfirmButton: true
                    });
                    modal.hide();
                });
        };
    }
}

// Edit assignment
function handleEditClick(e, button) {
    e.preventDefault();
    console.log("Edit button clicked");
    
    const itemId = button.closest("tr").querySelector(".id")?.getAttribute("data-id");
    const tr = button.closest("tr");
    
    if (!itemId) {
        console.error("Item ID not found");
        return;
    }
    
    const vettingUserId = tr.querySelector(".vetting_username")?.getAttribute("data-vetting_userid") || "";
    const subjectClassId = tr.querySelector(".sclass")?.getAttribute("data-schoolclassid") || "";
    const termId = tr.querySelector(".termname")?.getAttribute("data-termid") || "";
    const sessionId = tr.querySelector(".sessionname")?.getAttribute("data-sessionid") || "";
    const status = tr.querySelector(".status")?.textContent || "pending";
    
    console.log("Edit data:", { itemId, vettingUserId, subjectClassId, termId, sessionId, status });
    
    if (editIdField) editIdField.value = itemId;
    if (editUserIdField) editUserIdField.value = vettingUserId;
    if (editSubjectClassIdField) editSubjectClassIdField.value = subjectClassId;
    if (editTermIdField) editTermIdField.value = termId;
    if (editSessionIdField) editSessionIdField.value = sessionId;
    if (editStatusField) editStatusField.value = status;

    try {
        const modal = new bootstrap.Modal(document.getElementById("editMockModal"));
        modal.show();
        console.log("Edit mock modal opened with data populated");
    } catch (error) {
        console.error("Error opening edit mock modal:", error);
        Swal.fire({
            position: "center",
            icon: "error",
            title: "Error opening edit modal",
            text: "Please try again or contact support.",
            showConfirmButton: true
        });
    }
}

// Clear form fields
function clearAddFields() {
    if (addIdField) addIdField.value = "";
    if (addUserIdField) addUserIdField.value = "";
    if (addSessionIdField) addSessionIdField.value = "";
    document.querySelectorAll('#addMockSubjectVettingModal input[name="termid[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.querySelectorAll('#addMockSubjectVettingModal input[name="subjectclassid[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function clearEditFields() {
    if (editIdField) editIdField.value = "";
    if (editUserIdField) editUserIdField.value = "";
    if (editTermIdField) editTermIdField.value = "";
    if (editSessionIdField) editSessionIdField.value = "";
    if (editSubjectClassIdField) editSubjectClassIdField.value = "";
    if (editStatusField) editStatusField.value = "pending";
}

// Delete multiple assignments
function deleteMultiple() {
    console.log("Delete multiple mock triggered");
    
    const ids_array = [];
    const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]:checked');
    
    checkboxes.forEach((checkbox) => {
        const id = checkbox.closest("tr").querySelector(".id")?.getAttribute("data-id");
        if (id) ids_array.push(id);
    });
    
    if (ids_array.length === 0) {
        Swal.fire({
            title: "Please select at least one checkbox",
            confirmButtonClass: "btn btn-info",
            buttonsStyling: false,
            showCloseButton: true
        });
        return;
    }
    
    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn btn-primary w-xs me-2 mt-2",
        cancelButtonClass: "btn btn-danger w-xs mt-2",
        confirmButtonText: "Yes, delete it!",
        buttonsStyling: false,
        showCloseButton: true
    }).then((result) => {
        if (result.isConfirmed) {
            Promise.all(ids_array.map((id) => axios.delete(`/mocksubjectvetting/${id}`)))
                .then(() => {
                    Swal.fire({
                        title: "Deleted!",
                        text: "Your mock subject vetting assignments have been deleted.",
                        icon: "success",
                        confirmButtonClass: "btn btn-info w-xs mt-2",
                        buttonsStyling: false
                    });
                    setTimeout(() => refreshTable(), 1000);
                })
                .catch((error) => {
                    console.error("Bulk delete error:", error);
                    Swal.fire({
                        title: "Error!",
                        text: error.response?.data?.message || "Failed to delete mock subject vetting assignments",
                        icon: "error",
                        confirmButtonClass: "btn btn-info w-xs mt-2",
                        buttonsStyling: false
                    });
                });
        }
    });
}

// Initialize List.js
let mockSubjectVettingList;

function initializeListJS() {
    const mockSubjectVettingListContainer = document.getElementById('kt_mock_subject_vetting_table');
    const hasRows = document.querySelectorAll('#kt_mock_subject_vetting_table tbody tr:not(.noresult)').length > 0;
    
    if (mockSubjectVettingListContainer && hasRows) {
        try {
            if (mockSubjectVettingList) {
                mockSubjectVettingList.clear();
            }
            
            mockSubjectVettingList = new List('mockSubjectVettingList', {
                valueNames: ['sn', 'vetting_username', 'subjectname', 'sclass', 'schoolarm', 'teachername', 'termname', 'sessionname', 'status', 'datereg'],
                page: 10,
                pagination: {
                    innerWindow: 2,
                    outerWindow: 1,
                    left: 0,
                    right: 0,
                    paginationClass: "listjs-pagination"
                },
                listClass: 'list'
            });
            
            console.log("List.js initialized with pagination for mock");
            
            mockSubjectVettingList.on('updated', function () {
                const totalRecords = mockSubjectVettingList.items.length;
                const visibleRecords = mockSubjectVettingList.visibleItems.length;
                const showingRecords = Math.min(visibleRecords, mockSubjectVettingList.page);
                const currentPage = Math.ceil((mockSubjectVettingList.i - 1) / mockSubjectVettingList.page) + 1;
                
                document.getElementById('showing-records').textContent = showingRecords;
                document.getElementById('total-records').textContent = totalRecords;
                document.getElementById('total-records-footer').textContent = totalRecords;
                
                const pagination = document.querySelector('.listjs-pagination');
                if (pagination) {
                    pagination.querySelectorAll('.page').forEach(page => {
                        page.classList.remove('active');
                        if (parseInt(page.textContent) === currentPage) {
                            page.classList.add('active');
                        }
                    });
                }
                
                const noResultRow = document.querySelector('.noresult');
                if (noResultRow) {
                    noResultRow.style.display = visibleRecords === 0 ? 'block' : 'none';
                }
                
                initializeCheckboxes();
            });
            
            mockSubjectVettingList.update();
            
        } catch (error) {
            console.error("List.js initialization failed for mock:", error);
        }
    } else {
        console.warn("No mock subject vetting assignments available for List.js initialization");
        document.getElementById('showing-records').textContent = 0;
        document.getElementById('total-records').textContent = 0;
        document.getElementById('total-records-footer').textContent = 0;
    }
}

// Filter data
function filterData() {
    const searchInput = document.querySelector(".search-box input.search");
    const searchValue = searchInput?.value || "";
    
    console.log("Filtering mock with search:", searchValue);
    
    if (mockSubjectVettingList) {
        mockSubjectVettingList.search(searchValue, ['sn', 'vetting_username', 'subjectname', 'sclass', 'schoolarm', 'teachername', 'termname', 'sessionname', 'status']);
        mockSubjectVettingList.update();
    }
}

// Refresh table and chart
let isRefreshing = false;

function refreshTable() {
    if (isRefreshing) {
        console.log("Refresh table skipped: Previous request still in progress");
        return;
    }

    isRefreshing = true;
    console.log("Refreshing mock table and chart via AJAX...");
    
    axios.get('/mocksubjectvetting', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.data || !response.data.mocksubjectvettings) {
            throw new Error('Invalid response data structure');
        }

        console.log("Refresh response:", response.data);

        // Clear existing items
        if (mockSubjectVettingList) {
            mockSubjectVettingList.clear();
            
            // Add new items
            response.data.mocksubjectvettings.forEach((item, index) => {
                mockSubjectVettingList.add({
                    sn: index + 1,
                    vetting_username: item.vetting_username,
                    subjectname: `${item.subjectname} ${item.subjectcode ? `(${item.subjectcode})` : ''}`,
                    sclass: item.sclass,
                    schoolarm: item.schoolarm || '',
                    teachername: item.teachername || '',
                    termname: item.termname,
                    sessionname: item.sessionname,
                    status: item.status,
                    datereg: item.updated_at.split(' ')[0],
                    className: item.status === 'completed' ? 'table-success' :
                               item.status === 'pending' ? 'table-danger' :
                               item.status === 'rejected' ? 'table-warning' : ''
                });
            });
            
            // Update the list
            mockSubjectVettingList.update();
            
            // Update chart data
            window.mockVettingStatusCounts = response.data.statusCounts || { 
                pending: 0, 
                completed: 0, 
                rejected: 0 
            };
            
            // Reinitialize chart
            initializeMockVettingStatusChart();
            
            // Reinitialize checkboxes
            initializeCheckboxes();
        }
    })
    .catch(error => {
        console.error("Error refreshing mock table and chart:", error);
        
        let errorMessage = "An error occurred while refreshing the mock data.";
        if (error.response) {
            errorMessage = error.response.data.message || errorMessage;
        }
        
        Swal.fire({
            icon: "error",
            title: "Error refreshing mock data",
            text: errorMessage,
            showConfirmButton: true
        });
    })
    .finally(() => {
        isRefreshing = false;
    });
}

// Add assignment
const addMockSubjectVettingForm = document.getElementById("add-mocksubjectvetting-form");
if (addMockSubjectVettingForm) {
    addMockSubjectVettingForm.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Add mock form submitted at", new Date().toISOString());

        const errorMsg = document.getElementById("alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");

        const formData = new FormData(addMockSubjectVettingForm);
        const userId = formData.get('userid');
        const termIds = formData.getAll('termid[]');
        const sessionId = formData.get('sessionid');
        const subjectClassIds = [...new Set(formData.getAll('subjectclassid[]'))];

        console.log("Form data (before sending):", { userId, termIds, sessionId, subjectClassIds });

        if (!userId || userId === "") {
            if (errorMsg) {
                errorMsg.innerHTML = "Please select a staff member";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        if (termIds.length === 0) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please select at least one term";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        if (!sessionId || sessionId === "") {
            if (errorMsg) {
                errorMsg.innerHTML = "Please select a session";
                errorMsg.classList.remove("d-none");
            }
            return;
        }
        
        if (subjectClassIds.length === 0) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please select at least one subject-class";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        const submitBtn = document.getElementById("add-btn");
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = "Adding...";
        }

        console.log("Sending add mock request:", { userId, termIds, sessionId, subjectClassIds });

        axios.post('/mocksubjectvetting', {
            userid: userId,
            termid: termIds,
            sessionid: sessionId,
            subjectclassid: subjectClassIds
        }, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(function (response) {
            console.log("Add mock success:", response.data);
            
            const modalElement = document.getElementById("addMockSubjectVettingModal");
            const modal = bootstrap.Modal.getInstance(modalElement);
            
            if (modal) {
                modal.hide();
            }
            
            setTimeout(() => {
                Swal.fire({
                    position: "center",
                    icon: "success",
                    title: response.data.message || "Mock Subject Vetting assignment(s) added successfully!",
                    showConfirmButton: false,
                    timer: 2000,
                    showCloseButton: true
                });
                
                setTimeout(() => refreshTable(), 500);
            }, 300);
        })
        .catch(function (error) {
            console.error("Add mock error:", error.response?.data || error);
            
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = "Add Mock Subject Vetting Assignment";
            }
            
            if (errorMsg) {
                errorMsg.innerHTML = error.response?.data?.message ||
                    Object.values(error.response?.data?.errors || {}).flat().join(", ") ||
                    "Error adding mock subject vetting assignment";
                errorMsg.classList.remove("d-none");
            }
        });
    });
}

// Edit assignment
const editMockSubjectVettingForm = document.getElementById("edit-mocksubjectvetting-form");
if (editMockSubjectVettingForm) {
    editMockSubjectVettingForm.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Edit mock form submitted");
        
        const errorMsg = document.getElementById("edit-alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");
        
        const formData = new FormData(editMockSubjectVettingForm);
        const userId = formData.get('userid');
        const termId = formData.get('termid');
        const sessionId = formData.get('sessionid');
        const subjectClassId = formData.get('subjectclassid');
        const status = formData.get('status');
        const id = editIdField?.value;
        
        if (!id || !userId || !termId || !sessionId || !subjectClassId || !status) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please fill all required fields";
                errorMsg.classList.remove("d-none");
                console.warn("Form validation failed: Invalid fields");
            }
            return;
        }
        
        const submitBtn = document.getElementById("update-btn");
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = "Updating...";
        }
        
        console.log("Sending edit mock request:", { id, userId, termId, sessionId, subjectClassId, status });
        
        axios.put(`/mocksubjectvetting/${id}`, { userid: userId, termid: termId, sessionid: sessionId, subjectclassid: subjectClassId, status })
            .then(function (response) {
                console.log("Edit mock success:", response.data);
                
                const modalElement = document.getElementById("editMockModal");
                const modal = bootstrap.Modal.getInstance(modalElement);
                
                if (modal) {
                    modal.hide();
                }
                
                setTimeout(() => {
                    Swal.fire({
                        position: "center",
                        icon: "success",
                        title: response.data.message || "Mock Subject Vetting assignment updated successfully!",
                        showConfirmButton: false,
                        timer: 2000,
                        showCloseButton: true
                    });
                    
                    setTimeout(() => refreshTable(), 500);
                }, 300);
            })
            .catch(function (error) {
                console.error("Edit mock error:", error.response?.data || error);
                
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = "Update";
                }
                
                if (errorMsg) {
                    errorMsg.innerHTML = error.response?.data?.message ||
                        Object.values(error.response?.data?.errors || {}).flat().join(", ") ||
                        "Error updating mock subject vetting assignment";
                    errorMsg.classList.remove("d-none");
                }
            });
    });
}

// Modal events
const addMockModal = document.getElementById("addMockSubjectVettingModal");
if (addMockModal) {
    addMockModal.addEventListener("show.bs.modal", function (e) {
        console.log("Add mock modal show event");

        const modalLabel = document.getElementById("addModalLabel");
        const addBtn = document.getElementById("add-btn");

        if (modalLabel) modalLabel.innerHTML = "Add Mock Subject Vetting Assignment";
        if (addBtn) {
            addBtn.disabled = true;
            addBtn.innerHTML = "Add Mock Subject Vetting Assignment";
        }

        const updateSubjectClassCheckboxes = () => {
            const selectedTermIds = Array.from(
                document.querySelectorAll('#addMockSubjectVettingModal input[name="termid[]"]:checked')
            ).map(checkbox => checkbox.value);

            const subjectClassCheckboxes = document.querySelectorAll(
                '#addMockSubjectVettingModal input[name="subjectclassid[]"]'
            );

            subjectClassCheckboxes.forEach(checkbox => {
                const termId = checkbox.getAttribute('data-termid');
                const isEnabled = selectedTermIds.length === 0 || selectedTermIds.includes(termId);
                checkbox.disabled = !isEnabled;
                checkbox.closest('.form-check').style.opacity = isEnabled ? '1' : '0.5';
                if (!isEnabled) checkbox.checked = false;
            });

            updateSubmitButton();
        };

        const updateSubmitButton = () => {
            const userId = document.getElementById("userid")?.value;
            const sessionId = document.getElementById("sessionid")?.value;
            const checkedTerms = document.querySelectorAll('#addMockSubjectVettingModal input[name="termid[]"]:checked').length;
            const checkedClasses = document.querySelectorAll('#addMockSubjectVettingModal input[name="subjectclassid[]"]:checked').length;
            if (addBtn) addBtn.disabled = !userId || !sessionId || checkedTerms === 0 || checkedClasses === 0;
        };

        const userIdSelect = document.getElementById("userid");
        const sessionIdSelect = document.getElementById("sessionid");
        const termCheckboxes = document.querySelectorAll('#addMockSubjectVettingModal input[name="termid[]"]');
        const subjectClassCheckboxes = document.querySelectorAll('#addMockSubjectVettingModal input[name="subjectclassid[]"]');

        if (userIdSelect) {
            const newUserIdSelect = userIdSelect.cloneNode(true);
            userIdSelect.parentNode.replaceChild(newUserIdSelect, userIdSelect);
            newUserIdSelect.addEventListener("change", updateSubmitButton);
        }

        if (sessionIdSelect) {
            const newSessionIdSelect = sessionIdSelect.cloneNode(true);
            sessionIdSelect.parentNode.replaceChild(newSessionIdSelect, sessionIdSelect);
            newSessionIdSelect.addEventListener("change", updateSubmitButton);
        }

        termCheckboxes.forEach(cb => {
            const newCb = cb.cloneNode(true);
            cb.parentNode.replaceChild(newCb, cb);
            newCb.addEventListener("change", () => {
                updateSubjectClassCheckboxes();
                updateSubmitButton();
            });
        });

        subjectClassCheckboxes.forEach(cb => {
            const newCb = cb.cloneNode(true);
            cb.parentNode.replaceChild(newCb, cb);
            newCb.addEventListener("change", updateSubmitButton);
        });

        updateSubjectClassCheckboxes();
    });

    addMockModal.addEventListener("hidden.bs.modal", function () {
        console.log("Add mock modal hidden - cleaning up");
        clearAddFields();
        const errorMsg = document.getElementById("alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");

        const addBtn = document.getElementById("add-btn");
        if (addBtn) {
            addBtn.disabled = true;
            addBtn.innerHTML = "Add Mock Subject Vetting Assignment";
        }

        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }

        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';

        const subjectClassCheckboxes = document.querySelectorAll(
            '#addMockSubjectVettingModal input[name="subjectclassid[]"]'
        );
        subjectClassCheckboxes.forEach(checkbox => {
            checkbox.disabled = false;
            checkbox.closest('.form-check').style.opacity = '1';
        });
    });
}

const editMockModal = document.getElementById("editMockModal");
if (editMockModal) {
    editMockModal.addEventListener("show.bs.modal", function (e) {
        console.log("Edit mock modal show event");

        const modalLabel = document.getElementById("editModalLabel");
        const updateBtn = document.getElementById("update-btn");

        if (modalLabel) modalLabel.innerHTML = "Edit Mock Subject Vetting Assignment";
        if (updateBtn) {
            updateBtn.disabled = false;
            updateBtn.innerHTML = "Update";
        }
    });

    editMockModal.addEventListener("hidden.bs.modal", function () {
        console.log("Edit mock modal hidden - cleaning up");
        clearEditFields();
        const errorMsg = document.getElementById("edit-alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");

        const updateBtn = document.getElementById("update-btn");
        if (updateBtn) {
            updateBtn.disabled = false;
            updateBtn.innerHTML = "Update";
        }

        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }

        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    console.log("DOM fully loaded, initializing mock components");
    initializeListJS();
    initializeMockVettingStatusChart();
    initializeCheckboxes();

    const searchInput = document.querySelector(".search-box input.search");
    if (searchInput) {
        searchInput.addEventListener("input", debounce(filterData, 300));
    }
});