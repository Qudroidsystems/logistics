// document.addEventListener('DOMContentLoaded', function () {
//     // Debounce function to limit search execution
//     function debounce(func, wait) {
//         let timeout;
//         return function (...args) {
//             clearTimeout(timeout);
//             timeout = setTimeout(() => func.apply(this, args), wait);
//         };
//     }

//     // Initialize search functionality
//     function initializeSearch(tableId, searchInputId, clearSearchId, countId, noDataAlertId, searchFields) {
//         const searchInput = document.getElementById(searchInputId);
//         const clearSearch = document.getElementById(clearSearchId);
//         const tableBody = document.getElementById(tableId + 'Body');
//         const noDataAlert = document.getElementById(noDataAlertId);
//         const countElement = document.getElementById(countId);

//         if (!searchInput || !tableBody) return;

//         const performSearch = debounce(function () {
//             const searchTerm = searchInput.value.trim().toLowerCase();
//             const rows = tableBody.querySelectorAll('tr:not(#noDataRow)');
//             let visibleRows = 0;

//             rows.forEach(row => {
//                 let match = false;
//                 searchFields.forEach(field => {
//                     const cell = row.querySelector(`.${field}`);
//                     if (cell && (cell.dataset[field] || '').toLowerCase().includes(searchTerm)) {
//                         match = true;
//                     }
//                 });
//                 row.style.display = match ? '' : 'none';
//                 if (match) visibleRows++;
//             });

//             noDataAlert.style.display = visibleRows === 0 && rows.length > 0 ? 'block' : 'none';
//             if (countElement) countElement.textContent = visibleRows;
//             const noDataRow = document.getElementById('noDataRow');
//             if (noDataRow) noDataRow.style.display = rows.length === 0 ? '' : 'none';
//         }, 300);

//         searchInput.addEventListener('input', performSearch);
//         clearSearch.addEventListener('click', function () {
//             searchInput.value = '';
//             performSearch();
//         });
//     }

//     // Initialize checkbox selection
//     function initializeCheckboxes(tableId, checkAllId) {
//         const checkAll = document.getElementById(checkAllId);
//         const tableBody = document.getElementById(tableId + 'Body');

//         if (!checkAll || !tableBody) return;

//         checkAll.addEventListener('change', function () {
//             const checkboxes = tableBody.querySelectorAll('.form-check-input');
//             checkboxes.forEach(checkbox => {
//                 checkbox.checked = this.checked;
//             });
//         });
//     }

//     // Initialize delete functionality
//     function initializeDelete(tableId, deleteSelector) {
//         const tableBody = document.getElementById(tableId + 'Body');
//         if (!tableBody) return;

//         tableBody.addEventListener('click', function (e) {
//             const deleteBtn = e.target.closest(deleteSelector);
//             if (!deleteBtn) return;

//             const url = deleteBtn.dataset.url;
//             if (confirm('Are you sure you want to delete this payment?')) {
//                 fetch(url, {
//                     method: 'POST',
//                     headers: {
//                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
//                         'Content-Type': 'application/json',
//                         'Accept': 'application/json'
//                     },
//                     body: JSON.stringify({})
//                 })
//                 .then(response => response.json())
//                 .then(data => {
//                     if (data.success) {
//                         deleteBtn.closest('tr').remove();
//                         alert(data.message);
//                         const countElement = document.getElementById('paymentCount');
//                         if (countElement) {
//                             countElement.textContent = parseInt(countElement.textContent) - 1;
//                         }
//                         const tableRows = tableBody.querySelectorAll('tr:not(#noDataRow)');
//                         document.getElementById('noDataAlert').style.display = tableRows.length === 0 ? 'block' : 'none';
//                         const noDataRow = document.getElementById('noDataRow');
//                         if (noDataRow) noDataRow.style.display = tableRows.length === 0 ? '' : 'none';
//                         // Update Generate Invoice button
//                         const invoiceBtn = document.querySelector('.btn-primary[href*="invoice"]');
//                         if (invoiceBtn && tableRows.length === 0) {
//                             invoiceBtn.outerHTML = '<button class="btn btn-primary me-2" disabled><i class="ri-download-line me-1"></i> Generate Invoice</button>';
//                         }
//                     } else {
//                         alert(data.message);
//                     }
//                 })
//                 .catch(error => {
//                     console.error('Error:', error);
//                     alert('An error occurred while deleting the payment.');
//                 });
//             }
//         });
//     }

//     // Initialize for studentpayment
//     initializeSearch('paymentsTable', 'searchInput', 'clearSearch', 'paymentCount', 'noDataAlert', ['title', 'description']);
//     initializeCheckboxes('paymentsTable', 'checkAll');
//     initializeDelete('paymentsTable', '.delete-payment');
// });
