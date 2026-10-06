console.log("myprincipalscomment.init.js with AUTO-SAVE is loaded!");

// Verify dependencies
try {
    if (typeof axios === 'undefined') throw new Error("Axios not loaded");
    if (typeof Swal === 'undefined') throw new Error("SweetAlert2 not loaded");
    if (typeof bootstrap === 'undefined') throw new Error("Bootstrap not loaded");
    console.log("All dependencies loaded");
} catch (e) {
    console.error("Dependency error:", e.message);
}

// CSRF Token
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

// Debounce utility
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// =============================================
// AUTO-SAVE FOR PRINCIPAL'S COMMENT
// =============================================

const commentInputs = document.querySelectorAll('.teacher-comment-input');
const saveUrl = document.getElementById('commentsForm')?.action || null;

if (commentInputs.length > 0 && saveUrl) {
    console.log(`Auto-save enabled for ${commentInputs.length} comment fields`);

    commentInputs.forEach(input => {
        // Create status indicator next to input
        const indicator = document.createElement('small');
        indicator.className = 'auto-save-status text-muted ms-2';
        indicator.style.fontSize = '0.8em';
        input.parentNode.appendChild(indicator);

        let originalValue = input.value.trim();

        const autoSave = debounce(function () {
            const currentValue = input.value.trim();
            const studentId = input.name.match(/teacher_comments\[(\d+)\]/)?.[1];

            if (!studentId || currentValue === originalValue) {
                indicator.textContent = '';
                return;
            }

            indicator.textContent = 'Saving...';
            indicator.style.color = '#0d6efd';

            axios.post(saveUrl, {
                teacher_comments: { [studentId]: currentValue }
            })
            .then(response => {
                originalValue = currentValue;
                indicator.textContent = '✓ Saved';
                indicator.style.color = '#198754';
                setTimeout(() => {
                    if (indicator.textContent === '✓ Saved') {
                        indicator.textContent = '';
                    }
                }, 2000);
            })
            .catch(error => {
                console.error("Auto-save failed:", error);
                indicator.textContent = '✗ Failed';
                indicator.style.color = '#dc3545';
                setTimeout(() => {
                    if (indicator.textContent === '✗ Failed') {
                        indicator.textContent = '(click to retry)';
                        indicator.style.cursor = 'pointer';
                        indicator.onclick = () => autoSave(); // Retry on click
                    }
                }, 2000);
            });
        }, 1500); // Save after 1.5s of no typing

        input.addEventListener('input', autoSave);
        input.addEventListener('blur', autoSave); // Also save on blur
    });
}

// =============================================
// REGULAR FORM SAVE (Manual "Save All" button)
// =============================================

const commentsForm = document.getElementById('commentsForm');
if (commentsForm) {
    commentsForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving all...';

        const formData = new FormData(this);

        axios.post(this.action, formData)
            .then(response => {
                Swal.fire({
                    icon: 'success',
                    title: 'All Saved!',
                    text: response.data.message || "All Principal's comments saved successfully!",
                    timer: 2500,
                    showConfirmButton: false
                });

                // Update original values so auto-save doesn't trigger unnecessarily
                document.querySelectorAll('.teacher-comment-input').forEach(input => {
                    const currentValue = input.value.trim();
                    // We don't have originalValue per input here, but auto-save will handle it
                });
            })
            .catch(error => {
                let msg = "Failed to save.";
                if (error.response?.data?.message) msg = error.response.data.message;
                else if (error.response?.data?.errors) {
                    msg = Object.values(error.response.data.errors).flat().join(', ');
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Save Failed',
                    text: msg,
                });
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
    });
}

// =============================================
// SEARCH FUNCTIONALITY (Desktop + Mobile)
// =============================================

const searchInput = document.getElementById('searchInput');
const resultsCount = document.getElementById('resultsCount');

if (searchInput) {
    searchInput.addEventListener('input', debounce(function () {
        const term = this.value.toLowerCase().trim();

        // Desktop rows
        const desktopRows = document.querySelectorAll('.desktop-table .student-row');
        let desktopVisible = 0;
        desktopRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (term === '' || text.includes(term)) {
                row.style.display = '';
                desktopVisible++;
            } else {
                row.style.display = 'none';
            }
        });

        // Mobile cards
        const mobileCards = document.querySelectorAll('.mobile-cards .student-card');
        let mobileVisible = 0;
        mobileCards.forEach(card => {
            const content = (card.getAttribute('data-search-content') || '').toLowerCase();
            if (term === '' || content.includes(term)) {
                card.style.display = '';
                mobileVisible++;
            } else {
                card.style.display = 'none';
            }
        });

        const total = desktopVisible + mobileVisible;
        if (resultsCount) {
            if (term && total > 0) {
                resultsCount.textContent = `${total} result(s) found`;
                resultsCount.style.display = 'block';
            } else if (term && total === 0) {
                resultsCount.textContent = 'No matches found';
                resultsCount.style.display = 'block';
            } else {
                resultsCount.style.display = 'none';
            }
        }

        const noMobile = document.getElementById('noMobileResults');
        if (noMobile) {
            noMobile.style.display = (mobileVisible === 0 && term) ? 'block' : 'none';
        }
    }, 300));
}

// =============================================
// CLEANUP MODALS
// =============================================

document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('hidden.bs.modal', () => {
        document.body.classList.remove('modal-open');
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });
});

console.log("myprincipalscomment.init.js with AUTO-SAVE fully initialized!");
