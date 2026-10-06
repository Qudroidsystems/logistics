console.log("subject.init.js is loaded and executing!");

// Verify dependencies
try {
    if (typeof axios === 'undefined') throw new Error("Axios is not loaded");
    if (typeof Swal === 'undefined') throw new Error("SweetAlert2 is not loaded");
    if (typeof bootstrap === 'undefined') throw new Error("Bootstrap is not loaded");
    if (typeof List === 'undefined') throw new Error("List.js is not loaded");
} catch (error) {
    console.error("Dependency check failed:", error);
}

// Set Axios CSRF token globally
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

// Debounce function for search input
function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Check all checkbox
var checkAll = document.getElementById("checkAll");
if (checkAll) {
    checkAll.onclick = function () {
        console.log("checkAll clicked");
        var checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
        checkboxes.forEach((checkbox) => {
            checkbox.checked = this.checked;
            const row = checkbox.closest("tr");
            if (checkbox.checked) {
                row.classList.add("table-active");
            } else {
                row.classList.remove("table-active");
            }
        });
        updateRemoveActionsVisibility();
    };
}

// Form fields
var addIdField = document.getElementById("add-id-field");
var addSubjectField = document.getElementById("subject");
var addSubjectCodeField = document.getElementById("subject_code");
var addRemarkField = document.getElementById("remark");
var editIdField = document.getElementById("edit-id-field");
var editSubjectField = document.getElementById("edit-subject");
var editSubjectCodeField = document.getElementById("edit-subject_code");
var editRemarkField = document.getElementById("edit-remark");

// Update remove actions visibility
function updateRemoveActionsVisibility() {
    const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
    var removeActions = document.getElementById("remove-actions");
    if (removeActions) {
        removeActions.classList.toggle("d-none", checkedCount === 0);
    }
}

// Update checkAll state
function updateCheckAllState() {
    const allCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]');
    const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
    if (checkAll) {
        checkAll.checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCount;
    }
}

// Checkbox handling
function ischeckboxcheck() {
    const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
    checkboxes.forEach((checkbox) => {
        checkbox.removeEventListener("change", handleCheckboxChange);
        checkbox.addEventListener("change", handleCheckboxChange);
    });
}

function handleCheckboxChange(e) {
    const row = e.target.closest("tr");
    if (e.target.checked) {
        row.classList.add("table-active");
    } else {
        row.classList.remove("table-active");
    }
    updateRemoveActionsVisibility();
    updateCheckAllState();
}

// Update no results display
function updateNoResultsDisplay() {
    const tbody = document.querySelector("#kt_roles_view_table tbody");
    const rowCount = document.querySelectorAll("#kt_roles_view_table tbody tr:not(.noresult)").length;
    
    // Remove existing no results row
    const existingNoResult = document.querySelector("#kt_roles_view_table tbody .noresult");
    if (existingNoResult) {
        existingNoResult.closest('tr').remove();
    }
    
    if (rowCount === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="noresult text-center py-4">No results found</td></tr>';
    }
}

// Refresh table data
function refreshTableData() {
    console.log("Refreshing table data...");
    const currentUrl = new URL(window.location.href);
    const searchParams = currentUrl.searchParams;
    
    axios.get(currentUrl.pathname, {
        params: Object.fromEntries(searchParams),
        headers: { 
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(function (response) {
        // If the response contains HTML (partial view), update the table
        if (response.data.html) {
            document.querySelector("#kt_roles_view_table tbody").innerHTML = response.data.html;
            document.getElementById("pagination-element").outerHTML = response.data.pagination;
            
            // Update count display
            document.querySelector("#pagination-element .text-muted").innerHTML =
                `Showing <span class="fw-semibold">${response.data.count}</span> of <span class="fw-semibold">${response.data.total}</span> Results`;
        } else {
            // Fallback: reload the page
            window.location.reload();
        }
        
        // Re-initialize components
        if (subjectList) {
            subjectList.reIndex();
        }
        ischeckboxcheck();
        updateNoResultsDisplay();
        
    }).catch(function (error) {
        console.error("Error refreshing table data:", error);
        // Fallback: reload the page
        window.location.reload();
    });
}

// Event delegation for edit, remove, and pagination buttons
document.addEventListener('click', function (e) {
    if (e.target.closest('.edit-item-btn')) {
        handleEditClick(e);
    } else if (e.target.closest('.remove-item-btn')) {
        handleRemoveClick(e);
    } else if (e.target.closest('.pagination-prev, .pagination-next, .pagination .page-link')) {
        e.preventDefault();
        const url = e.target.closest('a').getAttribute('data-url');
        if (url) fetchPage(url);
    }
});

// Fetch paginated data
function fetchPage(url) {
    if (!url) return;
    console.log("Fetching page:", url);
    axios.get(url, {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(function (response) {
        console.log("Fetch page success:", response.data);
        document.querySelector("#kt_roles_view_table tbody").innerHTML = response.data.html;
        document.getElementById("pagination-element").outerHTML = response.data.pagination;
        if (subjectList) {
            subjectList.reIndex();
        }
        ischeckboxcheck();
        document.querySelector("#pagination-element .text-muted").innerHTML =
            `Showing <span class="fw-semibold">${response.data.count}</span> of <span class="fw-semibold">${response.data.total}</span> Results`;
        updateNoResultsDisplay();
    }).catch(function (error) {
        console.error("Error fetching page:", error);
        Swal.fire({
            position: "center",
            icon: "error",
            title: "Error loading page",
            text: error.response?.data?.message || "An error occurred",
            showConfirmButton: true
        });
    });
}

// Delete single subject
function handleRemoveClick(e) {
    e.preventDefault();
    var itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
    var deleteUrl = e.target.closest("tr").getAttribute("data-url");
    var modal = new bootstrap.Modal(document.getElementById("deleteRecordModal"));
    modal.show();

    var deleteButton = document.getElementById("delete-record");
    if (deleteButton) {
        // Remove existing event listeners
        deleteButton.replaceWith(deleteButton.cloneNode(true));
        deleteButton = document.getElementById("delete-record");
        
        deleteButton.onclick = function () {
            console.log("Sending DELETE request for subject:", itemId);
            axios.delete(deleteUrl).then(function (response) {
                console.log("Delete subject success:", response.data);
                
                // Remove row from DOM immediately
                const row = document.querySelector(`tr[data-id="${itemId}"]`);
                if (row) {
                    row.remove();
                }
                
                // Remove from List.js if initialized
                if (subjectList) {
                    subjectList.remove("id", itemId);
                    subjectList.reIndex();
                }
                
                // Update UI components
                updateNoResultsDisplay();
                updateRemoveActionsVisibility();
                updateCheckAllState();
                
                modal.hide();
                
                Swal.fire({
                    position: "center",
                    icon: "success",
                    title: response.data.message || "Subject deleted successfully!",
                    showConfirmButton: false,
                    timer: 2000,
                    showCloseButton: true
                });
                
                // Check if we need to fetch previous page
                const rowCount = document.querySelectorAll("#kt_roles_view_table tbody tr:not(.noresult)").length;
                if (rowCount === 0 && document.querySelector("#pagination-element .pagination-prev:not(.disabled)")) {
                    const prevUrl = document.querySelector("#pagination-element .pagination-prev").getAttribute("data-url");
                    if (prevUrl) {
                        console.log("Fetching previous page:", prevUrl);
                        fetchPage(prevUrl);
                    }
                }
                
            }).catch(function (error) {
                console.error("Delete subject error:", error.response);
                modal.hide();
                Swal.fire({
                    position: "center",
                    icon: "error",
                    title: "Error deleting subject",
                    text: error.response?.data?.message || "An error occurred",
                    showConfirmButton: true
                });
            });
        };
    }
}

// Edit subject
function handleEditClick(e) {
    e.preventDefault();
    var itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
    var tr = e.target.closest("tr");
    if (editIdField) editIdField.value = itemId;
    if (editSubjectField) editSubjectField.value = tr.querySelector(".subject")?.innerText || "";
    if (editSubjectCodeField) editSubjectCodeField.value = tr.querySelector(".subjectcode")?.innerText || "";
    if (editRemarkField) editRemarkField.value = tr.querySelector(".remark")?.innerText || "";
    try {
        var modal = new bootstrap.Modal(document.getElementById("editModal"));
        modal.show();
    } catch (error) {
        console.error("Error opening edit modal:", error);
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
    if (addSubjectField) addSubjectField.value = "";
    if (addSubjectCodeField) addSubjectCodeField.value = "";
    if (addRemarkField) addRemarkField.value = "";
}

function clearEditFields() {
    if (editIdField) editIdField.value = "";
    if (editSubjectField) editSubjectField.value = "";
    if (editSubjectCodeField) editSubjectCodeField.value = "";
    if (editRemarkField) editRemarkField.value = "";
}

// Delete multiple subjects
function deleteMultiple() {
    const ids_array = [];
    const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]:checked');
    
    // Collect checked IDs
    checkboxes.forEach((checkbox) => {
        const id = checkbox.closest("tr").querySelector(".id").getAttribute("data-id");
        ids_array.push(id);
    });

    console.log("Selected IDs for deletion:", ids_array);

    if (ids_array.length === 0) {
        console.warn("No checkboxes selected");
        Swal.fire({
            title: "Please select at least one checkbox",
            icon: "info",
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
            console.log("Confirmed deletion, sending requests...");
            Promise.allSettled(
                ids_array.map((id) => {
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    const deleteUrl = row ? row.getAttribute("data-url") : `/subject/${id}`;
                    console.log(`Deleting subject ID ${id} with URL: ${deleteUrl}`);
                    return axios.delete(deleteUrl, {
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                })
            ).then((results) => {
                console.log("Deletion results:", results);
                let successCount = 0;
                let errorMessages = [];

                // Process deletion results and remove successful ones from DOM immediately
                results.forEach((result, index) => {
                    const id = ids_array[index];
                    if (result.status === "fulfilled") {
                        successCount++;
                        // Remove row from DOM
                        const row = document.querySelector(`tr[data-id="${id}"]`);
                        if (row) {
                            console.log(`Removing row for ID ${id} from DOM`);
                            row.remove();
                        }
                        // Remove from List.js
                        if (subjectList) {
                            subjectList.remove("id", id);
                        }
                    } else {
                        console.error(`Failed to delete ID ${id}:`, result.reason);
                        errorMessages.push(`ID ${id}: ${result.reason.response?.data?.message || "Unknown error"}`);
                    }
                });

                // Reindex List.js and update UI
                if (subjectList) {
                    subjectList.reIndex();
                }
                
                // Update UI components
                updateNoResultsDisplay();
                updateRemoveActionsVisibility();
                updateCheckAllState();

                // Check if we need to fetch previous page
                const rowCount = document.querySelectorAll("#kt_roles_view_table tbody tr:not(.noresult)").length;
                if (rowCount === 0 && document.querySelector("#pagination-element .pagination-prev:not(.disabled)")) {
                    const prevUrl = document.querySelector("#pagination-element .pagination-prev").getAttribute("data-url");
                    if (prevUrl) {
                        console.log("Table empty, fetching previous page:", prevUrl);
                        fetchPage(prevUrl);
                    }
                }

                // Show feedback to user
                if (successCount === ids_array.length) {
                    console.log("All deletions successful");
                    Swal.fire({
                        title: "Deleted!",
                        text: `${successCount} subject(s) deleted successfully.`,
                        icon: "success",
                        confirmButtonClass: "btn btn-info w-xs mt-2",
                        buttonsStyling: false,
                        showCloseButton: true
                    });
                } else {
                    console.warn("Partial deletion failure");
                    Swal.fire({
                        title: "Partial Success",
                        text: `${successCount} subject(s) deleted. ${errorMessages.length} failed: ${errorMessages.join(", ")}`,
                        icon: "warning",
                        confirmButtonClass: "btn btn-info w-xs mt-2",
                        buttonsStyling: false,
                        showCloseButton: true
                    });
                }
            }).catch((error) => {
                console.error("Unexpected error in deleteMultiple:", error);
                Swal.fire({
                    title: "Error!",
                    text: error.response?.data?.message || "Failed to delete subjects",
                    icon: "error",
                    confirmButtonClass: "btn btn-info w-xs mt-2",
                    buttonsStyling: false,
                    showCloseButton: true
                });
            });
        }
    });
}

// Initialize List.js for client-side filtering
var subjectList;
var subjectListContainer = document.getElementById('subjectList');
if (subjectListContainer && document.querySelectorAll('#subjectList tbody tr:not(.noresult)').length > 0) {
    try {
        subjectList = new List('subjectList', {
            valueNames: ['sn', 'subject', 'subjectcode', 'remark', 'datereg'],
            page: 1000,
            pagination: false,
            listClass: 'list'
        });
    } catch (error) {
        console.error("List.js initialization failed:", error);
    }
} else {
    console.warn("No subjects available for List.js initialization");
}

// Update no results message for search
if (subjectList) {
    subjectList.on('searchComplete', function () {
        var noResultRow = document.querySelector('.noresult');
        if (subjectList.visibleItems.length === 0) {
            if (noResultRow) {
                noResultRow.style.display = 'block';
            }
        } else {
            if (noResultRow) {
                noResultRow.style.display = 'none';
            }
        }
    });
}

// Filter data (client-side)
function filterData() {
    var searchInput = document.querySelector(".search-box input.search");
    var searchValue = searchInput ? searchInput.value : "";
    console.log("Filtering with search:", searchValue);
    if (subjectList) {
        subjectList.search(searchValue, ['sn', 'subject', 'subjectcode', 'remark']);
    }
}

// Add subject
var addSubjectForm = document.getElementById("add-subject-form");
if (addSubjectForm) {
    addSubjectForm.addEventListener("submit", function (e) {
        e.preventDefault();
        var errorMsg = document.getElementById("alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");
        var formData = new FormData(addSubjectForm);
        var subject = formData.get('subject');
        var subject_code = formData.get('subject_code');
        var remark = formData.get('remark');
        if (!subject || !subject_code || !remark) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please fill all required fields";
                errorMsg.classList.remove("d-none");
            }
            return;
        }
        console.log("Submitting Add Subject:", { subject, subject_code, remark });
        axios.post('/subject', {
            subject: subject,
            subject_code: subject_code,
            remark: remark
        }, {
            headers: { 'Content-Type': 'application/json' }
        }).then(function (response) {
            console.log("Add Subject Success:", response.data);
            Swal.fire({
                position: "center",
                icon: "success",
                title: "Subject added successfully!",
                showConfirmButton: false,
                timer: 2000,
                showCloseButton: true
            });
            
            // Close modal and clear form
            var modal = bootstrap.Modal.getInstance(document.getElementById("addSubjectModal"));
            if (modal) modal.hide();
            clearAddFields();
            
            // Refresh table data instead of full page reload
            setTimeout(() => refreshTableData(), 500);
            
        }).catch(function (error) {
            console.error("Add Subject Error:", error.response);
            if (errorMsg) {
                errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(", ") || "Error adding subject";
                errorMsg.classList.remove("d-none");
            }
        });
    });
}

// Edit subject
var editSubjectForm = document.getElementById("edit-subject-form");
if (editSubjectForm) {
    editSubjectForm.addEventListener("submit", function (e) {
        e.preventDefault();
        var errorMsg = document.getElementById("edit-alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");
        var formData = new FormData(editSubjectForm);
        var subject = formData.get('subject');
        var subject_code = formData.get('subject_code');
        var remark = formData.get('remark');
        var id = editIdField.value;
        if (!subject || !subject_code || !remark) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please fill all required fields";
                errorMsg.classList.remove("d-none");
            }
            return;
        }
        console.log("Submitting Edit Subject:", { id, subject, subject_code, remark });
        axios.put(`/subject/${id}`, {
            subject: subject,
            subject_code: subject_code,
            remark: remark
        }, {
            headers: { 'Content-Type': 'application/json' }
        }).then(function (response) {
            console.log("Edit Subject Success:", response.data);
            Swal.fire({
                position: "center",
                icon: "success",
                title: "Subject updated successfully!",
                showConfirmButton: false,
                timer: 2000,
                showCloseButton: true
            });
            
            // Close modal and clear form
            var modal = bootstrap.Modal.getInstance(document.getElementById("editModal"));
            if (modal) modal.hide();
            clearEditFields();
            
            // Refresh table data instead of full page reload
            setTimeout(() => refreshTableData(), 500);
            
        }).catch(function (error) {
            console.error("Edit Subject Error:", error.response);
            if (errorMsg) {
                errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(", ") || "Error updating subject";
                errorMsg.classList.remove("d-none");
            }
        });
    });
}

// Modal events
var addModal = document.getElementById("addSubjectModal");
if (addModal) {
    addModal.addEventListener("show.bs.modal", function (e) {
        if (e.relatedTarget && e.relatedTarget.classList.contains("add-btn")) {
            var modalLabel = document.getElementById("exampleModalLabel");
            var addBtn = document.getElementById("add-btn");
            if (modalLabel) modalLabel.innerHTML = "Add Subject";
            if (addBtn) addBtn.innerHTML = "Add Subject";
        }
    });
    addModal.addEventListener("hidden.bs.modal", function () {
        clearAddFields();
    });
}

var editModal = document.getElementById("editModal");
if (editModal) {
    editModal.addEventListener("show.bs.modal", function () {
        var modalLabel = document.getElementById("editModalLabel");
        var updateBtn = document.getElementById("update-btn");
        if (modalLabel) modalLabel.innerHTML = "Edit Subject";
        if (updateBtn) updateBtn.innerHTML = "Update";
    });
    editModal.addEventListener("hidden.bs.modal", function () {
        clearEditFields();
    });
}

// Initialize listeners
document.addEventListener("DOMContentLoaded", function () {
    console.log("DOMContentLoaded fired");
    var searchInput = document.querySelector(".search-box input.search");
    if (searchInput) {
        searchInput.addEventListener("input", debounce(function () {
            console.log("Search input changed:", searchInput.value);
            filterData();
        }, 300));
    } else {
        console.error("Search input not found!");
    }

    ischeckboxcheck();
});

// Expose functions to global scope
window.deleteMultiple = deleteMultiple;