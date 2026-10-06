// myclass-list.init.js

console.log("myclass-list.init.js is loaded and executing at", new Date().toISOString());

var perPage = 5;
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

const options = {
    valueNames: ['classid', 'schoolclass', 'schoolarm', 'term', 'session', 'classcategory', 'updated_at'],
    page: perPage,
    pagination: false
};
const classList = new List('classList', options);

console.log("Initial classList items:", classList.items.length);

classList.on("updated", function (e) {
    console.log("List.js updated, matching items:", e.matchingItems.length, "total items:", classList.items.length);
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

// Form Field References
const addIdField = document.getElementById("add-id-field");
const addSchoolClassIdField = document.getElementById("vschoolclassid");
const addTermIdField = document.getElementById("termid");
const addSessionIdField = document.getElementById("sessionid");
const addNoSchoolOpenedField = document.getElementById("noschoolopened");
const addTermEndsField = document.getElementById("termends");
const addNextTermBeginsField = document.getElementById("nexttermbegins");
const editIdField = document.getElementById("edit-id-field");
const editSchoolClassIdField = document.getElementById("edit-vschoolclassid");
const editTermIdField = document.getElementById("edit-termid");
const editSessionIdField = document.getElementById("edit-sessionid");
const editNoSchoolOpenedField = document.getElementById("edit-noschoolopened");
const editTermEndsField = document.getElementById("edit-termends");
const editNextTermBeginsField = document.getElementById("edit-nexttermbegins");

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

    const classSelect = document.getElementById("idclass");
    const sessionSelect = document.getElementById("idsession");
    if (classSelect && sessionSelect) {
        classSelect.addEventListener("change", filterData);
        sessionSelect.addEventListener("change", filterData);
    } else {
        console.error("Class or session select elements not found");
    }
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

function refreshCallbacks() {
    console.log("refreshCallbacks executed at", new Date().toISOString());
    const removeButtons = document.getElementsByClassName("remove-item-btn");
    const editButtons = document.getElementsByClassName("edit-item-btn");
    console.log("Attaching event listeners to", removeButtons.length, "remove buttons and", editButtons.length, "edit buttons");

    Array.from(removeButtons).forEach(function (btn) {
        btn.removeEventListener("click", handleRemoveClick);
        btn.addEventListener("click", handleRemoveClick);
    });

    Array.from(editButtons).forEach(function (btn) {
        btn.removeEventListener("click", handleEditClick);
        btn.addEventListener("click", handleEditClick);
    });
}

function handleRemoveClick(e) {
    try {
        const itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
        console.log("Remove button clicked for ID:", itemId);

        const editModal = document.getElementById("editModal");
        if (editModal && bootstrap.Modal.getInstance(editModal)) {
            bootstrap.Modal.getInstance(editModal).hide();
        }
        const addModal = document.getElementById("addClassModal");
        if (addModal && bootstrap.Modal.getInstance(addModal)) {
            bootstrap.Modal.getInstance(addModal).hide();
        }

        const modalElement = document.getElementById("deleteRecordModal");
        if (!modalElement) {
            console.error("Delete modal element not found");
            Swal.fire({
                position: "center",
                icon: "error",
                title: "Error",
                text: "Delete modal not found",
                showConfirmButton: true
            });
            return;
        }

        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        console.log("Delete modal opened");

        const deleteButton = document.getElementById("delete-record");
        if (deleteButton) {
            deleteButton.onclick = function () {
                console.log("Deleting class setting ID:", itemId);
                axios.delete(`/myclass/${itemId}`, {
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                }).then(function (response) {
                    console.log("Delete success:", response.data);
                    modal.hide();
                    window.location.reload();
                    Swal.fire({
                        position: "center",
                        icon: "success",
                        title: response.data.message || "Class setting deleted successfully!",
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
                        title: "Error deleting class setting",
                        text: error.response?.data?.message || "An error occurred",
                        showConfirmButton: true
                    });
                });
            };
        } else {
            console.error("Delete button not found");
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
        console.log("Edit button clicked for ID:", itemId);
        axios.get(`/myclass/${itemId}/edit`, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).then(function (response) {
            console.log("Edit fetch success:", response.data);
            const setting = response.data.setting;
            editlist = true;
            editIdField.value = setting.id;
            editSchoolClassIdField.value = setting.vschoolclassid;
            editTermIdField.value = setting.termid;
            editSessionIdField.value = setting.sessionid;
            editNoSchoolOpenedField.value = setting.noschoolopened || '';
            editTermEndsField.value = setting.termends || '';
            editNextTermBeginsField.value = setting.nexttermbegins || '';
            const modal = new bootstrap.Modal(document.getElementById("editModal"));
            modal.show();
            console.log("Edit modal opened");
        }).catch(function (error) {
            console.error("Edit fetch error:", error.response?.data || error.message);
            Swal.fire({
                position: "center",
                icon: "error",
                title: "Error loading class setting",
                text: error.response?.data?.message || error.message,
                showConfirmButton: true
            });
        });
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
    if (addSchoolClassIdField) addSchoolClassIdField.value = "";
    if (addTermIdField) addTermIdField.value = "";
    if (addSessionIdField) addSessionIdField.value = "";
    if (addNoSchoolOpenedField) addNoSchoolOpenedField.value = "";
    if (addTermEndsField) addTermEndsField.value = "";
    if (addNextTermBeginsField) addNextTermBeginsField.value = "";
    const errorMsg = document.getElementById("alert-error-msg");
    if (errorMsg) errorMsg.classList.add("d-none");
}

function clearEditFields() {
    if (editIdField) editIdField.value = "";
    if (editSchoolClassIdField) editSchoolClassIdField.value = "";
    if (editTermIdField) editTermIdField.value = "";
    if (editSessionIdField) editSessionIdField.value = "";
    if (editNoSchoolOpenedField) editNoSchoolOpenedField.value = "";
    if (editTermEndsField) editTermEndsField.value = "";
    if (editNextTermBeginsField) editNextTermBeginsField.value = "";
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
            Promise.all(ids_array.map((id) => axios.delete(`/myclass/${id}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            }))).then(() => {
                window.location.reload();
                Swal.fire({
                    title: "Deleted!",
                    text: "Your class settings have been deleted.",
                    icon: "success",
                    confirmButtonClass: "btn btn-info w-xs mt-2",
                    buttonsStyling: false
                });
            }).catch((error) => {
                console.error("Bulk delete error:", error.response?.data || error.message);
                Swal.fire({
                    title: "Error!",
                    text: error.response?.data?.message || "Failed to delete class settings",
                    icon: "error",
                    confirmButtonClass: "btn btn-info w-xs mt-2",
                    buttonsStyling: false
                });
            });
        }
    });
}

function filterData() {
    console.log("filterData called");
    const classSelect = document.getElementById("idclass");
    const sessionSelect = document.getElementById("idsession");
    const searchInput = document.getElementById("searchInput");
    const classValue = classSelect ? classSelect.value : '';
    const sessionValue = sessionSelect ? sessionSelect.value : '';
    const searchValue = searchInput ? searchInput.value.toLowerCase() : '';

    console.log("Filter values:", { classValue, sessionValue, searchValue });

    if (classValue === 'ALL' && sessionValue === 'ALL' && !searchValue) {
        classList.filter();
        return;
    }

    classList.filter(function (item) {
        const classMatch = !classValue || classValue === 'ALL' || item.values().schoolclassid === classValue;
        const sessionMatch = !sessionValue || sessionValue === 'ALL' || item.values().sessionid === sessionValue;
        const searchMatch = !searchValue || 
            item.values().schoolclass.toLowerCase().includes(searchValue) ||
            item.values().schoolarm.toLowerCase().includes(searchValue) ||
            item.values().term.toLowerCase().includes(searchValue) ||
            item.values().session.toLowerCase().includes(searchValue) ||
            item.values().classcategory.toLowerCase().includes(searchValue);
        return classMatch && sessionMatch && searchMatch;
    });
}

const addClassForm = document.getElementById("add-class-form");
if (addClassForm) {
    addClassForm.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Add form submitted");
        const errorMsg = document.getElementById("alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");

        const staffid = document.getElementById("staffid").value;
        const vschoolclassid = addSchoolClassIdField.value;
        const termid = addTermIdField.value;
        const sessionid = addSessionIdField.value;
        const noschoolopened = addNoSchoolOpenedField.value;
        const termends = addTermEndsField.value;
        const nexttermbegins = addNextTermBeginsField.value;

        if (!vschoolclassid || !termid || !sessionid) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please fill in all required fields";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        console.log("Sending add request:", { staffid, vschoolclassid, termid, sessionid, noschoolopened, termends, nexttermbegins });
        axios.post('/myclass', {
            staffid,
            vschoolclassid,
            termid,
            sessionid,
            noschoolopened,
            termends,
            nexttermbegins,
            _token: csrfToken
        }).then(function (response) {
            console.log("Add success:", response.data);
            window.location.reload();
            Swal.fire({
                position: "center",
                icon: "success",
                title: response.data.message || "Class setting added successfully!",
                showConfirmButton: false,
                timer: 2000,
                showCloseButton: true
            });
        }).catch(function (error) {
            console.error("Add error:", error.response?.data || error.message);
            if (errorMsg) {
                const errors = error.response?.data?.errors;
                let errorMessage = error.response?.data?.message || "Error adding class setting";
                if (errors) {
                    errorMessage = Object.values(errors).flat().join(", ");
                }
                errorMsg.innerHTML = errorMessage;
                errorMsg.classList.remove("d-none");
            }
        });
    });
}

const editClassForm = document.getElementById("edit-class-form");
if (editClassForm) {
    editClassForm.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Edit form submitted at", new Date().toISOString());
        const errorMsg = document.getElementById("edit-alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");

        const id = editIdField.value;
        const staffid = document.getElementById("edit-staffid").value;
        const vschoolclassid = editSchoolClassIdField.value;
        const termid = editTermIdField.value;
        const sessionid = editSessionIdField.value;
        const noschoolopened = editNoSchoolOpenedField.value;
        const termends = editTermEndsField.value;
        const nexttermbegins = editNextTermBeginsField.value;

        if (!id || !vschoolclassid || !termid || !sessionid) {
            console.error("Validation failed:", { id, vschoolclassid, termid, sessionid });
            if (errorMsg) {
                errorMsg.innerHTML = "Please fill in all required fields";
                errorMsg.classList.remove("d-none");
            }
            return;
        }

        console.log("Sending edit request:", { id, staffid, vschoolclassid, termid, sessionid, noschoolopened, termends, nexttermbegins });
        axios.put(`/myclass/${id}`, {
            staffid,
            vschoolclassid,
            termid,
            sessionid,
            noschoolopened,
            termends,
            nexttermbegins,
            _token: csrfToken
        }).then(function (response) {
            console.log("Edit success:", response.data);
            window.location.reload();
            Swal.fire({
                position: "center",
                icon: "success",
                title: response.data.message || "Class setting updated successfully!",
                showConfirmButton: false,
                timer: 2000,
                showCloseButton: true
            });
        }).catch(function (error) {
            console.error("Edit error:", error.response?.data || error.message);
            const errorMsgText = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(", ") || "Failed to update class setting";
            if (errorMsg) {
                errorMsg.innerHTML = errorMsgText;
                errorMsg.classList.remove("d-none");
            }
            Swal.fire({
                position: "center",
                icon: "error",
                title: "Error updating class setting",
                text: errorMsgText,
                showConfirmButton: true
            });
        });
    });
}

const addModal = document.getElementById("addClassModal");
if (addModal) {
    addModal.addEventListener("show.bs.modal", function () {
        console.log("Add modal show event");
        const modalLabel = document.getElementById("addModalLabel");
        const addBtn = document.getElementById("add-btn");
        if (modalLabel) modalLabel.innerHTML = "Add Class Setting";
        if (addBtn) addBtn.innerHTML = "Add Class Setting";
    });
    addModal.addEventListener("hidden.bs.modal", function () {
        console.log("Add modal hidden");
        clearAddFields();
    });
}

const editModal = document.getElementById("editModal");
if (editModal) {
    editModal.addEventListener("shown.bs.modal", function () {
        console.log("Edit modal shown at", new Date().toISOString());
    });
    editModal.addEventListener("hidden.bs.modal", function () {
        console.log("Edit modal hidden");
        clearEditFields();
    });
}

window.deleteMultiple = deleteMultiple;