console.log("session.init.js is loaded and executing!");

try {
    // Verify dependencies
    if (typeof axios === 'undefined') throw new Error("Axios is not loaded");
    if (typeof Swal === 'undefined') throw new Error("SweetAlert2 is not loaded");
    if (typeof bootstrap === 'undefined') throw new Error("Bootstrap is not loaded");
    if (typeof List === 'undefined') throw new Error("List.js is not loaded");

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
    var addSessionField = document.getElementById("session");
    var addStatusField = document.getElementById("sessionstatus");
    var editIdField = document.getElementById("edit-id-field");
    var editSessionField = document.getElementById("edit-session");
    var editStatusField = document.getElementById("edit-sessionstatus");

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

    // Refresh edit/delete button callbacks
    function refreshCallbacks() {
        console.log("refreshCallbacks executed at", new Date().toISOString());
        var removeButtons = document.getElementsByClassName("remove-item-btn");
        var editButtons = document.getElementsByClassName("edit-item-btn");

        Array.from(removeButtons).forEach(function (btn) {
            btn.removeEventListener("click", handleRemoveClick);
            btn.addEventListener("click", handleRemoveClick);
        });

        Array.from(editButtons).forEach(function (btn) {
            btn.removeEventListener("click", handleEditClick);
            btn.addEventListener("click", handleEditClick);
        });
    }

    // Delete single session
    function handleRemoveClick(e) {
        e.preventDefault();
        var itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
        var deleteButton = document.getElementById("delete-record");
        if (deleteButton) {
            deleteButton.addEventListener("click", function () {
                axios.delete(`/session/${itemId}`, {
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                }).then(function () {
                    Swal.fire({
                        position: "center",
                        icon: "success",
                        title: "Session deleted successfully!",
                        showConfirmButton: false,
                        timer: 2000,
                        showCloseButton: true
                    });
                    window.location.reload();
                }).catch(function (error) {
                    Swal.fire({
                        position: "center",
                        icon: "error",
                        title: "Error deleting session",
                        text: error.response?.data?.message || "An error occurred",
                        showConfirmButton: true
                    });
                });
            }, { once: true });
        }
        var modal = new bootstrap.Modal(document.getElementById("deleteRecordModal"));
        modal.show();
    }

    // Edit session
    function handleEditClick(e) {
        e.preventDefault();
        var itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
        var tr = e.target.closest("tr");
        if (editIdField) editIdField.value = itemId;
        if (editSessionField) editSessionField.value = tr.querySelector(".session").innerText;
        if (editStatusField) editStatusField.value = tr.querySelector(".status").innerText;
        var modal = new bootstrap.Modal(document.getElementById("editModal"));
        modal.show();
    }

    // Clear form fields
    function clearAddFields() {
        if (addIdField) addIdField.value = "";
        if (addSessionField) addSessionField.value = "";
        if (addStatusField) addStatusField.value = "";
    }

    function clearEditFields() {
        if (editIdField) editIdField.value = "";
        if (editSessionField) editSessionField.value = "";
        if (editStatusField) editStatusField.value = "";
    }

    // Delete multiple sessions
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
                confirmButtonText: "Yes, delete it!",
                buttonsStyling: false,
                showCloseButton: true
            }).then((result) => {
                if (result.value) {
                    Promise.all(ids_array.map((id) => {
                        return axios.delete(`/session/${id}`, {
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                        });
                    })).then(() => {
                        Swal.fire({
                            title: "Deleted!",
                            text: "Your sessions have been deleted.",
                            icon: "success",
                            confirmButtonClass: "btn btn-info w-xs mt-2",
                            buttonsStyling: false
                        });
                        window.location.reload();
                    }).catch((error) => {
                        Swal.fire({
                            title: "Error!",
                            text: error.response?.data?.message || "Failed to delete sessions",
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
    var sessionList = new List('sessionList', {
        valueNames: ['session', 'status', 'datereg'],
        page: 1000, // Set high to include all items on the page
        pagination: false // Disable List.js pagination since server-side pagination is used
    });

    // Update no results message
    sessionList.on('searchComplete', function () {
        var noResultRow = document.querySelector('.noresult');
        if (sessionList.visibleItems.length === 0) {
            noResultRow.style.display = 'block';
        } else {
            noResultRow.style.display = 'none';
        }
    });

    // Filter data (client-side)
    function filterData() {
        var searchInput = document.querySelector(".search-box input.search");
        var searchValue = searchInput ? searchInput.value : "";
        console.log("Filtering with search:", searchValue);
        sessionList.search(searchValue, ['session', 'status']); // Search in session and status columns
    }

    // Add session
    var addSessionForm = document.getElementById("add-session-form");
    if (addSessionForm) {
        addSessionForm.addEventListener("submit", function (e) {
            e.preventDefault();
            var errorMsg = document.getElementById("alert-error-msg");
            if (errorMsg) {
                errorMsg.classList.remove("d-none");
                setTimeout(() => errorMsg.classList.add("d-none"), 2000);
            }

            if (!addSessionField || !addSessionField.value) {
                if (errorMsg) errorMsg.innerHTML = "Please enter a session name";
                return false;
            }
            if (!addStatusField || !addStatusField.value) {
                if (errorMsg) errorMsg.innerHTML = "Please select a status";
                return false;
            }

            axios.post('/session', {
                session: addSessionField.value,
                sessionstatus: addStatusField.value,
                _token: document.querySelector('meta[name="csrf-token"]').content
            }).then(function (response) {
                Swal.fire({
                    position: "center",
                    icon: "success",
                    title: "Session added successfully!",
                    showConfirmButton: false,
                    timer: 2000,
                    showCloseButton: true
                });
                window.location.reload();
            }).catch(function (error) {
                if (errorMsg) {
                    errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(", ") || "Error adding session";
                }
            });
        });
    }

    // Edit session
    var editSessionForm = document.getElementById("edit-session-form");
    if (editSessionForm) {
        editSessionForm.addEventListener("submit", function (e) {
            e.preventDefault();
            var errorMsg = document.getElementById("edit-alert-error-msg");
            if (errorMsg) {
                errorMsg.classList.remove("d-none");
                setTimeout(() => errorMsg.classList.add("d-none"), 2000);
            }

            if (!editSessionField || !editSessionField.value) {
                if (errorMsg) errorMsg.innerHTML = "Please enter a session name";
                return false;
            }
            if (!editStatusField || !editStatusField.value) {
                if (errorMsg) errorMsg.innerHTML = "Please select a status";
                return false;
            }

            axios.put(`/session/${editIdField.value}`, {
                session: editSessionField.value,
                sessionstatus: editStatusField.value,
                _token: document.querySelector('meta[name="csrf-token"]').content
            }).then(function (response) {
                Swal.fire({
                    position: "center",
                    icon: "success",
                    title: "Session updated successfully!",
                    showConfirmButton: false,
                    timer: 2000,
                    showCloseButton: true
                });
                window.location.reload();
            }).catch(function (error) {
                if (errorMsg) {
                    errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(", ") || "Error updating session";
                }
            });
        });
    }

    // Modal events
    var addModal = document.getElementById("addSessionModal");
    if (addModal) {
        addModal.addEventListener("show.bs.modal", function (e) {
            if (e.relatedTarget.classList.contains("add-btn")) {
                var modalLabel = document.getElementById("exampleModalLabel");
                var addBtn = document.getElementById("add-btn");
                if (modalLabel) modalLabel.innerHTML = "Add Session";
                if (addBtn) addBtn.innerHTML = "Add Session";
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
            if (modalLabel) modalLabel.innerHTML = "Edit Session";
            if (updateBtn) updateBtn.innerHTML = "Update";
        });
        editModal.addEventListener("hidden.bs.modal", function () {
            clearEditFields();
        });
    }

    // Initialize listeners
    document.addEventListener("DOMContentLoaded", function () {
        var searchInput = document.querySelector(".search-box input.search");
        if (searchInput) {
            searchInput.addEventListener("input", debounce(function () {
                console.log("Search input changed:", searchInput.value);
                filterData();
            }, 300)); // Debounce for 300ms
        } else {
            console.error("Search input not found!");
        }

        refreshCallbacks();
        ischeckboxcheck();
    });

    // Expose functions to global scope
    window.deleteMultiple = deleteMultiple;

} catch (error) {
    console.error("Error in session.init.js:", error);
}