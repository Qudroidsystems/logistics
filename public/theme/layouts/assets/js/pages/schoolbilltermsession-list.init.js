console.log("schoolbilltermsession.init.js is loaded and executing!");

// Verify dependencies
try {
    if (typeof axios === 'undefined') throw new Error("Axios is not loaded");
    if (typeof Swal === 'undefined') throw new Error("SweetAlert2 is not loaded");
    if (typeof bootstrap === 'undefined') throw new Error("Bootstrap is not loaded");
    if (typeof List === 'undefined') throw new Error("List.js is not loaded");
    console.log("All dependencies loaded successfully");
} catch (error) {
    console.error("Dependency check failed:", error);
}

// Set Axios CSRF token globally
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
if (!csrfToken) console.warn("CSRF token not found");

// Debounce function for search input
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
const addBillIdField = document.getElementById("bill_id");
const addClassIdField = document.getElementById("add-class-checkboxes");
const addTermIdField = document.getElementById("add-term-checkboxes");
const addSessionIdField = document.getElementById("add-session-radio");
const editIdField = document.getElementById("edit-id-field");
const editBillIdField = document.getElementById("edit-bill_id");
const editClassIdField = document.getElementById("edit-class-checkboxes");
const editTermIdField = document.getElementById("edit-term-checkboxes");
const editSessionIdField = document.getElementById("edit-session-radio");

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

// Explicit event listener for Create School Bill Term Session button
const createButton = document.querySelector('.add-btn');
if (createButton) {
    createButton.addEventListener('click', function (e) {
        e.preventDefault();
        console.log("Create School Bill Term Session button clicked");
        try {
            const modal = new bootstrap.Modal(document.getElementById("addSchoolBillTermSessionModal"));
            modal.show();
            console.log("Add modal opened");
        } catch (error) {
            console.error("Error opening add modal:", error);
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

// Event delegation for edit and remove buttons
document.addEventListener('click', function (e) {
    const editBtn = e.target.closest('.edit-item-btn');
    const removeBtn = e.target.closest('.remove-item-btn');
    if (editBtn) {
        handleEditClick(e, editBtn);
    } else if (removeBtn) {
        handleRemoveClick(e, removeBtn);
    }
});

// Delete single school bill term session
function handleRemoveClick(e, button) {
    e.preventDefault();
    console.log("Remove button clicked");
    const itemId = button.getAttribute("data-id");
    if (!itemId) {
        console.error("Item ID not found");
        return;
    }
    const deleteButton = document.getElementById("delete-record");
    if (deleteButton) {
        deleteButton.onclick = function () {
            console.log("Deleting school bill term session:", itemId);
            axios.delete(`/schoolbilltermsession/${itemId}`)
                .then(function () {
                    Swal.fire({
                        position: "center",
                        icon: "success",
                        title: "School Bill Term Session deleted successfully!",
                        showConfirmButton: false,
                        timer: 2000,
                        showCloseButton: true
                    });
                    window.location.reload();
                })
                .catch(function (error) {
                    console.error("Delete error:", error);
                    Swal.fire({
                        position: "center",
                        icon: "error",
                        title: "Error deleting school bill term session",
                        text: error.response?.data?.message || "An error occurred",
                        showConfirmButton: true
                    });
                });
        };
    }
    try {
        const modal = new bootstrap.Modal(document.getElementById("deleteRecordModal"));
        modal.show();
        console.log("Delete modal opened");
    } catch (error) {
        console.error("Error opening delete modal:", error);
    }
}

// Edit school bill term session
function handleEditClick(e, button) {
    e.preventDefault();
    console.log("Edit button clicked at", new Date().toISOString());
    const itemId = button.getAttribute("data-id");
    if (!itemId) {
        console.error("Item ID not found");
        return;
    }

    // Clear previous form data
    clearEditFields();

    // Fetch related records for the same bill_id and session_id
    axios.get(`/schoolbilltermsession/${itemId}/related`, {
        headers: { 'X-CSRF-TOKEN': csrfToken }
    })
        .then(function (response) {
            console.log("Related records response:", response.data);
            if (!response.data.success) {
                console.error("AJAX response unsuccessful:", response.data.message);
                Swal.fire({
                    position: "center",
                    icon: "error",
                    title: "Error",
                    text: response.data.message || "Failed to fetch related records",
                    showConfirmButton: true
                });
                return;
            }

            // Extract data with proper defaults and type checking
            const bill_id = response.data.bill_id || null;
            const class_ids = Array.isArray(response.data.class_ids) ? response.data.class_ids : [];
            const term_ids = Array.isArray(response.data.term_ids) ? response.data.term_ids : [];
            const session_id = response.data.session_id || null;

            console.log("Pre-selecting:", { bill_id, class_ids, term_ids, session_id });

            // Set fields
            if (editIdField) editIdField.value = itemId;
            if (editBillIdField) {
                editBillIdField.value = bill_id || "";
                console.log("Bill ID set to:", bill_id);
            }

            // Set class checkboxes
            const classCheckboxes = document.querySelectorAll('#edit-class-checkboxes .class-checkbox');
            console.log("Available class checkboxes:", Array.from(classCheckboxes).map(cb => cb.value));
            classCheckboxes.forEach(cb => {
                const value = Number(cb.value);
                const isChecked = Array.isArray(class_ids) && class_ids.includes(value);
                cb.checked = isChecked;
                console.log(`Class checkbox ${value}: ${isChecked ? "checked" : "unchecked"}`);
            });

            // Set term checkboxes
            const termCheckboxes = document.querySelectorAll('#edit-term-checkboxes .term-checkbox');
            console.log("Available term checkboxes:", Array.from(termCheckboxes).map(cb => cb.value));
            termCheckboxes.forEach(cb => {
                const value = Number(cb.value);
                const isChecked = Array.isArray(term_ids) && term_ids.includes(value);
                cb.checked = isChecked;
                console.log(`Term checkbox ${value}: ${isChecked ? "checked" : "unchecked"}`);
            });

            // Set session radio
            const sessionRadio = document.querySelector(`#edit-session-${session_id}`);
            if (sessionRadio) {
                sessionRadio.checked = true;
                console.log("Session radio set to:", session_id);
            } else {
                console.error("Session radio button not found:", session_id);
            }

            // Open modal
            try {
                const modal = new bootstrap.Modal(document.getElementById("editSchoolBillTermSessionModal"));
                modal.show();
                console.log("Edit modal opened");
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
        })
        .catch(function (error) {
            console.error("Error fetching related records:", error.response?.status, error.response?.data || error.message);
            Swal.fire({
                position: "center",
                icon: "error",
                title: "Error loading data",
                text: error.response?.data?.message || "An error occurred while fetching related records",
                showConfirmButton: true
            });
            // Open modal anyway with empty form
            try {
                const modal = new bootstrap.Modal(document.getElementById("editSchoolBillTermSessionModal"));
                modal.show();
            } catch (modalError) {
                console.error("Error opening modal after fetch error:", modalError);
            }
        });
}

// Clear form fields
function clearAddFields() {
    if (addIdField) addIdField.value = "";
    if (addBillIdField) addBillIdField.value = "";
    if (addClassIdField) {
        addClassIdField.querySelectorAll('.class-checkbox').forEach(cb => cb.checked = false);
        const selectAll = document.getElementById('add-class-select-all');
        if (selectAll) selectAll.checked = false;
    }
    if (addTermIdField) {
        addTermIdField.querySelectorAll('.term-checkbox').forEach(cb => cb.checked = false);
    }
    if (addSessionIdField) {
        addSessionIdField.querySelectorAll('.session-radio').forEach(radio => radio.checked = false);
    }
}

function clearEditFields() {
    if (editIdField) editIdField.value = "";
    if (editBillIdField) editBillIdField.value = "";
    if (editClassIdField) {
        editClassIdField.querySelectorAll('.class-checkbox').forEach(cb => cb.checked = false);
        const selectAll = document.getElementById('edit-class-select-all');
        if (selectAll) selectAll.checked = false;
    }
    if (editTermIdField) {
        editTermIdField.querySelectorAll('.term-checkbox').forEach(cb => cb.checked = false);
    }
    if (editSessionIdField) {
        editSessionIdField.querySelectorAll('.session-radio').forEach(radio => radio.checked = false);
    }
}

// Delete multiple school bill term sessions
function deleteMultiple() {
    console.log("Delete multiple triggered");
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
            Promise.all(ids_array.map((id) => axios.delete(`/schoolbilltermsession/${id}`)))
                .then(() => {
                    Swal.fire({
                        title: "Deleted!",
                        text: "Your school bill term sessions have been deleted.",
                        icon: "success",
                        confirmButtonClass: "btn btn-info w-xs mt-2",
                        buttonsStyling: false
                    });
                    window.location.reload();
                })
                .catch((error) => {
                    console.error("Bulk delete error:", error);
                    Swal.fire({
                        title: "Error!",
                        text: error.response?.data?.message || "Failed to delete school bill term sessions",
                        icon: "error",
                        confirmButtonClass: "btn btn-info w-xs mt-2",
                        buttonsStyling: false
                    });
                });
        }
    });
}

// Initialize List.js for client-side filtering
let schoolBillTermSessionList;
const schoolBillTermSessionListContainer = document.getElementById('schoolBillTermSessionList');
if (schoolBillTermSessionListContainer && document.querySelectorAll('#schoolBillTermSessionList tbody tr').length > 0) {
    try {
        schoolBillTermSessionList = new List('schoolBillTermSessionList', {
            valueNames: ['sn', 'schoolbill', 'schoolclass', 'schoolterm', 'createdBy', 'updated_at'],
            page: 1000,
            pagination: false
        });
        console.log("List.js initialized");
    } catch (error) {
        console.error("List.js initialization failed:", error);
    }
} else {
    console.warn("No school bill term sessions available for List.js initialization");
}

// Update no results message
if (schoolBillTermSessionList) {
    schoolBillTermSessionList.on('searchComplete', function () {
        const noResultRow = document.querySelector('.noresult');
        if (noResultRow) {
            noResultRow.style.display = schoolBillTermSessionList.visibleItems.length === 0 ? 'block' : 'none';
        }
    });
}

// Filter data
function filterData() {
    const searchInput = document.querySelector(".search-box input.search");
    const searchValue = searchInput?.value || "";
    console.log("Filtering with search:", searchValue);
    if (schoolBillTermSessionList) {
        schoolBillTermSessionList.search(searchValue, ['sn', 'schoolbill', 'schoolclass', 'schoolterm', 'createdBy', 'updated_at']);
    }
}

// Add school bill term session
const addSchoolBillTermSessionForm = document.getElementById("add-schoolbilltermsession-form");
if (addSchoolBillTermSessionForm) {
    addSchoolBillTermSessionForm.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Add form submitted");
        const errorMsg = document.getElementById("alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");

        const formData = new FormData(addSchoolBillTermSessionForm);
        const data = {
            bill_id: formData.get('bill_id'),
            class_id: formData.getAll('class_id[]').map(Number),
            termid_id: formData.getAll('termid_id[]').map(Number),
            session_id: formData.get('session_id')
        };

        console.log("Form data:", data);

        if (!data.bill_id || data.class_id.length === 0 || data.termid_id.length === 0 || !data.session_id) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please fill all required fields: School Bill, at least one Class, at least one Term, and Session.";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        console.log("Sending add request:", data);
        axios.post('/schoolbilltermsession', data)
            .then(function (response) {
                console.log("Add success:", response.data);
                Swal.fire({
                    position: "center",
                    icon: "success",
                    title: "School Bill Term Session added successfully!",
                    showConfirmButton: false,
                    timer: 2000,
                    showCloseButton: true
                });
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById("addSchoolBillTermSessionModal"));
                if (modal) modal.hide();
                clearAddFields();
                window.location.reload();
            })
            .catch(function (error) {
                console.error("Add error:", error.response?.data || error);
                if (errorMsg) {
                    errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(", ") || "Error adding school bill term session";
                    errorMsg.classList.remove("d-none");
                }
            });
    });
}

// Edit school bill term session
const editSchoolBillTermSessionForm = document.getElementById("edit-schoolbilltermsession-form");
if (editSchoolBillTermSessionForm) {
    editSchoolBillTermSessionForm.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Edit form submitted");
        const errorMsg = document.getElementById("edit-alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");

        const formData = new FormData(editSchoolBillTermSessionForm);
        const id = editIdField?.value;
        const data = {
            bill_id: formData.get('bill_id'),
            class_id: formData.getAll('class_id[]').map(Number),
            termid_id: formData.getAll('termid_id[]').map(Number),
            session_id: formData.get('session_id')
        };

        console.log("Edit form data:", { id, ...data });

        if (!id || !data.bill_id || data.class_id.length === 0 || data.termid_id.length === 0 || !data.session_id) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please fill all required fields: School Bill, at least one Class, at least one Term, and Session.";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        console.log("Sending edit request:", { id, ...data });
        axios.put(`/schoolbilltermsession/${id}`, data)
            .then(function (response) {
                console.log("Edit success:", response.data);
                Swal.fire({
                    position: "center",
                    icon: "success",
                    title: "School Bill Term Session updated successfully!",
                    showConfirmButton: false,
                    timer: 2000,
                    showCloseButton: true
                });
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById("editSchoolBillTermSessionModal"));
                if (modal) modal.hide();
                clearEditFields();
                window.location.reload();
            })
            .catch(function (error) {
                console.error("Edit error:", error.response?.data || error);
                if (errorMsg) {
                    errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(", ") || "Error updating school bill term session";
                    errorMsg.classList.remove("d-none");
                }
            });
    });
}

// Modal events
const addModal = document.getElementById("addSchoolBillTermSessionModal");
if (addModal) {
    addModal.addEventListener("show.bs.modal", function () {
        console.log("Add modal show event");
        const modalLabel = document.getElementById("addSchoolBillTermSessionModalLabel");
        const addBtn = document.getElementById("add-btn");
        if (modalLabel) modalLabel.innerHTML = "Add School Bill Term Session";
        if (addBtn) addBtn.innerHTML = "Add School Bill Term Session";
        clearAddFields();
    });
    addModal.addEventListener("hidden.bs.modal", function () {
        console.log("Add modal hidden");
        clearAddFields();
        const errorMsg = document.getElementById("alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");
    });
}

const editModal = document.getElementById("editSchoolBillTermSessionModal");
if (editModal) {
    editModal.addEventListener("show.bs.modal", function () {
        console.log("Edit modal show event");
        const modalLabel = document.getElementById("editSchoolBillTermSessionModalLabel");
        const updateBtn = document.getElementById("update-btn");
        if (modalLabel) modalLabel.innerHTML = "Edit School Bill Term Session";
        if (updateBtn) updateBtn.innerHTML = "Update";
    });
    editModal.addEventListener("hidden.bs.modal", function () {
        console.log("Edit modal hidden");
        clearEditFields();
        const errorMsg = document.getElementById("edit-alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");
    });
}

// Initialize listeners
document.addEventListener("DOMContentLoaded", function () {
    console.log("DOMContentLoaded fired");
    const searchInput = document.querySelector(".search-box input.search");
    if (searchInput) {
        searchInput.addEventListener("input", debounce(function () {
            console.log("Search input changed:", searchInput.value);
            filterData();
        }, 300));
    } else {
        console.error("Search input not found");
    }
    initializeCheckboxes();

    // Reinitialize checkboxes and List.js on pagination link click
    document.querySelectorAll('.pagination a').forEach(link => {
        link.addEventListener('click', function (e) {
            console.log("Pagination link clicked");
            setTimeout(() => {
                initializeCheckboxes();
                if (schoolBillTermSessionList) {
                    schoolBillTermSessionList.reIndex();
                    filterData();
                }
            }, 500);
        });
    });
});

// Expose functions to global scope
window.deleteMultiple = deleteMultiple;
