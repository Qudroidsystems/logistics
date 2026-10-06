console.log("role.init.js is loaded and executing!");

document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM loaded, initializing scripts...");

    // Role permission checkboxes
    const selectAllCheckbox = document.getElementById("kt_roles_select_all");
    const permissionCheckboxes = document.querySelectorAll('input[name="permission[]"]');

    // Check if all permissions are pre-checked
    if (selectAllCheckbox && permissionCheckboxes.length) {
        const allChecked = Array.from(permissionCheckboxes).every(checkbox => checkbox.checked);
        selectAllCheckbox.checked = allChecked;

        selectAllCheckbox.addEventListener("change", function () {
            console.log("Select All toggled, state:", this.checked);
            permissionCheckboxes.forEach((checkbox) => {
                checkbox.checked = this.checked;
            });
        });

        permissionCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener("change", function () {
                console.log("Permission checkbox toggled, value:", this.value, "state:", this.checked);
            });
        });
    }

    // Initialize List.js safely
    let userList = null;
    const table = document.getElementById("userList");
    const tbody = table?.querySelector("tbody");
    if (table && tbody && tbody.children.length > 0) {
        try {
            userList = new List("userList", {
                valueNames: ["id", "name", "datereg"],
                page: 5,
                pagination: true
            });
            console.log("Initial userList items:", userList.items.length);

            userList.on("updated", function (e) {
                console.log("List.js updated, matching items:", e.matchingItems.length, "total items:", userList.items.length);
                const noResult = document.querySelector(".noresult");
                if (noResult) {
                    noResult.style.display = e.matchingItems.length === 0 ? "block" : "none";
                }
                setTimeout(() => {
                    refreshCallbacks();
                    ischeckboxcheck();
                }, 100);
            });
        } catch (error) {
            console.error("Failed to initialize List.js:", error);
        }
    } else {
        console.warn("Table #userList is empty or invalid, skipping List.js initialization");
    }

    // Check All checkbox
    const checkAll = document.getElementById("checkAll");
    if (checkAll) {
        checkAll.addEventListener("click", function () {
            console.log("checkAll clicked");
            const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
            console.log("checkAll clicked, checkboxes found:", checkboxes.length);
            checkboxes.forEach((checkbox) => {
                checkbox.checked = this.checked;
                const row = checkbox.closest("tr");
                row.classList.toggle("table-active", this.checked);
            });
            document.getElementById("remove-actions")?.classList.toggle("d-none", !this.checked);
        });
    }

    function ischeckboxcheck() {
        const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
        checkboxes.forEach((checkbox) => {
            checkbox.removeEventListener("change", handleCheckboxChange);
            checkbox.addEventListener("change", handleCheckboxChange);
        });
    }

    function handleCheckboxChange(e) {
        const row = e.target.closest("tr");
        row.classList.toggle("table-active", e.target.checked);
        const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
        document.getElementById("remove-actions")?.classList.toggle("d-none", checkedCount === 0);
        const allCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]');
        document.getElementById("checkAll").checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCount;
    }

    function refreshCallbacks() {
        console.log("refreshCallbacks executed at", new Date().toISOString());
        const removeButtons = document.getElementsByClassName("remove-item-btn");
        console.log("Attaching event listeners to", removeButtons.length, "remove buttons");

        Array.from(removeButtons).forEach((btn) => {
            btn.removeEventListener("click", handleRemoveClick);
            btn.addEventListener("click", handleRemoveClick);
        });
    }

    function handleRemoveClick(e) {
        e.preventDefault();
        try {
            const link = e.target.closest("a");
            if (!link) {
                console.error("No anchor element found for remove button");
                Swal.fire({
                    position: "center",
                    icon: "error",
                    title: "Configuration error",
                    text: "Remove button link not found",
                    showConfirmButton: true
                });
                return;
            }
            const deleteUrl = link.getAttribute("data-url");
            const rowId = link.closest("tr").getAttribute("data-id");
            console.log("Remove button clicked for row ID:", rowId, "URL:", deleteUrl);

            if (!deleteUrl || !rowId) {
                console.error("Missing deleteUrl or rowId", { deleteUrl, rowId });
                Swal.fire({
                    position: "center",
                    icon: "error",
                    title: "Configuration error",
                    text: "Delete URL or row ID is missing",
                    showConfirmButton: true
                });
                return;
            }

            window.deleteInfo = { deleteUrl, rowId };
            console.log("Opening deleteRecordModal...");
            const modal = new bootstrap.Modal(document.getElementById("deleteRecordModal"));
            modal.show();
        } catch (error) {
            console.error("Error in remove-item-btn click:", error);
        }
    }

    // Delete confirmation
    const deleteButton = document.getElementById("delete-record");
    if (deleteButton) {
        deleteButton.addEventListener("click", function () {
            console.log("Delete button clicked");
            const { deleteUrl, rowId } = window.deleteInfo || {};
            const modal = bootstrap.Modal.getInstance(document.getElementById("deleteRecordModal"));

            if (!deleteUrl || !rowId) {
                console.error("Missing deleteUrl or rowId", { deleteUrl, rowId });
                Swal.fire({
                    position: "center",
                    icon: "error",
                    title: "Configuration error",
                    text: "Delete URL or row ID is missing",
                    showConfirmButton: true
                });
                if (modal) modal.hide();
                return;
            }

            if (typeof axios === 'undefined') {
                console.error("Axios is not defined");
                Swal.fire({
                    position: "center",
                    icon: "error",
                    title: "Configuration error",
                    text: "Axios library is missing",
                    showConfirmButton: true
                });
                if (modal) modal.hide();
                return;
            }

            console.log("Sending DELETE request:", { deleteUrl, rowId });
            axios.delete(deleteUrl, {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(function (response) {
                console.log("Delete successful:", response.data);
                try {
                    if (userList) {
                        userList.remove("id", rowId);
                    }
                    const row = document.querySelector(`tr[data-id="${rowId}"]`);
                    if (row) row.remove();
                    if (modal) modal.hide();
                    Swal.fire({
                        position: "center",
                        icon: "success",
                        title: response.data.message || "User role removed successfully!",
                        showConfirmButton: false,
                        timer: 2000,
                        showCloseButton: true
                    });
                    // Update noresult display
                    const noResult = document.querySelector(".noresult");
                    const rowCount = document.querySelectorAll("#userList tbody tr").length;
                    if (noResult) {
                        noResult.style.display = rowCount === 0 ? "block" : "none";
                    } else {
                        console.warn("No .noresult element found; ensuring empty table state");
                        if (rowCount === 0 && tbody) {
                            tbody.innerHTML = '<tr><td colspan="4" class="noresult" style="display: block;">No results found</td></tr>';
                        }
                    }
                    // Fetch previous page if table is empty and pagination exists
                    if (rowCount === 0 && document.querySelector("#pagination-element .pagination-prev")) {
                        const prevUrl = document.querySelector("#pagination-element .pagination-prev").getAttribute("data-url");
                        console.log("Fetching previous page:", prevUrl);
                        fetchPage(prevUrl);
                    }
                } catch (error) {
                    console.error("Error updating UI after delete:", error);
                    Swal.fire({
                        position: "center",
                        icon: "error",
                        title: "UI update error",
                        text: "Role removed, but UI update failed: " + error.message,
                        showConfirmButton: true
                    });
                }
            }).catch(function (error) {
                console.error("Delete error:", error.response || error);
                console.log("Error details:", {
                    status: error.response?.status,
                    data: error.response?.data,
                    message: error.response?.data?.message || error.message
                });
                Swal.fire({
                    position: "center",
                    icon: "error",
                    title: "Error removing user role",
                    text: error.response?.data?.message || error.message || "An error occurred",
                    showConfirmButton: true
                });
                if (modal) modal.hide();
            });
        });
    } else {
        console.error("delete-record button not found");
    }

    // Pagination
    function fetchPage(url) {
        if (!url) return;
        console.log("Fetching page:", url);
        axios.get(url, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        }).then(function (response) {
            document.querySelector("#userList tbody").innerHTML = response.data.html;
            document.getElementById("pagination-element").outerHTML = response.data.pagination;
            if (userList) {
                userList.reIndex();
            }
            refreshCallbacks();
            ischeckboxcheck();
            document.querySelector("#pagination-element .text-muted").innerHTML =
                `Showing <span class="fw-semibold">${response.data.count}</span> of <span class="fw-semibold">${response.data.total}</span> Results`;
            // Ensure noresult display after pagination
            const noResult = document.querySelector(".noresult");
            const rowCount = document.querySelectorAll("#userList tbody tr").length;
            if (noResult) {
                noResult.style.display = rowCount === 0 ? "block" : "none";
            }
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

    document.addEventListener("click", function (e) {
        const paginationLink = e.target.closest(".pagination-prev, .pagination-next, .pagination .page-link");
        if (paginationLink) {
            e.preventDefault();
            fetchPage(paginationLink.getAttribute("data-url"));
        }
    });

    // Initialize callbacks
    refreshCallbacks();
    ischeckboxcheck();
});