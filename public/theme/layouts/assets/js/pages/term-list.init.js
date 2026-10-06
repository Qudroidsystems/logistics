console.log("term.init.js is loaded and executing!");

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
        const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
        var removeActions = document.getElementById("remove-actions");
        if (removeActions) {
            removeActions.classList.toggle("d-none", checkedCount === 0);
        }
    };
}

// Form fields
var addIdField = document.getElementById("add-id-field");
var addTermField = document.getElementById("term");
var addStatusSwitch = document.getElementById("add-status-switch");
var editIdField = document.getElementById("edit-id-field");
var editTermField = document.getElementById("edit-term");
var editStatusSwitch = document.getElementById("edit-status-switch");

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
    const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
    var removeActions = document.getElementById("remove-actions");
    if (removeActions) {
        removeActions.classList.toggle("d-none", checkedCount === 0);
    }
    const allCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]');
    if (checkAll) {
        checkAll.checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCount;
    }
}

// Event delegation for edit and remove buttons
document.addEventListener('click', function (e) {
    if (e.target.closest('.edit-item-btn')) {
        handleEditClick(e);
    } else if (e.target.closest('.remove-item-btn')) {
        handleRemoveClick(e);
    }
});

// Delete single term
function handleRemoveClick(e) {
    e.preventDefault();
    var itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
    var deleteButton = document.getElementById("delete-record");

    // Remove any existing event listeners
    deleteButton.replaceWith(deleteButton.cloneNode(true));
    deleteButton = document.getElementById("delete-record");

    deleteButton.addEventListener("click", function () {
        axios.delete(`/term/${itemId}`).then(function () {
            Swal.fire({
                position: "center",
                icon: "success",
                title: "Term deleted successfully!",
                showConfirmButton: false,
                timer: 2000,
                showCloseButton: true
            });
            setTimeout(() => window.location.reload(), 2000);
        }).catch(function (error) {
            Swal.fire({
                position: "center",
                icon: "error",
                title: "Error deleting term",
                text: error.response?.data?.message || "An error occurred",
                showConfirmButton: true
            });
        });
    });

    try {
        var modal = new bootstrap.Modal(document.getElementById("deleteRecordModal"));
        modal.show();
    } catch (error) {
        console.error("Error opening delete modal:", error);
    }
}

// Edit term
function handleEditClick(e) {
    e.preventDefault();
    var itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
    var tr = e.target.closest("tr");

    if (editIdField) editIdField.value = itemId;
    if (editTermField) editTermField.value = tr.querySelector(".term").innerText;

    // Set status in edit modal
    var statusCheckbox = tr.querySelector('.status-toggle');
    if (editStatusSwitch) {
        editStatusSwitch.checked = statusCheckbox ? statusCheckbox.checked : false;
    }

    try {
        var modal = new bootstrap.Modal(document.getElementById("editModal"));
        modal.show();
    } catch (error) {
        console.error("Error opening edit modal:", error);
    }
}

// Status toggle functionality
function handleStatusToggle() {
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('status-toggle')) {
            e.preventDefault();

            const checkbox = e.target;
            const termId = checkbox.getAttribute('data-id');
            const isChecked = checkbox.checked;
            const label = checkbox.nextElementSibling;

            // Disable checkbox during API call to prevent rapid clicks
            checkbox.disabled = true;

            // Show loading state
            const originalLabel = label.textContent;
            label.textContent = 'Updating...';
            label.classList.remove('active', 'inactive');

            // Call API to update status
            axios.patch(`/term/${termId}/status`, {
                status: isChecked ? 1 : 0
            }, {
                headers: { 'Content-Type': 'application/json' }
            })
            .then(function(response) {
                console.log("Status updated successfully:", response.data);

                // Update label
                label.textContent = isChecked ? 'Active' : 'Inactive';
                label.classList.add(isChecked ? 'active' : 'inactive');

                // Show success notification
                Swal.fire({
                    position: "top-end",
                    icon: "success",
                    title: "Status updated!",
                    showConfirmButton: false,
                    timer: 1500,
                    toast: true
                });
            })
            .catch(function(error) {
                console.error("Status update error:", error);

                // Revert checkbox state on error
                checkbox.checked = !isChecked;

                // Revert label
                label.textContent = !isChecked ? 'Active' : 'Inactive';
                label.classList.add(!isChecked ? 'active' : 'inactive');

                // Show error notification
                Swal.fire({
                    position: "top-end",
                    icon: "error",
                    title: "Failed to update status",
                    text: error.response?.data?.message || "Please try again",
                    showConfirmButton: false,
                    timer: 3000,
                    toast: true
                });
            })
            .finally(function() {
                // Re-enable checkbox
                checkbox.disabled = false;
            });
        }
    });
}

// Clear form fields
function clearAddFields() {
    if (addIdField) addIdField.value = "";
    if (addTermField) addTermField.value = "";
    if (addStatusSwitch) addStatusSwitch.checked = true;
}

function clearEditFields() {
    if (editIdField) editIdField.value = "";
    if (editTermField) editTermField.value = "";
    if (editStatusSwitch) editStatusSwitch.checked = true;
}

// Delete multiple terms
function deleteMultiple() {
    const ids_array = [];
    const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
    checkboxes.forEach((checkbox) => {
        if (checkbox.checked) {
            const id = checkbox.closest("tr").querySelector(".id").getAttribute("data-id");
            ids_array.push(id);
        }
    });
    if (ids_array.length > 0) {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn btn-primary w-xs me-2 mt-2",
            cancelButtonClass: "btn btn-danger w-xs mt-2",
            confirmButtonText: "Yes, delete them!",
            buttonsStyling: false,
            showCloseButton: true
        }).then((result) => {
            if (result.value) {
                Promise.all(ids_array.map((id) => {
                    return axios.delete(`/term/${id}`);
                })).then(() => {
                    Swal.fire({
                        title: "Deleted!",
                        text: "Selected terms have been deleted.",
                        icon: "success",
                        confirmButtonClass: "btn btn-info w-xs mt-2",
                        buttonsStyling: false
                    });
                    setTimeout(() => window.location.reload(), 2000);
                }).catch((error) => {
                    Swal.fire({
                        title: "Error!",
                        text: error.response?.data?.message || "Failed to delete terms",
                        icon: "error",
                        confirmButtonClass: "btn btn-info w-xs mt-2",
                        buttonsStyling: false
                    });
                });
            }
        });
    } else {
        Swal.fire({
            title: "Please select at least one checkbox",
            confirmButtonClass: "btn btn-info",
            buttonsStyling: false,
            showCloseButton: true
        });
    }
}

// Initialize List.js for client-side filtering
var termList;
var termListContainer = document.getElementById('termList');
if (termListContainer && document.querySelectorAll('#termList tbody tr').length > 0) {
    try {
        termList = new List('termList', {
            valueNames: ['term', 'status', 'datereg'],
            page: 1000,
            pagination: false,
            listClass: 'list'
        });
    } catch (error) {
        console.error("List.js initialization failed:", error);
    }
} else {
    console.warn("No terms available for List.js initialization");
}

// Update no results message
if (termList) {
    termList.on('searchComplete', function () {
        var noResultRow = document.querySelector('.noresult');
        if (termList.visibleItems.length === 0) {
            noResultRow.style.display = 'block';
        } else {
            noResultRow.style.display = 'none';
        }
    });
}

// Filter data (client-side)
function filterData() {
    var searchInput = document.querySelector(".search-box input.search");
    var searchValue = searchInput ? searchInput.value : "";
    console.log("Filtering with search:", searchValue);
    if (termList) {
        termList.search(searchValue, ['term', 'status']);
    }
}

// Add term
var addTermForm = document.getElementById("add-term-form");
if (addTermForm) {
    addTermForm.addEventListener("submit", function (e) {
        e.preventDefault();
        var errorMsg = document.getElementById("alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");

        var formData = new FormData(addTermForm);
        var term = formData.get('term');
        var status = addStatusSwitch ? addStatusSwitch.checked : true;

        if (!term) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please enter a term name";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        console.log("Submitting Add Term:", { term, status });
        axios.post('/term', {
            term: term,
            status: status
        }, {
            headers: { 'Content-Type': 'application/json' }
        }).then(function (response) {
            console.log("Add Term Success:", response.data);
            Swal.fire({
                position: "center",
                icon: "success",
                title: "Term added successfully!",
                showConfirmButton: false,
                timer: 2000,
                showCloseButton: true
            });
            setTimeout(() => window.location.reload(), 2000);
        }).catch(function (error) {
            console.error("Add Term Error:", error.response);
            if (errorMsg) {
                errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(", ") || "Error adding term";
                errorMsg.classList.remove("d-none");
            }
        });
    });
}

// Edit term
var editTermForm = document.getElementById("edit-term-form");
if (editTermForm) {
    editTermForm.addEventListener("submit", function (e) {
        e.preventDefault();
        var errorMsg = document.getElementById("edit-alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");

        var formData = new FormData(editTermForm);
        var term = formData.get('term');
        var status = editStatusSwitch ? editStatusSwitch.checked : true;
        var id = editIdField.value;

        if (!term) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please enter a term name";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        console.log("Submitting Edit Term:", { id, term, status });
        axios.put(`/term/${id}`, {
            term: term,
            status: status
        }, {
            headers: { 'Content-Type': 'application/json' }
        }).then(function (response) {
            console.log("Edit Term Success:", response.data);
            Swal.fire({
                position: "center",
                icon: "success",
                title: "Term updated successfully!",
                showConfirmButton: false,
                timer: 2000,
                showCloseButton: true
            });
            setTimeout(() => window.location.reload(), 2000);
        }).catch(function (error) {
            console.error("Edit Term Error:", error.response);
            if (errorMsg) {
                errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(", ") || "Error updating term";
                errorMsg.classList.remove("d-none");
            }
        });
    });
}

// Modal events
var addModal = document.getElementById("addTermModal");
if (addModal) {
    addModal.addEventListener("show.bs.modal", function (e) {
        if (e.relatedTarget && e.relatedTarget.classList.contains("add-btn")) {
            var modalLabel = document.getElementById("exampleModalLabel");
            var addBtn = document.getElementById("add-btn");
            if (modalLabel) modalLabel.innerHTML = "Add Term";
            if (addBtn) addBtn.innerHTML = "Add Term";
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
        if (modalLabel) modalLabel.innerHTML = "Edit Term";
        if (updateBtn) updateBtn.innerHTML = "Update";
    });
    editModal.addEventListener("hidden.bs.modal", function () {
        clearEditFields();
    });
}

// Initialize all event listeners
function initializeAllEventListeners() {
    // Status toggle listener
    handleStatusToggle();

    // Initialize status labels
    document.querySelectorAll('.status-label').forEach(function(label) {
        const isActive = label.textContent.trim() === 'Active';
        label.classList.add(isActive ? 'active' : 'inactive');
    });

    // Initialize checkboxes
    ischeckboxcheck();
}

// DOM Content Loaded
document.addEventListener("DOMContentLoaded", function () {
    console.log("DOMContentLoaded fired");

    // Initialize all event listeners
    initializeAllEventListeners();

    // Search functionality
    var searchInput = document.querySelector(".search-box input.search");
    if (searchInput) {
        searchInput.addEventListener("input", debounce(function () {
            filterData();
        }, 300));
    } else {
        console.error("Search input not found!");
    }
});

// Expose functions to global scope
window.deleteMultiple = deleteMultiple;
window.initializeAllEventListeners = initializeAllEventListeners;
