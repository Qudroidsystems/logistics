console.log("mymocksubjectvetting.init.js is loaded and executing!");

let chartInitCount = 0;

try {
    if (typeof axios === 'undefined') throw new Error("Axios is not loaded");
    if (typeof Swal === 'undefined') throw new Error("SweetAlert2 is not loaded");
    if (typeof bootstrap === 'undefined') throw new Error("Bootstrap is not loaded");
    if (typeof List === 'undefined') throw new Error("List.js is not loaded");
    if (typeof Chart === 'undefined') throw new Error("Chart.js is not loaded");
    console.log("All dependencies loaded successfully");
} catch (error) {
    console.error("Dependency check failed:", error);
}

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
if (!csrfToken) console.warn("CSRF token not found");

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

let vettingStatusChart;

function initializeVettingStatusChart() {
    chartInitCount++;
    console.log(`Attempting to initialize chart (attempt #${chartInitCount}) with data:`, window.vettingStatusCounts);
    
    const ctx = document.getElementById('vettingStatusChart')?.getContext('2d');
    if (!ctx) {
        console.error("Chart canvas not found");
        return;
    }

    if (!window.vettingStatusCounts) {
        console.warn("vettingStatusCounts is undefined, using default data");
        window.vettingStatusCounts = { pending: 0, completed: 0, rejected: 0 };
    }

    if (vettingStatusChart) {
        console.log("Destroying existing chart instance");
        vettingStatusChart.destroy();
    }

    try {
        vettingStatusChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Pending', 'Completed', 'Rejected'],
                datasets: [{
                    label: 'Mock Vetting Assignments',
                    data: [
                        window.vettingStatusCounts.pending || 0,
                        window.vettingStatusCounts.completed || 0,
                        window.vettingStatusCounts.rejected || 0
                    ],
                    backgroundColor: ['#ffce56', '#36c6d3', '#ff6384'],
                    borderColor: ['#e0b446', '#2aa8b3', '#e05574'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: Math.max(10, (window.vettingStatusCounts.pending || 0) + 2),
                        ticks: { stepSize: 1 },
                        title: { display: true, text: 'Number of Assignments' }
                    },
                    x: { title: { display: true, text: 'Status' } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}`;
                            }
                        }
                    }
                }
            }
        });
        console.log("Vetting status chart initialized successfully");
    } catch (error) {
        console.error("Failed to initialize chart:", error);
    }
}

let mockSubjectVettingList;

function initializeListJS() {
    const mockSubjectVettingListContainer = document.getElementById('kt_mock_subject_vetting_table');
    const hasRows = document.querySelectorAll('#kt_mock_subject_vetting_table tbody tr:not(.noresult)').length > 0;
    
    if (mockSubjectVettingListContainer && hasRows) {
        try {
            if (mockSubjectVettingList) {
                mockSubjectVettingList.clear();
            }
            
            mockSubjectVettingList = new List('mockSubjectVettingList', {
                valueNames: ['subjectname', 'teachername', 'sclass', 'schoolarm', 'termname', 'sessionname', 'status'],
                page: 10,
                pagination: {
                    innerWindow: 2,
                    outerWindow: 1,
                    left: 0,
                    right: 0,
                    paginationClass: "listjs-pagination"
                },
                listClass: 'list'
            });
            
            console.log("List.js initialized with pagination");
            
            mockSubjectVettingList.on('updated', function () {
                const totalRecords = mockSubjectVettingList.items.length;
                const visibleRecords = mockSubjectVettingList.visibleItems.length;
                const showingRecords = Math.min(visibleRecords, mockSubjectVettingList.page);
                const currentPage = Math.ceil((mockSubjectVettingList.i - 1) / mockSubjectVettingList.page) + 1;
                
                document.getElementById('showing-records').textContent = showingRecords;
                document.getElementById('total-records').textContent = totalRecords;
                document.getElementById('total-records-footer').textContent = totalRecords;
                
                const pagination = document.querySelector('.listjs-pagination');
                if (pagination) {
                    pagination.querySelectorAll('.page').forEach(page => {
                        page.classList.remove('active');
                        if (parseInt(page.textContent) === currentPage) {
                            page.classList.add('active');
                        }
                    });
                }
                
                const noResultRow = document.querySelector('.noresult');
                if (noResultRow) {
                    noResultRow.style.display = visibleRecords === 0 ? 'block' : 'none';
                }

                updateButtonVisibility();
            });
            
            mockSubjectVettingList.update();
            
        } catch (error) {
            console.error("List.js initialization failed:", error);
        }
    } else {
        console.warn("No mock subject vetting assignments available for List.js initialization");
        document.getElementById('showing-records').textContent = 0;
        document.getElementById('total-records').textContent = 0;
        document.getElementById('total-records-footer').textContent = 0;
        updateButtonVisibility();
    }
}

function filterData() {
    const searchInput = document.querySelector(".search-box input.search");
    const termFilter = document.getElementById("idTerm")?.value || "all";
    const sessionFilter = document.getElementById("idSession")?.value || "all";
    const searchValue = searchInput?.value || "";
    
    console.log("Filtering with:", { search: searchValue, term: termFilter, session: sessionFilter });
    
    if (mockSubjectVettingList) {
        mockSubjectVettingList.filter(function(item) {
            const matchesSearch = !searchValue || 
                item.values().subjectname.toLowerCase().includes(searchValue.toLowerCase()) ||
                item.values().teachername.toLowerCase().includes(searchValue.toLowerCase()) ||
                item.values().sclass.toLowerCase().includes(searchValue.toLowerCase()) ||
                item.values().schoolarm.toLowerCase().includes(searchValue.toLowerCase());
            
            const matchesTerm = termFilter === "all" || item.values().termname === termFilter;
            const matchesSession = sessionFilter === "all" || item.values().sessionname === sessionFilter;
            
            return matchesSearch && matchesTerm && matchesSession;
        });
        mockSubjectVettingList.update();
    }
}

function updateButtonVisibility(itemId = null) {
    const updateBtn = document.getElementById('update-btn');
    if (!updateBtn) return;

    if (itemId) {
        const vetting = window.vettingData.find(v => v.svid == itemId);
        if (vetting) {
            updateBtn.classList.toggle('d-none', !vetting.all_vetted);
        } else {
            updateBtn.classList.add('d-none');
        }
    } else {
        updateBtn.classList.add('d-none');
    }
}

let isRefreshing = false;

function refreshTable() {
    if (isRefreshing) {
        console.log("Refresh table skipped: Previous request still in progress");
        return;
    }

    isRefreshing = true;
    console.log("Refreshing table and chart via AJAX...");
    
    axios.get('/mymocksubjectvettings', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.data || !response.data.mocksubjectvettings) {
            throw new Error('Invalid response data structure');
        }

        console.log("Refresh response:", response.data);

        if (mockSubjectVettingList) {
            mockSubjectVettingList.clear();
            
            response.data.mocksubjectvettings.forEach((item, index) => {
                mockSubjectVettingList.add({
                    subjectname: `${item.subjectname} ${item.subjectcode ? `(${item.subjectcode})` : ''}`,
                    teachername: item.teachername || '',
                    sclass: item.sclass,
                    schoolarm: item.schoolarm || '',
                    termname: item.termname,
                    sessionname: item.sessionname,
                    status: item.status
                });

                const row = document.querySelector(`tr[data-id="${item.svid}"]`);
                if (row) {
                    row.setAttribute('data-all-vetted', item.all_vetted ? 'true' : 'false');
                    const editBtn = row.querySelector('.edit-item-btn');
                    if (editBtn) {
                        editBtn.classList.toggle('disabled', !item.all_vetted);
                        editBtn.setAttribute('title', item.all_vetted ? 'Edit vetting status' : 'All scores must be vetted');
                    }
                }
            });
            
            mockSubjectVettingList.update();
            window.vettingStatusCounts = response.data.statusCounts || { 
                pending: 0, 
                completed: 0, 
                rejected: 0 
            };
            window.vettingData = response.data.mocksubjectvettings.map(item => ({
                svid: item.svid,
                all_vetted: item.all_vetted
            }));
            initializeVettingStatusChart();
            updateButtonVisibility();
        }
    })
    .catch(error => {
        console.error("Error refreshing table and chart:", error);
        
        let errorMessage = "An error occurred while refreshing the data.";
        if (error.response) {
            errorMessage = error.response.data.message || errorMessage;
        }
        
        Swal.fire({
            icon: "error",
            title: "Error refreshing data",
            text: errorMessage,
            showConfirmButton: true
        });
    })
    .finally(() => {
        isRefreshing = false;
    });
}

document.addEventListener('click', function (e) {
    const editBtn = e.target.closest('.edit-item-btn');
    if (editBtn && !editBtn.classList.contains('disabled')) {
        e.preventDefault();
        console.log("Edit button clicked");
        
        const tr = editBtn.closest("tr");
        const itemId = tr.getAttribute("data-id");
        const status = tr.querySelector(".status")?.textContent || "pending";
        
        if (!itemId) {
            console.error("Item ID not found");
            return;
        }
        
        console.log("Edit data:", { itemId, status });
        
        const editIdField = document.getElementById("edit-id-field");
        const editStatusField = document.getElementById("edit-status");
        
        if (editIdField) editIdField.value = itemId;
        if (editStatusField) editStatusField.value = status;

        try {
            const modal = new bootstrap.Modal(document.getElementById("editModal"));
            modal.show();
            console.log("Edit modal opened with data populated");
            updateButtonVisibility(itemId);
        } catch (error) {
            console.error("Error opening edit modal:", error);
            Swal.fire({
                position: "center",
                icon: "error",
                title: "Error opening edit modal",
                text: "Please try again or contact support.",
                showConfirmButton: true
            });
        }
    }
});

const editMockSubjectVettingForm = document.getElementById("edit-mocksubjectvetting-form");
if (editMockSubjectVettingForm) {
    editMockSubjectVettingForm.addEventListener("submit", function (e) {
        e.preventDefault();
        console.log("Edit form submitted");
        
        const errorMsg = document.getElementById("edit-alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");
        
        const formData = new FormData(editMockSubjectVettingForm);
        const status = formData.get('status');
        const id = formData.get('id');
        
        if (!id || !status) {
            if (errorMsg) {
                errorMsg.innerHTML = "Please select a status";
                errorMsg.classList.remove("d-none");
                console.warn("Form validation failed: Invalid fields");
            }
            return;
        }
        
        const submitBtn = document.getElementById("update-btn");
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = "Updating...";
        }
        
        console.log("Sending edit request:", { id, status });
        
        axios.put(`/mymocksubjectvettings/${id}`, { status }, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(function (response) {
            console.log("Edit success:", response.data);
            
            const modalElement = document.getElementById("editModal");
            const modal = bootstrap.Modal.getInstance(modalElement);
            
            if (modal) {
                modal.hide();
            }
            
            setTimeout(() => {
                Swal.fire({
                    position: "center",
                    icon: "success",
                    title: response.data.message || "Mock vetting status updated successfully!",
                    showConfirmButton: false,
                    timer: 2000,
                    showCloseButton: true
                });
                
                setTimeout(() => refreshTable(), 500);
            }, 300);
        })
        .catch(function (error) {
            console.error("Edit error:", error.response?.data || error);
            
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = "Update";
            }
            
            if (errorMsg) {
                errorMsg.innerHTML = error.response?.data?.message ||
                    Object.values(error.response?.data?.errors || {}).flat().join(", ") ||
                    "Error updating mock vetting status";
                errorMsg.classList.remove("d-none");
            }
        });
    });
}

const editModal = document.getElementById("editModal");
if (editModal) {
    editModal.addEventListener("show.bs.modal", function (event) {
        console.log("Edit modal show event");
        
        const modalLabel = document.getElementById("editModalLabel");
        const updateBtn = document.getElementById("update-btn");
        const itemId = event.relatedTarget.closest('tr')?.getAttribute('data-id');
        
        if (modalLabel) modalLabel.innerHTML = "Update Mock Vetting Status";
        if (updateBtn) {
            updateBtn.innerHTML = "Update";
            updateBtn.disabled = false;
        }
        
        updateButtonVisibility(itemId);
    });
    
    editModal.addEventListener("hidden.bs.modal", function () {
        console.log("Edit modal hidden - cleaning up");
        const editIdField = document.getElementById("edit-id-field");
        const editStatusField = document.getElementById("edit-status");
        if (editIdField) editIdField.value = "";
        if (editStatusField) editStatusField.value = "pending";
        const errorMsg = document.getElementById("edit-alert-error-msg");
        if (errorMsg) errorMsg.classList.add("d-none");
        
        const updateBtn = document.getElementById("update-btn");
        if (updateBtn) {
            updateBtn.disabled = true;
            updateBtn.innerHTML = "Update";
            updateBtn.classList.add('d-none');
        }
        
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });
}

document.removeEventListener('DOMContentLoaded', initializePage);
document.addEventListener('DOMContentLoaded', initializePage);

function initializePage() {
    console.log("DOM fully loaded, initializing components");
    initializeListJS();
    initializeVettingStatusChart();
    
    const searchInput = document.querySelector(".search-box input.search");
    if (searchInput) {
        searchInput.removeEventListener("input", filterDataDebounced);
        searchInput.addEventListener("input", filterDataDebounced);
    }
    
    const termFilter = document.getElementById("idTerm");
    if (termFilter) {
        termFilter.removeEventListener("change", filterDataDebounced);
        termFilter.addEventListener("change", filterDataDebounced);
    }
    
    const sessionFilter = document.getElementById("idSession");
    if (sessionFilter) {
        sessionFilter.removeEventListener("change", filterDataDebounced);
        sessionFilter.addEventListener("change", filterDataDebounced);
    }
}

const filterDataDebounced = debounce(filterData, 300);