console.log("compulsorysubjectclass.init.js is loaded and executing!");

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
const addSchoolClassIdField = document.getElementById("schoolclassid");
const editIdField = document.getElementById("edit-id-field");
const editSchoolClassIdField = document.getElementById("edit-schoolclassid");

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

// Explicit event listener for Create Compulsory Subject Class button
const createButton = document.querySelector('.add-btn');
if (createButton) {
    createButton.addEventListener('click', function (e) {
        e.preventDefault();
        console.log("Create Compulsory Subject Class button clicked");
        try {
            const modal = new bootstrap.Modal(document.getElementById("addCompulsorySubjectClassModal"));
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

// Delete single compulsory subject class
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
    console.log("Delete modal opened");

    const deleteButton = document.getElementById("delete-record");
    if (deleteButton) {
        deleteButton.onclick = null;
        deleteButton.onclick = function () {
            console.log("Deleting compulsory subject class:", itemId);
            
            axios.delete(deleteUrl)
                .then(function (response) {
                    console.log("Delete success:", response.data);
                    
                    Swal.fire({
                        position: "center",
                        icon: "success",
                        title: response.data.message || "Compulsory Subject Class deleted successfully!",
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
                        title: "Error deleting compulsory subject class",
                        text: error.response?.data?.message || "An error occurred",
                        showConfirmButton: true
                    });
                    modal.hide();
                });
        };
    }
}

// Edit compulsory subject class
function handleEditClick(e, button) {
    e.preventDefault();
    console.log("Edit button clicked");
    
    const itemId = button.closest("tr").querySelector(".id")?.getAttribute("data-id");
    const tr = button.closest("tr");
    
    if (!itemId) {
        console.error("Item ID not found");
        return;
    }
    
    const schoolClassId = tr.querySelector(".sclass")?.getAttribute("data-schoolclassid") || "";
    const subjectId = tr.querySelector(".subject")?.getAttribute("data-subjectid") || "";
    
    console.log("Edit data:", { itemId, schoolClassId, subjectId });
    
    if (editIdField) editIdField.value = itemId;
    if (editSchoolClassIdField) editSchoolClassIdField.value = schoolClassId;
    
    document.querySelectorAll('#editModal input[name="subjectId[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    if (subjectId) {
        const subjectCheckbox = document.querySelector(`#editModal input[name="subjectId[]"][value="${subjectId}"]`);
        if (subjectCheckbox) {
            subjectCheckbox.checked = true;
        }
    }

    try {
        const modal = new bootstrap.Modal(document.getElementById("editModal"));
        modal.show();
        console.log("Edit modal opened with data populated");
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
    if (addSchoolClassIdField) addSchoolClassIdField.value = "";
    document.querySelectorAll('#addCompulsorySubjectClassModal input[name="subjectId[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function clearEditFields() {
    if (editIdField) editIdField.value = "";
    if (editSchoolClassIdField) editSchoolClassIdField.value = "";
    document.querySelectorAll('#editModal input[name="subjectId[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

// Delete multiple compulsory subject classes
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
            Promise.all(ids_array.map((id) => axios.delete(`/compulsorysubjectclass/${id}`)))
                .then(() => {
                    Swal.fire({
                        title: "Deleted!",
                        text: "Your compulsory subject classes have been deleted.",
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
                        text: error.response?.data?.message || "Failed to delete compulsory subject classes",
                        icon: "error",
                        confirmButtonClass: "btn btn-info w-xs mt-2",
                        buttonsStyling: false
                    });
                });
        }
    });
}

// Initialize List.js for client-side filtering and pagination
let compulsorySubjectClassList;

function initializeListJS() {
    const compulsorySubjectClassListContainer = document.getElementById('compulsorySubjectClassList');
    const hasRows = document.querySelectorAll('#compulsorySubjectClassList tbody tr:not(.noresult)').length > 0;
    
    if (compulsorySubjectClassListContainer && hasRows) {
        try {
            if (compulsorySubjectClassList) {
                compulsorySubjectClassList.clear();
            }
            
            compulsorySubjectClassList = new List('compulsorySubjectClassList', {
                valueNames: ['sn', 'subject', 'sclass', 'schoolarm', 'datereg'],
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
            
            console.log("List.js initialized with pagination");
            
            compulsorySubjectClassList.on('updated', function () {
                const totalRecords = compulsorySubjectClassList.items.length;
                const visibleRecords = compulsorySubjectClassList.visibleItems.length;
                const showingRecords = Math.min(visibleRecords, compulsorySubjectClassList.page);
                const currentPage = Math.ceil((compulsorySubjectClassList.i - 1) / compulsorySubjectClassList.page) + 1;
                
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
            });
            
            compulsorySubjectClassList.update();
            
        } catch (error) {
            console.error("List.js initialization failed:", error);
        }
    } else {
        console.warn("No compulsory subject classes available for List.js initialization");
        document.getElementById('showing-records').textContent = 0;
        document.getElementById('total-records').textContent = 0;
        document.getElementById('total-records-footer').textContent = 0;
    }
}

// Filter data (client-side)
function filterData() {
    const searchInput = document.querySelector(".search-box input.search");
    const searchValue = searchInput?.value || "";
    
    console.log("Filtering with search:", searchValue);
    
    if (compulsorySubjectClassList) {
        compulsorySubjectClassList.search(searchValue, ['sn', 'subject', 'sclass', 'schoolarm']);
        compulsorySubjectClassList.update();
    }
}

// Refresh table after CRUD operations
function refreshTable() {
    console.log("Refreshing table...");
    window.location.reload();
}

// Add compulsory subject class
const addCompulsorySubjectClassForm = document.getElementById("add-compulsorysubjectclass-form");
if (addCompulsorySubjectClassForm) {
    addCompulsorySubjectClassForm.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Add form submitted at", new Date().toISOString());

        const errorMsg = document.getElementById("alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");

        const formData = new FormData(addCompulsorySubjectClassForm);
        const schoolclassid = formData.get('schoolclassid');
        const subjectIds = formData.getAll('subjectId[]');

        console.log("Form data:", { schoolclassid, subjectIds });

        if (!schoolclassid || schoolclassid === "") {
            if (errorMsg) {
                errorMsg.innerHTML = "Please select a class";
                errorMsg.classList.remove("d-none");
            }
            return;
        }
        
        if (subjectIds.length === 0) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please select at least one subject";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        const submitBtn = document.getElementById("add-btn");
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = "Adding...";
        }

        console.log("Sending add request:", { schoolclassid, subjectIds });

        axios.post('/compulsorysubjectclass', {
            schoolclassid,
            subjectId: subjectIds
        }, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(function (response) {
            console.log("Add success:", response.data);
            
            const modalElement = document.getElementById("addCompulsorySubjectClassModal");
            const modal = bootstrap.Modal.getInstance(modalElement);
            
            if (modal) {
                modal.hide();
            }
            
            setTimeout(() => {
                Swal.fire({
                    position: "center",
                    icon: "success",
                    title: response.data.message || "Compulsory Subject Class(es) added successfully!",
                    showConfirmButton: false,
                    timer: 2000,
                    showCloseButton: true
                });
                
                setTimeout(() => refreshTable(), 500);
            }, 300);
        })
        .catch(function (error) {
            console.error("Add error:", error.response?.data || error);
            
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = "Add Compulsory Subject Class";
            }
            
            if (errorMsg) {
                errorMsg.innerHTML = error.response?.data?.message ||
                    Object.values(error.response?.data?.errors || {}).flat().join(", ") ||
                    "Error adding compulsory subject class";
                errorMsg.classList.remove("d-none");
            }
        });
    });
}

// Edit compulsory subject class
const editCompulsorySubjectClassForm = document.getElementById("edit-compulsorysubjectclass-form");
if (editCompulsorySubjectClassForm) {
    editCompulsorySubjectClassForm.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Edit form submitted");
        
        const errorMsg = document.getElementById("edit-alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");
        
        const formData = new FormData(editCompulsorySubjectClassForm);
        const schoolclassid = formData.get('schoolclassid');
        const subjectIds = formData.getAll('subjectId[]');
        const id = editIdField?.value;
        
        if (!id || !schoolclassid || subjectIds.length === 0) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please select a class and at least one subject";
                errorMsg.classList.remove("d-none");
                console.warn("Form validation failed: Invalid ID, class, or subject");
            }
            return;
        }
        
        const submitBtn = document.getElementById("update-btn");
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = "Updating...";
        }
        
        console.log("Sending edit request:", { id, schoolclassid, subjectIds });
        
        const subjectId = subjectIds[0];
        
        axios.put(`/compulsorysubjectclass/${id}`, { schoolclassid, subjectId })
            .then(function (response) {
                console.log("Edit success:", response.data);
                
                const modalElement = document.getElementById("editModal");
                const modal = bootstrap.Modal.getInstance(modalElement);
                
                if (modal) {
                    modal.hide();
                }
                
                setTimeout(() => {
                    Swal.fire({
                        position: "center",
                        icon: "success",
                        title: response.data.message || "Compulsory Subject Class updated successfully!",
                        showConfirmButton: false,
                        timer: 2000,
                        showCloseButton: true
                    });
                    
                    setTimeout(() => refreshTable(), 500);
                }, 300);
            })
            .catch(function (error) {
                console.error("Edit error:", error.response?.data || error);
                
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = "Update";
                }
                
                if (errorMsg) {
                    errorMsg.innerHTML = error.response?.data?.message ||
                        Object.values(error.response?.data?.errors || {}).flat().join(", ") ||
                        "Error updating compulsory subject class";
                    errorMsg.classList.remove("d-none");
                }
            });
    });
}

// Modal events
const addModal = document.getElementById("addCompulsorySubjectClassModal");
if (addModal) {
    addModal.addEventListener("show.bs.modal", function (e) {
        console.log("Add modal show event");
        
        const modalLabel = document.getElementById("exampleModalLabel");
        const addBtn = document.getElementById("add-btn");
        
        if (modalLabel) modalLabel.innerHTML = "Add Compulsory Subject Class";
        if (addBtn) {
            addBtn.innerHTML = "Add Compulsory Subject Class";
            addBtn.disabled = true;
        }
        
        const updateSubmitButton = () => {
            const schoolclassid = document.getElementById("schoolclassid")?.value;
            const checkedSubjects = document.querySelectorAll('#addCompulsorySubjectClassModal input[name="subjectId[]"]:checked').length;
            if (addBtn) addBtn.disabled = !schoolclassid || checkedSubjects === 0;
        };
        
        document.getElementById("schoolclassid")?.addEventListener("change", updateSubmitButton);
        document.querySelectorAll('#addCompulsorySubjectClassModal input[name="subjectId[]"]').forEach(cb => {
            cb.addEventListener("change", updateSubmitButton);
        });
    });
    
    addModal.addEventListener("hidden.bs.modal", function () {
        console.log("Add modal hidden - cleaning up");
        clearAddFields();
        const errorMsg = document.getElementById("alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");
        
        const addBtn = document.getElementById("add-btn");
        if (addBtn) {
            addBtn.disabled = true;
            addBtn.innerHTML = "Add Compulsory Subject Class";
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

const editModal = document.getElementById("editModal");
if (editModal) {
    editModal.addEventListener("show.bs.modal", function () {
        console.log("Edit modal show event");
        
        const modalLabel = document.getElementById("editModalLabel");
        const updateBtn = document.getElementById("update-btn");
        
        if (modalLabel) modalLabel.innerHTML = "Edit Compulsory Subject Class";
        if (updateBtn) {
            updateBtn.innerHTML = "Update";
            updateBtn.disabled = false;
        }
    });
    
    editModal.addEventListener("hidden.bs.modal", function () {
        console.log("Edit modal hidden - cleaning up");
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
    initializeListJS();
});

// Expose functions to global scope
window.deleteMultiple = deleteMultiple;