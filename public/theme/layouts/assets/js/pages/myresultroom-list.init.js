// console.log("myresultroom-list.init.js loaded at", new Date().toISOString());

// // Verify dependencies
// try {
//     if (typeof axios === 'undefined') throw new Error("Axios is not loaded");
//     if (typeof Swal === 'undefined') throw new Error("SweetAlert2 is not loaded");
//     if (typeof bootstrap === 'undefined') throw new Error("Bootstrap is not loaded");
//     if (typeof List === 'undefined') throw new Error("List.js is not loaded");
//     console.log("All dependencies loaded successfully");
//     console.log("List.js version:", List.version || "Unknown");
// } catch (error) {
//     console.error("Dependency check failed:", error);
//     Swal.fire({
//         icon: "error",
//         title: "Dependency Error",
//         text: "Required libraries are missing. Check console for details.",
//         showConfirmButton: true
//     });
// }

// // Set Axios CSRF token
// const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
// axios.defaults.headers.common['X-CSRF-Token'] = csrfToken;
// if (!csrfToken) {
//     console.warn("CSRF token not found. AJAX requests may fail.");
// }

// // Debounce function for search
// function debounce(func, wait) {
//     let timeout;
//     return function (...args) {
//         clearTimeout(timeout);
//         timeout = setTimeout(() => func.apply(this, args), wait);
//     };
// }

// // Initialize List.js
// let subjectList = null;
// function initializeList() {
//     console.log("Attempting to initialize List.js");
//     const subjectListContainer = document.getElementById('subjectListTable');
//     const tableBody = document.querySelector('#subjectTableBody');

//     if (!subjectListContainer || !tableBody) {
//         console.error("List initialization error: #subjectListTable or #subjectTableBody not found");
//         Swal.fire({
//             icon: "error",
//             title: "Error",
//             text: "Table structure not found.",
//             showConfirmButton: true
//         });
//         return false;
//     }

//     const rows = tableBody.querySelectorAll('tr:not(.noresult)');
//     console.log("Table rows found:", rows.length);
//     if (rows.length === 0) {
//         console.log("No valid rows for List.js, skipping initialization");
//         customSearch('');
//         return false;
//     }

//     const firstRow = rows[0];
//     const valueNames = ['schoolclass', 'subject', 'subjectcode'];
//     let missingClasses = [];
//     valueNames.forEach(name => {
//         const element = firstRow.querySelector(`.${name}`);
//         if (!element) {
//             console.warn(`Class "${name}" not found in row`);
//             missingClasses.push(name);
//         } else {
//             console.log(`Class "${name}" found with content: ${element.textContent}`);
//         }
//     });

//     if (missingClasses.length > 0) {
//         console.error("Missing required classes:", missingClasses.join(', '));
//         console.log("Using custom search fallback due to missing classes");
//         customSearch('');
//         return false;
//     }

//     // Clear existing List.js instance
//     if (subjectList) {
//         console.log("Clearing existing List.js instance");
//         subjectList.clear();
//         subjectList = null;
//     }

//     try {
//         subjectList = new List('subjectList', {
//             valueNames: ['schoolclass', 'subject', 'subjectcode'],
//             page: 1000,
//             pagination: false
//         });
//         console.log("List.js initialized successfully", subjectList);
//         return true;
//     } catch (error) {
//         console.error("List.js initialization failed:", error.message, error.stack);
//         console.log("Using custom search fallback");
//         customSearch('');
//         return false;
//     }
// }

// // Custom search
// function customSearch(searchString) {
//     console.log("Executing custom search for:", searchString);
//     const tableBody = document.querySelector('#subjectTableBody');
//     const rows = tableBody.querySelectorAll('tr:not(.noresult)');
//     const lowerSearch = searchString.toLowerCase();

//     let hasMatches = false;
//     rows.forEach(row => {
//         const schoolclass = row.querySelector('.schoolclass')?.textContent.toLowerCase() || '';
//         const subject = row.querySelector('.subject')?.textContent.toLowerCase() || '';
//         const subjectcode = row.querySelector('.subjectcode')?.textContent.toLowerCase() || '';
//         const matches = schoolclass.includes(lowerSearch) || subject.includes(lowerSearch) || subjectcode.includes(lowerSearch);
//         row.style.display = matches ? '' : 'none';
//         if (matches) hasMatches = true;
//     });

//     const noDataAlert = document.getElementById('noDataAlert');
//     if (noDataAlert) {
//         noDataAlert.style.display = hasMatches ? 'none' : 'block';
//     }

//     console.log("Custom search completed, hasMatches:", hasMatches);
// }

// // Trigger search
// const triggerSearch = debounce(() => {
//     const searchInput = document.getElementById('searchInput');
//     if (!searchInput) {
//         console.warn("Search input not found");
//         return;
//     }

//     const searchString = searchInput.value.trim();
//     console.log("Searching for:", searchString);

//     const tableBody = document.querySelector('#subjectTableBody');
//     const hasValidRows = tableBody && tableBody.querySelectorAll('tr:not(.noresult)')?.length > 0;

//     if (!hasValidRows) {
//         console.warn("No valid rows to search");
//         Swal.fire({
//             icon: "info",
//             title: "No Data",
//             text: "No subjects available to search.",
//             showConfirmButton: true
//         });
//         return;
//     }

//     if (subjectList) {
//         try {
//             subjectList.search(searchString, ['schoolclass', 'subject', 'subjectcode']);
//             console.log("List.js search executed");
//         } catch (error) {
//             console.error("List.js search error:", error.message);
//             console.log("Using custom search fallback");
//             customSearch(searchString);
//         }
//     } else {
//         console.log("List.js not initialized, using custom search");
//         customSearch(searchString);
//     }
// }, 300);

// // Initialize search
// function initializeSearch() {
//     const searchInput = document.getElementById('searchInput');
//     const clearSearch = document.getElementById('clearSearch');

//     if (!searchInput) {
//         console.warn("Search input not found");
//         return;
//     }

//     searchInput.removeEventListener('input', triggerSearch);
//     searchInput.addEventListener('input', triggerSearch);
//     console.log("Search input listener attached");

//     if (clearSearch) {
//         clearSearch.removeEventListener('click', clearSearchHandler);
//         clearSearch.addEventListener('click', clearSearchHandler);
//         console.log("Clear search listener attached");
//     } else {
//         console.warn("Clear search button not found");
//     }

//     function clearSearchHandler() {
//         searchInput.value = '';
//         if (subjectList) {
//             try {
//                 subjectList.search('');
//                 console.log("List.js search cleared");
//             } catch (error) {
//                 console.error("Clear List.js error:", error.message);
//             }
//         }
//         customSearch('');
//         console.log("Custom search cleared");
//     }

//     const tableBody = document.querySelector('#subjectTableBody');
//     const hasValidRows = tableBody && tableBody.querySelectorAll('tr:not(.noresult)')?.length > 0;
//     searchInput.disabled = !hasValidRows;
//     console.log("Search input initialized, disabled:", searchInput.disabled, "hasValidRows:", hasValidRows);
// }

// // Update subjects table
// function updateSubjectsTable(mysubjects) {
//     console.log("Updating subjects table with:", mysubjects);
//     const tableBody = document.querySelector('#subjectTableBody');
//     const subjectCountElement = document.getElementById('subjectcount');
//     const noDataAlert = document.getElementById('noDataAlert');

//     if (!tableBody) {
//         console.error("Table body #subjectTableBody not found");
//         Swal.fire({
//             icon: "error",
//             title: "Error",
//             text: "Table element not found.",
//             showConfirmButton: true
//         });
//         return;
//     }

//     let tableHtml = '';
//     if (mysubjects.length > 0) {
//         let sn = 1;
//         tableHtml = mysubjects.map(subject => {
//             console.log("Processing subject:", subject);
//             if (!subject.schoolclass || !subject.subject || !subject.subjectcode) {
//                 console.warn("Invalid subject data:", subject);
//             }
//             const schoolclass = subject.schoolclass || 'N/A';
//             const subjectName = subject.subject || 'N/A';
//             const subjectCode = subject.subjectcode || 'N/A';
//             const term = subject.term || 'N/A';
//             const session = subject.session || '';
//             const scoreSheetUrl = `/subjectscoresheet/${subject.schoolclassid || ''}/${subject.subjectclassid || ''}/${subject.userid || ''}/${subject.termid || ''}/${subject.session_id || ''}`;
//             const mockSheetUrl = `/subjectscoresheet-mock/${subject.schoolclassid || ''}/${subject.subjectclassid || ''}/${subject.userid || ''}/${subject.termid || ''}/${subject.session_id || ''}`;
//             return `
//                 <tr>
//                     <td class="id" data-id="${subject.id || ''}">
//                         <div class="form-check">
//                             <input class="form-check-input" type="checkbox" name="chk_child" data-subjectclassid="${subject.subjectclassid || ''}" data-termid="${subject.termid || ''}" data-sessionid="${subject.session_id || ''}" data-staffid="${subject.userid || ''}">
//                             <label class="form-check-label"></label>
//                         </div>
//                     </td>
//                     <td class="sn">${sn++}</td>
//                     <td class="schoolclass" data-schoolclass="${schoolclass}">${schoolclass}</td>
//                     <td class="subject" data-subject="${subjectName}">${subjectName}</td>
//                     <td class="subjectcode" data-subjectcode="${subjectCode}">${subjectCode}</td>
//                     <td class="term" data-term="${term}">${term}</td>
//                     <td class="session" data-session="${session}">${session}</td>
//                     <td>
//                         <ul class="d-flex gap-2 list-unstyled mb-0">
//                             ${subject.broadsheet_exists ?
//                                 `<li><a href="${scoreSheetUrl}" class="btn btn-success btn-sm" title="View Terminal Record"><i class="ph-file-list me-1"></i>View Terminal</a></li>` :
//                                 `<li><span class="badge bg-warning" title="No Terminal Record Available">N/A</span></li>`}
//                             ${subject.broadsheet_mock_exists ?
//                                 `<li><a href="${mockSheetUrl}" class="btn btn-warning btn-sm" title="View Mock Record"><i class="ph-clipboard me-1"></i>Mock</a></li>` :
//                                 `<li><span class="badge bg-warning" title="No Mock Record Available">N/A</span></li>`}
//                         </ul>
//                     </td>
//                 </tr>
//             `;
//         }).join('');
//         if (noDataAlert) noDataAlert.style.display = 'none';
//     } else {
//         tableHtml = '<tr class="noresult"><td colspan="8" class="text-center text-muted">No results found</td></tr>';
//         if (noDataAlert) noDataAlert.style.display = 'block';
//     }

//     tableBody.innerHTML = tableHtml;
//     if (subjectCountElement) {
//         subjectCountElement.textContent = mysubjects.length;
//     } else {
//         console.warn("subjectcount element not found");
//     }

//     // Reinitialize List.js after DOM update
//     setTimeout(() => {
//         const initSuccess = initializeList();
//         if (!initSuccess) {
//             console.log("List.js failed, ensuring custom search is active");
//             const searchInput = document.getElementById('searchInput');
//             if (searchInput && searchInput.value.trim()) {
//                 customSearch(searchInput.value.trim());
//             }
//         }
//     }, 100);
// }

// // Update subject teachers
// function updateSubjectTeachers(subjectTeachers) {
//     const container = document.querySelector('#subjectTeachersContainer');
//     const countElement = document.getElementById('subjectTeacherCount');
//     const subjectTeachersCard = document.getElementById('subjectTeachersCard');

//     if (!container) {
//         console.warn("SubjectTeachersContainer not found. Checking subjectTeachers data:", subjectTeachers);
//         if (subjectTeachersCard) {
//             subjectTeachersCard.style.display = subjectTeachers.length > 0 ? 'block' : 'none';
//         }
//         return;
//     }

//     let html = '';
//     if (subjectTeachers.length > 0) {
//         const termGroups = {};
//         subjectTeachers.forEach(teacher => {
//             if (!termGroups[teacher.termid]) termGroups[teacher.termid] = { term: teacher.term || 'Unknown Term', teachers: [] };
//             termGroups[teacher.termid].teachers.push(teacher);
//         });

//         html = Object.keys(termGroups).map(termId => {
//             const group = termGroups[termId];
//             return `
//                 <h6 class="mt-3">${group.term}</h6>
//                 <div class="row">
//                     ${group.teachers.map(teacher => `
//                         <div class="col-md-6 col-lg-4">
//                             <div class="form-check mb-2">
//                                 <input class="form-check-input subject-checkbox" type="checkbox" id="subject-${teacher.subjectclassid}"
//                                        data-subjectclassid="${teacher.subjectclassid}" data-staffid="${teacher.userid}"
//                                        data-termid="${teacher.termid}" checked>
//                                 <label class="form-check-label" for="subject-${teacher.subjectclassid}">
//                                     <strong>${teacher.subjectname}</strong><br>
//                                     <small class="text-muted">${teacher.schoolclass} - ${teacher.staffname}</small>
//                                 </label>
//                             </div>
//                         </div>
//                     `).join('')}
//                 </div>
//             `;
//         }).join('');
//         if (subjectTeachersCard) subjectTeachersCard.style.display = 'block';
//     } else {
//         html = '<p class="text-center text-muted">No subject teachers found.</p>';
//         if (subjectTeachersCard) subjectTeachersCard.style.display = 'none';
//     }

//     container.innerHTML = html;
//     if (countElement) countElement.textContent = subjectTeachers.length;
// }

// // Filter data
// function filterData() {
//     const sessionId = document.getElementById('idsession')?.value || 'ALL';
//     const termId = document.getElementById('idterm')?.value || 'ALL';

//     if (sessionId === 'ALL' || termId === 'ALL') {
//         Swal.fire({
//             icon: 'error',
//             title: 'Error',
//             text: 'Please select both session and term.',
//             showConfirmButton: true
//         });
//         return;
//     }

//     const filterButton = document.getElementById('filterButton');
//     if (filterButton) {
//         filterButton.disabled = true;
//         filterButton.innerHTML = '<i class="bi bi-hourglass-split align-baseline me-1"></i> Loading...';
//     }

//     axios.post('/myresultroom', { termid: termId, sessionid: sessionId }, {
//         headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
//         timeout: 15000
//     }).then(response => {
//         console.log('Full response:', response);
//         console.log('mysubjects:', response.data.data.mysubjects || []);
//         console.log('subjectTeachers:', response.data.data.subjectTeachers || []);
//         if (response.data.success) {
//             updateSubjectsTable(response.data.data.mysubjects || []);
//             updateSubjectTeachers(response.data.data.subjectTeachers || []);
//             initializeCheckboxes();
//             updateSubjectCount();
//             initializeSearch();

//             Swal.fire({
//                 icon: 'success',
//                 title: 'Success',
//                 text: 'Subjects loaded successfully!',
//                 timer: 2000,
//                 showConfirmButton: false
//             });
//         } else {
//             console.error("Server response unsuccessful:", response.data.message);
//             Swal.fire({
//                 icon: 'error',
//                 title: 'Error',
//                 text: response.data.message || 'Failed to load subjects.',
//                 showConfirmButton: true
//             });
//         }
//     }).catch(error => {
//         console.error("Filter error:", error, error.stack);
//         console.log("Error response:", error.response);
//         let message = 'Failed to load subjects';
//         if (error.response) {
//             if (error.response.status === 422) message = Object.values(error.response.data.errors || {}).flat().join(', ');
//             else if (error.response.status === 403) message = 'Authentication error.';
//             else if (error.response.status === 404) message = 'Endpoint not found.';
//             else message = error.response.data.message || message;
//         } else if (error.code === 'ECONNABORTED') {
//             message = 'Request timed out.';
//         } else {
//             message = error.message || message;
//         }
//         Swal.fire({
//             icon: 'error',
//             title: 'Error',
//             text: message,
//             showConfirmButton: true
//         });
//     }).finally(() => {
//         if (filterButton) {
//             filterButton.disabled = false;
//             filterButton.innerHTML = '<i class="bi bi-funnel align-baseline me-1"></i> Search';
//         }
//     });
// }

// // Checkbox handling
// function initializeCheckAll() {
//     const checkAll = document.getElementById('checkAll');
//     if (checkAll) {
//         checkAll.addEventListener('click', function() {
//             console.log("CheckAll clicked, checked:", this.checked);
//             document.querySelectorAll('tbody input[name="chk_child"]').forEach(checkbox => {
//                 checkbox.checked = this.checked;
//                 const row = checkbox.closest('tr');
//                 row.classList.toggle('table-active', this.checked);
//             });
//         });
//         console.log("CheckAll listener attached");
//     } else {
//         console.warn("checkAll not found");
//     }
// }

// function initializeCheckboxes() {
//     const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');
//     console.log("Initializing checkboxes:", checkboxes.length);
//     checkboxes.forEach(checkbox => {
//         checkbox.removeEventListener('change', handleCheckboxChange);
//         checkbox.addEventListener('change', handleCheckboxChange);
//     });

//     function handleCheckboxChange(e) {
//         console.log("Checkbox changed:", e.target.checked);
//         const row = e.target.closest('tr');
//         row.classList.toggle('table-active', e.target.checked);
//         const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
//         const checkAll = document.getElementById('checkAll');
//         if (checkAll) {
//             checkAll.checked = checkedCount > 0 && checkedCount === checkboxes.length;
//         }
//     }
// }

// // Subject selection
// function selectAllSubjects() {
//     console.log("Select all subjects");
//     document.querySelectorAll('#subjectTeachersContainer .subject-checkbox').forEach(checkbox => {
//         checkbox.checked = true;
//     });
//     updateSubjectCount();
// }

// function deselectAllSubjects() {
//     console.log("Deselect all subjects");
//     document.querySelectorAll('#subjectTeachersContainer .subject-checkbox').forEach(checkbox => {
//         checkbox.checked = false;
//     });
//     updateSubjectCount();
// }

// function updateSubjectCount() {
//     const checkedCount = document.querySelectorAll('#subjectTeachersContainer .subject-checkbox:checked').length;
//     const countElement = document.getElementById('subjectTeacherCount');
//     if (countElement) {
//         countElement.textContent = checkedCount;
//     } else {
//         console.warn("subjectTeacherCount not found");
//     }
// }

// // Delete subjects
// function deleteSelectedSubjects() {
//     console.log("Delete selected subjects triggered");
//     const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]:checked');
//     if (!checkboxes.length) {
//         Swal.fire({
//             icon: 'info',
//             title: 'No Selection',
//             text: 'Please select at least one subject.',
//             showConfirmButton: true
//         });
//         return;
//     }

//     const subjects = Array.from(checkboxes).map(checkbox => {
//         const tr = checkbox.closest('tr');
//         const id = tr.querySelector('.id')?.getAttribute('data-id');
//         return {
//             id,
//             subjectclassid: checkbox.dataset.subjectclassid,
//             termid: checkbox.dataset.termid,
//             sessionid: checkbox.dataset.sessionid,
//             staffid: checkbox.dataset.staffid
//         };
//     }).filter(subject => subject.id && subject.subjectclassid && subject.termid && subject.sessionid && subject.staffid);

//     if (!subjects.length) {
//         Swal.fire({
//             icon: 'error',
//             title: 'Invalid Data',
//             text: 'Selected rows have invalid data.',
//             showConfirmButton: true
//         });
//         return;
//     }

//     Swal.fire({
//         title: 'Are you sure?',
//         text: "You won't be able to revert this!",
//         icon: 'warning',
//         showCancelButton: true,
//         confirmButtonText: 'Yes, delete it!',
//         showCloseButton: true
//     }).then(result => {
//         if (result.isConfirmed) {
//             Promise.all(subjects.map(subject => axios.delete('/subjects/registered-classes', {
//                 data: {
//                     subjectclassid: subject.subjectclassid,
//                     termid: subject.termid,
//                     sessionid: subject.sessionid,
//                     staffid: subject.staffid
//                 }
//             }))).then(() => {
//                 Swal.fire({
//                     icon: 'success',
//                     title: 'Deleted',
//                     text: 'Subjects deleted successfully.',
//                     timer: 2000
//                 });
//                 filterData();
//             }).catch(error => {
//                 console.error("Delete error:", error);
//                 Swal.fire({
//                     icon: 'error',
//                     title: 'Error',
//                     text: error.response?.data?.message || 'Failed to delete subjects.',
//                     showConfirmButton: true
//                 });
//             });
//         }
//     });
// }

// // Initialize
// document.addEventListener('DOMContentLoaded', () => {
//     console.log("DOMContentLoaded fired");

//     const filterButton = document.getElementById('filterButton');
//     if (filterButton) {
//         filterButton.addEventListener('click', () => {
//             console.log("Filter button clicked");
//             filterData();
//         });
//     } else {
//         console.error("Filter button not found");
//         Swal.fire({
//             icon: 'error',
//             title: 'Error',
//             text: 'Search button not found.',
//             showConfirmButton: true
//         });
//     }

//     initializeCheckAll();
//     initializeCheckboxes();
//     updateSubjectCount();
//     // Delay List.js initialization to check for initial data
//     setTimeout(() => {
//         initializeList();
//         initializeSearch();
//     }, 100);

//     console.log("Initialization complete");
// });

// // Expose global functions
// window.filterData = filterData;
// window.selectAllSubjects = selectAllSubjects;
// window.deselectAllSubjects = deselectAllSubjects;
// window.deleteSelectedSubjects = deleteSelectedSubjects;
// window.triggerSearch = triggerSearch;
