// document.addEventListener('DOMContentLoaded', function () {
//     let classTeacherList = null;

//     // Initialize List.js for table
//     function initializeListJs() {
//         const container = document.getElementById('classTeacherList');
//         if (!container) {
//             console.error("Class teacher list container not found");
//             return;
//         }

//         const rows = document.querySelectorAll('#classTeacherList tbody tr:not(.noresult)');
//         if (rows.length > 0) {
//             try {
//                 if (classTeacherList) {
//                     classTeacherList.clear();
//                     classTeacherList = null;
//                 }
//                 classTeacherList = new List('classTeacherList', {
//                     valueNames: ['sn', 'staffname', 'schoolclass', 'schoolarm', 'term', 'session', 'datereg'],
//                     pagination: false,
//                     page: 1000,
//                     listClass: 'list'
//                 });
//                 console.log("List.js initialized with", rows.length, "rows");
//                 console.log("List.js rows:", classTeacherList?.size());
//                 classTeacherList.on('searchComplete', function () {
//                     const noResult = document.querySelector('.noresult');
//                     if (noResult) {
//                         noResult.style.display = classTeacherList.visibleItems.length === 0 ? 'block' : 'none';
//                     }
//                 });
//             } catch (error) {
//                 console.error("List.js initialization failed:", error);
//             }
//         } else {
//             console.log("No rows found, showing noresult");
//             const noResult = document.querySelector('.noresult');
//             if (noResult) {
//                 noResult.style.display = 'block';
//             }
//         }
//     }

//     // Initialize checkboxes for bulk selection
//     function initializeCheckboxes() {
//         const checkAll = document.getElementById('checkAll');
//         if (checkAll) {
//             checkAll.addEventListener('click', function () {
//                 document.querySelectorAll('.form-check-all input[type=checkbox]').forEach(checkbox => {
//                     checkbox.checked = checkAll.checked;
//                 });
//                 toggleRemoveButton();
//             });
//         }
//         document.querySelectorAll('.form-check-all input[type=checkbox]').forEach(checkbox => {
//             checkbox.addEventListener('change', toggleRemoveButton);
//         });
//     }

//     // Toggle remove button visibility
//     function toggleRemoveButton() {
//         const removeActions = document.getElementById('remove-actions');
//         if (removeActions) {
//             removeActions.classList.toggle('d-none', !document.querySelectorAll('.form-check-all input[type=checkbox]:checked').length);
//         }
//     }

//     // Fetch page via AJAX to update table
//     function fetchPage(url) {
//         if (!url) {
//             console.error("No URL provided for fetchPage, using default");
//             url = '/classteacher'; // Fallback to default route
//         }
//         console.log("Fetching page:", url);
//         axios.get(url, {
//             headers: { 'X-Requested-With': 'XMLHttpRequest' },
//             params: { _t: new Date().getTime() } // Cache-busting
//         }).then(response => {
//             console.log("Fetch response:", {
//                 htmlLength: response.data.html?.length || 0,
//                 count: response.data.count,
//                 total: response.data.total,
//                 success: response.data.success
//             });
//             console.log("Response data:", response.data);

//             if (!response.data.success || !response.data.html) {
//                 console.error("No HTML content or unsuccessful response");
//                 Swal.fire({
//                     position: "center",
//                     icon: "error",
//                     title: "Error updating table",
//                     text: response.data.message || "No content received from server",
//                     showConfirmButton: true
//                 });
//                 return;
//             }

//             const parser = new DOMParser();
//             const doc = parser.parseFromString(response.data.html, 'text/html');
//             console.log("Parsed document:", doc.documentElement.outerHTML);

//             // Update table body
//             const newTbody = doc.querySelector('#kt_roles_view_table tbody');
//             const tbody = document.querySelector('#kt_roles_view_table tbody');
//             if (newTbody && tbody) {
//                 tbody.innerHTML = newTbody.innerHTML || '<tr><td colspan="9" class="noresult" style="display: block;">No results found</td></tr>';
//                 console.log("Table body updated, rows:", tbody.querySelectorAll('tr').length);
//             } else {
//                 console.error("Table body not found in response or DOM", { newTbody, tbody });
//                 Swal.fire({
//                     position: "center",
//                     icon: "error",
//                     title: "Error updating table",
//                     text: "Table structure not found",
//                     showConfirmButton: true
//                 });
//                 return;
//             }

//             // Update pagination
//             const newPagination = doc.querySelector('#pagination-element');
//             const pagination = document.querySelector('#pagination-element');
//             if (newPagination && pagination) {
//                 pagination.outerHTML = newPagination.outerHTML;
//                 console.log("Pagination updated");
//             } else {
//                 console.error("Pagination element not found", { newPagination, pagination });
//             }

//             // Update badge
//             const newBadge = doc.querySelector('.badge.bg-dark-subtle');
//             const badge = document.querySelector('.badge.bg-dark-subtle');
//             if (newBadge && badge) {
//                 badge.outerHTML = newBadge.outerHTML;
//                 console.log("Badge updated");
//             } else {
//                 console.error("Badge element not found", { newBadge, badge });
//             }

//             // Update results text
//             const resultsText = document.querySelector("#pagination-element .text-muted");
//             if (resultsText && response.data.count !== undefined && response.data.total !== undefined) {
//                 resultsText.innerHTML = `Showing <span class="fw-semibold">${response.data.count}</span> of <span class="fw-semibold">${response.data.total}</span> Results`;
//                 console.log("Results text updated");
//             } else {
//                 console.error("Results text element or data not found", { resultsText, count: response.data.count, total: response.data.total });
//             }

//             // Update noresult display
//             const noResult = document.querySelector(".noresult");
//             const rowCount = document.querySelectorAll("#kt_roles_view_table tbody tr:not(.noresult)").length;
//             if (noResult) {
//                 noResult.style.display = rowCount === 0 ? "block" : "none";
//             }

//             // Reinitialize components
//             initializeListJs();
//             initializeCheckboxes();
//             bindEventListeners();
//             console.log("Table update completed");
//         }).catch(error => {
//             console.error("Fetch error:", error.response?.data || error.message);
//             Swal.fire({
//                 position: "center",
//                 icon: "error",
//                 title: "Error updating table",
//                 text: error.response?.data?.message || error.message || "Failed to update table",
//                 showConfirmButton: true
//             });
//         });
//     }

//     // Add class teacher
//     const addClassTeacherForm = document.getElementById("add-classteacher-form");
//     if (addClassTeacherForm) {
//         addClassTeacherForm.addEventListener("submit", function (e) {
//             e.preventDefault();
//             console.log("Add form submitted");
//             const errorMsg = document.getElementById("alert-error-msg");
//             if (errorMsg) errorMsg.classList.add("d-none");

//             const formData = new FormData(addClassTeacherForm);
//             const staffid = formData.get('staffid');
//             const schoolclassids = formData.getAll('schoolclassid[]');
//             const termid = formData.get('termid');
//             const sessionid = formData.get('sessionid');

//             if (!staffid || schoolclassids.length === 0 || !termid || !sessionid) {
//                 if (errorMsg) {
//                     errorMsg.innerHTML = "Please fill all required fields.";
//                     errorMsg.classList.remove("d-none");
//                 }
//                 return;
//             }

//             console.log("Sending add request:", { staffid, schoolclassids, termid, sessionid });
//             axios.post('/classteacher', { staffid, schoolclassid: schoolclassids, termid, sessionid })
//                 .then(response => {
//                     console.log("Add success:", response.data);
//                     Swal.fire({
//                         position: "center",
//                         icon: "success",
//                         title: response.data.message || "Class Teacher added!",
//                         showConfirmButton: false,
//                         timer: 2000,
//                         showCloseButton: true
//                     });
//                     const currentPageUrl = document.querySelector('.pagination .page-item.active .page-link')?.getAttribute('data-url') || '/classteacher';
//                     fetchPage(currentPageUrl);
//                     bootstrap.Modal.getInstance(document.getElementById("addClassTeacherModal"))?.hide();
//                 })
//                 .catch(error => {
//                     console.error("Add error:", error.response?.data || error.message);
//                     if (errorMsg) {
//                         errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join("<br>") || "Error adding class teacher";
//                         errorMsg.classList.remove("d-none");
//                     }
//                 });
//         });
//     }

//     // Edit class teacher
//     const editClassTeacherForm = document.getElementById("edit-classteacher-form");
//     if (editClassTeacherForm) {
//         editClassTeacherForm.addEventListener("submit", function (e) {
//             e.preventDefault();
//             console.log("Edit form submitted");
//             const errorMsg = document.getElementById("edit-alert-error-msg");
//             if (errorMsg) errorMsg.classList.add("d-none");

//             const formData = new FormData(editClassTeacherForm);
//             const staffid = formData.get('staffid');
//             const schoolclassids = formData.getAll('schoolclassid[]');
//             const termid = formData.get('termid');
//             const sessionid = formData.get('sessionid');
//             const id = document.getElementById('edit-id-field')?.value;

//             if (!id || !staffid || schoolclassids.length === 0 || !termid || !sessionid) {
//                 if (errorMsg) {
//                     errorMsg.innerHTML = "Please fill all required fields.";
//                     errorMsg.classList.remove("d-none");
//                 }
//                 return;
//             }

//             console.log("Sending edit request:", { id, staffid, schoolclassids, termid, sessionid });
//             axios.put(`/classteacher/${id}`, { staffid, schoolclassid: schoolclassids, termid, sessionid })
//                 .then(response => {
//                     console.log("Edit success:", response.data);
//                     Swal.fire({
//                         position: "center",
//                         icon: "success",
//                         title: response.data.message || "Class Teacher updated!",
//                         showConfirmButton: false,
//                         timer: 2000,
//                         showCloseButton: true
//                     });
//                     const currentPageUrl = document.querySelector('.pagination .page-item.active .page-link')?.getAttribute('data-url') || '/classteacher';
//                     fetchPage(currentPageUrl);
//                     bootstrap.Modal.getInstance(document.getElementById("editModal"))?.hide();
//                 })
//                 .catch(error => {
//                     console.error("Edit error:", error.response?.data || error.message);
//                     if (errorMsg) {
//                         errorMsg.innerHTML = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join("<br>") || "Error updating class teacher";
//                         errorMsg.classList.remove("d-none");
//                     }
//                 });
//         });
//     }

//     // Handle edit button click
//     function handleEditClick(e) {
//         e.preventDefault();
//         const button = e.currentTarget;
//         console.log("Edit button clicked");
//         const itemId = button.closest("tr").querySelector(".id")?.getAttribute("data-id");
//         const staffId = button.closest("tr").querySelector(".staffname")?.getAttribute("data-staffid");
//         const termId = button.closest("tr").querySelector(".term")?.getAttribute("data-termid");
//         const sessionId = button.closest("tr").querySelector(".session")?.getAttribute("data-sessionid");

//         if (!itemId || !staffId || !termId || !sessionId) {
//             console.error("Missing data attributes for edit:", { itemId, staffId, termId, sessionId });
//             Swal.fire({
//                 position: "center",
//                 icon: "error",
//                 title: "Error loading edit data",
//                 text: "Missing required data",
//                 showConfirmButton: true
//             });
//             return;
//         }

//         console.log("Fetching assignments for:", { staffId, termId, sessionId });
//         axios.get(`/classteacher/assignments/${staffId}/${termId}/${sessionId}`)
//             .then(response => {
//                 console.log("Assignments fetched:", response.data);
//                 const editIdField = document.getElementById('edit-id-field');
//                 const editStaffIdField = document.getElementById('edit-staffid');
//                 if (editIdField) editIdField.value = itemId;
//                 if (editStaffIdField) editStaffIdField.value = staffId;

//                 document.querySelectorAll('input[name="schoolclassid[]"]').forEach(checkbox => {
//                     checkbox.checked = false;
//                 });
//                 document.querySelectorAll('input[name="termid"]').forEach(radio => {
//                     radio.checked = false;
//                 });
//                 document.querySelectorAll('input[name="sessionid"]').forEach(radio => {
//                     radio.checked = false;
//                 });

//                 response.data.classIds?.forEach(classId => {
//                     const checkbox = document.querySelector(`#edit_class_${classId}`);
//                     if (checkbox) {
//                         checkbox.checked = true;
//                         console.log("Checked class:", classId);
//                     }
//                 });

//                 const termRadio = document.querySelector(`#edit_term_${termId}`);
//                 if (termRadio) {
//                     termRadio.checked = true;
//                     console.log("Checked term:", termId);
//                 } else {
//                     console.error("Term radio not found for ID:", termId);
//                 }

//                 const sessionRadio = document.querySelector(`#edit_session_${sessionId}`);
//                 if (sessionRadio) {
//                     sessionRadio.checked = true;
//                     console.log("Checked session:", sessionId);
//                 } else {
//                     console.error("Session radio not found for ID:", sessionId);
//                 }

//                 bootstrap.Modal.getOrCreateInstance(document.getElementById("editModal")).show();
//                 console.log("Edit modal opened");
//             })
//             .catch(error => {
//                 console.error("Error fetching assignments:", error.response?.data || error.message);
//                 Swal.fire({
//                     position: "center",
//                     icon: "error",
//                     title: "Error loading edit data",
//                     text: error.response?.data?.message || "Failed to load assignments",
//                     showConfirmButton: true
//                 });
//             });
//     }

//     // Handle delete button click
//     function handleRemoveClick(e) {
//         e.preventDefault();
//         const button = e.currentTarget;
//         const deleteModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteRecordModal'));
//         deleteModal.show();

//         document.getElementById('delete-record').onclick = function () {
//             const deleteUrl = button.closest('tr').getAttribute('data-url');
//             console.log("Deleting:", deleteUrl);
//             axios.delete(deleteUrl)
//                 .then(response => {
//                     console.log("Delete success:", response.data);
//                     Swal.fire({
//                         position: "center",
//                         icon: "success",
//                         title: response.data.message || "Class Teacher deleted!",
//                         showConfirmButton: false,
//                         timer: 2000,
//                         showCloseButton: true
//                     });
//                     const currentPageUrl = document.querySelector('.pagination .page-item.active .page-link')?.getAttribute('data-url') || '/classteacher';
//                     fetchPage(currentPageUrl);
//                     deleteModal.hide();
//                 })
//                 .catch(error => {
//                     console.error("Delete error:", error.response?.data || error.message);
//                     Swal.fire({
//                         position: "center",
//                         icon: "error",
//                         title: error.response?.data?.message || "Error deleting class teacher",
//                         showConfirmButton: true
//                     });
//                 });
//         };
//     }

//     // Delete multiple class teachers
//     function deleteMultiple() {
//         const ids = Array.from(document.querySelectorAll('input[name=chk_child]:checked'))
//             .map(checkbox => checkbox.closest('tr').querySelector('.id').getAttribute('data-id'))
//             .filter(id => id);

//         if (ids.length === 0) {
//             console.log("No class teachers selected");
//             Swal.fire({
//                 position: "center",
//                 icon: "warning",
//                 title: "Please select at least one record",
//                 showConfirmButton: true
//             });
//             return;
//         }

//         Swal.fire({
//             title: "Are you sure?",
//             text: "You won't be able to revert this!",
//             icon: "warning",
//             showCancelButton: true,
//             confirmButtonClass: "btn btn-primary",
//             cancelButtonClass: "btn btn-light ms-2",
//             confirmButtonText: "Yes, delete it!"
//         }).then(result => {
//             if (result.isConfirmed) {
//                 console.log("Deleting multiple:", ids);
//                 axios.post('/classteacher/delete', { ids })
//                     .then(response => {
//                         console.log("Delete multiple success:", response.data);
//                         Swal.fire({
//                             position: "center",
//                             icon: "success",
//                             title: response.data.message || "Class Teachers deleted!",
//                             showConfirmButton: false,
//                             timer: 2000,
//                             showCloseButton: true
//                         });
//                         const currentPageUrl = document.querySelector('.pagination .page-item.active .page-link')?.getAttribute('data-url') || '/classteacher';
//                         fetchPage(currentPageUrl);
//                     })
//                     .catch(error => {
//                         console.error("Delete multiple error:", error.response?.data || error.message);
//                         Swal.fire({
//                             position: "center",
//                             icon: "error",
//                             title: error.response?.data?.message || "Error deleting class teachers",
//                             showConfirmButton: true
//                         });
//                     });
//             }
//         });
//     }

//     // Bind event listeners
//     function bindEventListeners() {
//         document.querySelectorAll('.edit-item-btn').forEach(button => {
//             button.removeEventListener('click', handleEditClick);
//             button.addEventListener('click', handleEditClick);
//         });
//         document.querySelectorAll('.remove-item-btn').forEach(button => {
//             button.removeEventListener('click', handleRemoveClick);
//             button.addEventListener('click', handleRemoveClick);
//         });
//         document.querySelectorAll('.pagination .page-link').forEach(link => {
//             link.removeEventListener('click', handlePaginationClick);
//             link.addEventListener('click', handlePaginationClick);
//         });
//     }

//     function handlePaginationClick(e) {
//         e.preventDefault();
//         const url = e.target.getAttribute('data-url');
//         if (url) {
//             console.log("Pagination click:", url);
//             fetchPage(url);
//         }
//     }

//     // Initialize page
//     console.log("Initializing class teacher page");
//     initializeListJs();
//     initializeCheckboxes();
//     bindEventListeners();

//     // Expose deleteMultiple globally
//     window.deleteMultiple = deleteMultiple;
// });
