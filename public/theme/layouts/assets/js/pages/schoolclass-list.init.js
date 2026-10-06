console.log("schoolclass-list.init.js is loaded and executing at", new Date().toISOString());

var perPage = 100;
var editlist = false;

try {
    if (typeof axios === 'undefined') throw new Error("Axios is not loaded");
    if (typeof Swal === 'undefined') throw new Error("SweetAlert2 is not loaded");
    if (typeof bootstrap === 'undefined') throw new Error("Bootstrap is not loaded");
    if (typeof List === 'undefined') throw new Error("List.js is not loaded");
    console.log("All dependencies loaded successfully");
} catch (error) {
    console.error("Dependency check failed:", error);
}

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
if (!csrfToken) console.warn("CSRF token not found");

const updateUrl = window.routeUrls?.updateSchoolClass || '/schoolclass/:id';
const getArmsUrl = window.routeUrls?.getArms || '/schoolclass/:id/arms';
console.log("URLs:", { updateUrl, getArmsUrl });

const options = {
    valueNames: ['schoolclassid', 'schoolclass', 'arm', 'classcategory', 'datereg'],
    page: perPage,
    pagination: false
};
const schoolClassList = new List('schoolClassList', options);

console.log("Initial schoolClassList items:", schoolClassList.items.length);

schoolClassList.on("updated", function (e) {
    console.log("List.js updated, matching items:", e.matchingItems.length, "total items:", schoolClassList.items.length);
    const noResult = document.querySelector(".noresult");
    if (noResult) {
        noResult.style.display = e.matchingItems.length === 0 ? "block" : "none";
    }
    setTimeout(() => {
        refreshCallbacks();
        ischeckboxcheck();
    }, 100);
});

const checkAll = document.getElementById("checkAll");
if (checkAll) {
    checkAll.onclick = function () {
        console.log("checkAll clicked");
        const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
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
        const removeActions = document.getElementById("remove-actions");
        if (removeActions) {
            removeActions.classList.toggle("d-none", checkedCount === 0);
        }
    };
}

const addIdField = document.getElementById("add-id-field");
const addSchoolClassField = document.getElementById("schoolclass");
const addArmCheckboxes = document.querySelectorAll('#add-arm-checkboxes input[name="arm_id[]"]');
const addCategoryCheckboxes = document.querySelectorAll('#add-category-checkboxes input[name="classcategoryid[]"]');
const editIdField = document.getElementById("edit-id-field");
const editSchoolClassField = document.getElementById("edit-schoolclass");
const editArmCheckboxes = document.querySelectorAll('#edit-arm-checkboxes input[name="arm_id[]"]');
const editCategoryCheckboxes = document.querySelectorAll('#edit-category-checkboxes input[name="classcategoryid[]"]');

document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM loaded, initializing List.js...");
    refreshCallbacks();
    ischeckboxcheck();

    const searchInput = document.querySelector(".search-box input.search");
    if (searchInput) {
        searchInput.addEventListener("input", function () {
            console.log("Search input changed:", searchInput.value);
            filterData();
        });
    } else {
        console.error("Search input not found");
    }

    initializeCheckboxLogic();
});

function ischeckboxcheck() {
    const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
    checkboxes.forEach((checkbox) => {
        checkbox.removeEventListener("change", handleCheckboxChange);
        checkbox.addEventListener("change", handleCheckboxChange);
    });
}

function handleCheckboxChange(e) {
    console.log("Checkbox changed:", e.target.checked);
    const row = e.target.closest("tr");
    if (e.target.checked) {
        row.classList.add("table-active");
    } else {
        row.classList.remove("table-active");
    }
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

function initializeCheckboxLogic() {
    const addModal = document.getElementById('addSchoolClassModal');
    if (addModal) {
        addModal.addEventListener('shown.bs.modal', function () {
            const addArmSelectAll = document.getElementById('add-arm-select-all');
            const addCategorySelectAll = document.getElementById('add-category-select-all');
            const addArmCheckboxes = document.querySelectorAll('#add-arm-checkboxes input[name="arm_id[]"]');
            const addCategoryCheckboxes = document.querySelectorAll('#add-category-checkboxes input[name="classcategoryid[]"]');

            console.log('Add modal checkboxes - Arms:', addArmCheckboxes.length, 'Categories:', addCategoryCheckboxes.length);

            // Multiple selection for arms
            addArmCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    updateSelectAllState(addArmSelectAll, addArmCheckboxes);
                });
            });

            // Single selection for categories
            addCategoryCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    if (this.checked) {
                        addCategoryCheckboxes.forEach(cb => {
                            if (cb !== this) cb.checked = false;
                        });
                    }
                    updateSelectAllState(addCategorySelectAll, addCategoryCheckboxes);
                });
            });

            if (addArmSelectAll) {
                addArmSelectAll.addEventListener('change', function () {
                    console.log('Add arm select all toggled:', this.checked);
                    addArmCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    updateSelectAllState(addArmSelectAll, addArmCheckboxes);
                });
            }

            if (addCategorySelectAll) {
                addCategorySelectAll.addEventListener('change', function () {
                    console.log('Add category select all toggled:', this.checked);
                    addCategoryCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                        if (this.checked) {
                            addCategoryCheckboxes.forEach(cb => {
                                if (cb !== checkbox) cb.checked = false;
                            });
                        }
                    });
                    updateSelectAllState(addCategorySelectAll, addCategoryCheckboxes);
                });
            }

            updateSelectAllState(addArmSelectAll, addArmCheckboxes);
            updateSelectAllState(addCategorySelectAll, addCategoryCheckboxes);
        });
    }

    const editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('shown.bs.modal', function () {
            console.log('Edit modal shown explicitly at', new Date().toISOString());
            const editArmSelectAll = document.getElementById('edit-arm-select-all');
            const editCategorySelectAll = document.getElementById('edit-category-select-all');
            const editArmCheckboxes = document.querySelectorAll('#edit-arm-checkboxes input[name="arm_id[]"]');
            const editCategoryCheckboxes = document.querySelectorAll('#edit-category-checkboxes input[name="classcategoryid[]"]');

            console.log('Edit modal checkboxes - Arms:', editArmCheckboxes.length, 'Categories:', editCategoryCheckboxes.length);

            // Single selection for arms
            editArmCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    if (this.checked) {
                        editArmCheckboxes.forEach(cb => {
                            if (cb !== this) cb.checked = false;
                        });
                    }
                    updateSelectAllState(editArmSelectAll, editArmCheckboxes);
                });
            });

            // Single selection for categories
            editCategoryCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    if (this.checked) {
                        editCategoryCheckboxes.forEach(cb => {
                            if (cb !== this) cb.checked = false;
                        });
                    }
                    updateSelectAllState(editCategorySelectAll, editCategoryCheckboxes);
                });
            });

            if (editArmSelectAll) {
                editArmSelectAll.addEventListener('change', function () {
                    console.log('Edit arm select all toggled:', this.checked);
                    editArmCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                        if (this.checked) {
                            editArmCheckboxes.forEach(cb => {
                                if (cb !== checkbox) cb.checked = false;
                            });
                        }
                    });
                    updateSelectAllState(editArmSelectAll, editArmCheckboxes);
                });
            }

            if (editCategorySelectAll) {
                editCategorySelectAll.addEventListener('change', function () {
                    console.log('Edit category select all toggled:', this.checked);
                    editCategoryCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                        if (this.checked) {
                            editCategoryCheckboxes.forEach(cb => {
                                if (cb !== checkbox) cb.checked = false;
                            });
                        }
                    });
                    updateSelectAllState(editCategorySelectAll, editCategoryCheckboxes);
                });
            }

            updateSelectAllState(editArmSelectAll, editArmCheckboxes);
            updateSelectAllState(editCategorySelectAll, editCategoryCheckboxes);
        });
    }

    function updateSelectAllState(selectAllCheckbox, checkboxes) {
        if (!selectAllCheckbox || !checkboxes.length) return;
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        const someChecked = Array.from(checkboxes).some(cb => cb.checked);
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = someChecked && !allChecked;
        console.log('Select all state updated:', { allChecked, someChecked, indeterminate: selectAllCheckbox.indeterminate });
    }
}

function refreshCallbacks() {
    console.log("refreshCallbacks executed at", new Date().toISOString());
    const removeButtons = document.getElementsByClassName("remove-item-btn");
    const editButtons = document.getElementsByClassName("edit-item-btn");
    console.log("Attaching event listeners to", removeButtons.length, "remove buttons and", editButtons.length, "edit buttons");

    Array.from(removeButtons).forEach(function (btn) {
        btn.removeEventListener("click", handleRemoveClick);
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            handleRemoveClick(e);
        });
    });

    Array.from(editButtons).forEach(function (btn) {
        btn.removeEventListener("click", handleEditClick);
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            console.log("Edit button clicked explicitly at", new Date().toISOString());
            handleEditClick(e);
        });
    });
}

function handleRemoveClick(e) {
    try {
        const itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
        console.log("Remove button clicked for ID:", itemId);

        const editModal = document.getElementById("editModal");
        if (editModal && bootstrap.Modal.getInstance(editModal)) {
            bootstrap.Modal.getInstance(editModal).hide();
            console.log("Closed edit modal");
        }
        const addModal = document.getElementById("addSchoolClassModal");
        if (addModal && bootstrap.Modal.getInstance(addModal)) {
            bootstrap.Modal.getInstance(addModal).hide();
            console.log("Closed add modal");
        }

        const modalElement = document.getElementById("deleteRecordModal");
        if (!modalElement) {
            console.error("Delete modal element not found in DOM");
            Swal.fire({
                position: "center",
                icon: "error",
                title: "Error",
                text: "Delete modal not found in DOM",
                showConfirmButton: true
            });
            return;
        }

        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        console.log("Delete modal opened");

        const deleteButton = document.getElementById("delete-record");
        if (deleteButton) {
            console.log("Delete button found, attaching onclick");
            deleteButton.onclick = function () {
                console.log("Deleting school class ID:", itemId);
                axios.delete(`/schoolclass/${itemId}`, {
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                }).then(function (response) {
                    console.log("Delete success:", response.data);
                    modal.hide();
                    window.location.reload();
                    Swal.fire({
                        position: "center",
                        icon: "success",
                        title: response.data.message || "School class deleted successfully!",
                        showConfirmButton: false,
                        timer: 2000,
                        showCloseButton: true
                    });
                }).catch(function (error) {
                    console.error("Delete error:", error.response?.data || error.message);
                    modal.hide();
                    Swal.fire({
                        position: "center",
                        icon: "error",
                        title: "Error deleting school class",
                        text: error.response?.data?.message || "An error occurred",
                        showConfirmButton: true
                    });
                });
            };
        } else {
            console.error("Delete button not found in deleteRecordModal");
        }
    } catch (error) {
        console.error("Error in remove-item-btn click:", error);
        Swal.fire({
            position: "center",
            icon: "error",
            title: "Error",
            text: "Failed to open delete modal: " + error.message,
            showConfirmButton: true
        });
    }
}

function handleEditClick(e) {
    try {
        const itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
        console.log("Edit button clicked for ID:", itemId, "at", new Date().toISOString());
        const tr = e.target.closest("tr");
        editlist = true;
        editIdField.value = itemId;
        editSchoolClassField.value = tr.querySelector(".schoolclass").innerText;
        const categoryName = tr.querySelector(".classcategory").innerText;

        // Reset arm checkboxes
        const editArmCheckboxes = document.querySelectorAll('#edit-arm-checkboxes input[name="arm_id[]"]');
        editArmCheckboxes.forEach(checkbox => checkbox.checked = false);
        console.log("Available arm checkbox values:", Array.from(editArmCheckboxes).map(cb => cb.value));

        // Fetch arms
        const armsUrl = getArmsUrl.replace(':id', itemId);
        axios.get(armsUrl, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).then(response => {
            if (response.data.success) {
                const armId = response.data.armIds[0]?.toString();
                console.log("Arm fetched for ID", itemId, ":", armId);
                editArmCheckboxes.forEach(checkbox => {
                    const isChecked = checkbox.value === armId;
                    checkbox.checked = isChecked;
                    console.log(`Checkbox value=${checkbox.value} checked=${isChecked}`);
                });

                // Update select all state
                const editArmSelectAll = document.getElementById('edit-arm-select-all');
                if (editArmSelectAll) {
                    const allChecked = Array.from(editArmCheckboxes).every(cb => cb.checked);
                    const someChecked = Array.from(editArmCheckboxes).some(cb => cb.checked);
                    editArmSelectAll.checked = allChecked;
                    editArmSelectAll.indeterminate = someChecked && !allChecked;
                }

                // Open modal after setting arms
                const modal = new bootstrap.Modal(document.getElementById("editModal"));
                modal.show();
                console.log("Edit modal opened");
            } else {
                console.error("Get arms failed:", response.data.message);
                Swal.fire({
                    position: "center",
                    icon: "error",
                    title: "Failed to load arms",
                    text: response.data.message || "Unknown error",
                    showConfirmButton: true
                });
                // Open modal anyway
                const modal = new bootstrap.Modal(document.getElementById("editModal"));
                modal.show();
            }
        }).catch(error => {
            console.error("Error fetching arms for ID", itemId, ":", error.response?.data || error.message);
            Swal.fire({
                position: "center",
                icon: "error",
                title: "Error loading arms",
                text: error.response?.data?.message || error.message,
                showConfirmButton: true
            });
            // Open modal anyway
            const modal = new bootstrap.Modal(document.getElementById("editModal"));
            modal.show();
        });

        // Set category
        const editCategoryCheckboxes = document.querySelectorAll('#edit-category-checkboxes input[name="classcategoryid[]"]');
        editCategoryCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
            const label = checkbox.parentElement.querySelector("label").innerText;
            if (label.toLowerCase() === categoryName.toLowerCase()) {
                checkbox.checked = true;
                console.log("Category selected:", label);
            }
        });

        // Update category select all state
        const editCategorySelectAll = document.getElementById('edit-category-select-all');
        if (editCategorySelectAll) {
            const allChecked = Array.from(editCategoryCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(editCategoryCheckboxes).some(cb => cb.checked);
            editCategorySelectAll.checked = allChecked;
            editCategorySelectAll.indeterminate = someChecked && !allChecked;
        }
    } catch (error) {
        console.error("Error in edit-item-btn click:", error);
        Swal.fire({
            position: "center",
            icon: "error",
            title: "Error opening edit modal",
            text: error.message,
            showConfirmButton: true
        });
    }
}

function clearAddFields() {
    if (addIdField) addIdField.value = "";
    if (addSchoolClassField) addSchoolClassField.value = "";
    document.querySelectorAll('#add-arm-checkboxes input[name="arm_id[]"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('#add-category-checkboxes input[name="classcategoryid[]"]').forEach(cb => cb.checked = false);
    const addArmSelectAll = document.getElementById('add-arm-select-all');
    const addCategorySelectAll = document.getElementById('add-category-select-all');
    if (addArmSelectAll) addArmSelectAll.checked = false;
    if (addCategorySelectAll) addCategorySelectAll.checked = false;
    const errorMsg = document.getElementById("alert-error-msg");
    if (errorMsg) errorMsg.classList.add("d-none");
}

function clearEditFields() {
    if (editIdField) editIdField.value = "";
    if (editSchoolClassField) editSchoolClassField.value = "";
    document.querySelectorAll('#edit-arm-checkboxes input[name="arm_id[]"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('#edit-category-checkboxes input[name="classcategoryid[]"]').forEach(cb => cb.checked = false);
    const editArmSelectAll = document.getElementById('edit-arm-select-all');
    const editCategorySelectAll = document.getElementById('edit-category-select-all');
    if (editArmSelectAll) editArmSelectAll.checked = false;
    if (editCategorySelectAll) editCategorySelectAll.checked = false;
    const errorMsg = document.getElementById("edit-alert-error-msg");
    if (errorMsg) errorMsg.classList.add("d-none");
}

function deleteMultiple() {
    console.log("Delete multiple triggered");
    const ids_array = [];
    const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]:checked');
    checkboxes.forEach((checkbox) => {
        const id = checkbox.closest("tr").querySelector(".id").getAttribute("data-id");
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
            Promise.all(ids_array.map((id) => axios.delete(`/schoolclass/${id}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            }))).then(() => {
                window.location.reload();
                Swal.fire({
                    title: "Deleted!",
                    text: "Your school classes have been deleted.",
                    icon: "success",
                    confirmButtonClass: "btn btn-info w-xs mt-2",
                    buttonsStyling: false
                });
            }).catch((error) => {
                console.error("Bulk delete error:", error.response?.data || error.message);
                Swal.fire({
                    title: "Error!",
                    text: error.response?.data?.message || "Failed to delete school classes",
                    icon: "error",
                    confirmButtonClass: "btn btn-info w-xs mt-2",
                    buttonsStyling: false
                });
            });
        }
    });
}

function filterData() {
    const searchInput = document.querySelector(".search-box input.search").value.toLowerCase();
    console.log("Filtering with search:", searchInput);

    schoolClassList.filter(function (item) {
        const classMatch = item.values().schoolclass.toLowerCase().includes(searchInput);
        const armMatch = item.values().arm.toLowerCase().includes(searchInput);
        const categoryMatch = item.values().classcategory.toLowerCase().includes(searchInput);
        return classMatch || armMatch || categoryMatch;
    });
}

const addSchoolClassForm = document.getElementById("add-schoolclass-form");
if (addSchoolClassForm) {
    addSchoolClassForm.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Add form submitted");
        const errorMsg = document.getElementById("alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");

        const schoolclass = addSchoolClassField.value;
        const arm_id = Array.from(document.querySelectorAll('#add-arm-checkboxes input[name="arm_id[]"]:checked')).map(cb => cb.value);
        const classcategoryid = Array.from(document.querySelectorAll('#add-category-checkboxes input[name="classcategoryid[]"]:checked')).map(cb => cb.value)[0] || '';

        if (!schoolclass || !arm_id.length || !classcategoryid) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please fill in all required fields";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        console.log("Sending add request:", { schoolclass, arm_id, classcategoryid });
        axios.post('/schoolclass', {
            schoolclass,
            arm_id,
            classcategoryid,
            _token: csrfToken
        }).then(function (response) {
            console.log("Add success:", response.data);
            window.location.reload();
            Swal.fire({
                position: "center",
                icon: "success",
                title: "School class added successfully!",
                showConfirmButton: false,
                timer: 2000,
                showCloseButton: true
            });
        }).catch(function (error) {
            console.error("Add error:", error.response?.data || error.message);
            if (errorMsg) {
                const errors = error.response?.data?.errors;
                let errorMessage = "Error adding school class";
                if (errors && errors.schoolclass) {
                    errorMessage = errors.schoolclass.join(", ");
                } else if (error.response?.data?.message) {
                    errorMessage = error.response.data.message;
                }
                errorMsg.innerHTML = errorMessage;
                errorMsg.classList.remove("d-none");
                Swal.fire({
                    position: "center",
                    icon: "error",
                    title: "Error adding school class",
                    text: errorMessage,
                    showConfirmButton: true
                });
            }
        });
    });
}

const editSchoolClassForm = document.getElementById("edit-schoolclass-form");
if (editSchoolClassForm) {
    editSchoolClassForm.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Edit form submitted at", new Date().toISOString());
        const errorMsg = document.getElementById("edit-alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");

        const schoolclass = editSchoolClassField.value;
        const arm_id = Array.from(document.querySelectorAll('#edit-arm-checkboxes input[name="arm_id[]"]:checked')).map(cb => cb.value);
        const classcategoryid = Array.from(document.querySelectorAll('#edit-category-checkboxes input[name="classcategoryid[]"]:checked')).map(cb => cb.value)[0] || '';
        const id = editIdField.value;

        if (!id || !schoolclass || arm_id.length !== 1 || !classcategoryid) {
            console.error("Validation failed:", { id, schoolclass, arm_id, classcategoryid });
            if (errorMsg) {
                errorMsg.innerHTML = "Please fill in all required fields and select exactly one arm";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        const url = updateUrl.replace(':id', id);
        console.log("Sending edit request to:", url, "with data:", { schoolclass, arm_id, classcategoryid, _method: 'PUT' });

        axios.post(url, {
            _method: 'PUT',
            schoolclass,
            arm_id,
            classcategoryid,
            _token: csrfToken
        }).then(function (response) {
            console.log("Edit success:", response.status, response.data);
            window.location.reload();
            Swal.fire({
                position: "center",
                icon: "success",
                title: response.data.message || "School class updated successfully!",
                showConfirmButton: false,
                timer: 2000,
                showCloseButton: true
            });
        }).catch(function (error) {
            console.error("Edit error:", error.response?.status, error.response?.data || error.message);
            const errors = error.response?.data?.errors;
            let errorMsgText = "Failed to update school class";
            if (errors && errors.schoolclass) {
                errorMsgText = errors.schoolclass.join(", ");
            } else if (error.response?.data?.message) {
                errorMsgText = error.response.data.message;
            }
            if (errorMsg) {
                errorMsg.innerHTML = errorMsgText;
                errorMsg.classList.remove("d-none");
            }
            Swal.fire({
                position: "center",
                icon: "error",
                title: "Error updating school class",
                text: errorMsgText,
                showConfirmButton: true
            });
        });
    });
}

function handleError(error, errorMsg) {
    const errors = error.response?.data?.errors;
    let errorMessage = "Error processing request";
    if (errors && errors.schoolclass) {
        errorMessage = errors.schoolclass.join(", ");
    } else if (error.response?.data?.message) {
        errorMessage = error.response.data.message;
    } else if (error.message) {
        errorMessage = error.message;
    }
    console.error("Error details:", errorMessage);
    if (errorMsg) {
        errorMsg.innerHTML = errorMessage;
        errorMsg.classList.remove("d-none");
    }
}

const addModal = document.getElementById("addSchoolClassModal");
if (addModal) {
    addModal.addEventListener("show.bs.modal", function () {
        console.log("Add modal show event");
        const modalLabel = document.getElementsByClassName("modal-title")[0];
        const addBtn = document.getElementById("add-btn");
        if (modalLabel) modalLabel.innerHTML = "Add School Class";
        if (addBtn) addBtn.innerHTML = "Add Class";
    });
    addModal.addEventListener("hidden.bs.modal", function () {
        console.log("Add modal hidden");
        clearAddFields();
    });
}

const editModal = document.getElementById("editModal");
if (editModal) {
    editModal.addEventListener("shown.bs.modal", function () {
        console.log("Edit modal shown explicitly at", new Date().toISOString());
    });
    editModal.addEventListener("hidden.bs.modal", function () {
        console.log("Edit modal hidden");
        clearEditFields();
    });
}

window.deleteMultiple = deleteMultiple;
