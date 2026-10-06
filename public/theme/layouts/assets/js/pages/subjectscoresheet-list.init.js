// console.log("subjectscoresheet.init.js loaded at", new Date().toISOString());

// // Dependency checks
// try {
//     if (typeof axios === 'undefined') throw new Error("Axios is not loaded");
//     if (typeof Swal === 'undefined') throw new Error("SweetAlert2 is not loaded");
//     if (typeof bootstrap === 'undefined') throw new Error("Bootstrap is not loaded");
//     console.log("All dependencies loaded successfully");
// } catch (error) {
//     console.error("Dependency check failed:", error.message);
//     Swal.fire({
//         icon: "error",
//         title: "Dependency Error",
//         text: "Required libraries are missing. Check console for details.",
//         showConfirmButton: true
//     });
// }

// // Set CSRF token for Axios
// const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
// axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
// if (!csrfToken) {
//     console.warn("CSRF token not found. AJAX requests may fail.");
// }

// // Default image constant
// const DEFAULT_IMAGE = '/storage/student_avatars/unnamed.jpg';

// // Utility: Normalize picture path
// function normalizePicturePath(picture) {
//     if (!picture || picture === 'none' || picture === '') {
//         console.log("normalizePicturePath: Empty or 'none' picture, returning 'unnamed.jpg'");
//         return 'unnamed.jpg';
//     }
//     const normalized = picture.replace(/^studentavatar\/|^\//, '');
//     console.log(`normalizePicturePath: Original: ${picture}, Normalized: ${normalized}`);
//     return normalized;
// }

// // Utility: Ensure broadsheets is a flat array
// function ensureBroadsheetsArray() {
//     console.log("Raw broadsheets before processing:", JSON.stringify(window.broadsheets, null, 2));
//     if (typeof window.broadsheets === 'undefined' || window.broadsheets === null) {
//         window.broadsheets = [];
//     } else if (!Array.isArray(window.broadsheets)) {
//         console.warn("Broadsheets is not an array, attempting to convert:", window.broadsheets);
//         window.broadsheets = Array.isArray(window.broadsheets.data) ? window.broadsheets.data : [window.broadsheets];
//     }
//     // Ensure all items are valid objects with required fields
//     window.broadsheets = window.broadsheets.filter(
//         item => item && typeof item === 'object' && item.id && item.admissionno
//     );
//     console.log('Processed broadsheets:', JSON.stringify(window.broadsheets.map(b => ({
//         id: b.id,
//         admissionno: b.admissionno,
//         picture: b.picture || 'none',
//         grade: b.grade,
//         position: b.position
//     })), null, 2));
// }

// // Utility: Debounce function
// function debounce(func, wait) {
//     let timeout;
//     return function executedFunction(...args) {
//         const later = () => {
//             clearTimeout(timeout);
//             func(...args);
//         };
//         clearTimeout(timeout);
//         timeout = setTimeout(later, wait);
//     };
// }

// // Grade calculation (client-side fallback)
// function calculateGrade(score) {
//     if (isNaN(score) || score < 0 || score > 100) {
//         console.warn(`Invalid score for grading: ${score}`);
//         return '-';
//     }

//     const isSenior = window.is_senior === true;
//     console.log(`Calculating grade for score=${score}, is_senior=${isSenior}`);

//     if (isSenior) {
//         if (score >= 75) return 'A1';
//         if (score >= 70) return 'B2';
//         if (score >= 65) return 'B3';
//         if (score >= 60) return 'C4';
//         if (score >= 55) return 'C5';
//         if (score >= 50) return 'C6';
//         if (score >= 45) return 'D7';
//         if (score >= 40) return 'E8';
//         return 'F9';
//     } else {
//         if (score >= 70) return 'A';
//         if (score >= 60) return 'B';
//         if (score >= 50) return 'C';
//         if (score >= 40) return 'D';
//         return 'F';
//     }
// }

// // Fetch grade from backend as fallback
// async function fetchGradeFromBackend(schoolclass_id, cum) {
//     try {
//         const response = await axios.post(window.routes.gradePreview, {
//             schoolclass_id,
//             cum
//         }, {
//             headers: {
//                 'X-Requested-With': 'XMLHttpRequest',
//                 'Content-Type': 'application/json'
//             },
//             timeout: 5000
//         });
//         console.log(`Backend grade for cum=${cum}: ${response.data.grade}`);
//         return response.data.grade || '-';
//     } catch (error) {
//         console.error('Failed to fetch grade from backend:', error);
//         return null;
//     }
// }

// // Ordinal for position
// function getOrdinalSuffix(position) {
//     if (!position || isNaN(position)) return '-';
//     position = parseInt(position);
//     if (position % 100 >= 11 && position % 100 <= 13) return position + 'th';
//     switch (position % 10) {
//         case 1: return position + 'st';
//         case 2: return position + 'nd';
//         case 3: return position + 'rd';
//         default: return position + 'th';
//     }
// }

// // Update a table row and trigger position update if necessary
// async function updateRowTotal(row) {
//     const scoreInputs = row.querySelectorAll('.score-input');
//     const id = row.querySelector('.score-input')?.dataset.id;
//     if (!id) {
//         console.error("No dataset.id found for row");
//         return;
//     }

//     let ca1 = 0, ca2 = 0, ca3 = 0, exam = 0;
//     scoreInputs.forEach(input => {
//         const value = parseFloat(input.value) || 0;
//         switch (input.dataset.field) {
//             case 'ca1': ca1 = value; break;
//             case 'ca2': ca2 = value; break;
//             case 'ca3': ca3 = value; break;
//             case 'exam': exam = value; break;
//         }
//     });

//     const caAverage = (ca1 + ca2 + ca3) / 3;
//     const total = (caAverage + exam) / 2;
//     let broadsheet = window.broadsheets.find(b => String(b.id) === String(id));
//     if (!broadsheet) {
//         console.warn(`No broadsheet found for id=${id}, using bf=0`);
//         broadsheet = { id, bf: 0, total: 0, cum: 0, grade: '-', position: null };
//     }
//     const bf = parseFloat(broadsheet.bf) || 0;
//     const cum = window.term_id === 1 ? total : (bf + total) / 2;

//     const hasChanges = ca1 !== (parseFloat(broadsheet.ca1) || 0) ||
//                       ca2 !== (parseFloat(broadsheet.ca2) || 0) ||
//                       ca3 !== (parseFloat(broadsheet.ca3) || 0) ||
//                       exam !== (parseFloat(broadsheet.exam) || 0);

//     let totalDisplayValue = hasChanges ? total.toFixed(1) : (parseFloat(broadsheet.total) || 0).toFixed(1);
//     let cumDisplayValue = hasChanges ? cum.toFixed(2) : (parseFloat(broadsheet.cum) || 0).toFixed(2);
//     let grade = hasChanges ? calculateGrade(cum) : (broadsheet.grade || '-');
//     let position = broadsheet.position;

//     if (hasChanges && window.is_senior === true && !['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9'].includes(grade)) {
//         console.warn(`Client-side grade (${grade}) for senior class seems incorrect, fetching from backend`);
//         grade = await fetchGradeFromBackend(window.schoolclass_id, cum) || grade;
//     }

//     console.log(`updateRowTotal: id=${id}, ca1=${ca1}, ca2=${ca2}, ca3=${ca3}, exam=${exam}, total=${total.toFixed(1)}, bf=${bf.toFixed(2)}, cum=${cum.toFixed(2)}, grade=${grade}, is_senior=${window.is_senior}, hasChanges=${hasChanges}, position=${position}`);

//     const totalDisplay = row.querySelector('.total-display span');
//     if (totalDisplay) {
//         totalDisplay.textContent = totalDisplayValue;
//         totalDisplay.classList.add('bg-warning');
//         setTimeout(() => totalDisplay.classList.remove('bg-warning'), 500);
//     } else {
//         console.warn("total-display span not found in row");
//     }
//     const bfDisplay = row.querySelector('.bf-display span');
//     if (bfDisplay) bfDisplay.textContent = bf.toFixed(2);
//     const cumDisplay = row.querySelector('.cum-display span');
//     if (cumDisplay) {
//         cumDisplay.textContent = cumDisplayValue;
//         cumDisplay.classList.add('bg-warning');
//         setTimeout(() => cumDisplay.classList.remove('bg-warning'), 500);
//     } else {
//         console.warn("cum-display span not found in row");
//     }
//     const gradeDisplay = row.querySelector('.grade-display span');
//     if (gradeDisplay) {
//         gradeDisplay.textContent = grade;
//         gradeDisplay.classList.add('bg-warning');
//         setTimeout(() => gradeDisplay.classList.remove('bg-warning'), 500);
//     } else {
//         console.warn("grade-display span not found in row");
//     }
//     const positionDisplay = row.querySelector('.position-display span');
//     if (positionDisplay && !hasChanges) {
//         positionDisplay.textContent = position ? getOrdinalSuffix(position) : '-';
//         positionDisplay.classList.add('bg-info');
//     }

//     const broadsheetIndex = window.broadsheets.findIndex(b => String(b.id) === String(id));
//     if (broadsheetIndex !== -1) {
//         window.broadsheets[broadsheetIndex] = {
//             ...window.broadsheets[broadsheetIndex],
//             ca1, ca2, ca3, exam, total, cum, grade, position
//         };
//     } else {
//         window.broadsheets.push({ id, ca1, ca2, ca3, exam, total, bf, cum, grade, position });
//     }

//     if (hasChanges) {
//         forceUpdatePositions();
//     }
// }

// // Standard competition ranking (tied ranks)
// function forceUpdatePositions() {
//     ensureBroadsheetsArray();
//     if (!window.broadsheets || window.broadsheets.length === 0) {
//         console.warn("No broadsheets available for position calculation");
//         document.querySelectorAll('#scoresheetTableBody tr:not(#noDataRow)').forEach(row => {
//             const positionDisplay = row.querySelector('.position-display span');
//             if (positionDisplay) {
//                 positionDisplay.textContent = "-";
//                 positionDisplay.classList.remove('bg-warning');
//                 positionDisplay.classList.add('bg-info');
//             }
//         });
//         return;
//     }

//     const hasValidServerPositions = window.broadsheets.some(b =>
//         b.position !== null && b.position !== undefined && !isNaN(b.position) && b.position > 0
//     );

//     console.log(`forceUpdatePositions: hasValidServerPositions=${hasValidServerPositions}`);
//     console.log('Broadsheets:', JSON.stringify(window.broadsheets.map(b => ({
//         id: b.id,
//         admissionno: b.admissionno,
//         cum: b.cum,
//         grade: b.grade,
//         position: b.position
//     })), null, 2));

//     if (hasValidServerPositions) {
//         console.log("Using server-side positions for broadsheets with valid positions");
//         window.broadsheets.forEach(broadsheet => {
//             const row = document.querySelector(`tr:has(input[data-id="${broadsheet.id}"])`);
//             if (row) {
//                 const positionDisplay = row.querySelector('.position-display span');
//                 if (positionDisplay) {
//                     const positionText = (broadsheet.position && !isNaN(broadsheet.position) && broadsheet.position > 0)
//                         ? getOrdinalSuffix(broadsheet.position)
//                         : '-';
//                     positionDisplay.textContent = positionText;
//                     positionDisplay.classList.remove('bg-warning');
//                     positionDisplay.classList.add('bg-info');
//                     console.log(`Set server position for ID ${broadsheet.id}: ${positionText}`);
//                 } else {
//                     console.warn(`position-display span not found for ID ${broadsheet.id}`);
//                 }
//             } else {
//                 console.warn(`Row not found for broadsheet ID ${broadsheet.id}`);
//             }
//         });
//     } else {
//         console.log("No valid server positions found, calculating client-side positions");
//         const validScores = window.broadsheets.filter(b => {
//             const cum = parseFloat(b.cum) || 0;
//             return cum > 0;
//         });

//         if (validScores.length === 0) {
//             console.log("No valid scores for ranking");
//             window.broadsheets.forEach(broadsheet => {
//                 const row = document.querySelector(`tr:has(input[data-id="${broadsheet.id}"])`);
//                 if (row) {
//                     const positionDisplay = row.querySelector('.position-display span');
//                     if (positionDisplay) {
//                         positionDisplay.textContent = "-";
//                         positionDisplay.classList.remove('bg-warning');
//                         positionDisplay.classList.add('bg-info');
//                     }
//                 }
//             });
//             return;
//         }

//         validScores.sort((a, b) => {
//             const cumA = parseFloat(a.cum) || 0;
//             const cumB = parseFloat(b.cum) || 0;
//             if (cumB !== cumA) return cumB - cumA;
//             return parseInt(a.id) - parseInt(b.id);
//         });

//         let currentPosition = 1;
//         let lastCum = null;
//         let positionMap = {};

//         validScores.forEach((broadsheet, index) => {
//             const cum = parseFloat(broadsheet.cum) || 0;
//             if (lastCum !== null && cum !== lastCum) {
//                 currentPosition = index + 1;
//             }
//             positionMap[broadsheet.id] = currentPosition;
//             lastCum = cum;
//         });

//         window.broadsheets.forEach(broadsheet => {
//             const row = document.querySelector(`tr:has(input[data-id="${broadsheet.id}"])`);
//             if (row) {
//                 const positionDisplay = row.querySelector('.position-display span');
//                 if (positionDisplay) {
//                     const position = positionMap[broadsheet.id];
//                     positionDisplay.textContent = position ? getOrdinalSuffix(position) : '-';
//                     positionDisplay.classList.remove('bg-warning');
//                     positionDisplay.classList.add('bg-info');
//                     console.log(`Calculated position for ID ${broadsheet.id}: ${position ? getOrdinalSuffix(position) : '-'}`);
//                 }
//             }
//         });
//     }
// }

// // Bulk save all scores
// function bulkSaveAllScores() {
//     ensureBroadsheetsArray();
//     const scoreInputs = document.querySelectorAll('.score-input');
//     const progressContainer = document.getElementById('progressContainer');
//     const progressBar = progressContainer?.querySelector('.progress-bar');
//     const bulkUpdateScores = document.getElementById('bulkUpdateScores');
//     const originalBtnContent = bulkUpdateScores?.innerHTML;

//     if (!scoreInputs.length) {
//         Swal.fire({
//             icon: 'info',
//             title: 'No Scores',
//             text: 'No scores to save.',
//             timer: 2000
//         });
//         return;
//     }

//     const sessionVars = {
//         term_id: window.term_id,
//         session_id: window.session_id,
//         subjectclass_id: window.subjectclass_id,
//         schoolclass_id: window.schoolclass_id,
//         staff_id: window.staff_id
//     };
//     for (const [key, value] of Object.entries(sessionVars)) {
//         if (!value) {
//             Swal.fire({
//                 icon: 'error',
//                 title: 'Error',
//                 text: `Please select a ${key.replace('_id', '')} before saving.`,
//                 showConfirmButton: true
//             });
//             return;
//         }
//     }

//     const scores = [];
//     const scoreData = {};
//     const invalidInputs = [];

//     scoreInputs.forEach(input => {
//         const id = input.dataset.id;
//         const field = input.dataset.field;
//         const value = input.value.trim();
//         if (input.disabled) return;
//         input.classList.remove('is-invalid', 'is-valid');
//         if (!id || !field) {
//             input.classList.add('is-invalid');
//             invalidInputs.push({ input, error: `Missing required attributes for ${field} (ID: ${id})` });
//             return;
//         }
//         const numValue = value === '' ? 0 : parseFloat(value);
//         if (isNaN(numValue) || numValue < 0 || numValue > 100) {
//             input.classList.add('is-invalid');
//             const broadsheet = window.broadsheets.find(b => String(b.id) === String(id));
//             const studentName = broadsheet ? `${broadsheet.lname || ''} ${broadsheet.fname || ''}`.trim() || 'Unknown' : 'Unknown';
//             invalidInputs.push({
//                 input,
//                 error: `Invalid score for ${studentName} (${broadsheet?.admissionno || 'Unknown'}): ${field.toUpperCase()} must be between 0 and 100`
//             });
//             return;
//         }
//         input.classList.add('is-valid');
//         if (!scoreData[id]) scoreData[id] = { id: parseInt(id) };
//         scoreData[id][field] = numValue;
//     });

//     if (invalidInputs.length > 0) {
//         Swal.fire({
//             icon: 'error',
//             title: 'Validation Failed',
//             html: `Some scores are invalid:<ul>${invalidInputs.map(e => `<li>${e.error}</li>`).join('')}</ul>`,
//             showConfirmButton: true
//         });
//         invalidInputs.forEach(({ input }) => input.focus());
//         return;
//     }

//     Object.values(scoreData).forEach(scoreEntry => {
//         const ca1 = parseFloat(scoreEntry.ca1) || 0;
//         const ca2 = parseFloat(scoreEntry.ca2) || 0;
//         const ca3 = parseFloat(scoreEntry.ca3) || 0;
//         const exam = parseFloat(scoreEntry.exam) || 0;
//         const caAverage = (ca1 + ca2 + ca3) / 3;
//         const total = (caAverage + exam) / 2;
//         const broadsheet = window.broadsheets.find(b => String(b.id) === String(scoreEntry.id)) || { bf: 0 };
//         const bf = parseFloat(broadsheet.bf) || 0;
//         const cum = window.term_id === 1 ? total : (bf + total) / 2;

//         scoreEntry.ca1 = ca1;
//         scoreEntry.ca2 = ca2;
//         scoreEntry.ca3 = ca3;
//         scoreEntry.exam = exam;
//         scoreEntry.total = total;
//         scoreEntry.bf = bf;
//         scoreEntry.cum = cum;
//         scoreEntry.grade = calculateGrade(cum);
//         scoreEntry.position = null;
//         scores.push(scoreEntry);
//     });

//     if (!scores.length) {
//         Swal.fire({
//             icon: 'info',
//             title: 'No Scores',
//             text: 'No valid scores to save.',
//             timer: 2000
//         });
//         return;
//     }

//     if (progressContainer) progressContainer.style.display = 'block';
//     if (progressBar) progressBar.style.width = '20%';
//     if (bulkUpdateScores) {
//         bulkUpdateScores.disabled = true;
//         bulkUpdateScores.innerHTML = '<i class="ri-loader-4-line sync-icon"></i> Saving...';
//     }

//     axios.post(window.routes?.bulkUpdate || '/subjectscoresheet/bulk-update', {
//         scores,
//         ...sessionVars
//     }, {
//         headers: {
//             'X-Requested-With': 'XMLHttpRequest',
//             'Content-Type': 'application/json'
//         },
//         timeout: 30000,
//         onUploadProgress: progressEvent => {
//             if (progressEvent.lengthComputable) {
//                 const percentComplete = (progressEvent.loaded / progressEvent.total) * 100;
//                 if (progressBar) progressBar.style.width = `${percentComplete}%`;
//                 console.log(`Upload progress: ${percentComplete.toFixed(2)}%`);
//             }
//         }
//     })
//     .then(response => {
//         if (progressBar) progressBar.style.width = '100%';
//         if (response.data.success && response.data.data?.broadsheets) {
//             console.log('bulkSaveAllScores: Server response', response.data.data.broadsheets.map(b => ({
//                 id: b.id,
//                 admissionno: b.admissionno,
//                 ca1: b.ca1,
//                 ca2: b.ca2,
//                 ca3: b.ca3,
//                 exam: b.exam,
//                 total: b.total,
//                 bf: b.bf,
//                 cum: b.cum,
//                 grade: b.grade,
//                 position: b.position
//             })));

//             window.broadsheets = response.data.data.broadsheets;
//             ensureBroadsheetsArray();

//             window.broadsheets.forEach(broadsheet => {
//                 const row = document.querySelector(`tr:has(input[data-id="${broadsheet.id}"])`);
//                 if (row) {
//                     ['ca1', 'ca2', 'ca3', 'exam'].forEach(field => {
//                         const input = row.querySelector(`input[data-field="${field}"]`);
//                         if (input) {
//                             input.value = broadsheet[field] !== null && broadsheet[field] !== undefined ? broadsheet[field] : '';
//                             input.classList.add('is-valid');
//                         }
//                     });
//                     const totalDisplay = row.querySelector('.total-display span');
//                     if (totalDisplay) {
//                         totalDisplay.textContent = parseFloat(broadsheet.total || 0).toFixed(1);
//                         totalDisplay.classList.add('bg-warning');
//                         setTimeout(() => totalDisplay.classList.remove('bg-warning'), 500);
//                     }
//                     const bfDisplay = row.querySelector('.bf-display span');
//                     if (bfDisplay) {
//                         bfDisplay.textContent = parseFloat(broadsheet.bf || 0).toFixed(2);
//                     }
//                     const cumDisplay = row.querySelector('.cum-display span');
//                     if (cumDisplay) {
//                         cumDisplay.textContent = parseFloat(broadsheet.cum || 0).toFixed(2);
//                         cumDisplay.classList.add('bg-warning');
//                         setTimeout(() => cumDisplay.classList.remove('bg-warning'), 500);
//                     }
//                     const gradeDisplay = row.querySelector('.grade-display span');
//                     if (gradeDisplay) {
//                         gradeDisplay.textContent = broadsheet.grade || '-';
//                         gradeDisplay.classList.add('bg-warning');
//                         setTimeout(() => gradeDisplay.classList.remove('bg-warning'), 500);
//                         console.log(`Updated grade for ID ${broadsheet.id}: ${broadsheet.grade}, is_senior=${window.is_senior}`);
//                     }
//                     const positionDisplay = row.querySelector('.position-display span');
//                     if (positionDisplay) {
//                         positionDisplay.textContent = broadsheet.position ? getOrdinalSuffix(broadsheet.position) : '-';
//                         positionDisplay.classList.add('bg-info');
//                         console.log(`Updated position for ID ${broadsheet.id}: ${broadsheet.position}`);
//                     }
//                     const image = row.querySelector('.student-image');
//                     if (image && broadsheet.picture) {
//                         const existingPicture = image.dataset.picture || 'none';
//                         const newPicture = broadsheet.picture || existingPicture;
//                         const picture = normalizePicturePath(newPicture);
//                         const imageUrl = `/storage/student_avatars/${picture}`;
//                         image.src = imageUrl;
//                         image.dataset.image = imageUrl;
//                         image.dataset.picture = newPicture;
//                         image.onerror = () => {
//                             image.src = DEFAULT_IMAGE;
//                             image.dataset.image = DEFAULT_IMAGE;
//                             image.dataset.picture = 'none';
//                             console.log(`Image failed to load for admissionno: ${broadsheet.admissionno || 'unknown'}, picture: ${newPicture}, attempted URL: ${imageUrl}`);
//                         };
//                     }
//                 }
//             });

//             forceUpdatePositions();

//             Swal.fire({
//                 icon: 'success',
//                 title: 'Saved!',
//                 text: `Successfully updated ${scores.length} score${scores.length !== 1 ? 's' : ''} with grades and positions.`,
//                 timer: 2000,
//                 showConfirmButton: false
//             });
//         } else {
//             console.error('bulkSaveAllScores: Invalid server response', response.data);
//             Swal.fire({
//                 icon: 'error',
//                 title: 'Save Failed',
//                 text: response.data.message || 'Server did not return updated scores.',
//                 showConfirmButton: true
//             });
//         }
//     })
//     .catch(error => {
//         let errorMessage = 'Failed to save scores. Check console for details.';
//         if (error.response) errorMessage = error.response.data.message || errorMessage;
//         console.error('Bulk save error:', error);
//         Swal.fire({
//             icon: 'error',
//             title: 'Save Failed',
//             text: errorMessage,
//             showConfirmButton: true
//         });
//     })
//     .finally(() => {
//         if (progressContainer) {
//             setTimeout(() => {
//                 progressContainer.style.display = 'none';
//                 if (progressBar) progressBar.style.width = '0%';
//             }, 1000);
//         }
//         if (bulkUpdateScores) {
//             bulkUpdateScores.disabled = false;
//             bulkUpdateScores.innerHTML = originalBtnContent || '<i class="ri-save-line me-1"></i> Save All Scores';
//         }
//     });
// }

// // Delete selected scores
// function deleteSelectedScores() {
//     const selectedCheckboxes = document.querySelectorAll('.score-checkbox:checked');
//     if (!selectedCheckboxes.length) {
//         Swal.fire({
//             icon: 'info',
//             title: 'No Selection',
//             text: 'Please select at least one score to delete.',
//             timer: 2000
//         });
//         return;
//     }
//     Swal.fire({
//         title: 'Delete Selected Scores?',
//         text: 'This will clear the selected scores. Are you sure?',
//         icon: 'warning',
//         showCancelButton: true,
//         confirmButtonColor: '#d33',
//         cancelButtonColor: '#3085d6',
//         confirmButtonText: 'Yes, Delete!'
//     }).then((result) => {
//         if (result.isConfirmed) {
//             selectedCheckboxes.forEach(checkbox => {
//                 const row = checkbox.closest('tr');
//                 if (row) {
//                     ['ca1', 'ca2', 'ca3', 'exam'].forEach(field => {
//                         const input = row.querySelector(`input[data-field="${field}"]`);
//                         if (input) input.value = '';
//                     });
//                     updateRowTotal(row);
//                 }
//             });
//             Swal.fire({
//                 icon: 'success',
//                 title: 'Deleted!',
//                 text: 'Selected scores have been cleared.',
//                 timer: 2000
//             });
//         }
//     });
// }

// // Populate scores modal
// async function populateScoresModal() {
//     const modalBody = document.querySelector('#scoresModal .modal-body');
//     if (!modalBody) {
//         console.error("Scores modal body not found");
//         return;
//     }
//     ensureBroadsheetsArray();
//     console.log('Broadsheets in populateScoresModal:', JSON.stringify(window.broadsheets, null, 2));
//     if (!window.broadsheets || !Array.isArray(window.broadsheets) || window.broadsheets.length === 0) {
//         console.log("No broadsheets data available for modal");
//         modalBody.innerHTML = `<div class="alert alert-info text-center">
//             <i class="ri-information-line me-2"></i>
//             No scores available to display.
//         </div>`;
//         return;
//     }

//     console.log("Populating scores modal with", window.broadsheets.length, "records");

//     let html = `
//         <div class="table-responsive">
//             <table class="table table-centered align-middle table-nowrap mb-0">
//                 <thead class="table-active">
//                     <tr>
//                         <th>SN</th><th>Admission No</th><th>Name</th>
//                         <th>CA1</th><th>CA2</th><th>CA3</th><th>Exam</th>
//                         <th>Total</th><th>BF</th><th>Cum</th><th>Grade</th><th>Position</th>
//                     </tr>
//                 </thead>
//                 <tbody>`;

//     for (const [idx, broadsheet] of window.broadsheets.entries()) {
//         const ca1 = parseFloat(broadsheet.ca1) || 0;
//         const ca2 = parseFloat(broadsheet.ca2) || 0;
//         const ca3 = parseFloat(broadsheet.ca3) || 0;
//         const exam = parseFloat(broadsheet.exam) || 0;
//         const caAverage = (ca1 + ca2 + ca3) / 3;
//         const total = (caAverage + exam) / 2;
//         const bf = parseFloat(broadsheet.bf) || 0;
//         const cum = window.term_id === 1 ? total : (bf + total) / 2;
//         let grade = broadsheet.grade || calculateGrade(cum);
//         if (!broadsheet.grade && window.is_senior === true && !['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9'].includes(grade)) {
//             console.warn(`Client-side grade (${grade}) for senior class seems incorrect, fetching from backend`);
//             grade = await fetchGradeFromBackend(window.schoolclass_id, cum) || grade;
//         }
//         const name = `${broadsheet.fname || ''} ${broadsheet.lname || ''}`.trim() || 'Unknown';
//         const admissionno = broadsheet.admissionno || '-';
//         const picture = normalizePicturePath(broadsheet.picture);
//         const imageUrl = `/storage/student_avatars/${picture}`;
//         const position = broadsheet.position ? getOrdinalSuffix(broadsheet.position) : '-';

//         html += `<tr>
//             <td>${idx + 1}</td>
//             <td class="admissionno">${admissionno}</td>
//             <td class="name">
//                 <div class="d-flex align-items-center">
//                     <div class="avatar-sm me-2">
//                         <img src="${imageUrl}" alt="${name}" class="rounded-circle w-100 student-image" data-bs-toggle="modal" data-bs-target="#imageViewModal" data-image="${imageUrl}" data-picture="${broadsheet.picture || 'none'}" onerror="this.src='${DEFAULT_IMAGE}'; console.log('Modal image failed to load for admissionno: ${admissionno}, picture: ${broadsheet.picture || 'none'}, attempted URL: ${imageUrl}');">
//                     </div>
//                     <div class="d-flex flex-column">
//                         <span class="fw-bold">${broadsheet.lname || ''}</span> ${broadsheet.fname || ''} ${broadsheet.mname || ''}
//                     </div>
//                 </div>
//             </td>
//             <td>${ca1.toFixed(1)}</td>
//             <td>${ca2.toFixed(1)}</td>
//             <td>${ca3.toFixed(1)}</td>
//             <td>${exam.toFixed(1)}</td>
//             <td>${total.toFixed(1)}</td>
//             <td>${bf.toFixed(2)}</td>
//             <td>${cum.toFixed(2)}</td>
//             <td>${grade}</td>
//             <td>${position}</td>
//         </tr>`;
//     }
//     html += `</tbody></table></div>`;
//     modalBody.innerHTML = html;

//     const images = modalBody.querySelectorAll('.student-image');
//     images.forEach(img => {
//         const src = img.src;
//         img.src = '';
//         img.src = src;
//         console.log(`Forcing image load: ${src}`);
//     });
// }

// // Download Excel with progress
// function downloadExcel() {
//     const downloadExcelButton = document.getElementById('downloadExcel');
//     const downloadProgressContainer = document.getElementById('downloadProgressContainer');
//     const downloadProgressBar = document.getElementById('downloadProgressBar');
//     const originalBtnContent = downloadExcelButton?.innerHTML;

//     if (!downloadExcelButton || !downloadProgressContainer || !downloadProgressBar) {
//         Swal.fire({
//             icon: 'error',
//             title: 'Download Failed',
//             text: 'Required elements are missing.',
//             showConfirmButton: true
//         });
//         return;
//     }

//     downloadExcelButton.disabled = true;
//     downloadExcelButton.innerHTML = '<i class="ri-loader-4-line sync-icon"></i> Generating...';
//     downloadProgressContainer.style.display = 'block';
//     downloadProgressBar.style.width = '10%';

//     axios.get(window.routes?.export || '/subjectscoresheet/export', {
//         responseType: 'blob',
//         headers: {
//             'X-Requested-With': 'XMLHttpRequest',
//             'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
//         },
//         timeout: 30000,
//         onDownloadProgress: progressEvent => {
//             if (progressEvent.lengthComputable) {
//                 const percentComplete = (progressEvent.loaded / progressEvent.total) * 100;
//                 downloadProgressBar.style.width = `${percentComplete}%`;
//             }
//         }
//     })
//     .then(response => {
//         downloadProgressBar.style.width = '100%';

//         let filename = 'scoresheet.xlsx';
//         const contentDisposition = response.headers['content-disposition'];
//         if (contentDisposition) {
//             const match = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
//             if (match && match[1]) {
//                 filename = match[1].replace(/['"]/g, '');
//             }
//         }

//         const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }));
//         const link = document.createElement('a');
//         link.href = url;
//         link.setAttribute('download', filename);
//         document.body.appendChild(link);
//         link.click();
//         document.body.removeChild(link);
//         window.URL.revokeObjectURL(url);

//         Swal.fire({
//             icon: 'success',
//             title: 'Downloaded!',
//             html: 'Excel file downloaded successfully.<br><small>The file is password protected. Contact the administrator for the password.</small>',
//             timer: 3000,
//             showConfirmButton: false
//         });
//     })
//     .catch(error => {
//         let errorMessage = 'Failed to download Excel file.';

//         if (error.response && error.response.data) {
//             const reader = new FileReader();
//             reader.onload = function() {
//                 try {
//                     const errorData = JSON.parse(reader.result);
//                     errorMessage = errorData.message || errorMessage;
//                 } catch(e) {
//                     errorMessage = error.response.statusText || errorMessage;
//                 }
//                 Swal.fire({
//                     icon: 'error',
//                     title: 'Download Failed',
//                     text: errorMessage,
//                     showConfirmButton: true
//                 });
//             };
//             reader.readAsText(error.response.data);
//         } else {
//             errorMessage = error.message || errorMessage;
//             Swal.fire({
//                 icon: 'error',
//                 title: 'Download Failed',
//                 text: errorMessage,
//                 showConfirmButton: true
//             });
//         }
//     })
//     .finally(() => {
//         setTimeout(() => {
//             downloadProgressContainer.style.display = 'none';
//             downloadProgressBar.style.width = '0%';
//             downloadExcelButton.disabled = false;
//             downloadExcelButton.innerHTML = originalBtnContent || '<i class="ri-download-line me-1"></i> Export Excel';
//         }, 1000);
//     });
// }


// // Download Marks Sheet with progress
// function downloadMarksSheet() {
//     const downloadMarksSheetButton = document.getElementById('downloadMarksSheet');
//     const downloadProgressContainer = document.getElementById('downloadProgressContainer');
//     const downloadProgressBar = document.getElementById('downloadProgressBar');
//     const originalBtnContent = downloadMarksSheetButton?.innerHTML;

//     if (!downloadMarksSheetButton || !downloadProgressContainer || !downloadProgressBar) {
//         console.error('Download Marks Sheet elements not found in DOM');
//         Swal.fire({
//             icon: 'error',
//             title: 'Download Failed',
//             text: 'Required elements are missing. Check console for details.',
//             showConfirmButton: true
//         });
//         return;
//     }

//     downloadMarksSheetButton.disabled = true;
//     downloadMarksSheetButton.innerHTML = '<i class="ri-loader-4-line sync-icon"></i> Downloading...';
//     downloadProgressContainer.style.display = 'block';
//     downloadProgressBar.style.width = '10%';

//     axios.get(window.routes?.downloadMarksSheet || '/scoresheet/download-marks-sheet', {
//         responseType: 'blob',
//         headers: {
//             'X-Requested-With': 'XMLHttpRequest'
//         },
//         timeout: 30000,
//         onDownloadProgress: progressEvent => {
//             if (progressEvent.lengthComputable) {
//                 const percentComplete = (progressEvent.loaded / progressEvent.total) * 100;
//                 downloadProgressBar.style.width = `${percentComplete}%`;
//                 console.log(`Marks Sheet download progress: ${percentComplete.toFixed(2)}%`);
//             }
//         }
//     })
//     .then(response => {
//         downloadProgressBar.style.width = '100%';
//         const url = window.URL.createObjectURL(new Blob([response.data]));
//         const link = document.createElement('a');
//         link.href = url;
//         link.setAttribute('download', 'marks-sheet.pdf');
//         document.body.appendChild(link);
//         link.click();
//         document.body.removeChild(link);
//         window.URL.revokeObjectURL(url);
//         Swal.fire({
//             icon: 'success',
//             title: 'Downloaded!',
//             text: 'Marks sheet downloaded successfully.',
//             timer: 2000,
//             showConfirmButton: false
//         });
//     })
//     .catch(error => {
//         let errorMessage = 'Failed to download marks sheet. Check console for details.';
//         if (error.response) errorMessage = error.response.data.message || errorMessage;
//         console.error('Marks Sheet download error:', error);
//         Swal.fire({
//             icon: 'error',
//             title: 'Download Failed',
//             text: errorMessage,
//             showConfirmButton: true
//         });
//     })
//     .finally(() => {
//         setTimeout(() => {
//             downloadProgressContainer.style.display = 'none';
//             downloadProgressBar.style.width = '0%';
//             downloadMarksSheetButton.disabled = false;
//             downloadMarksSheetButton.innerHTML = originalBtnContent || '<i class="fas fa-file-pdf"></i> Download Marks Sheet';
//         }, 1000);
//     });
// }

// // Handle bulk upload with server-side progress
// // Handle bulk upload with server-side progress and NO page refresh
// function handleBulkUpload() {
//     const importForm = document.getElementById('importForm');
//     const importSubmit = document.getElementById('importSubmit');
//     const importLoader = document.getElementById('importLoader');
//     const uploadProgressBar = document.getElementById('uploadProgressBar');
//     const originalBtnContent = importSubmit?.innerHTML;

//     if (!importForm || !importSubmit || !importLoader || !uploadProgressBar) {
//         console.error('Upload elements not found in DOM');
//         return;
//     }

//     importForm.addEventListener('submit', function (e) {
//         e.preventDefault();

//         const fileInput = document.querySelector('input[name="file"]');
//         if (!fileInput.files.length) {
//             Swal.fire({
//                 icon: 'warning',
//                 title: 'No File Selected',
//                 text: 'Please select an Excel file to upload.',
//                 showConfirmButton: true
//             });
//             return;
//         }

//         const formData = new FormData(importForm);
//         importSubmit.disabled = true;
//         importSubmit.innerHTML = '<i class="ri-loader-4-line sync-icon"></i> Uploading...';
//         importLoader.style.display = 'block';
//         uploadProgressBar.style.width = '10%';

//         // Make the AJAX request
//         axios.post(window.routes?.import || '/subjectscoresheet/import', formData, {
//             headers: {
//                 'X-Requested-With': 'XMLHttpRequest',
//                 'Content-Type': 'multipart/form-data'
//             },
//             timeout: 60000,
//             onUploadProgress: progressEvent => {
//                 if (progressEvent.lengthComputable) {
//                     const percentComplete = (progressEvent.loaded / progressEvent.total) * 100;
//                     uploadProgressBar.style.width = `${percentComplete}%`;
//                     console.log(`Upload progress: ${percentComplete.toFixed(2)}%`);
//                 }
//             }
//         })
//         .then(response => {
//             uploadProgressBar.style.width = '100%';

//             if (response.data.success && response.data.data) {
//                 // Update the table with new data WITHOUT page refresh
//                 updateTableWithImportedData(response.data.data);

//                 // Update window.broadsheets for future operations
//                 if (response.data.data.broadsheets) {
//                     window.broadsheets = response.data.data.broadsheets;
//                     ensureBroadsheetsArray();
//                 }

//                 Swal.fire({
//                     icon: 'success',
//                     title: 'Imported Successfully!',
//                     text: response.data.message,
//                     timer: 2000,
//                     showConfirmButton: false
//                 });

//                 // Close modal
//                 const importModal = document.getElementById('importModal');
//                 if (importModal) {
//                     const modalInstance = bootstrap.Modal.getInstance(importModal);
//                     if (modalInstance) modalInstance.hide();
//                 }
//             } else if (response.data.warning) {
//                 // Update table even with warnings
//                 if (response.data.data) {
//                     updateTableWithImportedData(response.data.data);
//                     if (response.data.data.broadsheets) {
//                         window.broadsheets = response.data.data.broadsheets;
//                         ensureBroadsheetsArray();
//                     }
//                 }

//                 Swal.fire({
//                     icon: 'warning',
//                     title: 'Partial Success',
//                     text: response.data.message,
//                     showConfirmButton: true
//                 });

//                 const importModal = document.getElementById('importModal');
//                 if (importModal) {
//                     const modalInstance = bootstrap.Modal.getInstance(importModal);
//                     if (modalInstance) modalInstance.hide();
//                 }
//             } else {
//                 Swal.fire({
//                     icon: 'error',
//                     title: 'Import Failed',
//                     text: response.data.message || 'Server did not return updated scores.',
//                     showConfirmButton: true
//                 });
//             }
//         })
//         .catch(error => {
//             let errorMessage = 'Failed to upload scores.';

//             if (error.response && error.response.data) {
//                 if (typeof error.response.data === 'object') {
//                     errorMessage = error.response.data.message || errorMessage;
//                 } else {
//                     errorMessage = error.response.statusText || errorMessage;
//                 }
//             } else if (error.message) {
//                 errorMessage = error.message;
//             }

//             console.error('Upload error:', error);
//             Swal.fire({
//                 icon: 'error',
//                 title: 'Import Failed',
//                 text: errorMessage,
//                 showConfirmButton: true
//             });
//         })
//         .finally(() => {
//             setTimeout(() => {
//                 importLoader.style.display = 'none';
//                 uploadProgressBar.style.width = '0%';
//                 importSubmit.disabled = false;
//                 importSubmit.innerHTML = originalBtnContent || '<i class="ri-upload-line me-1"></i>Upload';

//                 // Reset file input
//                 fileInput.value = '';
//             }, 1000);
//         });
//     });
// }

// // Function to update table with imported data without page refresh
// function updateTableWithImportedData(data) {
//     if (!data.broadsheets || data.broadsheets.length === 0) {
//         // Show empty state
//         const tbody = document.getElementById('scoresheetTableBody');
//         if (tbody) {
//             const assessmentCount = data.assessments ? data.assessments.length : 0;
//             tbody.innerHTML = `<tr id="noDataRow">
//                 <td colspan="${3 + assessmentCount + 6}" class="text-center py-4 text-muted">
//                     <i class="ri-inbox-line ri-2x d-block mb-2"></i>No scores available.
//                 </td>
//             </tr>`;
//         }

//         const noDataAlert = document.getElementById('noDataAlert');
//         if (noDataAlert) noDataAlert.style.display = 'block';

//         const scoreCount = document.getElementById('scoreCount');
//         if (scoreCount) scoreCount.textContent = '0';

//         return;
//     }

//     // Refresh the table UI
//     refreshScoresheetTable(data.broadsheets, data.assessments);

//     // Update positions
//     if (typeof forceUpdatePositions === 'function') {
//         forceUpdatePositions();
//     }

//     // Update score count
//     const scoreCount = document.getElementById('scoreCount');
//     if (scoreCount) scoreCount.textContent = data.broadsheets.length;

//     // Hide no data alert
//     const noDataAlert = document.getElementById('noDataAlert');
//     if (noDataAlert) noDataAlert.style.display = 'none';

//     // Show success indicator
//     showToast('Scores imported successfully!', 'success');
// }

// // Function to refresh the table with new data
// function refreshScoresheetTable(broadsheets, assessments) {
//     const tbody = document.getElementById('scoresheetTableBody');
//     if (!tbody) return;

//     // Clear existing rows
//     tbody.innerHTML = '';

//     // Rebuild table rows
//     broadsheets.forEach((student, index) => {
//         const row = createTableRow(student, index, assessments);
//         tbody.appendChild(row);
//     });

//     // Re-initialize event listeners for new inputs
//     reinitializeScoreInputs();

//     // Re-initialize checkboxes
//     reinitializeCheckboxes();
// }

// // Function to create a table row
// function createTableRow(student, index, assessments) {
//     const tr = document.createElement('tr');
//     tr.setAttribute('data-id', student.id);

//     // Determine row class based on grade or vetted status
//     let rowClass = '';
//     if (student.grade === 'A' || student.grade === 'A1') {
//         rowClass = 'row-vetted';
//     } else if (student.grade === 'F' || student.grade === 'F9') {
//         rowClass = 'row-not-vetted';
//     } else {
//         rowClass = 'row-pending';
//     }
//     tr.className = rowClass;

//     // Get cum for color calculation
//     const cum = student.cum || 0;
//     let cumColorClass = '';
//     let cumTextColor = '';
//     if (cum >= 70) {
//         cumColorClass = 'bg-success-subtle';
//         cumTextColor = 'text-success';
//     } else if (cum >= 50) {
//         cumColorClass = 'bg-info-subtle';
//         cumTextColor = 'text-info';
//     } else if (cum >= 40) {
//         cumColorClass = 'bg-warning-subtle';
//         cumTextColor = 'text-warning';
//     } else {
//         cumColorClass = 'bg-danger-subtle';
//         cumTextColor = 'text-danger';
//     }

//     // Get position with ordinal suffix
//     let positionText = '-';
//     if (student.position && student.position !== null) {
//         positionText = student.position + getOrdinalSuffix(student.position);
//     }

//     // Build row HTML
//     let html = `
//         <td class="col-checkbox">
//             <div class="form-check mb-0">
//                 <input class="form-check-input score-checkbox" type="checkbox" data-id="${student.id}">
//             </div>
//         </td>
//         <td class="col-sn sn fw-medium">${index + 1}</td>
//         <td class="col-admissionno admissionno" data-admissionno="${student.admissionno || ''}">
//             <span class="text-muted small">${student.admissionno || '-'}</span>
//         </td>
//         <td class="col-name name" data-name="${((student.lname || '') + ' ' + (student.fname || '')).toLowerCase()}">
//             <div class="d-flex align-items-center gap-2">
//                 <div style="width:34px;height:34px;background:#e0e0e0;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;">📷</div>
//                 <div>
//                     <span class="fw-semibold d-block" style="font-size:12.5px;">${student.lname || ''}, ${student.fname || ''}</span>
//                     ${student.mname ? `<span class="text-muted small">${student.mname}</span>` : ''}
//                 </div>
//             </div>
//         </td>
//     `;

//     // Add assessment score inputs
//     if (assessments && assessments.length) {
//         assessments.forEach(assessment => {
//             let scoreValue = 0;
//             if (student.assessment_scores) {
//                 const scoreObj = student.assessment_scores.find(s => s.assessment_id == assessment.id);
//                 if (scoreObj) scoreValue = scoreObj.score;
//             }
//             html += `
//                 <td class="col-assessment-${assessment.id} assessment-col text-center">
//                     <input type="number"
//                            class="score-input"
//                            data-field="${assessment.id}"
//                            data-max="${assessment.max_score}"
//                            data-id="${student.id}"
//                            data-original="${scoreValue}"
//                            value="${scoreValue}"
//                            min="0" max="${assessment.max_score}" step="0.1"
//                            style="width:72px;min-width:72px;height:36px;padding:4px 6px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px;text-align:center;">
//                     </td>
//             `;
//         });
//     }

//     // Calculate total from assessment scores
//     let total = student.total || 0;
//     if (student.assessment_scores) {
//         total = student.assessment_scores.reduce((sum, s) => sum + s.score, 0);
//     }

//     html += `
//         <td class="col-total text-center">
//             <span class="badge bg-primary" data-total="${total}">${total.toFixed(1)}</span>
//         </td>
//         <td class="col-bf text-center">
//             <span class="badge bg-secondary-subtle text-secondary">${(student.bf || 0).toFixed(2)}</span>
//         </td>
//         <td class="col-cum text-center">
//             <span class="badge ${cumColorClass} ${cumTextColor} fw-bold" style="font-size:12px;">${cum.toFixed(1)}</span>
//         </td>
//         <td class="col-gpa text-center">
//             <span class="badge bg-warning-subtle text-warning fw-semibold">${(student.gpa || 0).toFixed(2)}</span>
//         </td>
//         <td class="col-cgpa text-center">
//             <span class="badge bg-dark-subtle text-dark">${(student.cgpa || 0).toFixed(2)}</span>
//         </td>
//         <td class="col-grade text-center fw-bold" style="font-size:13px;">${student.grade || '-'}</td>
//         <td class="col-position text-center">
//             <span class="badge" style="background:var(--ss-primary);">${positionText}</span>
//         </td>
//         <td class="col-vetted text-center">
//             <span class="badge bg-warning-subtle text-warning"><i class="ri-time-line me-1"></i>Pending</span>
//         </td>
//     `;

//     tr.innerHTML = html;
//     return tr;
// }

// // Reinitialize score input event listeners
// function reinitializeScoreInputs() {
//     document.querySelectorAll('.score-input').forEach(input => {
//         // Remove old listeners by cloning and replacing
//         const newInput = input.cloneNode(true);
//         input.parentNode.replaceChild(newInput, input);

//         newInput.addEventListener('focus', function() {
//             this.select();
//         });

//         newInput.addEventListener('input', function() {
//             validateInput(this);
//             const row = this.closest('tr');
//             if (row && typeof updateRowTotal === 'function') {
//                 updateRowTotal(row);
//             }
//         });

//         newInput.addEventListener('blur', function() {
//             validateInput(this);
//             const row = this.closest('tr');
//             if (row && typeof updateRowTotal === 'function') {
//                 updateRowTotal(row);
//             }
//             const orig = parseFloat(this.dataset.original) || 0;
//             const curr = parseFloat(this.value) || 0;
//             if (Math.abs(curr - orig) > 0.001) {
//                 this.dataset.original = this.value;
//                 if (typeof saveIndividualScore === 'function') {
//                     saveIndividualScore(this);
//                 }
//             }
//         });

//         newInput.addEventListener('keydown', function(e) {
//             if (e.key === 'Enter') {
//                 e.preventDefault();
//                 if (typeof saveIndividualScore === 'function') {
//                     saveIndividualScore(this);
//                 }
//                 const inputs = Array.from(document.querySelectorAll('.score-input'));
//                 const idx = inputs.indexOf(this);
//                 if (idx < inputs.length - 1) inputs[idx + 1].focus();
//             }
//         });
//     });
// }

// // Reinitialize checkboxes
// function reinitializeCheckboxes() {
//     const checkAll = document.getElementById('checkAll');
//     if (checkAll) {
//         const newCheckAll = checkAll.cloneNode(true);
//         checkAll.parentNode.replaceChild(newCheckAll, checkAll);

//         newCheckAll.addEventListener('change', function() {
//             document.querySelectorAll('.score-checkbox').forEach(cb => {
//                 cb.checked = this.checked;
//             });
//         });
//     }

//     document.querySelectorAll('.score-checkbox').forEach(checkbox => {
//         const newCheckbox = checkbox.cloneNode(true);
//         checkbox.parentNode.replaceChild(newCheckbox, checkbox);
//     });
// }

// // Helper function for ordinal suffix
// function getOrdinalSuffix(position) {
//     if (!position || isNaN(position)) return '-';
//     position = parseInt(position);
//     if (position % 100 >= 11 && position % 100 <= 13) return position + 'th';
//     switch (position % 10) {
//         case 1: return position + 'st';
//         case 2: return position + 'nd';
//         case 3: return position + 'rd';
//         default: return position + 'th';
//     }
// }

// // Helper function to validate input
// function validateInput(input) {
//     const max = parseFloat(input.dataset.max) || 0;
//     const val = parseFloat(input.value) || 0;
//     if (val > max) {
//         input.classList.add('is-invalid');
//         return false;
//     }
//     input.classList.remove('is-invalid');
//     return true;
// }

// // Helper function to show toast message
// function showToast(message, type = 'info') {
//     const toastHtml = `
//         <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050">
//             <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
//                 <div class="d-flex">
//                     <div class="toast-body">
//                         ${message}
//                     </div>
//                     <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
//                 </div>
//             </div>
//         </div>
//     `;

//     $('body').append(toastHtml);
//     const toast = new bootstrap.Toast($('.toast').last()[0]);
//     toast.show();

//     setTimeout(() => {
//         $('.toast').last().remove();
//     }, 3000);
// }


// // Function to update table with imported data without page refresh
// function updateTableWithImportedData(data) {
//     if (data.broadsheets) {
//         window.broadsheets = data.broadsheets;
//         ensureBroadsheetsArray();

//         // Refresh the table UI
//         refreshScoresheetTable(data.broadsheets, data.assessments);

//         // Update positions
//         forceUpdatePositions();

//         // Update score count
//         const scoreCount = document.getElementById('scoreCount');
//         if (scoreCount) scoreCount.textContent = window.broadsheets.length;

//         // Show/hide no data alert
//         const noDataAlert = document.getElementById('noDataAlert');
//         if (noDataAlert) noDataAlert.style.display = window.broadsheets.length ? 'none' : 'block';
//     }
// }

// // Function to refresh the table with new data
// function refreshScoresheetTable(broadsheets, assessments) {
//     const tbody = document.getElementById('scoresheetTableBody');
//     if (!tbody) return;

//     // Clear existing rows
//     tbody.innerHTML = '';

//     if (!broadsheets || broadsheets.length === 0) {
//         tbody.innerHTML = '<tr id="noDataRow"><td colspan="' + (3 + (assessments ? assessments.length : 0) + 6) + '" class="text-center py-4 text-muted"><i class="ri-inbox-line ri-2x d-block mb-2"></i>No scores available.</td></tr>';
//         return;
//     }

//     // Rebuild table rows
//     broadsheets.forEach((student, index) => {
//         const row = createTableRow(student, index, assessments);
//         tbody.appendChild(row);
//     });

//     // Re-initialize event listeners for new inputs
//     reinitializeScoreInputs();
// }

// // Function to create a table row
// function createTableRow(student, index, assessments) {
//     const tr = document.createElement('tr');
//     tr.setAttribute('data-id', student.id);

//     const vClass = student.grade === 'A' ? 'row-vetted' : 'row-not-vetted';
//     tr.className = vClass;

//     // Build row HTML
//     let html = `
//         <td class="col-checkbox">
//             <div class="form-check mb-0">
//                 <input class="form-check-input score-checkbox" type="checkbox" data-id="${student.id}">
//             </div>
//         </td>
//         <td class="col-sn sn fw-medium">${index + 1}</td>
//         <td class="col-admissionno admissionno" data-admissionno="${student.admissionno}">
//             <span class="text-muted small">${student.admissionno || '-'}</span>
//         </td>
//         <td class="col-name name" data-name="${(student.lname || '') + ' ' + (student.fname || '')}">
//             <div class="d-flex align-items-center gap-2">
//                 <div style="width:34px;height:34px;background:#e0e0e0;border-radius:50%;display:flex;align-items:center;justify-content:center;">📷</div>
//                 <div>
//                     <span class="fw-semibold d-block" style="font-size:12.5px;">${student.lname || ''}, ${student.fname || ''}</span>
//                     ${student.mname ? `<span class="text-muted small">${student.mname}</span>` : ''}
//                 </div>
//             </div>
//         </td>
//     `;

//     // Add assessment score inputs
//     if (assessments && assessments.length) {
//         assessments.forEach(assessment => {
//             const scoreObj = student.assessment_scores?.find(s => s.assessment_id == assessment.id);
//             const scoreValue = scoreObj ? scoreObj.score : 0;
//             html += `
//                 <td class="col-assessment-${assessment.id} assessment-col text-center">
//                     <input type="number"
//                            class="score-input"
//                            data-field="${assessment.id}"
//                            data-max="${assessment.max_score}"
//                            data-id="${student.id}"
//                            data-original="${scoreValue}"
//                            value="${scoreValue}"
//                            min="0" max="${assessment.max_score}" step="0.1">
//                 </td>
//             `;
//         });
//     }

//     html += `
//         <td class="col-total text-center">
//             <span class="badge bg-primary" data-total="${student.total || 0}">${(student.total || 0).toFixed(1)}</span>
//         </td>
//         <td class="col-bf text-center">
//             <span class="badge bg-secondary-subtle text-secondary">${(student.bf || 0).toFixed(2)}</span>
//         </td>
//         <td class="col-cum text-center">
//             <span class="badge bg-${student.cum >= 70 ? 'success' : (student.cum >= 50 ? 'info' : (student.cum >= 40 ? 'warning' : 'danger'))}-subtle text-${student.cum >= 70 ? 'success' : (student.cum >= 50 ? 'info' : (student.cum >= 40 ? 'warning' : 'danger'))} fw-bold" style="font-size:12px;">${(student.cum || 0).toFixed(1)}</span>
//         </td>
//         <td class="col-gpa text-center">
//             <span class="badge bg-warning-subtle text-warning fw-semibold">${(student.gpa || 0).toFixed(2)}</span>
//         </td>
//         <td class="col-cgpa text-center">
//             <span class="badge bg-dark-subtle text-dark">${(student.cgpa || 0).toFixed(2)}</span>
//         </td>
//         <td class="col-grade text-center fw-bold" style="font-size:13px;">${student.grade || '-'}</td>
//         <td class="col-position text-center">
//             <span class="badge" style="background:var(--ss-primary);">${student.position ? student.position + getOrdinalSuffix(student.position) : '-'}</span>
//         </td>
//         <td class="col-vetted text-center">
//             <span class="badge bg-warning-subtle text-warning"><i class="ri-time-line me-1"></i>Pending</span>
//         </td>
//     `;

//     tr.innerHTML = html;
//     return tr;
// }

// // Reinitialize score input event listeners
// function reinitializeScoreInputs() {
//     document.querySelectorAll('.score-input').forEach(input => {
//         // Remove old listeners by cloning and replacing
//         const newInput = input.cloneNode(true);
//         input.parentNode.replaceChild(newInput, input);

//         newInput.addEventListener('focus', function() { this.select(); });
//         newInput.addEventListener('input', function() {
//             validateInput(this);
//             updateRowTotal(this.closest('tr'));
//         });
//         newInput.addEventListener('blur', function() {
//             validateInput(this);
//             updateRowTotal(this.closest('tr'));
//             const orig = parseFloat(this.dataset.original) || 0;
//             const curr = parseFloat(this.value) || 0;
//             if (Math.abs(curr - orig) > 0.001) {
//                 this.dataset.original = this.value;
//                 saveIndividualScore(this);
//             }
//         });
//         newInput.addEventListener('keydown', function(e) {
//             if (e.key === 'Enter') {
//                 e.preventDefault();
//                 saveIndividualScore(this);
//                 const inputs = Array.from(document.querySelectorAll('.score-input'));
//                 const idx = inputs.indexOf(this);
//                 if (idx < inputs.length - 1) inputs[idx + 1].focus();
//             }
//         });
//     });
// }

// // Helper function for ordinal suffix
// function getOrdinalSuffix(position) {
//     if (!position || isNaN(position)) return '-';
//     position = parseInt(position);
//     if (position % 100 >= 11 && position % 100 <= 13) return position + 'th';
//     switch (position % 10) {
//         case 1: return position + 'st';
//         case 2: return position + 'nd';
//         case 3: return position + 'rd';
//         default: return position + 'th';
//     }
// }


// // Bulk actions and modal initialization
// function initializeBulkActions() {
//     const bulkUpdateScores = document.getElementById('bulkUpdateScores');
//     const selectAllScores = document.getElementById('selectAllScores');
//     const clearAllScores = document.getElementById('clearAllScores');
//     const checkAll = document.getElementById('checkAll');
//     const scoresModal = document.getElementById('scoresModal');
//     const imageViewModal = document.getElementById('imageViewModal');
//     const downloadExcelButton = document.getElementById('downloadExcel');
//     const downloadMarksSheetButton = document.getElementById('downloadMarksSheet');

//     console.log('Initializing bulk actions:', {
//         bulkUpdateScores: !!bulkUpdateScores,
//         selectAllScores: !!selectAllScores,
//         clearAllScores: !!clearAllScores,
//         checkAll: !!checkAll,
//         scoresModal: !!scoresModal,
//         imageViewModal: !!imageViewModal,
//         downloadExcelButton: !!downloadExcelButton,
//         downloadMarksSheetButton: !!downloadMarksSheetButton
//     });

//     if (bulkUpdateScores) {
//         bulkUpdateScores.addEventListener('click', e => {
//             e.preventDefault();
//             console.log('Bulk Update Scores button clicked');
//             bulkSaveAllScores();
//         });
//     } else {
//         console.warn('bulkUpdateScores button not found in DOM');
//     }

//     if (selectAllScores) {
//         selectAllScores.addEventListener('click', () => {
//             console.log('Select All Scores button clicked');
//             document.querySelectorAll('.score-checkbox').forEach(checkbox => {
//                 checkbox.checked = true;
//             });
//             if (checkAll) checkAll.checked = true;
//         });
//     } else {
//         console.warn('selectAllScores button not found in DOM');
//     }

//     if (clearAllScores) {
//         clearAllScores.addEventListener('click', () => {
//             console.log('Clear All Scores button clicked');
//             Swal.fire({
//                 title: 'Clear All Scores?',
//                 text: 'This will clear all score inputs. Are you sure?',
//                 icon: 'warning',
//                 showCancelButton: true,
//                 confirmButtonColor: '#d33',
//                 cancelButtonColor: '#3085d6',
//                 confirmButtonText: 'Yes, Clear All!'
//             }).then((result) => {
//                 if (result.isConfirmed) {
//                     document.querySelectorAll('.score-input').forEach(input => {
//                         input.value = '';
//                     });
//                     document.querySelectorAll('#scoresheetTableBody tr:not(#noDataRow)').forEach(row => {
//                         updateRowTotal(row);
//                     });
//                     Swal.fire({
//                         icon: 'success',
//                         title: 'Cleared!',
//                         text: 'All scores have been cleared.',
//                         timer: 2000
//                     });
//                 }
//             });
//         });
//     } else {
//         console.warn('clearAllScores button not found in DOM');
//     }

//     if (checkAll) {
//         checkAll.addEventListener('change', function () {
//             console.log('Check All checkbox changed:', this.checked);
//             document.querySelectorAll('.score-checkbox').forEach(checkbox => {
//                 checkbox.checked = this.checked;
//             });
//         });
//     } else {
//         console.warn('checkAll checkbox not found in DOM');
//     }

//     if (scoresModal) {
//         scoresModal.addEventListener('show.bs.modal', () => {
//             console.log('Scores modal show event triggered');
//             populateScoresModal();
//         });
//     } else {
//         console.warn('scoresModal not found in DOM');
//     }

//     if (imageViewModal) {
//         imageViewModal.addEventListener('show.bs.modal', function (event) {
//             console.log('Image view modal show event triggered');
//             const button = event.relatedTarget;
//             const imageUrl = button.getAttribute('data-image') || DEFAULT_IMAGE;
//             const enlargedImage = imageViewModal.querySelector('#enlargedImage');
//             if (enlargedImage) {
//                 enlargedImage.src = imageUrl;
//                 enlargedImage.onerror = () => {
//                     enlargedImage.src = DEFAULT_IMAGE;
//                     console.log(`Enlarged image failed to load: ${imageUrl}`);
//                 };
//                 console.log(`Setting enlarged image src: ${imageUrl}`);
//             } else {
//                 console.warn('enlargedImage not found in imageViewModal');
//             }
//         });
//     } else {
//         console.warn('imageViewModal not found in DOM');
//     }

//     if (downloadExcelButton) {
//         downloadExcelButton.addEventListener('click', e => {
//             e.preventDefault();
//             console.log('Download Excel button clicked');
//             downloadExcel();
//         });
//     } else {
//         console.warn('downloadExcel button not found in DOM');
//     }

//     if (downloadMarksSheetButton) {
//         downloadMarksSheetButton.addEventListener('click', e => {
//             e.preventDefault();
//             console.log('Download Marks Sheet button clicked');
//             downloadMarksSheet();
//         });
//     } else {
//         console.warn('downloadMarksSheet button not found in DOM');
//     }

//     // Initialize bulk upload handler
//     handleBulkUpload();

//     // Initialize score input listeners
//     document.querySelectorAll('.score-input').forEach(input => {
//         // Clear default '0' or '0.0' on focus
//         input.addEventListener('focus', () => {
//             if (input.value === '0' || input.value === '0.0') {
//                 console.log(`Clearing default score for input with id=${input.dataset.id}, field=${input.dataset.field}`);
//                 input.value = '';
//             }
//         });

//         // Handle score updates
//         input.addEventListener('input', debounce(() => {
//             const row = input.closest('tr');
//             if (row) {
//                 updateRowTotal(row);
//             } else {
//                 console.warn('Parent row not found for score input');
//             }
//         }, 300));
//     });

//     // Initialize search functionality
//     const searchInput = document.getElementById('searchInput');
//     const clearSearch = document.getElementById('clearSearch');
//     if (searchInput) {
//         searchInput.addEventListener('input', debounce(() => {
//             const query = searchInput.value.toLowerCase().trim();
//             console.log(`Search query: ${query}`);
//             document.querySelectorAll('#scoresheetTableBody tr:not(#noDataRow)').forEach(row => {
//                 const admissionno = row.querySelector('.admissionno')?.dataset.admissionno?.toLowerCase() || '';
//                 const name = row.querySelector('.name')?.dataset.name?.toLowerCase() || '';
//                 const match = admissionno.includes(query) || name.includes(query);
//                 row.style.display = match ? '' : 'none';
//             });
//         }, 300));
//     } else {
//         console.warn('searchInput not found in DOM');
//     }

//     if (clearSearch) {
//         clearSearch.addEventListener('click', () => {
//             if (searchInput) {
//                 searchInput.value = '';
//                 document.querySelectorAll('#scoresheetTableBody tr:not(#noDataRow)').forEach(row => {
//                     row.style.display = '';
//                 });
//                 console.log('Search cleared');
//             }
//         });
//     } else {
//         console.warn('clearSearch button not found in DOM');
//     }

//     // Initialize Ctrl+S shortcut for saving scores
//     document.addEventListener('keydown', (e) => {
//         if (e.ctrlKey && e.key === 's') {
//             e.preventDefault();
//             console.log('Ctrl+S pressed, triggering bulk save');
//             bulkSaveAllScores();
//         }
//     });
// }

// // Initialize everything on DOMContentLoaded
// document.addEventListener('DOMContentLoaded', () => {
//     console.log('DOMContentLoaded, initializing bulk actions');
//     ensureBroadsheetsArray();
//     initializeBulkActions();
//     forceUpdatePositions();
// });
