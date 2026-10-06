// var perPage = 10,
//     checkAll = document.getElementById("checkAll");

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
//     const registerButton = document.getElementById("register-selected-btn");
//     const unregisterButton = document.getElementById("unregister-selected-btn");
//     if (registerButton) {
//         registerButton.classList.toggle("d-none", checkedCount === 0);
//     }
//     if (unregisterButton) {
//         unregisterButton.classList.toggle("d-none", checkedCount === 0);
//     }
//     const allCheckboxes = document.querySelectorAll('tbody input[name="chk_child"]');
//     document.getElementById("checkAll").checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCount;
// }

// function refreshCallbacks() {
//     console.log("refreshCallbacks executed at", new Date().toISOString());
//     ischeckboxcheck();
// }

// function updateAdmissionNoOptions(students) {
//     const select = document.getElementById("idadmission");
//     if (!select) return;

//     select.innerHTML = '<option value="ALL">Select Admission No</option>';

//     const uniqueAdmissionNos = [...new Set(students.map(s => s.admissionno).filter(Boolean))].sort();

//     uniqueAdmissionNos.forEach(admissionNo => {
//         const option = document.createElement("option");
//         option.value = admissionNo;
//         option.text = admissionNo;
//         select.appendChild(option);
//     });
// }

// function selectAllSubjects() {
//     const subjectCheckboxes = document.querySelectorAll('.subject-checkbox');
//     subjectCheckboxes.forEach(checkbox => {
//         checkbox.checked = true;
//     });
// }

// function deselectAllSubjects() {
//     const subjectCheckboxes = document.querySelectorAll('.subject-checkbox');
//     subjectCheckboxes.forEach(checkbox => {
//         checkbox.checked = false;
//     });
// }

// document.addEventListener("DOMContentLoaded", function () {
//     console.log("DOM loaded, initializing...");
//     refreshCallbacks();

//     if (typeof Choices !== 'undefined') {
//         const choicesElements = ['idclass', 'idsession', 'idgender', 'idadmission'];
//         choicesElements.forEach(id => {
//             const element = document.getElementById(id);
//             if (element) {
//                 new Choices(element, { searchEnabled: true });
//             }
//         });
//     } else {
//         console.warn("Choices.js not available, using native select");
//     }

//     if (checkAll) {
//         checkAll.onclick = function () {
//             console.log("checkAll clicked");
//             var checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
//             checkboxes.forEach((checkbox) => {
//                 checkbox.checked = this.checked;
//                 const row = checkbox.closest("tr");
//                 if (checkbox.checked) {
//                     row.classList.add("table-active");
//                 } else {
//                     row.classList.remove("table-active");
//                 }
//             });
//             const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
//             const registerButton = document.getElementById("register-selected-btn");
//             const unregisterButton = document.getElementById("unregister-selected-btn");
//             if (registerButton) {
//                 registerButton.classList.toggle("d-none", checkedCount === 0);
//             }
//             if (unregisterButton) {
//                 unregisterButton.classList.toggle("d-none", checkedCount === 0);
//             }
//         };
//     }

//     const registeredClassesModal = document.getElementById('registeredClassesModal');
//     if (registeredClassesModal) {
//         registeredClassesModal.addEventListener('show.bs.modal', function () {
//             loadRegisteredClasses();
//         });
//     }
// });

// function filterData() {
//     if (!ensureAxios()) return;

//     const classSelect = document.getElementById("idclass");
//     const sessionSelect = document.getElementById("idsession");
//     const searchInput = document.querySelector(".search-box input.search");
//     const genderSelect = document.getElementById("idgender");
//     const admissionNoSelect = document.getElementById("idadmission");

//     if (!classSelect || !sessionSelect) {
//         console.error("Required filter elements not found");
//         Swal.fire({
//             icon: "error",
//             title: "Error",
//             text: "Filter elements not found. Please refresh the page.",
//             showConfirmButton: true
//         });
//         return;
//     }

//     const classValue = classSelect.value;
//     const sessionValue = sessionSelect.value;
//     const searchValue = searchInput ? searchInput.value.toLowerCase() : '';
//     const genderValue = genderSelect ? genderSelect.value : 'ALL';
//     const admissionNoValue = admissionNoSelect ? admissionNoSelect.value : 'ALL';

//     if (classValue === 'ALL' || sessionValue === 'ALL') {
//         Swal.fire({
//             icon: "warning",
//             title: "Missing filters",
//             text: "Please select a class and session",
//             showConfirmButton: true
//         });
//         return;
//     }

//     console.log("Filtering with:", {
//         search: searchValue,
//         class_id: classValue,
//         session_id: sessionValue,
//         gender: genderValue,
//         admissionno: admissionNoValue
//     });

//     const tableBody = document.getElementById('studentTableBody');
//     const subjectTeachersContainer = document.getElementById('subjectTeachersContainer');
//     const subjectTeachersCard = document.getElementById('subjectTeachersCard');
//     const subjectTeacherCount = document.getElementById('subjectTeacherCount');

//     if (tableBody) {
//         tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';
//     }
//     if (subjectTeachersContainer) {
//         subjectTeachersContainer.innerHTML = '<div class="col-12 text-center">Loading subject teachers...</div>';
//     }

//     axios.get('/subjects', {
//         params: {
//             search: searchValue,
//             class_id: classValue,
//             session_id: sessionValue,
//             gender: genderValue,
//             admissionno: admissionNoValue
//         },
//         headers: {
//             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
//             'X-Requested-With': 'XMLHttpRequest'
//         }
//     }).then(function (response) {
//         console.log("AJAX response received");

//         const parser = new DOMParser();
//         const doc = parser.parseFromString(response.data, 'text/html');

//         const newTableBody = doc.querySelector('#studentTableBody');
//         const currentTableBody = document.getElementById('studentTableBody');
//         if (newTableBody && currentTableBody) {
//             currentTableBody.innerHTML = newTableBody.innerHTML;
//         }

//         const newPagination = doc.querySelector('#pagination-container');
//         const currentPagination = document.getElementById('pagination-container');
//         if (newPagination && currentPagination) {
//             currentPagination.innerHTML = newPagination.innerHTML;
//         }

//         const newStudentCount = doc.querySelector('#studentcount');
//         const currentStudentCount = document.getElementById('studentcount');
//         if (newStudentCount && currentStudentCount) {
//             currentStudentCount.innerText = newStudentCount.innerText;
//         }

//         const newSubjectTeachersContainer = doc.querySelector('#subjectTeachersContainer');
//         if (newSubjectTeachersContainer && subjectTeachersContainer) {
//             subjectTeachersContainer.innerHTML = newSubjectTeachersContainer.innerHTML;
//             const subjectCount = subjectTeachersContainer.querySelectorAll('.subject-checkbox').length;
//             if (subjectTeacherCount) {
//                 subjectTeacherCount.innerText = subjectCount;
//             }
//             if (subjectTeachersCard) {
//                 subjectTeachersCard.style.display = subjectCount > 0 ? 'block' : 'none';
//             }
//         }

//         const studentRows = doc.querySelectorAll('#studentTableBody tr');
//         const students = [];
//         studentRows.forEach(row => {
//             const admissionCell = row.querySelector('.admissionno');
//             if (admissionCell) {
//                 const admissionNo = admissionCell.dataset.admissionno || admissionCell.textContent.trim();
//                 if (admissionNo && admissionNo !== 'Select class and session to view students.') {
//                     students.push({ admissionno: admissionNo });
//                 }
//             }
//         });

//         updateAdmissionNoOptions(students);
//         refreshCallbacks();
//         setupPaginationLinks();

//         if (students.length === 0 && currentTableBody.innerHTML.includes('Select class and session')) {
//             // Initial empty state
//         } else if (students.length === 0) {
//             Swal.fire({
//                 icon: "info",
//                 title: "No Results",
//                 text: "No students found matching your criteria",
//                 showConfirmButton: true
//             });
//         }
//     }).catch(function (error) {
//         console.error("Error filtering data:", error);

//         if (tableBody) {
//             tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading data. Please try again.</td></tr>';
//         }
//         if (subjectTeachersContainer) {
//             subjectTeachersContainer.innerHTML = '<div class="col-12 text-center text-danger">Error loading subject teachers.</div>';
//         }

//         Swal.fire({
//             icon: "error",
//             title: "Error",
//             text: error.response?.data?.message || "Failed to fetch filtered data. Please try again.",
//             showConfirmButton: true
//         });
//     });
// }

// function setupPaginationLinks() {
//     const paginationLinks = document.querySelectorAll('#pagination-container a');
//     paginationLinks.forEach(link => {
//         link.addEventListener('click', function(e) {
//             e.preventDefault();
//             const url = this.href;
//             if (url && !this.classList.contains('disabled')) {
//                 loadPage(url);
//             }
//         });
//     });
// }

// function loadPage(url) {
//     if (!ensureAxios()) return;

//     const tableBody = document.getElementById('studentTableBody');
//     const subjectTeachersContainer = document.getElementById('subjectTeachersContainer');
//     const subjectTeachersCard = document.getElementById('subjectTeachersCard');
//     const subjectTeacherCount = document.getElementById('subjectTeacherCount');

//     if (tableBody) {
//         tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';
//     }
//     if (subjectTeachersContainer) {
//         subjectTeachersContainer.innerHTML = '<div class="col-12 text-center">Loading subject teachers...</div>';
//     }

//     axios.get(url, {
//         headers: {
//             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
//             'X-Requested-With': 'XMLHttpRequest'
//         }
//     }).then(function (response) {
//         const parser = new DOMParser();
//         const doc = parser.parseFromString(response.data, 'text/html');

//         const newTableBody = doc.querySelector('#studentTableBody');
//         const currentTableBody = document.getElementById('studentTableBody');
//         if (newTableBody && currentTableBody) {
//             currentTableBody.innerHTML = newTableBody.innerHTML;
//         }

//         const newPagination = doc.querySelector('#pagination-container');
//         const currentPagination = document.getElementById('pagination-container');
//         if (newPagination && currentPagination) {
//             currentPagination.innerHTML = newPagination.innerHTML;
//         }

//         const newStudentCount = doc.querySelector('#studentcount');
//         const currentStudentCount = document.getElementById('studentcount');
//         if (newStudentCount && currentStudentCount) {
//             currentStudentCount.innerText = newStudentCount.innerText;
//         }

//         const newSubjectTeachersContainer = doc.querySelector('#subjectTeachersContainer');
//         if (newSubjectTeachersContainer && subjectTeachersContainer) {
//             subjectTeachersContainer.innerHTML = newSubjectTeachersContainer.innerHTML;
//             const subjectCount = subjectTeachersContainer.querySelectorAll('.subject-checkbox').length;
//             if (subjectTeacherCount) {
//                 subjectTeacherCount.innerText = subjectCount;
//             }
//             if (subjectTeachersCard) {
//                 subjectTeachersCard.style.display = subjectCount > 0 ? 'block' : 'none';
//             }
//         }

//         refreshCallbacks();
//         setupPaginationLinks();
//     }).catch(function (error) {
//         console.error("Error loading page:", error);
//         if (tableBody) {
//             tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading data. Please try again.</td></tr>';
//         }
//         if (subjectTeachersContainer) {
//             subjectTeachersContainer.innerHTML = '<div class="col-12 text-center text-danger">Error loading subject teachers.</div>';
//         }
//     });
// }

// function registerSelectedStudentsBatch() {
//     if (!ensureAxios()) return;

//     const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]:checked');
//     const classSelect = document.getElementById("idclass");
//     const sessionSelect = document.getElementById("idsession");
//     const subjectCheckboxes = document.querySelectorAll('.subject-checkbox:checked');
//     const registerButton = document.getElementById("register-selected-btn");
//     const loadingSpinner = document.getElementById("register-loading-spinner");

//     if (!classSelect || !sessionSelect) {
//         Swal.fire({
//             icon: "error",
//             title: "Error",
//             text: "Required filter elements not found. Please refresh the page.",
//             showConfirmButton: true
//         });
//         return;
//     }

//     const classValue = classSelect.value;
//     const sessionValue = sessionSelect.value;

//     if (classValue === 'ALL' || sessionValue === 'ALL') {
//         Swal.fire({
//             icon: "warning",
//             title: "Missing filters",
//             text: "Please select a class and session before registering students.",
//             showConfirmButton: true
//         });
//         return;
//     }

//     if (checkboxes.length === 0) {
//         Swal.fire({
//             icon: "warning",
//             title: "No students selected",
//             text: "Please select at least one student to register.",
//             showConfirmButton: true
//         });
//         return;
//     }

//     if (subjectCheckboxes.length === 0) {
//         Swal.fire({
//             icon: "warning",
//             title: "No subjects selected",
//             text: "Please select at least one subject to register.",
//             showConfirmButton: true
//         });
//         return;
//     }

//     if (loadingSpinner) loadingSpinner.classList.remove("d-none");
//     if (registerButton) {
//         registerButton.disabled = true;
//         registerButton.setAttribute("aria-disabled", "true");
//     }

//     const studentIds = Array.from(checkboxes).map(checkbox => checkbox.closest('tr').querySelector('.id').dataset.id);
//     const subjectClasses = Array.from(subjectCheckboxes).map(checkbox => ({
//         subjectclassid: checkbox.dataset.subjectclassid,
//         staffid: checkbox.dataset.staffid,
//         termid: checkbox.dataset.termid
//     }));

//     axios.post('/subjectregistration/batch', {
//         studentids: studentIds,
//         subjectclasses: subjectClasses,
//         sessionid: sessionValue
//     }, {
//         headers: {
//             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
//         }
//     }).then(function (response) {
//         if (loadingSpinner) loadingSpinner.classList.add("d-none");
//         if (registerButton) {
//             registerButton.disabled = false;
//             registerButton.setAttribute("aria-disabled", "false");
//         }

//         if (response.data.success) {
//             Swal.fire({
//                 icon: 'success',
//                 title: 'Success',
//                 text: `Successfully registered ${studentIds.length} student(s) for ${subjectClasses.length} subject(s).`,
//                 showConfirmButton: true
//             });
//         } else {
//             Swal.fire({
//                 icon: 'warning',
//                 title: 'Partial/Failed',
//                 html: `Some or all registrations failed.<br>${(response.data.error_details || []).map(e => e.message).join('<br>')}`,
//                 showConfirmButton: true
//             });
//         }
//         filterData();
//     }).catch(function (error) {
//         if (loadingSpinner) loadingSpinner.classList.add("d-none");
//         if (registerButton) {
//             registerButton.disabled = false;
//             registerButton.setAttribute("aria-disabled", "false");
//         }
//         Swal.fire({
//             icon: "error",
//             title: "Error",
//             text: error.response?.data?.message || "Failed to register subjects. Please try again.",
//             showConfirmButton: true
//         });
//     });
// }

// function unregisterSelectedStudentsBatch() {
//     if (!ensureAxios()) return;

//     const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]:checked');
//     const classSelect = document.getElementById("idclass");
//     const sessionSelect = document.getElementById("idsession");
//     const subjectCheckboxes = document.querySelectorAll('.subject-checkbox:checked');
//     const unregisterButton = document.getElementById("unregister-selected-btn");
//     const loadingSpinner = document.getElementById("register-loading-spinner"); // Reusing same spinner

//     if (!classSelect || !sessionSelect) {
//         Swal.fire({
//             icon: "error",
//             title: "Error",
//             text: "Required filter elements not found. Please refresh the page.",
//             showConfirmButton: true
//         });
//         return;
//     }

//     const classValue = classSelect.value;
//     const sessionValue = sessionSelect.value;

//     if (classValue === 'ALL' || sessionValue === 'ALL') {
//         Swal.fire({
//             icon: "warning",
//             title: "Missing filters",
//             text: "Please select a class and session before unregistering students.",
//             showConfirmButton: true
//         });
//         return;
//     }

//     if (checkboxes.length === 0) {
//         Swal.fire({
//             icon: "warning",
//             title: "No students selected",
//             text: "Please select at least one student to unregister.",
//             showConfirmButton: true
//         });
//         return;
//     }

//     if (subjectCheckboxes.length === 0) {
//         Swal.fire({
//             icon: "warning",
//             title: "No subjects selected",
//             text: "Please select at least one subject to unregister.",
//             showConfirmButton: true
//         });
//         return;
//     }

//     if (loadingSpinner) loadingSpinner.classList.remove("d-none");
//     if (unregisterButton) {
//         unregisterButton.disabled = true;
//         unregisterButton.setAttribute("aria-disabled", "true");
//     }

//     const studentIds = Array.from(checkboxes).map(checkbox => checkbox.closest('tr').querySelector('.id').dataset.id);
//     const subjectClasses = Array.from(subjectCheckboxes).map(checkbox => ({
//         subjectclassid: checkbox.dataset.subjectclassid,
//         staffid: checkbox.dataset.staffid,
//         termid: checkbox.dataset.termid
//     }));

//     axios.post('/subjectregistration/destroy', {
//         studentids: studentIds,
//         subjectclasses: subjectClasses,
//         sessionid: sessionValue
//     }, {
//         headers: {
//             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
//         }
//     }).then(function (response) {
//         if (loadingSpinner) loadingSpinner.classList.add("d-none");
//         if (unregisterButton) {
//             unregisterButton.disabled = false;
//             unregisterButton.setAttribute("aria-disabled", "false");
//         }

//         if (response.data.success) {
//             Swal.fire({
//                 icon: 'success',
//                 title: 'Success',
//                 text: `Successfully unregistered ${response.data.success_count} student(s) from ${subjectClasses.length} subject(s).`,
//                 showConfirmButton: true
//             });
//         } else {
//             Swal.fire({
//                 icon: 'warning',
//                 title: 'Partial/Failed',
//                 html: `Some or all unregistrations failed.<br>${(response.data.error_details || []).map(e => e.message).join('<br>')}`,
//                 showConfirmButton: true
//             });
//         }
//         filterData();
//     }).catch(function (error) {
//         if (loadingSpinner) loadingSpinner.classList.add("d-none");
//         if (unregisterButton) {
//             unregisterButton.disabled = false;
//             unregisterButton.setAttribute("aria-disabled", "false");
//         }
//         Swal.fire({
//             icon: "error",
//             title: "Error",
//             text: error.response?.data?.message || "Failed to unregister subjects. Please try again.",
//             showConfirmButton: true
//         });
//     });
// }

// function loadRegisteredClasses() {
//     if (!ensureAxios()) {
//         console.error('Axios not initialized.');
//         return;
//     }

//     const modalContent = document.getElementById('registeredClassesContent');
//     if (!modalContent) {
//         console.error('Modal content element not found.');
//         Swal.fire({
//             icon: 'error',
//             title: 'Error',
//             text: 'Modal container not found.',
//             showConfirmButton: true
//         });
//         return;
//     }

//     const classSelect = document.getElementById('idclass');
//     const sessionSelect = document.getElementById('idsession');

//     if (!classSelect || !sessionSelect) {
//         console.error('Required selectors missing:', { classSelect, sessionSelect });
//         modalContent.innerHTML = '<p class="text-center text-red-500">Class or session selector not found.</p>';
//         Swal.fire({
//             icon: 'error',
//             title: 'Error',
//             text: 'Please ensure class and session selectors are present.',
//             showConfirmButton: true
//         });
//         return;
//     }

//     const classId = classSelect.value;
//     const sessionId = sessionSelect.value;

//     if (!classId || classId === 'ALL' || !sessionId || sessionId === 'ALL') {
//         console.warn('Invalid class or session selection.');
//         modalContent.innerHTML = '<p class="text-center text-muted">Please select a valid class and session.</p>';
//         Swal.fire({
//             icon: 'warning',
//             title: 'Missing Selection',
//             text: 'Please select a valid class and session.',
//             showConfirmButton: true
//         });
//         return;
//     }

//     modalContent.innerHTML = '<p class="text-center">Loading registered classes...</p>';

//     axios.get('/subjects/registered-classes', {
//         params: { class_id: classId, session_id: sessionId },
//         headers: {
//             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
//             'X-Requested-With': 'XMLHttpRequest'
//         },
//         timeout: 15000
//     }).then(response => {
//         console.log('Registered classes response:', JSON.stringify(response.data, null, 2));
//         if (response.data.success) {
//             const classes = response.data.data;
//             let html = '<div class="table-responsive"><table class="table table-bordered table-striped">';
//             html += '<thead><tr><th>Class</th><th>Arm</th><th>Session</th><th>Term</th><th>Students</th><th>Subjects</th><th>Teachers</th></tr></thead><tbody>';

//             if (!classes || classes.length === 0) {
//                 html += '<tr><td colspan="7" class="text-center">No registered classes found.</td></tr>';
//             } else {
//                 classes.forEach(cls => {
//                     html += `<tr>
//                         <td>${cls.class_name || '-'}</td>
//                         <td>${cls.arm_name || '-'}</td>
//                         <td>${cls.session_name || '-'}</td>
//                         <td>${cls.term_name || '-'}</td>
//                         <td>${cls.student_count || 0}</td>
//                         <td>${cls.subjects || '-'}</td>
//                         <td>${cls.teachers || '-'}</td>
//                     </tr>`;
//                 });
//             }

//             html += '</tbody></table></div>';
//             modalContent.innerHTML = html;
//         } else {
//             console.error('Failed to load:', response.data.message);
//             modalContent.innerHTML = '<p class="text-center text-red-500">Failed to load registered classes.</p>';
//             Swal.fire({
//                 icon: 'error',
//                 title: 'Error',
//                 text: response.data.message || 'Failed to load registered classes.',
//                 showConfirmButton: true
//             });
//         }
//     }).catch(error => {
//         console.error('Error loading registered classes:', {
//             message: error.message,
//             response: error.response?.data,
//             status: error.response?.status
//         });
//         modalContent.innerHTML = '<p class="text-center text-red-500">Error loading registered classes. Please try again.</p>';
//         Swal.fire({
//             icon: 'error',
//             title: 'Error',
//             text: error.response?.data?.message || 'An error occurred while loading registered classes.',
//             showConfirmButton: true
//         });
//     });
// }

// // Attach to buttons
// document.addEventListener("DOMContentLoaded", function () {
//     const registerBtn = document.getElementById("register-selected-btn");
//     const unregisterBtn = document.getElementById("unregister-selected-btn");
//     if (registerBtn) {
//         registerBtn.onclick = registerSelectedStudentsBatch;
//     }
//     if (unregisterBtn) {
//         unregisterBtn.onclick = unregisterSelectedStudentsBatch;
//     }
// });


