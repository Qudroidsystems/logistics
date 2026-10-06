(function () {
    // Log initialization
    console.log("subjectscoresheet-mock.init.js loaded at", new Date().toISOString());

    // Check dependencies (axios, SweetAlert2, Bootstrap)
    function checkDependencies() {
        if (!window.axios || !window.Swal || !window.bootstrap) {
            console.error("Missing dependencies: axios, SweetAlert2, or Bootstrap");
            Swal.fire({
                icon: "error",
                title: "Dependency Error",
                text: "Required libraries are missing. Check console for details.",
                showConfirmButton: true
            });
            return false;
        }
        console.log("Dependencies loaded: axios, SweetAlert2, Bootstrap");
        return true;
    }

    // Set up Axios with CSRF token
    function setupAxios() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrfToken) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
            console.log("CSRF token set for Axios");
        } else {
            console.warn("CSRF token not found");
            Swal.fire({
                icon: "warning",
                title: "CSRF Token Missing",
                text: "AJAX requests may fail due to missing CSRF token.",
                timer: 3000
            });
        }
    }

    // Normalize picture path
    function normalizePicturePath(picture) {
        if (!picture || picture === 'none' || picture === '') {
            console.log("normalizePicturePath: Empty or 'none' picture, returning 'unnamed.jpg'");
            return 'unnamed.jpg';
        }
        const normalized = picture.replace(/^studentavatar\/|^\//, '');
        console.log(`normalizePicturePath: Original: ${picture}, Normalized: ${normalized}`);
        return normalized;
    }

    // Normalize broadsheets array
    function ensureBroadsheetsArray() {
        if (!window.broadsheets || !Array.isArray(window.broadsheets)) {
            console.warn("Broadsheets is not an array or undefined:", window.broadsheets);
            window.broadsheets = [];
        }
        window.broadsheets = window.broadsheets.filter(item => item && item.id && item.admissionno);
        console.log("Normalized broadsheets:", window.broadsheets.length, "records");
    }

    // Debounce function for input handling
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    }

    // Client-side grade calculation (fallback)
    function calculateGrade(score) {
        if (isNaN(score) || score < 0 || score > 100) {
            console.warn(`Invalid score for grading: ${score}`);
            return '-';
        }
        const isSenior = window.is_senior === true;
        console.log(`Calculating grade: score=${score}, isSenior=${isSenior}`);
        return isSenior
            ? score >= 75 ? 'A1' : score >= 70 ? 'B2' : score >= 65 ? 'B3' :
              score >= 60 ? 'C4' : score >= 55 ? 'C5' : score >= 50 ? 'C6' :
              score >= 45 ? 'D7' : score >= 40 ? 'E8' : 'F9'
            : score >= 70 ? 'A' : score >= 60 ? 'B' : score >= 50 ? 'C' :
              score >= 40 ? 'D' : 'F';
    }

    // Client-side remarks calculation (placeholder, adjust based on backend logic)
    function calculateRemarks(score) {
        if (isNaN(score) || score < 0 || score > 100) return '-';
        return score >= 70 ? 'Excellent' : score >= 60 ? 'Good' :
               score >= 50 ? 'Fair' : score >= 40 ? 'Pass' : 'Fail';
    }

    // Fetch grade from backend
    async function fetchGradeFromBackend(schoolclass_id, score) {
        if (!window.routes.calculateGrade) {
            console.error("Grade calculation route missing");
            return null;
        }
        if (!schoolclass_id || isNaN(schoolclass_id) || schoolclass_id <= 0) {
            console.warn(`Invalid schoolclass_id: ${schoolclass_id}`);
            return null;
        }
        try {
            console.log(`Fetching grade: schoolclass_id=${schoolclass_id}, score=${score}`);
            const response = await axios.post(window.routes.calculateGrade, {
                schoolclass_id,
                cum: score
            }, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                timeout: 5000
            });
            console.log(`Backend grade: ${response.data.grade}`);
            return response.data.grade || null;
        } catch (error) {
            console.error("Grade fetch failed:", {
                status: error.response?.status,
                data: error.response?.data,
                message: error.message
            });
            return null;
        }
    }

    // Get ordinal suffix for position
    function getOrdinalSuffix(position) {
        if (!position || isNaN(position)) return '-';
        position = parseInt(position);
        if (position % 100 >= 11 && position % 100 <= 13) return `${position}th`;
        return position + { 1: 'st', 2: 'nd', 3: 'rd' }[position % 10] || `${position}th`;
    }

    // Update row with score, total, grade, remarks, and position
    async function updateRowTotal(row) {
        const input = row.querySelector('.score-input[data-field="exam"]');
        const id = input?.dataset.id;
        if (!id) {
            console.error("Missing dataset.id for row");
            return;
        }

        const exam = parseFloat(input.value) || 0;
        const total = exam;
        let grade = window.schoolclass_id && exam > 0
            ? await fetchGradeFromBackend(window.schoolclass_id, total) || calculateGrade(total)
            : calculateGrade(total);
        const remarks = calculateRemarks(total);

        console.log(`updateRowTotal: id=${id}, exam=${exam}, total=${total.toFixed(1)}, grade=${grade}, remarks=${remarks}`);

        row.querySelector('.total-display span').textContent = total.toFixed(1);
        row.querySelector('.total-display span').classList.toggle('text-danger', total < 40 && total > 0);
        row.querySelector('.grade-display span').textContent = grade;
        const remarksDisplay = row.querySelector('.remarks-display span');
        if (remarksDisplay) remarksDisplay.textContent = remarks;

        const broadsheetIndex = window.broadsheets.findIndex(b => String(b.id) === String(id));
        if (broadsheetIndex !== -1) {
            window.broadsheets[broadsheetIndex] = { ...window.broadsheets[broadsheetIndex], exam, total, grade, remarks };
        } else {
            window.broadsheets.push({ id, exam, total, grade, remarks, subject_position_class: null });
        }

        forceUpdatePositions();
    }

    // Calculate positions (client-side fallback)
    function forceUpdatePositions() {
        ensureBroadsheetsArray();
        if (!window.broadsheets.length) {
            console.log("No broadsheets for position calculation");
            document.querySelectorAll('#scoresheetTableBody tr:not(#noDataRow)').forEach(row => {
                row.querySelector('.position-display span').textContent = '-';
            });
            return;
        }

        const hasServerPositions = window.broadsheets.some(b => b.subject_position_class > 0);
        if (hasServerPositions) {
            console.log("Using server-side positions");
            window.broadsheets.forEach(b => {
                const row = document.querySelector(`tr:has(input[data-id="${b.id}"])`);
                if (row) {
                    row.querySelector('.position-display span').textContent =
                        b.subject_position_class ? getOrdinalSuffix(b.subject_position_class) : '-';
                }
            });
        } else {
            console.log("Calculating client-side positions");
            const validScores = window.broadsheets.filter(b => b.total > 0).sort((a, b) => b.total - a.total || a.id - b.id);
            let position = 1, lastTotal = null;
            const positionMap = {};
            validScores.forEach((b, i) => {
                if (lastTotal !== b.total) position = i + 1;
                positionMap[b.id] = position;
                lastTotal = b.total;
            });

            window.broadsheets.forEach(b => {
                const row = document.querySelector(`tr:has(input[data-id="${b.id}"])`);
                if (row) {
                    row.querySelector('.position-display span').textContent = positionMap[b.id] ? getOrdinalSuffix(positionMap[b.id]) : '-';
                }
            });
        }
    }

    // Bulk save scores
    async function bulkSaveAllScores() {
        ensureBroadsheetsArray();
        const inputs = document.querySelectorAll('.score-input');
        if (!inputs.length) {
            Swal.fire({ icon: 'info', title: 'No Scores', text: 'No scores to save.', timer: 2000 });
            return;
        }

        const requiredVars = ['term_id', 'session_id', 'subjectclass_id', 'schoolclass_id', 'staff_id'];
        for (const key of requiredVars) {
            if (!window[key]) {
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Data',
                    text: `Please select a ${key.replace('_id', '')} before saving.`,
                    showConfirmButton: true
                });
                return;
            }
        }

        const scores = [];
        let hasInvalid = false;
        inputs.forEach(input => {
            const id = input.dataset.id;
            const value = input.value === '' ? 0 : parseFloat(input.value);
            input.classList.remove('is-invalid', 'is-valid');
            if (isNaN(value) || value < 0 || value > 100) {
                input.classList.add('is-invalid');
                hasInvalid = true;
                return;
            }
            input.classList.add('is-valid');
            scores.push({
                id: parseInt(id),
                exam: value,
                total: value,
                grade: calculateGrade(value),
                remarks: calculateRemarks(value)
            });
        });

        if (hasInvalid) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Scores',
                text: 'Some scores are invalid. Please enter values between 0 and 100.',
                showConfirmButton: true
            });
            return;
        }

        const progressContainer = document.getElementById('progressContainer');
        const progressBar = progressContainer?.querySelector('.progress-bar');
        const bulkUpdateBtn = document.getElementById('bulkUpdateScores');
        const originalBtnContent = bulkUpdateBtn?.innerHTML;

        if (progressContainer) progressContainer.style.display = 'block';
        if (progressBar) progressBar.style.width = '20%';
        if (bulkUpdateBtn) bulkUpdateBtn.disabled = true;

        try {
            const response = await axios.post(window.routes.bulkUpdate, {
                scores,
                term_id: window.term_id,
                session_id: window.session_id,
                subjectclass_id: window.subjectclass_id,
                schoolclass_id: window.schoolclass_id,
                staff_id: window.staff_id
            }, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                timeout: 30000
            });

            if (response.data.success && response.data.data?.broadsheets) {
                console.log("Bulk save response:", response.data.data.broadsheets);
                window.broadsheets = response.data.data.broadsheets;
                ensureBroadsheetsArray();

                // Update DOM with new broadsheets data
                document.querySelectorAll('#scoresheetTableBody tr:not(#noDataRow)').forEach(row => {
                    const id = row.querySelector('.score-input')?.dataset.id;
                    const b = window.broadsheets.find(b => String(b.id) === id);
                    if (b) {
                        row.querySelector('.score-input').value = b.exam ?? '';
                        // Sanitize total to ensure it's a number
                        const totalValue = isNaN(parseFloat(b.total)) ? 0 : parseFloat(b.total);
                        row.querySelector('.total-display span').textContent = totalValue.toFixed(1);
                        row.querySelector('.total-display span').classList.toggle('text-danger', totalValue < 40 && totalValue > 0);
                        row.querySelector('.grade-display span').textContent = b.grade || '-';
                        const remarksDisplay = row.querySelector('.remarks-display span');
                        if (remarksDisplay) remarksDisplay.textContent = b.remarks || calculateRemarks(totalValue);
                        row.querySelector('.position-display span').textContent =
                            b.subject_position_class ? getOrdinalSuffix(b.subject_position_class) : '-';
                        const image = row.querySelector('.student-image');
                        if (image && b.picture) {
                            const picture = normalizePicturePath(b.picture);
                            const imageUrl = `/storage/student_avatars/${picture}`;
                            image.src = imageUrl;
                            image.dataset.image = imageUrl;
                            image.dataset.picture = b.picture || 'none';
                            image.onerror = () => {
                                image.src = '/storage/student_avatars/unnamed.jpg';
                                image.dataset.image = '/storage/student_avatars/unnamed.jpg';
                                image.dataset.picture = 'none';
                                console.log(`Image failed to load for admissionno: ${b.admissionno || 'unknown'}, picture: ${b.picture || 'none'}, attempted URL: ${imageUrl}`);
                            };
                        }
                    }
                });

                forceUpdatePositions();
                Swal.fire({
                    icon: 'success',
                    title: 'Saved',
                    text: `Updated ${scores.length} score${scores.length !== 1 ? 's' : ''}.`,
                    timer: 2000
                });
            } else {
                throw new Error(response.data.message || 'Invalid server response');
            }
        } catch (error) {
            console.error("Bulk save failed:", error.response?.data || error.message);
            Swal.fire({
                icon: 'error',
                title: 'Save Failed',
                text: error.response?.data?.message || 'Failed to save scores.',
                showConfirmButton: true
            });
        } finally {
            if (progressContainer) progressContainer.style.display = 'none';
            if (progressBar) progressBar.style.width = '0%';
            if (bulkUpdateBtn) {
                bulkUpdateBtn.disabled = false;
                bulkUpdateBtn.innerHTML = originalBtnContent;
            }
        }
    }

    // Delete selected scores
    window.SubjectScoresheetMock = {
        deleteSelectedScores: function () {
            const selectedCheckboxes = document.querySelectorAll('.score-checkbox:checked');
            if (!selectedCheckboxes.length) {
                Swal.fire({
                    icon: 'info',
                    title: 'No Selection',
                    text: 'Please select at least one score to delete.',
                    timer: 2000
                });
                return;
            }
            Swal.fire({
                title: 'Delete Selected Scores?',
                text: 'This will clear the selected mock scores. Are you sure?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, Delete!'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    for (const checkbox of selectedCheckboxes) {
                        const row = checkbox.closest('tr');
                        if (row) {
                            const input = row.querySelector('.score-input');
                            if (input) {
                                input.value = '';
                                await updateRowTotal(row);
                            }
                        }
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted',
                        text: 'Selected scores cleared.',
                        timer: 2000
                    });
                }
            });
        }
    };

    // Initialize score inputs
    function initializeScoreInputs() {
        document.querySelectorAll('.score-input').forEach(input => {
            // Clear default '0' on focus
            input.addEventListener('focus', () => {
                if (input.value === '0' || input.value === '0.0') {
                    console.log(`Clearing default score for input with id=${input.dataset.id}`);
                    input.value = '';
                }
            });

            // Handle score updates
            input.addEventListener('input', debounce(async () => {
                const row = input.closest('tr');
                if (row) await updateRowTotal(row);
            }, 300));
        });
    }

    // Initialize image modal
    function initializeImageModal() {
        const imageViewModal = document.getElementById('imageViewModal');
        if (imageViewModal) {
            imageViewModal.addEventListener('show.bs.modal', event => {
                console.log('Image modal show event triggered');
                const button = event.relatedTarget;
                const imageUrl = button.getAttribute('data-image') || '/storage/student_avatars/unnamed.jpg';
                const enlargedImage = imageViewModal.querySelector('#enlargedImage');
                if (enlargedImage) {
                    enlargedImage.src = imageUrl;
                    enlargedImage.onerror = () => {
                        enlargedImage.src = '/storage/student_avatars/unnamed.jpg';
                        console.log(`Enlarged image failed to load: ${imageUrl}`);
                    };
                    console.log(`Setting enlarged image src: ${imageUrl}`);
                }
            });
        } else {
            console.warn("imageViewModal not found in DOM");
        }
    }

    // Initialize bulk actions and checkboxes
    function initializeBulkActions() {
        const bulkUpdateBtn = document.getElementById('bulkUpdateScores');
        const selectAllBtn = document.getElementById('selectAllScores');
        const clearAllBtn = document.getElementById('clearAllScores');
        const checkAll = document.getElementById('checkAll');

        console.log('Initializing bulk actions:', {
            bulkUpdateBtn: !!bulkUpdateBtn,
            selectAllBtn: !!selectAllBtn,
            clearAllBtn: !!clearAllBtn,
            checkAll: !!checkAll
        });

        if (bulkUpdateBtn) {
            bulkUpdateBtn.addEventListener('click', async e => {
                e.preventDefault();
                console.log('Bulk Update Scores clicked');
                await bulkSaveAllScores();
            });
        } else {
            console.warn("bulkUpdateScores button not found");
        }

        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', () => {
                console.log('Select All clicked');
                document.querySelectorAll('.score-checkbox').forEach(cb => cb.checked = true);
                if (checkAll) checkAll.checked = true;
            });
        } else {
            console.warn("selectAllScores button not found");
        }

        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', () => {
                console.log('Clear All clicked');
                Swal.fire({
                    title: 'Clear All Scores?',
                    text: 'This will clear all score inputs. Are you sure?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, Clear All!'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        document.querySelectorAll('.score-input').forEach(input => input.value = '');
                        for (const row of document.querySelectorAll('#scoresheetTableBody tr:not(#noDataRow)')) {
                            await updateRowTotal(row);
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'Cleared',
                            text: 'All scores cleared.',
                            timer: 2000
                        });
                    }
                });
            });
        } else {
            console.warn("clearAllScores button not found");
        }

        if (checkAll) {
            checkAll.addEventListener('change', () => {
                console.log('Check All changed:', checkAll.checked);
                document.querySelectorAll('.score-checkbox').forEach(cb => cb.checked = checkAll.checked);
            });
        } else {
            console.warn("checkAll checkbox not found");
        }

        // Ctrl+S shortcut
        document.addEventListener('keydown', async e => {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                console.log('Ctrl+S pressed');
                await bulkSaveAllScores();
            }
        });
    }

    // Initialize
    function init() {
        if (!checkDependencies()) return;
        setupAxios();
        ensureBroadsheetsArray();
        initializeScoreInputs();
        initializeBulkActions();
        initializeImageModal();
        forceUpdatePositions();
        // Initial row update
        document.querySelectorAll('#scoresheetTableBody tr:not(#noDataRow)').forEach(async row => {
            await updateRowTotal(row);
        });
    }

    // Run on DOM load
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})();
