/**
 * subjectvetting.init.js
 * Subject Vetting Management — Complete Script
 * Fix: Stats + chart read from List.js matchingItems (all pages),
 *      filter uses List.js .filter() instead of DOM row hiding,
 *      pagination works correctly across all pages.
 */

'use strict';

// ─── State ────────────────────────────────────────────────────────────────────
let currentView         = 'table';
let currentCardPage     = 1;
const cardsPerPage      = 9;
let currentTermFilter   = '';
let currentSessionFilter = '';
let subjectVettingList  = null;
let vettingStatusChart  = null;
let deleteId            = null;

// ─── Stats (reads ALL matchingItems, not just visible page) ──────────────────
function updateStatsFromList() {
    if (!subjectVettingList) return;

    const items = subjectVettingList.matchingItems; // full filtered set
    let total = items.length, pending = 0, completed = 0, rejected = 0;

    items.forEach(item => {
        const status = (item.elm.getAttribute('data-status') || 'pending').toLowerCase().trim();
        if (status === 'pending')   pending++;
        else if (status === 'completed') completed++;
        else if (status === 'rejected')  rejected++;
    });

    document.getElementById('stat-total').textContent     = total;
    document.getElementById('stat-pending').textContent   = pending;
    document.getElementById('stat-completed').textContent = completed;
    document.getElementById('stat-rejected').textContent  = rejected;

    const showingEl = document.getElementById('showing-records');
    const totalEl   = document.getElementById('total-records-footer');
    if (showingEl) showingEl.textContent = Math.min(total, 10);
    if (totalEl)   totalEl.textContent   = total;

    updateChart(pending, completed, rejected);
    if (currentView === 'card') renderCardView();
}

// ─── Chart ────────────────────────────────────────────────────────────────────
function updateChart(pending, completed, rejected) {
    if (!vettingStatusChart) return;
    vettingStatusChart.data.datasets[0].data = [pending, completed, rejected];
    vettingStatusChart.update('none');
}

function initializeVettingStatusChart() {
    const ctx = document.getElementById('vettingStatusChart')?.getContext('2d');
    if (!ctx) return;
    if (vettingStatusChart) vettingStatusChart.destroy();

    vettingStatusChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Pending', 'Completed', 'Rejected'],
            datasets: [{
                label: 'Vetting Assignments',
                data: [0, 0, 0],
                backgroundColor: ['#dc3545', '#28a745', '#ffc107'],
                borderWidth: 1,
                borderRadius: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            plugins: { legend: { display: false } }
        }
    });
}

// ─── List.js Filter (term + session) ─────────────────────────────────────────
function applyListFilter() {
    if (!subjectVettingList) return;

    if (!currentTermFilter && !currentSessionFilter) {
        subjectVettingList.filter(); // clear — show all
    } else {
        subjectVettingList.filter(item => {
            const el         = item.elm;
            const rowTerm    = el.getAttribute('data-term')    || '';
            const rowSession = el.getAttribute('data-session') || '';
            const termOk    = !currentTermFilter    || rowTerm    === currentTermFilter;
            const sessionOk = !currentSessionFilter || rowSession === currentSessionFilter;
            return termOk && sessionOk;
        });
    }

    currentCardPage = 1;
    // 'updated' event fires automatically → updateStatsFromList() called there
}

// ─── List.js Init ─────────────────────────────────────────────────────────────
function initializeListJS() {
    try {
        subjectVettingList = new List('subjectVettingList', {
            valueNames: [
                'sn', 'vetting_username', 'subjectname', 'sclass',
                'schoolarm', 'teachername', 'termname', 'sessionname',
                'status', 'datereg'
            ],
            page: 10,
            pagination: { paginationClass: 'listjs-pagination' }
        });

        subjectVettingList.on('updated', updateStatsFromList);
        console.log('✅ List.js initialized');
    } catch (e) {
        console.error('List.js init failed:', e);
    }
}

// ─── Card View ────────────────────────────────────────────────────────────────
function renderCardView() {
    const container = document.getElementById('cardsContainer');
    if (!container || !subjectVettingList) return;

    const items = subjectVettingList.matchingItems; // respects search + filter
    container.innerHTML = '';

    if (!items.length) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="ri-inbox-line fs-48 text-muted"></i>
                <h5 class="mt-3">No Subject Vetting Assignments Found</h5>
            </div>`;
        document.getElementById('card-showing-records').textContent = 0;
        document.getElementById('card-total-records').textContent   = 0;
        renderCardPagination(0, 0);
        return;
    }

    const totalPages = Math.ceil(items.length / cardsPerPage);
    if (currentCardPage > totalPages) currentCardPage = totalPages || 1;

    const start     = (currentCardPage - 1) * cardsPerPage;
    const pageItems = items.slice(start, start + cardsPerPage);

    pageItems.forEach(item => {
        const row        = item.elm;
        const vettingName = row.querySelector('.vetting_username h6')?.textContent.trim() || 'N/A';
        const subject    = row.querySelector('.subjectname .fw-medium')?.textContent.trim() || 'N/A';
        const subjectCode = row.querySelector('.subjectname small')?.textContent.trim() || '';
        const sclass     = row.querySelector('.sclass')?.textContent.trim()     || 'N/A';
        const arm        = row.querySelector('.schoolarm')?.textContent.trim()  || '';
        const teacher    = row.querySelector('.teachername')?.textContent.trim()|| 'N/A';
        const term       = row.querySelector('.termname')?.textContent.trim()   || 'N/A';
        const session    = row.querySelector('.sessionname')?.textContent.trim()|| 'N/A';
        const statusText = row.querySelector('.status span')?.textContent.trim()|| 'Pending';
        const updated    = row.querySelector('.datereg small')?.textContent.trim() || 'N/A';

        const statusLower = statusText.toLowerCase();
        const statusClass = statusLower.includes('completed') ? 'completed'
                          : statusLower.includes('pending')   ? 'pending' : 'rejected';
        const icon = statusLower.includes('completed') ? 'ri-checkbox-circle-line'
                   : statusLower.includes('pending')   ? 'ri-time-line' : 'ri-close-circle-line';
        const badgeClass = statusClass === 'completed' ? 'badge-completed'
                         : statusClass === 'pending'   ? 'badge-pending' : 'badge-rejected';

        // Grab data attributes for edit/delete
        const svid           = row.getAttribute('data-id')    || '';
        const deleteUrl      = row.getAttribute('data-url')   || '';
        const vettingUserId  = row.querySelector('.vetting_username')?.getAttribute('data-vetting_userid') || '';
        const subjectclassid = row.querySelector('.subjectname')?.getAttribute('data-subjectclassid')      || '';
        const termid         = row.querySelector('.termname')?.getAttribute('data-termid')                 || '';
        const sessionid      = row.querySelector('.sessionname')?.getAttribute('data-sessionid')           || '';
        const subtid         = row.querySelector('.teachername')?.getAttribute('data-subtid')              || '';

        container.insertAdjacentHTML('beforeend', `
            <div class="col-md-6 col-xl-4">
                <div class="vetting-card ${statusClass}-card"
                     data-id="${svid}"
                     data-url="${deleteUrl}"
                     data-status="${statusClass}"
                     data-term="${termid}"
                     data-session="${sessionid}">
                    <div class="card-header-info">
                        <div class="staff-info-card">
                            <div class="staff-avatar-card">${vettingName.charAt(0).toUpperCase()}</div>
                            <div>
                                <h6 class="mb-0">${vettingName}</h6>
                                <small class="text-muted">Vetting Staff</small>
                            </div>
                        </div>
                        <span class="badge-status ${badgeClass}">
                            <i class="${icon} me-1"></i>${statusText}
                        </span>
                    </div>
                    <div class="card-details">
                        <div class="detail-item">
                            <i class="ri-book-open-line"></i>
                            <span><strong>Subject:</strong> ${subject}${subjectCode ? ` <small class="text-muted">(${subjectCode})</small>` : ''}</span>
                        </div>
                        <div class="detail-item">
                            <i class="ri-group-line"></i>
                            <span><strong>Class:</strong> ${sclass}${arm ? ` (${arm})` : ''}</span>
                        </div>
                        <div class="detail-item">
                            <i class="ri-user-line"></i>
                            <span><strong>Teacher:</strong> ${teacher}</span>
                        </div>
                        <div class="detail-item">
                            <i class="ri-calendar-line"></i>
                            <span><strong>Term:</strong> ${term}</span>
                        </div>
                        <div class="detail-item">
                            <i class="ri-calendar-event-line"></i>
                            <span><strong>Session:</strong> ${session}</span>
                        </div>
                        <div class="detail-item">
                            <i class="ri-time-line"></i>
                            <span><strong>Updated:</strong> ${updated}</span>
                        </div>
                    </div>
                    <div class="card-actions">
                        <button class="btn btn-light btn-sm action-btn card-edit-btn"
                                data-id="${svid}"
                                data-vetting_userid="${vettingUserId}"
                                data-subjectclassid="${subjectclassid}"
                                data-termid="${termid}"
                                data-sessionid="${sessionid}"
                                data-status="${statusClass}"
                                title="Edit">
                            <i class="ri-pencil-line"></i>
                        </button>
                        <button class="btn btn-light btn-sm action-btn text-danger card-delete-btn"
                                data-id="${svid}"
                                data-url="${deleteUrl}"
                                title="Delete">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </div>
            </div>`);
    });

    document.getElementById('card-showing-records').textContent = pageItems.length;
    document.getElementById('card-total-records').textContent   = items.length;
    renderCardPagination(items.length, totalPages);
}

function renderCardPagination(total, totalPages) {
    const ul = document.querySelector('.card-pagination');
    if (!ul) return;
    ul.innerHTML = '';
    if (totalPages <= 1) return;

    const prev = document.createElement('li');
    prev.className = `page-item${currentCardPage === 1 ? ' disabled' : ''}`;
    prev.innerHTML = `<a class="page-link" href="#">&laquo;</a>`;
    prev.addEventListener('click', e => {
        e.preventDefault();
        if (currentCardPage > 1) { currentCardPage--; renderCardView(); }
    });
    ul.appendChild(prev);

    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item${i === currentCardPage ? ' active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        li.addEventListener('click', e => {
            e.preventDefault();
            currentCardPage = i;
            renderCardView();
        });
        ul.appendChild(li);
    }

    const next = document.createElement('li');
    next.className = `page-item${currentCardPage === totalPages ? ' disabled' : ''}`;
    next.innerHTML = `<a class="page-link" href="#">&raquo;</a>`;
    next.addEventListener('click', e => {
        e.preventDefault();
        if (currentCardPage < totalPages) { currentCardPage++; renderCardView(); }
    });
    ul.appendChild(next);
}

// ─── Term & Session Filters ───────────────────────────────────────────────────
function initializeTermAndSessionFilters() {
    const termFilter    = document.getElementById('term-filter-stats');
    const sessionFilter = document.getElementById('session-filter-stats');
    const resetBtn      = document.getElementById('reset-stats-btn');

    if (termFilter) {
        termFilter.addEventListener('change', () => {
            currentTermFilter = termFilter.value;
            applyListFilter();
        });
    }

    if (sessionFilter) {
        sessionFilter.addEventListener('change', () => {
            currentSessionFilter = sessionFilter.value;
            applyListFilter();
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            currentTermFilter = currentSessionFilter = '';
            if (termFilter)    termFilter.value    = '';
            if (sessionFilter) sessionFilter.value = '';
            applyListFilter();
        });
    }
}

// ─── Stat Card Click Filter ───────────────────────────────────────────────────
function initializeStatCardFilters() {
    document.querySelectorAll('.stat-card-clickable').forEach(card => {
        card.addEventListener('click', () => {
            const status = card.getAttribute('data-status');

            document.querySelectorAll('.stat-card-clickable').forEach(c => c.classList.remove('active-stat'));
            card.classList.add('active-stat');

            if (!subjectVettingList) return;

            if (status === 'all') {
                subjectVettingList.filter(item => {
                    return applyTermSessionFilter(item);
                });
            } else {
                subjectVettingList.filter(item => {
                    const itemStatus = (item.elm.getAttribute('data-status') || 'pending').toLowerCase();
                    return itemStatus === status && applyTermSessionFilter(item);
                });
            }
            currentCardPage = 1;
        });
    });
}

function applyTermSessionFilter(item) {
    const el         = item.elm;
    const rowTerm    = el.getAttribute('data-term')    || '';
    const rowSession = el.getAttribute('data-session') || '';
    const termOk    = !currentTermFilter    || rowTerm    === currentTermFilter;
    const sessionOk = !currentSessionFilter || rowSession === currentSessionFilter;
    return termOk && sessionOk;
}

// ─── View Toggle ──────────────────────────────────────────────────────────────
function initializeViewToggle() {
    const tableBtn       = document.getElementById('tableViewBtn');
    const cardBtn        = document.getElementById('cardViewBtn');
    const tableContainer = document.querySelector('.table-view-container');
    const cardContainer  = document.getElementById('cardViewContainer');
    if (!tableBtn || !cardBtn) return;

    tableBtn.addEventListener('click', () => {
        currentView = 'table';
        tableBtn.classList.add('active');    cardBtn.classList.remove('active');
        tableContainer?.classList.remove('hide'); cardContainer?.classList.remove('active');
    });

    cardBtn.addEventListener('click', () => {
        currentView = 'card';
        cardBtn.classList.add('active');     tableBtn.classList.remove('active');
        tableContainer?.classList.add('hide');   cardContainer?.classList.add('active');
        currentCardPage = 1;
        renderCardView();
    });
}

// ─── Add Assignment ───────────────────────────────────────────────────────────
function initializeAddForm() {
    const form = document.getElementById('add-subjectvetting-form');
    if (!form) return;

    // Subject-class search
    const searchInput = document.getElementById('subjectClassSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const items = document.querySelectorAll('.subject-class-item');
            let hasResults = false;

            items.forEach(item => {
                const text = (item.getAttribute('data-search') || '').toLowerCase();
                const match = !query || text.includes(query);
                item.style.display = match ? '' : 'none';
                if (match && item.classList.contains('current-session-item')) hasResults = true;
            });

            const noResults = document.getElementById('noResultsMessage');
            if (noResults) noResults.style.display = hasResults ? 'none' : 'block';
        });
    }

    // Selection counter
    const checkboxes = document.querySelectorAll('#subjectClassList .form-check-input[name="subjectclassid[]"]');
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectionCount);
    });

    const clearBtn = document.getElementById('clearSelectionBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            checkboxes.forEach(cb => cb.checked = false);
            updateSelectionCount();
        });
    }

    // Form submit
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitAddForm();
    });
}

function updateSelectionCount() {
    const checked = document.querySelectorAll('#subjectClassList .form-check-input[name="subjectclassid[]"]:checked').length;
    const total   = document.querySelectorAll('#subjectClassList .form-check-input[name="subjectclassid[]"]').length;
    const countEl = document.getElementById('selectedCount');
    const totalEl = document.getElementById('totalCount');
    if (countEl) countEl.textContent = checked;
    if (totalEl) totalEl.textContent = total;
}

function submitAddForm() {
    const form    = document.getElementById('add-subjectvetting-form');
    const errorEl = document.getElementById('alert-error-msg');
    const addBtn  = document.getElementById('add-btn');

    const formData = new FormData(form);

    // Validate
    if (!formData.get('userid')) {
        showFormError(errorEl, 'Please select a vetting staff member.');
        return;
    }
    if (!formData.get('sessionid')) {
        showFormError(errorEl, 'Please select a session.');
        return;
    }
    if (!formData.getAll('termid[]').length) {
        showFormError(errorEl, 'Please select at least one term.');
        return;
    }
    if (!formData.getAll('subjectclassid[]').length) {
        showFormError(errorEl, 'Please select at least one subject-class assignment.');
        return;
    }

    if (errorEl) errorEl.classList.add('d-none');
    if (addBtn)  addBtn.disabled = true;

    fetch(window.subjectVettingStoreUrl || '/subject-vettings', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success || data.status === 'success') {
            const modal = bootstrap.Modal.getInstance(document.getElementById('addSubjectVettingModal'));
            if (modal) modal.hide();
            showToast('Assignment created successfully!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showFormError(errorEl, data.message || 'An error occurred. Please try again.');
        }
    })
    .catch(() => {
        showFormError(errorEl, 'Network error. Please try again.');
    })
    .finally(() => {
        if (addBtn) addBtn.disabled = false;
    });
}

// ─── Edit Assignment ──────────────────────────────────────────────────────────
function initializeEditForm() {
    const form = document.getElementById('edit-subjectvetting-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitEditForm();
    });

    // Table edit buttons
    document.querySelector('#kt_subject_vetting_table tbody')?.addEventListener('click', function (e) {
        const btn = e.target.closest('.edit-item-btn');
        if (!btn) return;
        const row = btn.closest('tr');
        if (row) populateEditModal(row);
    });

    // Card edit buttons (delegated)
    document.getElementById('cardsContainer')?.addEventListener('click', function (e) {
        const btn = e.target.closest('.card-edit-btn');
        if (!btn) return;
        populateEditModalFromCard(btn);
    });
}

function populateEditModal(row) {
    const svid         = row.getAttribute('data-id')     || '';
    const vettingUserId = row.querySelector('.vetting_username')?.getAttribute('data-vetting_userid') || '';
    const termid       = row.querySelector('.termname')?.getAttribute('data-termid')                   || '';
    const sessionid    = row.querySelector('.sessionname')?.getAttribute('data-sessionid')             || '';
    const subjectclassid = row.querySelector('.subjectname')?.getAttribute('data-subjectclassid')      || '';
    const status       = row.getAttribute('data-status') || 'pending';

    document.getElementById('edit-id-field').value       = svid;
    document.getElementById('edit-userid').value         = vettingUserId;
    document.getElementById('edit-termid').value         = termid;
    document.getElementById('edit-sessionid').value      = sessionid;
    document.getElementById('edit-subjectclassid').value = subjectclassid;
    document.getElementById('edit-status').value         = status;

    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

function populateEditModalFromCard(btn) {
    document.getElementById('edit-id-field').value       = btn.getAttribute('data-id')            || '';
    document.getElementById('edit-userid').value         = btn.getAttribute('data-vetting_userid') || '';
    document.getElementById('edit-termid').value         = btn.getAttribute('data-termid')         || '';
    document.getElementById('edit-sessionid').value      = btn.getAttribute('data-sessionid')      || '';
    document.getElementById('edit-subjectclassid').value = btn.getAttribute('data-subjectclassid') || '';
    document.getElementById('edit-status').value         = btn.getAttribute('data-status')         || 'pending';

    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

function submitEditForm() {
    const form    = document.getElementById('edit-subjectvetting-form');
    const errorEl = document.getElementById('edit-alert-error-msg');
    const updateBtn = document.getElementById('update-btn');
    const id      = document.getElementById('edit-id-field').value;

    if (!id) { showFormError(errorEl, 'Invalid record ID.'); return; }

    const formData = new FormData(form);
    formData.append('_method', 'PUT');

    if (updateBtn) updateBtn.disabled = true;

    fetch(`/subject-vettings/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success || data.status === 'success') {
            const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
            if (modal) modal.hide();
            showToast('Assignment updated successfully!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showFormError(errorEl, data.message || 'An error occurred.');
        }
    })
    .catch(() => showFormError(errorEl, 'Network error. Please try again.'))
    .finally(() => { if (updateBtn) updateBtn.disabled = false; });
}

// ─── Delete ───────────────────────────────────────────────────────────────────
function initializeDelete() {
    // Table delete buttons
    document.querySelector('#kt_subject_vetting_table tbody')?.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-item-btn');
        if (!btn) return;
        const row = btn.closest('tr');
        if (row) {
            deleteId = row.getAttribute('data-id');
            window._deleteUrl = row.getAttribute('data-url');
            const modal = new bootstrap.Modal(document.getElementById('deleteRecordModal'));
            modal.show();
        }
    });

    // Card delete buttons (delegated)
    document.getElementById('cardsContainer')?.addEventListener('click', function (e) {
        const btn = e.target.closest('.card-delete-btn');
        if (!btn) return;
        deleteId = btn.getAttribute('data-id');
        window._deleteUrl = btn.getAttribute('data-url');
        const modal = new bootstrap.Modal(document.getElementById('deleteRecordModal'));
        modal.show();
    });

    // Confirm delete
    document.getElementById('delete-record')?.addEventListener('click', function () {
        if (!deleteId || !window._deleteUrl) return;

        this.disabled = true;

        fetch(window._deleteUrl, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteRecordModal'));
            if (modal) modal.hide();
            if (data.success || data.status === 'success') {
                showToast('Record deleted successfully!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(data.message || 'Could not delete record.', 'danger');
            }
        })
        .catch(() => showToast('Network error. Please try again.', 'danger'))
        .finally(() => { this.disabled = false; });
    });
}

// ─── Bulk Delete ──────────────────────────────────────────────────────────────
function initializeBulkDelete() {
    const checkAll  = document.getElementById('checkAll');
    const removeBtn = document.getElementById('remove-actions');

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            document.querySelectorAll('input[name="chk_child"]').forEach(cb => {
                cb.checked = this.checked;
            });
            toggleRemoveBtn();
        });
    }

    document.querySelector('#kt_subject_vetting_table tbody')?.addEventListener('change', function (e) {
        if (e.target.name === 'chk_child') toggleRemoveBtn();
    });

    function toggleRemoveBtn() {
        const anyChecked = document.querySelectorAll('input[name="chk_child"]:checked').length > 0;
        if (removeBtn) removeBtn.classList.toggle('d-none', !anyChecked);
    }
}

window.deleteMultiple = function () {
    const ids = Array.from(document.querySelectorAll('input[name="chk_child"]:checked')).map(cb => cb.value);
    if (!ids.length) return;

    if (!confirm(`Are you sure you want to delete ${ids.length} record(s)?`)) return;

    fetch('/subject-vettings/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ ids })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success || data.status === 'success') {
            showToast(`${ids.length} record(s) deleted.`, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Could not delete records.', 'danger');
        }
    })
    .catch(() => showToast('Network error.', 'danger'));
};

// ─── Image Preview Modal ──────────────────────────────────────────────────────
function initializeImagePreview() {
    document.querySelectorAll('.staff-image').forEach(img => {
        img.addEventListener('click', function () {
            const src         = this.getAttribute('data-image')      || this.src;
            const teacherName = this.getAttribute('data-teachername') || '';
            const previewImg  = document.getElementById('preview-image');
            const previewName = document.getElementById('preview-teachername');
            if (previewImg)  previewImg.src         = src;
            if (previewName) previewName.textContent = teacherName;
        });
    });
}

// ─── Search ───────────────────────────────────────────────────────────────────
function initializeSearch() {
    const searchInput = document.querySelector('.search-box input.search');
    if (!searchInput) return;

    searchInput.addEventListener('input', () => {
        if (subjectVettingList) {
            subjectVettingList.search(searchInput.value);
            // 'updated' event fires → updateStatsFromList() called automatically
        }
    });
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function showFormError(el, message) {
    if (!el) return;
    el.textContent = message;
    el.classList.remove('d-none');
}

function showToast(message, type = 'success') {
    // Uses Bootstrap toasts if available, else alert
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        // Fallback: simple alert-style notification
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        alertDiv.style.zIndex = 9999;
        alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 3500);
        return;
    }

    const id   = 'toast-' + Date.now();
    const html = `
        <div id="${id}" class="toast align-items-center text-white bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;
    toastContainer.insertAdjacentHTML('beforeend', html);
    const toast = new bootstrap.Toast(document.getElementById(id));
    toast.show();
    setTimeout(() => document.getElementById(id)?.remove(), 4000);
}

// ─── Boot ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    initializeListJS();
    initializeVettingStatusChart();
    initializeTermAndSessionFilters();
    initializeStatCardFilters();
    initializeViewToggle();
    initializeAddForm();
    initializeEditForm();
    initializeDelete();
    initializeBulkDelete();
    initializeImagePreview();
    initializeSearch();

    // Initial stats load after List.js has settled
    setTimeout(updateStatsFromList, 250);

    console.log('✅ Subject Vetting fully initialized');
});
