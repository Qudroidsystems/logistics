// var perPage = 5,
//     editlist = false,
//     checkAll = document.getElementById("checkAll"),
//     options = {
//         valueNames: ["id", "sn", "admissionno", "name", "gender"],
//     },
//     studentList = new List("studentList", options);

// console.log("Initial studentList items:", studentList.items.length);

// studentList.on("updated", function (e) {
//     console.log("List.js updated, matching items:", e.matchingItems.length, "total items:", studentList.items.length);
//     document.getElementsByClassName("noresult")[0].style.display = e.matchingItems.length === 0 ? "block" : "none";
//     setTimeout(() => {
//         refreshCallbacks();
//         ischeckboxcheck();
//     }, 100);
// });

// document.addEventListener("DOMContentLoaded", function () {
//     console.log("DOM loaded, initializing List.js...");
//     console.log("Initial studentList items:", studentList.items.length);
//     refreshCallbacks();
//     ischeckboxcheck();

//     // Initialize Choices.js
//     if (typeof Choices !== 'undefined') {
//         var genderFilterVal = new Choices(document.getElementById("idGender"), { searchEnabled: true });
//         var admissionNoFilterVal = new Choices(document.getElementById("idAdmissionNo"), { searchEnabled: true });
//     } else {
//         console.warn("Choices.js not available, falling back to native select");
//     }
// });

// if (checkAll) {
//     checkAll.onclick = function () {
//         console.log("checkAll clicked");
//         var checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
//         console.log("checkAll clicked, checkboxes found:", checkboxes.length);
//         checkboxes.forEach((checkbox) => {
//             checkbox.checked = this.checked;
//             const row = checkbox.closest("tr");
//             if (checkbox.checked) {
//                 row.classList.add("table-active");
//             } else {
//                 row.classList.remove("table-active");
//             }
//         });
//         const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
//         document.getElementById("remove-actions").classList.toggle("d-none", checkedCount === 0);
//     };
// }

// var addIdField = document.getElementById("add-id-field"),
//     addAdmissionNoField = document.getElementById("admissionno"),
//     addFirstNameField = document.getElementById("firstname"),
//     addLastNameField = document.getElementById("lastname"),
//     addOtherNameField = document.getElementById("othername"),
//     addGenderField = document.getElementById("gender"),
//     editIdField = document.getElementById("edit-id-field"),
//     editAdmissionNoField = document.getElementById("edit-admissionno"),
//     editFirstNameField = document.getElementById("edit-firstname"),
//     editLastNameField = document.getElementById("edit-lastname"),
//     editOtherNameField = document.getElementById("edit-othername"),
//     editGenderField = document.getElementById("edit-gender");

// function ensureAxios() {
//     if (typeof axios === 'undefined') {
//         console.error("Axios is not defined. Please include Axios library.");
//         Swal.fire({
//             position: "center",
//             icon: "error",
//             title: "Configuration error",
//             text: "Axios library is missing",
//             showConfirmButton: true
//         });
//         return false;
//     }
//     return true;
// }

// function ischeckboxcheck() {
//     const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
//     checkboxes.forEach((checkbox) => {
//         checkbox.removeEventListener("change", handleCheckboxChange);
//         checkbox.addEventListener("change", handleCheckboxChange);
//     });
// }

// function handleCheckboxChange(e) {
//     const row = e.target.closest("tr");
//     if (e.target.checked) {
//         row.classList.add("table-active");
//     } else {
//         row.classList.remove("table-active");
//     }
//     const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
//     document.getElementById("remove-actions").classList.toggle("d-none", checkedCount === 0);
//     const allCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]');
//     document.getElementById("checkAll").checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCount;
// }

// function refreshCallbacks() {
//     console.log("refreshCallbacks executed at", new Date().toISOString());
//     var removeButtons = document.getElementsByClassName("remove-item-btn");
//     var editButtons = document.getElementsByClassName("edit-item-btn");
//     console.log("Attaching event listeners to", removeButtons.length, "remove buttons and", editButtons.length, "edit buttons");

//     Array.from(removeButtons).forEach(function (btn) {
//         btn.removeEventListener("click", handleRemoveClick);
//         btn.addEventListener("click", handleRemoveClick);
//     });

//     Array.from(editButtons).forEach(function (btn) {
//         btn.removeEventListener("click", handleEditClick);
//         btn.addEventListener("click", handleEditClick);
//     });
// }

// function handleRemoveClick(e) {
//     e.preventDefault();
//     try {
//         var itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
//         console.log("Remove button clicked for ID:", itemId);
//         document.getElementById("delete-record").addEventListener("click", function () {
//             if (!ensureAxios()) return;
//             axios.delete(`/students/${itemId}`, {
//                 headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
//             }).then(function () {
//                 console.log("Deleted student ID:", itemId);
//                 window.location.reload();
//                 Swal.fire({
//                     position: "center",
//                     icon: "success",
//                     title: "Student deleted successfully!",
//                     showConfirmButton: false,
//                     timer: 2000,
//                     showCloseButton: true
//                 });
//             }).catch(function (error) {
//                 console.error("Error deleting student:", error);
//                 Swal.fire({
//                     position: "center",
//                     icon: "error",
//                     title: "Error deleting student",
//                     text: error.response?.data?.message || "An error occurred",
//                     showConfirmButton: true
//                 });
//             });
//         }, { once: true });
//         var modal = new bootstrap.Modal(document.getElementById("deleteRecordModal"));
//         modal.show();
//     } catch (error) {
//         console.error("Error in remove-item-btn click:", error);
//         Swal.fire({
//             icon: "error",
//             title: "Error",
//             text: "Failed to initiate delete",
//             showConfirmButton: true
//         });
//     }
// }

// function handleEditClick(e) {
//     e.preventDefault();
//     try {
//         var itemId = e.target.closest("tr").querySelector(".id").getAttribute("data-id");
//         var tr = e.target.closest("tr");
//         console.log("Edit button clicked for ID:", itemId);
//         editlist = true;
//         editIdField.value = itemId;
//         editAdmissionNoField.value = tr.querySelector(".admissionno").innerText;
//         var nameParts = tr.querySelector(".name a").innerText.trim().split(" ");
//         editFirstNameField.value = nameParts[0];
//         editLastNameField.value = nameParts[1] || "";
//         editOtherNameField.value = nameParts[2] || "";
//         editGenderField.value = tr.querySelector(".gender").innerText;
//         var modal = new bootstrap.Modal(document.getElementById("editModal"));
//         modal.show();
//     } catch (error) {
//         console.error("Error in edit-item-btn click:", error);
//         Swal.fire({
//             icon: "error",
//             title: "Error",
//             text: "Failed to populate edit modal",
//             showConfirmButton: true
//         });
//     }
// }

// function clearAddFields() {
//     addIdField.value = "";
//     addAdmissionNoField.value = "";
//     addFirstNameField.value = "";
//     addLastNameField.value = "";
//     addOtherNameField.value = "";
//     addGenderField.value = "Male";
// }

// function clearEditFields() {
//     editIdField.value = "";
//     editAdmissionNoField.value = "";
//     editFirstNameField.value = "";
//     editLastNameField.value = "";
//     editOtherNameField.value = "";
//     editGenderField.value = "Male";
// }

// function deleteMultiple() {
//     const ids_array = [];
//     const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
//     checkboxes.forEach((checkbox) => {
//         if (checkbox.checked) {
//             const id = checkbox.closest("tr").querySelector(".id").getAttribute("data-id");
//             ids_array.push(id);
//         }
//     });
//     if (ids_array.length > 0) {
//         Swal.fire({
//             title: "Are you sure?",
//             text: "You won't be able to revert this!",
//             icon: "warning",
//             showCancelButton: true,
//             confirmButtonClass: "btn btn-primary w-xs me-2 mt-2",
//             cancelButtonClass: "btn btn-danger w-xs mt-2",
//             confirmButtonText: "Yes, delete it!",
//             buttonsStyling: false,
//             showCloseButton: true
//         }).then((result) => {
//             if (result.value) {
//                 if (!ensureAxios()) return;
//                 Promise.all(ids_array.map((id) => {
//                     return axios.delete(`/students/${id}`, {
//                         headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
//                     });
//                 })).then(() => {
//                     window.location.reload();
//                     Swal.fire({
//                         title: "Deleted!",
//                         text: "Your data has been deleted.",
//                         icon: "success",
//                         confirmButtonClass: "btn btn-info w-xs mt-2",
//                         buttonsStyling: false
//                     });
//                 }).catch((error) => {
//                     console.error("Error deleting students:", error);
//                     Swal.fire({
//                         title: "Error!",
//                         text: error.response?.data?.message || "Failed to delete students",
//                         icon: "error",
//                         confirmButtonClass: "btn btn-info w-xs mt-2",
//                         buttonsStyling: false
//                     });
//                 });
//             }
//         });
//     } else {
//         Swal.fire({
//             title: "Please select at least one checkbox",
//             confirmButtonClass: "btn btn-info",
//             buttonsStyling: false,
//             showCloseButton: true
//         });
//     }
// }

// function filterData() {
//     var searchInput = document.querySelector(".search-box input.search").value.toLowerCase();
//     var genderSelect = document.getElementById("idGender");
//     var admissionNoSelect = document.getElementById("idAdmissionNo");
//     var selectedGender = typeof Choices !== 'undefined' && genderFilterVal ? genderFilterVal.getValue(true) : genderSelect.value;
//     var selectedAdmissionNo = typeof Choices !== 'undefined' && admissionNoFilterVal ? admissionNoFilterVal.getValue(true) : admissionNoSelect.value;

//     console.log("Filtering with:", { search: searchInput, gender: selectedGender, admissionNo: selectedAdmissionNo });

//     studentList.filter(function (item) {
//         var nameMatch = item.values().name.toLowerCase().includes(searchInput);
//         var admissionNoMatch = item.values().admissionno.toLowerCase().includes(searchInput);
//         var genderMatch = selectedGender === "all" || item.values().gender === selectedGender;
//         var admissionNoSelectMatch = selectedAdmissionNo === "all" || item.values().admissionno === selectedAdmissionNo;

//         return (nameMatch || admissionNoMatch) && genderMatch && admissionNoSelectMatch;
//     });
// }

// document.getElementById("add-student-form").addEventListener("submit", function (e) {
//     e.preventDefault();
//     var errorMsg = document.getElementById("add-alert-error-msg");
//     errorMsg.classList.remove("d-none");
//     setTimeout(() => errorMsg.classList.add("d-none"), 2000);

//     if (addAdmissionNoField.value === "") {
//         errorMsg.innerHTML = "Please enter an admission number";
//         return false;
//     }
//     if (addFirstNameField.value === "") {
//         errorMsg.innerHTML = "Please enter a first name";
//         return false;
//     }
//     if (addLastNameField.value === "") {
//         errorMsg.innerHTML = "Please enter a last name";
//         return false;
//     }
//     if (addGenderField.value === "") {
//         errorMsg.innerHTML = "Please select a gender";
//         return false;
//     }

//     if (!ensureAxios()) return;

//     axios.post('/students', {
//         admissionno: addAdmissionNoField.value,
//         firstname: addFirstNameField.value,
//         lastname: addLastNameField.value,
//         othername: addOtherNameField.value,
//         gender: addGenderField.value,
//         schoolclassid: document.querySelector('input[name="schoolclassid"]').value,
//         termid: document.querySelector('input[name="termid"]').value,
//         sessionid: document.querySelector('input[name="sessionid"]').value,
//         _token: document.querySelector('meta[name="csrf-token"]').content
//     }).then(function (response) {
//         window.location.reload();
//         Swal.fire({
//             position: "center",
//             icon: "success",
//             title: "Student added successfully!",
//             showConfirmButton: false,
//             timer: 2000,
//             showCloseButton: true
//         });
//     }).catch(function (error) {
//         console.error("Error adding student:", error);
//         var message = error.response?.data?.message || "Error adding student";
//         if (error.response?.status === 422) {
//             message = Object.values(error.response.data.errors || {}).flat().join(", ");
//         }
//         errorMsg.innerHTML = message;
//     });
// });

// document.getElementById("edit-student-form").addEventListener("submit", function (e) {
//     e.preventDefault();
//     var errorMsg = document.getElementById("edit-alert-error-msg");
//     errorMsg.classList.remove("d-none");
//     setTimeout(() => errorMsg.classList.add("d-none"), 2000);

//     if (editAdmissionNoField.value === "") {
//         errorMsg.innerHTML = "Please enter an admission number";
//         return false;
//     }
//     if (editFirstNameField.value === "") {
//         errorMsg.innerHTML = "Please enter a first name";
//         return false;
//     }
//     if (editLastNameField.value === "") {
//         errorMsg.innerHTML = "Please enter a last name";
//         return false;
//     }
//     if (editGenderField.value === "") {
//         errorMsg.innerHTML = "Please select a gender";
//         return false;
//     }

//     if (!ensureAxios()) return;

//     axios.put(`/students/${editIdField.value}`, {
//         admissionno: editAdmissionNoField.value,
//         firstname: editFirstNameField.value,
//         lastname: editLastNameField.value,
//         othername: editOtherNameField.value,
//         gender: editGenderField.value,
//         schoolclassid: document.querySelector('input[name="schoolclassid"]').value,
//         termid: document.querySelector('input[name="termid"]').value,
//         sessionid: document.querySelector('input[name="sessionid"]').value,
//         _token: document.querySelector('meta[name="csrf-token"]').content
//     }).then(function (response) {
//         window.location.reload();
//         Swal.fire({
//             position: "center",
//             icon: "success",
//             title: "Student updated successfully!",
//             showConfirmButton: false,
//             timer: 2000,
//             showCloseButton: true
//         });
//     }).catch(function (error) {
//         console.error("Error updating student:", error);
//         var message = error.response?.data?.message || "Error updating student";
//         if (error.response?.status === 422) {
//             message = Object.values(error.response.data.errors || {}).flat().join(", ");
//         }
//         errorMsg.innerHTML = message;
//     });
// });

// document.getElementById("showModal").addEventListener("show.bs.modal", function (e) {
//     if (e.relatedTarget.classList.contains("add-btn")) {
//         console.log("Opening showModal for adding student...");
//         document.getElementById("addModalLabel").innerHTML = "Add Student";
//         document.getElementById("add-btn").innerHTML = "Add Student";
//     }
// });

// document.getElementById("editModal").addEventListener("show.bs.modal", function () {
//     console.log("Opening editModal...");
//     document.getElementById("editModalLabel").innerHTML = "Edit Student";
//     document.getElementById("update-btn").innerHTML = "Update";
// });

// document.getElementById("showModal").addEventListener("hidden.bs.modal", function () {
//     console.log("showModal closed, clearing fields...");
//     clearAddFields();
// });

// document.getElementById("editModal").addEventListener("hidden.bs.modal", function () {
//     console.log("editModal closed, clearing fields...");
//     clearEditFields();
// });