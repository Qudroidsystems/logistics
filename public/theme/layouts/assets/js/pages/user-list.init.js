var perPage = 100, // Pagination set to 100 items per page
    editlist = false,
    checkAll = document.getElementById("checkAll"),
    options = {
        valueNames: ["id", "name", "email", "role", "datereg"],
        page: perPage,
        pagination: true,
        item: '<tr><td class="id" data-id><div class="form-check"><input class="form-check-input" type="checkbox" name="chk_child"><label class="form-check-label"></label></div></td><td class="name" data-name><div class="d-flex align-items-center"><div><h6 class="mb-0"><a href="#" class="text-reset products"></a></h6></div></div></td><td class="email" data-email></td><td class="role" data-roles><div></div></td><td class="datereg"></td><td><ul class="d-flex gap-2 list-unstyled mb-0"><li><a href="#" class="btn btn-subtle-primary btn-icon btn-sm"><i class="ph-eye"></i></a></li><li><a href="javascript:void(0);" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn"><i class="ph-pencil"></i></a></li><li><a href="javascript:void(0);" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn"><i class="ph-trash"></i></a></li></ul></td></tr>'
    },
    userList = new List("userList", options);

console.log("Initial userList items:", userList.items.length);

userList.on("updated", function (e) {
    console.log("List.js updated, matching items:", e.matchingItems.length, "total items:", userList.items.length);
    const noResultElement = document.getElementsByClassName("noresult")[0];
    if (noResultElement) {
        noResultElement.style.display = e.matchingItems.length === 0 ? "block" : "none";
    } else {
        console.warn("No element with class 'noresult' found in the DOM");
    }
    document.getElementById("pagination-showing").innerText = e.matchingItems.length;
    document.getElementById("pagination-total").innerText = userList.items.length;
    setTimeout(() => {
        refreshCallbacks();
        ischeckboxcheck();
    }, 100);
});

document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM loaded, initializing List.js...");
    console.log("Initial userList items:", userList.items.length);
    refreshCallbacks();
    ischeckboxcheck();

    if (typeof Choices !== 'undefined') {
        var addRoleVal = new Choices(document.getElementById("role"), { searchEnabled: true, removeItemButton: true });
        var editRoleVal = new Choices(document.getElementById("edit-role"), { searchEnabled: true, removeItemButton: true });
        var roleFilterVal = new Choices(document.getElementById("idRole"), { searchEnabled: true });
        var emailFilterVal = new Choices(document.getElementById("idEmail"), { searchEnabled: true });
    } else {
        console.warn("Choices.js not available, falling back to native select");
    }

    // Update pagination display
    document.getElementById("pagination-showing").innerText = Math.min(perPage, userList.items.length);
    document.getElementById("pagination-total").innerText = userList.items.length;

    // Handle WhatsApp link generation
    document.getElementById("generate-whatsapp-link").addEventListener("click", function () {
        const phoneNumber = document.getElementById("whatsapp-phone").value;
        const email = document.getElementById("whatsapp-email").value;
        const password = document.getElementById("whatsapp-password").value;

        if (!phoneNumber || !phoneNumber.match(/^\+[1-9]\d{1,14}$/)) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Please enter a valid phone number in E.164 format (e.g., +1234567890)",
                showConfirmButton: true
            });
            return;
        }

        const message = encodeURIComponent(`Your account credentials to the school portal:\nUsername: ${email}\nPassword: ${password}\nPlease change your password after logging in.`);
        const whatsappLink = `https://wa.me/${phoneNumber}?text=${message}`;

        console.log("Generated WhatsApp link:", whatsappLink);

        const linkContainer = document.getElementById("whatsapp-link-container");
        const linkElement = document.getElementById("whatsapp-link");
        const previewElement = document.getElementById("whatsapp-message-preview");

        linkElement.href = whatsappLink;
        previewElement.textContent = decodeURIComponent(message);
        linkContainer.classList.remove("d-none");

        Swal.fire({
            position: "center",
            icon: "success",
            title: "WhatsApp link generated! Click the link to open WhatsApp.",
            showConfirmButton: false,
            timer: 2000,
            showCloseButton: true
        });
    });
});

if (checkAll) {
    checkAll.onclick = function () {
        console.log("checkAll clicked");
        var checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
        console.log("checkAll clicked, checkboxes found:", checkboxes.length);
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
        document.getElementById("remove-actions").classList.toggle("d-none", checkedCount === 0);
    };
}

var addIdField = document.getElementById("add-id-field"),
    addNameField = document.getElementById("name"),
    addEmailField = document.getElementById("email"),
    addRoleField = document.getElementById("role"),
    addPasswordField = document.getElementById("password"),
    addPasswordConfirmField = document.getElementById("password_confirmation"),
    editIdField = document.getElementById("edit-id-field"),
    editNameField = document.getElementById("edit-name"),
    editEmailField = document.getElementById("edit-email"),
    editRoleField = document.getElementById("edit-role"),
    editPasswordField = document.getElementById("edit-password"),
    editPasswordConfirmField = document.getElementById("edit-password_confirmation"),
    date = new Date().toUTCString().slice(5, 16);

function ensureAxios() {
    if (typeof axios === 'undefined') {
        console.error("Axios is not defined. Please include Axios library.");
        Swal.fire({
            position: "center",
            icon: "error",
            title: "Configuration error",
            text: "Axios library is missing",
            showConfirmButton: true
        });
        return false;
    }
    return true;
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
    if (e.target.checked) {
        row.classList.add("table-active");
    } else {
        row.classList.remove("table-active");
    }
    const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
    document.getElementById("remove-actions").classList.toggle("d-none", checkedCount === 0);
    const allCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]');
    document.getElementById("checkAll").checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCount;
}

function refreshCallbacks() {
    console.log("refreshCallbacks executed at", new Date().toISOString());
    var removeButtons = document.getElementsByClassName("remove-item-btn");
    var editButtons = document.getElementsByClassName("edit-item-btn");
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
    e.preventDefault();
    try {
        var itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
        console.log("Remove button clicked for ID:", itemId);
        document.getElementById("delete-record").addEventListener("click", function () {
            if (!ensureAxios()) return;
            axios.delete(`/users/${itemId}`, {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(function () {
                console.log("Deleted user ID:", itemId);
                userList.remove("id", itemId);
                Swal.fire({
                    position: "center",
                    icon: "success",
                    title: "User deleted successfully!",
                    showConfirmButton: false,
                    timer: 2000,
                    showCloseButton: true
                });
            }).catch(function (error) {
                console.error("Error deleting user:", error);
                Swal.fire({
                    position: "center",
                    icon: "error",
                    title: "Error deleting user",
                    text: error.response?.data?.message || "An error occurred",
                    showConfirmButton: true
                });
            });
        }, { once: true });
        var modal = new bootstrap.Modal(document.getElementById("deleteRecordModal"));
        modal.show();
    } catch (error) {
        console.error("Error in remove-item-btn click:", error);
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Failed to initiate delete",
            showConfirmButton: true
        });
    }
}

function handleEditClick(e) {
    e.preventDefault();
    try {
        const itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
        const tr = e.target.closest("tr");
        console.log("Edit button clicked for ID:", itemId);
        editlist = true;
        editIdField.value = itemId;
        editNameField.value = tr.querySelector(".name a").innerText || "";
        editEmailField.value = tr.querySelector(".email").innerText || "";
        const roles = tr.querySelector(".role").getAttribute("data-roles")?.split(",").filter(role => role.trim()) || [];
        console.log("Populating roles:", roles);
        if (typeof Choices !== 'undefined' && editRoleVal) {
            editRoleVal.removeActiveItems();
            editRoleVal.setChoiceByValue(roles);
        } else {
            console.warn("Choices.js not available, using native select");
            Array.from(editRoleField.options).forEach(option => {
                option.selected = roles.includes(option.value);
            });
        }
        const modal = new bootstrap.Modal(document.getElementById("editModal"));
        modal.show();
    } catch (error) {
        console.error("Error in edit-item-btn click:", error);
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Failed to populate edit modal",
            showConfirmButton: true
        });
    }
}

function clearAddFields() {
    addIdField.value = "";
    addNameField.value = "";
    addEmailField.value = "";
    addPasswordField.value = "";
    addPasswordConfirmField.value = "";
    if (typeof Choices !== 'undefined' && addRoleVal) {
        addRoleVal.setChoiceByValue([]);
    } else {
        Array.from(addRoleField.options).forEach(option => option.selected = false);
    }
}

function clearEditFields() {
    editIdField.value = "";
    editNameField.value = "";
    editEmailField.value = "";
    editPasswordField.value = "";
    editPasswordConfirmField.value = "";
    if (typeof Choices !== 'undefined' && editRoleVal) {
        editRoleVal.setChoiceByValue([]);
    } else {
        Array.from(editRoleField.options).forEach(option => option.selected = false);
    }
}

function clearWhatsAppFields() {
    document.getElementById("whatsapp-user-id").value = "";
    document.getElementById("whatsapp-email").value = "";
    document.getElementById("whatsapp-password").value = "";
    document.getElementById("whatsapp-phone").value = "";
    const linkContainer = document.getElementById("whatsapp-link-container");
    linkContainer.classList.add("d-none");
    document.getElementById("whatsapp-link").href = "#";
    document.getElementById("whatsapp-message-preview").textContent = "";
}

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
                if (!ensureAxios()) return;
                Promise.all(ids_array.map((id) => {
                    return axios.delete(`/users/${id}`, {
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                })).then(() => {
                    ids_array.forEach(id => userList.remove("id", id));
                    Swal.fire({
                        title: "Deleted!",
                        text: "Your data has been deleted.",
                        icon: "success",
                        confirmButtonClass: "btn btn-info w-xs mt-2",
                        buttonsStyling: false
                    });
                }).catch((error) => {
                    console.error("Error deleting users:", error);
                    Swal.fire({
                        title: "Error!",
                        text: error.response?.data?.message || "Failed to delete users",
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

function filterData() {
    var searchInput = document.querySelector(".search-box input.search").value.toLowerCase();
    var roleSelect = document.getElementById("idRole");
    var emailSelect = document.getElementById("idEmail");
    var selectedRole = typeof Choices !== 'undefined' && roleFilterVal ? roleFilterVal.getValue(true) : roleSelect.value;
    var selectedEmail = typeof Choices !== 'undefined' && emailFilterVal ? emailFilterVal.getValue(true) : emailSelect.value;

    console.log("Filtering with:", { search: searchInput, role: selectedRole, email: selectedEmail });

    userList.filter(function (item) {
        var nameMatch = item.values().name.toLowerCase().includes(searchInput);
        var emailMatch = item.values().email.toLowerCase().includes(searchInput);
        var roleMatch = selectedRole === "all" || item.values().role.split(",").includes(selectedRole);
        var emailSelectMatch = selectedEmail === "all" || item.values().email === selectedEmail;

        return (nameMatch || emailMatch) && roleMatch && emailSelectMatch;
    });
}

document.getElementById("add-user-form").addEventListener("submit", function (e) {
    e.preventDefault();
    var errorMsg = document.getElementById("alert-error-msg");
    errorMsg.classList.remove("d-none");
    setTimeout(() => errorMsg.classList.add("d-none"), 3000);

    if (addNameField.value === "") {
        errorMsg.innerHTML = "Please enter a name";
        return false;
    }
    if (addEmailField.value === "") {
        errorMsg.innerHTML = "Please enter an email";
        return false;
    }
    if (!addRoleField.selectedOptions.length) {
        errorMsg.innerHTML = "Please select at least one role";
        return false;
    }
    if (addPasswordField.value === "") {
        errorMsg.innerHTML = "Please enter a password";
        return false;
    }
    if (addPasswordField.value !== addPasswordConfirmField.value) {
        errorMsg.innerHTML = "Passwords do not match";
        return false;
    }

    if (!ensureAxios()) return;

    var roles = typeof Choices !== 'undefined' && addRoleVal 
        ? addRoleVal.getValue(true) 
        : Array.from(addRoleField.selectedOptions).map(option => option.value);
    axios.post('/users', {
        name: addNameField.value,
        email: addEmailField.value,
        roles: roles,
        password: addPasswordField.value,
        password_confirmation: addPasswordConfirmField.value,
        _token: document.querySelector('meta[name="csrf-token"]').content
    }).then(function (response) {
        userList.add({
            id: response.data.user.id,
            name: response.data.user.name,
            email: response.data.user.email,
            role: response.data.user.roles.join(','),
            datereg: new Date().toISOString().slice(0, 10)
        });
        userList.reIndex();
        userList.update();
        Swal.fire({
            position: "center",
            icon: "success",
            title: "User added successfully!",
            showConfirmButton: false,
            timer: 2000,
            showCloseButton: true
        });
        const addModal = bootstrap.Modal.getInstance(document.getElementById("showModal"));
        addModal.hide();

        // Show WhatsApp confirmation modal
        const whatsappModal = new bootstrap.Modal(document.getElementById("whatsappModal"));
        document.getElementById("whatsapp-user-id").value = response.data.user.id;
        document.getElementById("whatsapp-email").value = response.data.user.email;
        document.getElementById("whatsapp-password").value = response.data.user.password || "";
        document.getElementById("whatsapp-phone").value = response.data.user.phone_number || "";
        whatsappModal.show();
    }).catch(function (error) {
        console.error("Error adding user:", error);
        var message = error.response?.data?.message || "Error adding user";
        if (error.response?.status === 422) {
            message = Object.values(error.response.data.errors || {}).flat().join(", ");
        }
        errorMsg.innerHTML = message;
    });
});

document.getElementById("edit-user-form").addEventListener("submit", function (e) {
    e.preventDefault();
    console.log("Edit form submitted");
    const updateBtn = document.getElementById("update-btn");
    updateBtn.disabled = true;

    const errorMsg = document.getElementById("alert-error-msg");
    errorMsg.classList.add("d-none");

    if (!editNameField.value) {
        errorMsg.innerHTML = "Please enter a name";
        errorMsg.classList.remove("d-none");
        setTimeout(() => errorMsg.classList.add("d-none"), 3000);
        updateBtn.disabled = false;
        return;
    }
    if (!editEmailField.value) {
        errorMsg.innerHTML = "Please enter an email";
        errorMsg.classList.remove("d-none");
        setTimeout(() => errorMsg.classList.add("d-none"), 3000);
        updateBtn.disabled = false;
        return;
    }
    if (!editRoleField.selectedOptions.length) {
        errorMsg.innerHTML = "Please select at least one role";
        errorMsg.classList.remove("d-none");
        setTimeout(() => errorMsg.classList.add("d-none"), 3000);
        updateBtn.disabled = false;
        return;
    }
    if (editPasswordField.value && editPasswordField.value !== editPasswordConfirmField.value) {
        errorMsg.innerHTML = "Passwords do not match";
        errorMsg.classList.remove("d-none");
        setTimeout(() => errorMsg.classList.add("d-none"), 3000);
        updateBtn.disabled = false;
        return;
    }

    if (!ensureAxios()) {
        errorMsg.innerHTML = "Axios library is missing";
        errorMsg.classList.remove("d-none");
        setTimeout(() => errorMsg.classList.add("d-none"), 3000);
        updateBtn.disabled = false;
        return;
    }

    const roles = typeof Choices !== 'undefined' && editRoleVal 
        ? editRoleVal.getValue(true) 
        : Array.from(editRoleField.selectedOptions).map(option => option.value);
    const data = {
        name: editNameField.value,
        email: editEmailField.value,
        roles: roles,
        _token: document.querySelector('meta[name="csrf-token"]')?.content || '',
    };
    if (editPasswordField.value) {
        data.password = editPasswordField.value;
        data.password_confirmation = editPasswordConfirmField.value;
    }

    console.log("Submitting edit form with data:", data);

    axios.put(`/users/${editIdField.value}`, data, {
        headers: { 'X-CSRF-TOKEN': data._token }
    })
    .then(function (response) {
        console.log("Update successful:", response.data);
        userList.items.forEach(item => {
            if (item.values().id === response.data.user.id) {
                item.values({
                    id: response.data.user.id,
                    name: response.data.user.name,
                    email: response.data.user.email,
                    role: response.data.user.roles.join(','),
                    datereg: item.values().datereg
                });
            }
        });
        userList.reIndex();
        userList.update();
        Swal.fire({
            position: "center",
            icon: "success",
            title: "User updated successfully!",
            showConfirmButton: false,
            timer: 2000,
            showCloseButton: true
        });
        const editModal = bootstrap.Modal.getInstance(document.getElementById("editModal"));
        editModal.hide();

        // Show WhatsApp confirmation modal if password was updated
        if (data.password) {
            const whatsappModal = new bootstrap.Modal(document.getElementById("whatsappModal"));
            document.getElementById("whatsapp-user-id").value = response.data.user.id;
            document.getElementById("whatsapp-email").value = response.data.user.email;
            document.getElementById("whatsapp-password").value = response.data.user.password || data.password;
            document.getElementById("whatsapp-phone").value = response.data.user.phone_number || "";
            whatsappModal.show();
        }
        updateBtn.disabled = false;
    })
    .catch(function (error) {
        console.error("Error updating user:", error.response || error);
        let message = error.response?.data?.message || "Error updating user";
        if (error.response?.status === 422) {
            message = Object.values(error.response.data.errors || {}).flat().join(", ");
        }
        errorMsg.innerHTML = message;
        errorMsg.classList.remove("d-none");
        setTimeout(() => errorMsg.classList.add("d-none"), 3000);
        updateBtn.disabled = false;
    });
});

document.getElementById("showModal").addEventListener("show.bs.modal", function (e) {
    if (e.relatedTarget.classList.contains("add-btn")) {
        console.log("Opening showModal for adding user...");
        document.getElementById("addModalLabel").innerHTML = "Add User";
        document.getElementById("add-btn").innerHTML = "Add User";
    }
});

document.getElementById("editModal").addEventListener("show.bs.modal", function () {
    console.log("Opening editModal...");
    document.getElementById("editModalLabel").innerHTML = "Edit User";
    document.getElementById("update-btn").innerHTML = "Update";
});

document.getElementById("showModal").addEventListener("hidden.bs.modal", function () {
    console.log("showModal closed, clearing fields...");
    clearAddFields();
});

document.getElementById("editModal").addEventListener("hidden.bs.modal", function () {
    console.log("editModal closed, clearing fields...");
    clearEditFields();
});

document.getElementById("whatsappModal").addEventListener("hidden.bs.modal", function () {
    console.log("whatsappModal closed, clearing fields...");
    clearWhatsAppFields();
});