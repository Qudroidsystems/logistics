// console.log("schoolbill.init.js is loaded and executing!");

// // Verify dependencies
// try {
//     if (typeof axios === 'undefined') throw new Error("Axios is not loaded");
//     if (typeof Swal === 'undefined') throw new Error("SweetAlert2 is not loaded");
//     if (typeof bootstrap === 'undefined') throw new Error("Bootstrap is not loaded");
//     if (typeof List === 'undefined') throw new Error("List.js is not loaded");
//     console.log("All dependencies loaded successfully");
// } catch (error) {
//     console.error("Dependency check failed:", error);
// }

// // Set Axios CSRF token globally
// const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
// axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
// if (!csrfToken) console.warn("CSRF token not found");

// // Debounce function for search input
// function debounce(func, wait) {
//     let timeout;
//     return function (...args) {
//         clearTimeout(timeout);
//         timeout = setTimeout(() => func.apply(this, args), wait);
//     };
// }

// // Check all checkbox
// const checkAll = document.getElementById("checkAll");
// if (checkAll) {
//     checkAll.addEventListener("click", function () {
//         console.log("CheckAll clicked");
//         const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
//         checkboxes.forEach((checkbox) => {
//             checkbox.checked = this.checked;
//             const row = checkbox.closest("tr");
//             row.classList.toggle("table-active", this.checked);
//         });
//         const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
//         const removeActions = document.getElementById("remove-actions");
//         if (removeActions) {
//             removeActions.classList.toggle("d-none", checkedCount === 0);
//         }
//     });
// }

// // Form fields
// const addIdField = document.getElementById("add-id-field");
// const addTitleField = document.getElementById("title");
// const addBillAmountField = document.getElementById("bill_amount");
// const addDescriptionField = document.getElementById("description");
// const addStatusIdField = document.getElementById("statusId");
// const editIdField = document.getElementById("edit-id-field");
// const editTitleField = document.getElementById("edit-title");
// const editBillAmountField = document.getElementById("edit-bill_amount");
// const editDescriptionField = document.getElementById("edit-description");
// const editStatusIdField = document.getElementById("edit-statusId");

// // Checkbox handling
// function initializeCheckboxes() {
//     const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
//     checkboxes.forEach((checkbox) => {
//         checkbox.removeEventListener("change", handleCheckboxChange);
//         checkbox.addEventListener("change", handleCheckboxChange);
//     });
// }

// function handleCheckboxChange(e) {
//     console.log("Checkbox changed:", e.target.checked);
//     const row = e.target.closest("tr");
//     row.classList.toggle("table-active", e.target.checked);
//     const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
//     const removeActions = document.getElementById("remove-actions");
//     if (removeActions) {
//         removeActions.classList.toggle("d-none", checkedCount === 0);
//     }
//     const allCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]');
//     if (checkAll) {
//         checkAll.checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCount;
//     }
// }

// // Explicit event listener for Create School Bill button
// const createButton = document.querySelector('.add-btn');
// if (createButton) {
//     createButton.addEventListener('click', function (e) {
//         e.preventDefault();
//         console.log("Create School Bill button clicked");
//         try {
//             const modal = new bootstrap.Modal(document.getElementById("addSchoolBillModal"));
//             modal.show();
//             console.log("Add modal opened");
//         } catch (error) {
//             console.error("Error opening add modal:", error);
//             Swal.fire({
//                 position: "center",
//                 icon: "error",
//                 title: "Error opening modal",
//                 text: "Please ensure Bootstrap is loaded and try again.",
//                 showConfirmButton: true
//             });
//         }
//     });
// }

// // Event delegation for edit and remove buttons
// document.addEventListener('click', function (e) {
//     const editBtn = e.target.closest('.edit-item-btn');
//     const removeBtn = e.target.closest('.remove-item-btn');
//     if (editBtn) {
//         handleEditClick(e, editBtn);
//     } else if (removeBtn) {
//         handleRemoveClick(e, removeBtn);
//     }
// });

// // Delete single school bill
// function handleRemoveClick(e, button) {
//     e.preventDefault();
//     console.log("Remove button clicked");
//     const itemId = button.getAttribute("data-id");
//     if (!itemId) {
//         console.error("Item ID not found");
//         return;
//     }
//     const deleteButton = document.getElementById("delete-record");
//     if (deleteButton) {
//         deleteButton.onclick = function () {
//             console.log("Deleting school bill:", itemId);
//             axios.delete(`/schoolbill/${itemId}`)
//                 .then(function () {
//                     Swal.fire({
//                         position: "center",
//                         icon: "success",
//                         title: "School Bill deleted successfully!",
//                         showConfirmButton: false,
//                         timer: 2000,
//                         showCloseButton: true
//                     });
//                     window.location.reload();
//                 })
//                 .catch(function (error) {
//                     console.error("Delete error:", error);
//                     Swal.fire({
//                         position: "center",
//                         icon: "error",
//                         title: "Error deleting school bill",
//                         text: error.response?.data?.message || "An error occurred",
//                         showConfirmButton: true
//                     });
//                 });
//         };
//     }
//     try {
//         const modal = new bootstrap.Modal(document.getElementById("deleteRecordModal"));
//         modal.show();
//         console.log("Delete modal opened");
//     } catch (error) {
//         console.error("Error opening delete modal:", error);
//     }
// }

// // Edit school bill
// function handleEditClick(e, button) {
//     e.preventDefault();
//     console.log("Edit button clicked");
//     const itemId = button.getAttribute("data-id");
//     const tr = button.closest("tr");
//     if (!itemId) {
//         console.error("Item ID not found");
//         return;
//     }
//     if (editIdField) editIdField.value = itemId;
//     if (editTitleField) editTitleField.value = button.getAttribute("data-title") || "";
//     if (editBillAmountField) editBillAmountField.value = button.getAttribute("data-bill_amount") || "";
//     if (editDescriptionField) editDescriptionField.value = button.getAttribute("data-description") || "";
//     if (editStatusIdField) editStatusIdField.value = button.getAttribute("data-statusId") || "";
//     try {
//         const modal = new bootstrap.Modal(document.getElementById("editSchoolBillModal"));
//         modal.show();
//         console.log("Edit modal opened");
//         updateEditFormStatus();
//     } catch (error) {
//         console.error("Error opening edit modal:", error);
//         Swal.fire({
//             position: "center",
//             icon: "error",
//             title: "Error opening edit modal",
//             text: "Please try again or contact support.",
//             showConfirmButton: true
//         });
//     }
// }

// // Update edit form status
// function updateEditFormStatus() {
//     const statusId = editStatusIdField?.value;
//     if (editStatusIdField && statusId) {
//         editStatusIdField.querySelectorAll('option').forEach(option => {
//             option.selected = option.value === statusId;
//         });
//     }
// }

// // Clear form fields
// function clearAddFields() {
//     if (addIdField) addIdField.value = "";
//     if (addTitleField) addTitleField.value = "";
//     if (addBillAmountField) addBillAmountField.value = "";
//     if (addDescriptionField) addDescriptionField.value = "";
//     if (addStatusIdField) addStatusIdField.value = "";
// }

// function clearEditFields() {
//     if (editIdField) editIdField.value = "";
//     if (editTitleField) editTitleField.value = "";
//     if (editBillAmountField) editBillAmountField.value = "";
//     if (editDescriptionField) editDescriptionField.value = "";
//     if (editStatusIdField) editStatusIdField.value = "";
// }

// // Delete multiple school bills
// function deleteMultiple() {
//     console.log("Delete multiple triggered");
//     const ids_array = [];
//     const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]:checked');
//     checkboxes.forEach((checkbox) => {
//         const id = checkbox.closest("tr").querySelector(".id")?.getAttribute("data-id");
//         if (id) ids_array.push(id);
//     });
//     if (ids_array.length === 0) {
//         Swal.fire({
//             title: "Please select at least one checkbox",
//             confirmButtonClass: "btn btn-info",
//             buttonsStyling: false,
//             showCloseButton: true
//         });
//         return;
//     }
//     Swal.fire({
//         title: "Are you sure?",
//         text: "You won't be able to revert this!",
//         icon: "warning",
//         showCancelButton: true,
//         confirmButtonClass: "btn btn-primary w-xs me-2 mt-2",
//         cancelButtonClass: "btn btn-danger w-xs mt-2",
//         confirmButtonText: "Yes, delete it!",
//         buttonsStyling: false,
//         showCloseButton: true
//     }).then((result) => {
//         if (result.isConfirmed) {
//             Promise.all(ids_array.map((id) => axios.delete(`/schoolbill/${id}`)))
//                 .then(() => {
//                     Swal.fire({
//                         title: "Deleted!",
//                         text: "Your school bills have been deleted.",
//                         icon: "success",
//                         confirmButtonClass: "btn btn-info w-xs mt-2",
//                         buttonsStyling: false
//                     });
//                     window.location.reload();
//                 })
//                 .catch((error) => {
//                     console.error("Bulk delete error:", error);
//                     Swal.fire({
//                         title: "Error!",
//                         text: error.response?.data?.message || "Failed to delete school bills",
//                         icon: "error",
//                         confirmButtonClass: "btn btn-info w-xs mt-2",
//                         buttonsStyling: false
//                     });
//                 });
//         }
//     });
// }

// // Initialize List.js for client-side filtering on current page
// let schoolBillList;
// const schoolBillListContainer = document.getElementById('schoolBillList');
// if (schoolBillListContainer && document.querySelectorAll('#schoolBillList tbody tr').length > 0) {
//     try {
//         schoolBillList = new List('schoolBillList', {
//             valueNames: ['sn', 'title', 'bill_amount', 'description', 'statusId', 'updated_at'],
//             page: 1000, // Large page size to include all rows on current page
//             pagination: false // Disable List.js pagination to use Laravel's
//         });
//         console.log("List.js initialized");
//     } catch (error) {
//         console.error("List.js initialization failed:", error);
//     }
// } else {
//     console.warn("No school bills available for List.js initialization");
// }

// // Update no results message
// if (schoolBillList) {
//     schoolBillList.on('searchComplete', function () {
//         const noResultRow = document.querySelector('.noresult');
//         if (noResultRow) {
//             noResultRow.style.display = schoolBillList.visibleItems.length === 0 ? 'block' : 'none';
//         }
//     });
// }

// // Filter data (client-side for current page)
// function filterData() {
//     const searchInput = document.querySelector(".search-box input.search");
//     const searchValue = searchInput?.value || "";
//     console.log("Filtering with search:", searchValue);
//     if (schoolBillList) {
//         schoolBillList.search(searchValue, ['sn', 'title', 'bill_amount', 'description', 'statusId', 'updated_at']);
//     }
// }

// // Add school bill
// const addSchoolBillForm = document.getElementById("add-schoolbill-form");
// if (addSchoolBillForm) {
//     addSchoolBillForm.addEventListener("submit", function (e) {
//         e.preventDefault();
//         console.log("Add form submitted");
//         const errorMsg = document.getElementById("alert-error-msg");
//         if (errorMsg) errorMsg.classList.add("d-none");
//         const formData = new FormData(addSchoolBillForm);
//         const data = {
//             title: formData.get('title'),
//             bill_amount: formData.get('bill_amount'),
//             description: formData.get('description'),
//             statusId: formData.get('statusId')
//         };
//         if (!data.title || !data.bill_amount || !data.description || !data.statusId) {
//             if (errorMsg) {
//                 errorMsg.innerHTML = "Please fill all required fields";
//                 errorMsg.classList.remove("d-none");
//             }
//             return;
//         }
//         console.log("Sending add request:", data);
//         axios.post('/schoolbill', data)
//             .then(function (response) {
//                 console.log("Add success:", response.data);
//                 Swal.fire({
//                     position: "center",
//                     icon: "success",
//                     title: "School Bill added successfully!",
//                     showConfirmButton: false,
//                     timer: 2000,
//                     showCloseButton: true
//                 });
//                 window.location.reload();
//             })
//             .catch(function (error) {
//                 console.error("Add error:", error.response?.data || error);
//                 if (errorMsg) {
//                     errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(", ") || "Error adding school bill";
//                     errorMsg.classList.remove("d-none");
//                 }
//             });
//     });
// }

// // Edit school bill
// const editSchoolBillForm = document.getElementById("edit-schoolbill-form");
// if (editSchoolBillForm) {
//     editSchoolBillForm.addEventListener("submit", function (e) {
//         e.preventDefault();
//         console.log("Edit form submitted");
//         const errorMsg = document.getElementById("edit-alert-error-msg");
//         if (errorMsg) errorMsg.classList.add("d-none");
//         const formData = new FormData(editSchoolBillForm);
//         const id = editIdField?.value;
//         const data = {
//             title: formData.get('title'),
//             bill_amount: formData.get('bill_amount'),
//             description: formData.get('description'),
//             statusId: formData.get('statusId')
//         };
//         if (!id || !data.title || !data.bill_amount || !data.description || !data.statusId) {
//             if (errorMsg) {
//                 errorMsg.innerHTML = "Please fill all required fields";
//                 errorMsg.classList.remove("d-none");
//             }
//             return;
//         }
//         console.log("Sending edit request:", { id, ...data });
//         axios.put(`/schoolbill/${id}`, data)
//             .then(function (response) {
//                 console.log("Edit success:", response.data);
//                 Swal.fire({
//                     position: "center",
//                     icon: "success",
//                     title: "School Bill updated successfully!",
//                     showConfirmButton: false,
//                     timer: 2000,
//                     showCloseButton: true
//                 });
//                 window.location.reload();
//             })
//             .catch(function (error) {
//                 console.error("Edit error:", error.response?.data || error);
//                 if (errorMsg) {
//                     errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(", ") || "Error updating school bill";
//                     errorMsg.classList.remove("d-none");
//                 }
//             });
//     });
// }

// // Add modal events
// const addModal = document.getElementById("addSchoolBillModal");
// if (addModal) {
//     const modalInstance = new bootstrap.Modal(addModal);

//     addModal.addEventListener("show.bs.modal", function (e) {
//         console.log("Add modal show event");
//         const modalLabel = document.getElementById("addSchoolBillModalLabel");
//         const addBtn = document.getElementById("add-btn");
//         if (modalLabel) modalLabel.innerHTML = "Add School Bill";
//         if (addBtn) addBtn.innerHTML = "Add School Bill";
//     });

//     addModal.addEventListener("hidden.bs.modal", function () {
//         console.log("Add modal hidden");
//         clearAddFields();
//         const errorMsg = document.getElementById("alert-error-msg");
//         if (errorMsg) errorMsg.classList.add("d-none");

//         // Manually reset the modal state
//         addModal.style.display = 'none';
//         document.body.classList.remove('modal-open');
//         const modalBackdrop = document.querySelector('.modal-backdrop');
//         if (modalBackdrop) {
//             modalBackdrop.remove();
//         }
//     });

//     // Ensure close buttons work properly
//     const closeButtons = addModal.querySelectorAll('[data-bs-dismiss="modal"]');
//     closeButtons.forEach(button => {
//         button.addEventListener('click', () => {
//             modalInstance.hide();
//         });
//     });
// }

// const editModal = document.getElementById("editSchoolBillModal");
// if (editModal) {
//     editModal.addEventListener("show.bs.modal", function () {
//         console.log("Edit modal show event");
//         const modalLabel = document.getElementById("editSchoolBillModalLabel");
//         const updateBtn = document.getElementById("update-btn");
//         if (modalLabel) modalLabel.innerHTML = "Edit School Bill";
//         if (updateBtn) updateBtn.innerHTML = "Update";
//     });
//     editModal.addEventListener("hidden.bs.modal", function () {
//         console.log("Edit modal hidden");
//         clearEditFields();
//         const errorMsg = document.getElementById("edit-alert-error-msg");
//         if (errorMsg) errorMsg.classList.add("d-none");
//     });
// }

// // Initialize listeners
// document.addEventListener("DOMContentLoaded", function () {
//     console.log("DOMContentLoaded fired");
//     const searchInput = document.querySelector(".search-box input.search");
//     if (searchInput) {
//         searchInput.addEventListener("input", debounce(function () {
//             console.log("Search input changed:", searchInput.value);
//             filterData();
//         }, 300));
//     } else {
//         console.error("Search input not found");
//     }
//     initializeCheckboxes();

//     // Reinitialize checkboxes and List.js on pagination link click
//     document.querySelectorAll('.pagination a').forEach(link => {
//         link.addEventListener('click', function (e) {
//             console.log("Pagination link clicked");
//             setTimeout(() => {
//                 initializeCheckboxes();
//                 if (schoolBillList) {
//                     schoolBillList.reIndex();
//                     filterData();
//                 }
//             }, 500); // Delay to ensure DOM is updated
//         });
//     });
// });

// // Expose functions to global scope
// window.deleteMultiple = deleteMultiple;
