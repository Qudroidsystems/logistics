console.log("mysubject.init.js loaded at", new Date().toISOString());

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

// Set Axios CSRF token
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
if (!csrfToken) console.warn("CSRF token not found");

// Debounce function for search
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

// Initialize checkboxes
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

// Event delegation
document.addEventListener('click', function (e) {
    const editBtn = e.target.closest('.edit-item-btn');
    const removeBtn = e.target.closest('.remove-item-btn');
    const paginationLink = e.target.closest('.pagination .page-link');
    const removeActionsBtn = e.target.closest('#remove-actions');
    if (editBtn) {
        handleEditClick(e, editBtn);
    } else if (removeBtn) {
        handleRemoveClick(e, removeBtn);
    } else if (paginationLink) {
        e.preventDefault();
        const url = paginationLink.getAttribute('href');
        if (url) fetchPage(url);
    } else if (removeActionsBtn) {
        deleteMultipleRegistrations();
    }
});

// Fetch paginated data
function fetchPage(url) {
    if (!url) return;
    console.log("Fetching page:", url);
    axios.get(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (response) {
        console.log("Fetch page success");
        const parser = new DOMParser();
        const doc = parser.parseFromString(response.data, 'text/html');
        const newCardContainer = doc.querySelector('#subjectCardContainer');
        const newTbody = doc.querySelector('#subjectListTable tbody');
        const newPagination = doc.querySelector('.pagination');
        if (newCardContainer && newTbody && newPagination) {
            document.querySelector('#subjectCardContainer').innerHTML = newCardContainer.innerHTML;
            document.querySelector('#subjectListTable tbody').innerHTML = newTbody.innerHTML;
            document.querySelector('.pagination').outerHTML = newPagination.outerHTML;
            if (subjectList) {
                subjectList.reIndex();
            }
            initializeCheckboxes();
        }
    }).catch(function (error) {
        console.error("Error fetching page:", error);
        Swal.fire({
            icon: "error",
            title: "Error loading page",
            text: error.response?.data?.message || "An error occurred",
            showConfirmButton: true
        });
    });
}

// Handle edit click
function handleEditClick(e, button) {
    e.preventDefault();
    console.log("Edit button clicked");
    const tr = button.closest("tr") || button.closest(".card");
    const itemId = tr.querySelector(".id, .subject-id")?.getAttribute("data-id") || tr.querySelector(".id, .subject-id")?.textContent;
    if (!itemId) {
        console.error("Item ID not found");
        return;
    }
    axios.get(`/mysubject/${itemId}`)
        .then(function (response) {
            console.log("Edit data:", response.data);
            const data = response.data;
            document.getElementById("edit-id-field").value = itemId;
            document.getElementById("edit-subjectid").value = data.subjectid;
            document.getElementById("edit-schoolclassid").value = data.schoolclassid;
            document.getElementById("edit-termid").value = data.termid;
            document.getElementById("edit-sessionid").value = data.sessionid;
            const modal = new bootstrap.Modal(document.getElementById("editModal"));
            modal.show();
        })
        .catch(function (error) {
            console.error("Error fetching edit data:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: error.response?.data?.message || "Failed to load subject data",
                showConfirmButton: true
            });
        });
}

// Handle remove click
function handleRemoveClick(e, button) {
    e.preventDefault();
    console.log("Remove button clicked");
    const tr = button.closest("tr") || button.closest(".card");
    const itemId = tr.querySelector(".id, .subject-id")?.getAttribute("data-id") || tr.querySelector(".id, .subject-id")?.textContent;
    if (!itemId) {
        console.error("Item ID not found");
        return;
    }
    const modal = new bootstrap.Modal(document.getElementById("deleteRecordModal"));
    modal.show();
    const deleteButton = document.getElementById("delete-record");
    if (deleteButton) {
        deleteButton.onclick = function () {
            console.log("Deleting subject:", itemId);
            axios.delete(`/mysubject/${itemId}`)
                .then(function (response) {
                    console.log("Delete success:", response.data);
                    Swal.fire({
                        icon: "success",
                        title: response.data.message || "Subject deleted successfully!",
                        showConfirmButton: false,
                        timer: 2000
                    });
                    if (subjectList) {
                        subjectList.remove("id", itemId);
                    }
                    const row = document.querySelector(`tr[data-id="${itemId}"]`);
                    if (row) row.remove();
                    const card = document.querySelector(`.card .subject-id[value="${itemId}"]`)?.closest('.card');
                    if (card) card.closest('.col-md-4').remove();
                    modal.hide();
                    window.location.reload();
                })
                .catch(function (error) {
                    console.error("Delete error:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error deleting subject",
                        text: error.response?.data?.message || "An error occurred",
                        showConfirmButton: true
                    });
                    modal.hide();
                });
        };
    }
}

// Delete multiple subject registrations
function deleteMultipleRegistrations() {
    console.log("Delete multiple registrations triggered");
    const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]:checked');
    if (checkboxes.length === 0) {
        Swal.fire({
            title: "Please select at least one student",
            icon: "info",
            showCloseButton: true
        });
        return;
    }

    const studentIds = [];
    let subjectClassId, termId, sessionId, staffId;
    checkboxes.forEach((checkbox) => {
        const tr = checkbox.closest("tr");
        const studentId = tr.querySelector(".student-id")?.getAttribute("data-student-id");
        subjectClassId = tr.querySelector(".subjectclass-id")?.getAttribute("data-subjectclassid");
        termId = tr.querySelector(".term-id")?.getAttribute("data-termid");
        sessionId = tr.querySelector(".session-id")?.getAttribute("data-sessionid");
        staffId = tr.querySelector(".staff-id")?.getAttribute("data-staffid") || null;
        if (studentId) studentIds.push(parseInt(studentId));
    });

    console.log("Delete multiple data:", { studentIds, subjectClassId, termId, sessionId, staffId });

    if (!studentIds.length || !subjectClassId || !termId || !sessionId) {
        Swal.fire({
            title: "Missing required data",
            text: "Please ensure all selected rows have valid student, subject class, term, and session data.",
            icon: "error",
            showCloseButton: true
        });
        return;
    }

    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it!",
        showCloseButton: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.removeRegisteredClasses(studentIds, subjectClassId, termId, sessionId, staffId);
        }
    });
}

// Delete multiple subjects (original function for subjects, not registrations)
function deleteMultiple() {
    console.log("Delete multiple subjects triggered");
    const ids_array = [];
    const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]:checked');
    checkboxes.forEach((checkbox) => {
        const id = checkbox.closest("tr").querySelector(".id")?.getAttribute("data-id");
        if (id) ids_array.push(id);
    });
    if (ids_array.length === 0) {
        Swal.fire({
            title: "Please select at least one checkbox",
            icon: "info",
            showCloseButton: true
        });
        return;
    }
    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it!",
        showCloseButton: true
    }).then((result) => {
        if (result.isConfirmed) {
            Promise.all(ids_array.map((id) => axios.delete(`/mysubject/${id}`)))
                .then(() => {
                    Swal.fire({
                        title: "Deleted!",
                        text: "Subjects have been deleted.",
                        icon: "success"
                    });
                    window.location.reload();
                })
                .catch((error) => {
                    console.error("Bulk delete error:", error);
                    Swal.fire({
                        title: "Error!",
                        text: error.response?.data?.message || "Failed to delete subjects",
                        icon: "error"
                    });
                });
        }
    });
}

// Initialize List.js
let subjectList;
const subjectListContainer = document.getElementById('subjectListTable');
if (subjectListContainer && document.querySelectorAll('#subjectListTable tbody tr').length > 0) {
    subjectList = new List('subjectListTable', {
        valueNames: ['id', 'subject', 'subjectcode', 'schoolclass', 'arm', 'term', 'session'],
        page: 1000,
        pagination: false
    });
    console.log("List.js initialized");
}

// Filter data
function filterData() {
    const searchInput = document.getElementById("searchInput")?.value || "";
    const termFilter = document.getElementById("idTerm")?.value || "all";
    const sessionFilter = document.getElementById("idSession")?.value || "all";
    console.log("Filtering:", { searchInput, termFilter, sessionFilter });

    if (subjectList) {
        subjectList.filter(function (item) {
            const matchesSearch = !searchInput ||
                item.values().subject.toLowerCase().includes(searchInput.toLowerCase()) ||
                item.values().schoolclass.toLowerCase().includes(searchInput.toLowerCase());
            const matchesTerm = termFilter === "all" || item.values().term === termFilter;
            const matchesSession = sessionFilter === "all" || item.values().session === sessionFilter;
            return matchesSearch && matchesTerm && matchesSession;
        });

        const cards = document.querySelectorAll('#subjectCardContainer .col-md-4');
        cards.forEach(card => {
            const subject = card.querySelector('h6')?.textContent || "";
            const term = card.querySelector('li:nth-child(2)')?.textContent.replace('Term: ', '') || "";
            const session = card.querySelector('li:nth-child(3)')?.textContent.replace('Session:', '') || "";
            const matchesSearch = !searchInput ||
                subject.toLowerCase().includes(searchInput.toLowerCase()) ||
                term.toLowerCase().includes(searchInput.toLowerCase());
            const matchesTerm = termFilter === "all" || term.trim() === termFilter;
            const matchesSession = sessionFilter === "all" || session.trim() === sessionFilter;
            card.style.display = matchesSearch && matchesTerm && matchesSession ? 'block' : 'none';
        });
    }
}

// Add Subject Form
const addSubjectForm = document.getElementById("add-subject-form");
if (addSubjectForm) {
    addSubjectForm.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Add form submitted");
        const errorMsg = document.getElementById("alert-error-msg");
        const addBtn = document.getElementById("add-btn");
        if (errorMsg) errorMsg.classList.add("d-none");
        if (addBtn) addBtn.disabled = true;

        const formData = new FormData(addSubjectForm);
        const staffid = formData.get('staffid');
        const subjectid = formData.get('subjectid');
        const schoolclassid = formData.get('schoolclassid');
        const termid = formData.get('termid');
        const sessionid = formData.get('sessionid');

        console.log("Form Data:", { staffid, subjectid, schoolclassid, termid, sessionid });

        if (!staffid || !subjectid || !schoolclassid || !termid || !sessionid) {
            errorMsg.textContent = "Please select all required fields.";
            errorMsg.classList.remove("d-none");
            addBtn.disabled = false;
            return;
        }

        axios.post('/mysubject', { staffid, subjectid, schoolclassid, termid, sessionid })
            .then(function (response) {
                console.log("Add success:", response.data);
                Swal.fire({
                    icon: "success",
                    title: response.data.message || "Subject added successfully!",
                    showConfirmButton: false,
                    timer: 2000
                });
                addBtn.disabled = false;
                window.location.reload();
            })
            .catch(function (error) {
                console.error("Add error:", error);
                if (errorMsg) {
                    errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(", ") || "Error adding subject";
                    errorMsg.classList.remove("d-none");
                }
                addBtn.disabled = false;
            });
    });
}

// Edit Subject Form
const editSubjectForm = document.getElementById("edit-subject-form");
if (editSubjectForm) {
    editSubjectForm.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Edit form submitted");
        const errorMsg = document.getElementById("edit-alert-error");
        const updateBtn = document.getElementById("update-btn");
        if (errorMsg) errorMsg.classList.add("d-none");
        if (updateBtn) updateBtn.disabled = true;

        const formData = new FormData(editSubjectForm);
        const staffid = formData.get('staffid');
        const subjectid = formData.get('subjectid');
        const schoolclassid = formData.get('schoolclassid');
        const termid = formData.get('termid');
        const sessionid = formData.get('sessionid');
        const id = document.getElementById("edit-id-field").value;

        console.log("Form Data:", { id, staffid, subjectid, schoolclassid, termid, sessionid });

        if (!id || !staffid || !subjectid || !schoolclassid || !termid || !sessionid) {
            errorMsg.textContent = "Please select all required fields.";
            errorMsg.classList.remove("d-none");
            updateBtn.disabled = false;
            return;
        }

        axios.post(`/mysubject/${id}`, {
            _method: 'PUT',
            staffid,
            subjectid,
            schoolclassid,
            termid,
            sessionid,
            _token: csrfToken
        })
            .then(function (response) {
                console.log("Edit success:", response.data);
                Swal.fire({
                    icon: "success",
                    title: response.data.message || "Subject updated successfully!",
                    showConfirmButton: false,
                    timer: 2000
                });
                updateBtn.disabled = false;
                window.location.reload();
            })
            .catch(function (error) {
                console.error("Edit error:", error);
                if (errorMsg) {
                    errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(", ") || "Error updating subject";
                    errorMsg.classList.remove("d-none");
                }
                updateBtn.disabled = false;
            });
    });
}

// Modal listeners
const addModal = document.getElementById("showModal");
if (addModal) {
    addModal.addEventListener("show.bs.modal", function () {
        console.log("Add modal show");
        document.getElementById("add-id-field").value = '';
        document.getElementById("subjectid").value = '';
        document.getElementById("schoolclassid").value = '';
        document.getElementById("termid").value = '';
        document.getElementById("sessionid").value = '';
        const errorMsg = document.getElementById("alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");
    });
}

const editModal = document.getElementById("editModal");
if (editModal) {
    editModal.addEventListener("show.bs.modal", function () {
        console.log("Edit modal show");
        const errorMsg = document.getElementById("edit-alert-error");
        if (errorMsg) errorMsg.classList.add("d-none");
    });
    editModal.addEventListener("hidden.bs.modal", function () {
        console.log("Edit modal hidden");
        document.getElementById("edit-id-field").value = '';
        document.getElementById("edit-subjectid").value = '';
        document.getElementById("edit-schoolclassid").value = '';
        document.getElementById("edit-termid").value = '';
        document.getElementById("edit-sessionid").value = '';
        const errorMsg = document.getElementById("edit-alert-error");
        if (errorMsg) errorMsg.classList.add("d-none");
    });
}

// Initialize
document.addEventListener("DOMContentLoaded", function () {
    console.log("DOMContentLoaded");
    const searchInput = document.getElementById("searchInput");
    const termFilter = document.getElementById("idTerm");
    const sessionFilter = document.getElementById("idSession");
    if (searchInput) {
        searchInput.addEventListener("input", debounce(filterData, 300));
    }
    if (termFilter) {
        termFilter.addEventListener("change", filterData);
    }
    if (sessionFilter) {
        sessionFilter.addEventListener("change", filterData);
    }
    initializeCheckboxes();
});

// Expose global functions
window.deleteMultiple = deleteMultiple;
window.deleteMultipleRegistrations = deleteMultipleRegistrations;