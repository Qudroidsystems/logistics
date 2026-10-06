console.log("principalscomment.init.js loaded");

// CSRF
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

// Add Assignment
const addForm = document.getElementById('add-principalscomment-form');
if (addForm) {
    addForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const errorMsg = document.getElementById('alert-error-msg');
        if (errorMsg) errorMsg.classList.add('d-none');

        const formData = new FormData(this);

        const staffId = formData.get('staffId');
        const classes = formData.getAll('schoolclassid[]');

        if (!staffId) {
            errorMsg.innerHTML = 'Please select a staff member';
            errorMsg.classList.remove('d-none');
            return;
        }
        if (classes.length === 0) {
            errorMsg.innerHTML = 'Please select at least one class';
            errorMsg.classList.remove('d-none');
            return;
        }

        const btn = document.getElementById('add-btn');
        btn.disabled = true;
        btn.innerHTML = 'Adding...';

        axios.post('/principalscomment', formData)
            .then(res => {
                console.log('Add success:', res.data);

                Swal.fire({
                    icon: 'success',
                    title: 'Added!',
                    text: res.data.message || 'Assignment(s) added successfully',
                    timer: 2000,
                    showConfirmButton: false
                });

                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addPrincipalsCommentModal'));
                if (modal) modal.hide();

                // Force full reload to show new data
                setTimeout(() => {
                    window.location.reload(true); // true forces reload from server
                }, 800);
            })
            .catch(err => {
                console.error('Add error:', err.response || err);

                let msg = 'Failed to add assignment';
                if (err.response?.data?.errors) {
                    msg = Object.values(err.response.data.errors).flat().join('<br>');
                } else if (err.response?.data?.message) {
                    msg = err.response.data.message;
                }

                if (errorMsg) {
                    errorMsg.innerHTML = msg;
                    errorMsg.classList.remove('d-none');
                }

                btn.disabled = false;
                btn.innerHTML = 'Add Assignment';
            });
    });
}

// Edit Assignment
const editForm = document.getElementById('edit-principalscomment-form');
if (editForm) {
    editForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const errorMsg = document.getElementById('edit-alert-error-msg');
        if (errorMsg) errorMsg.classList.add('d-none');

        const formData = new FormData(this);
        const id = formData.get('id');

        const btn = document.getElementById('update-btn');
        btn.disabled = true;
        btn.innerHTML = 'Updating...';

        axios.put(`/principalscomment/${id}`, formData)
            .then(res => {
                Swal.fire('Updated!', res.data.message || 'Updated successfully', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                if (modal) modal.hide();
                setTimeout(() => window.location.reload(true), 800);
            })
            .catch(err => {
                let msg = 'Failed to update';
                if (err.response?.data?.errors) msg = Object.values(err.response.data.errors).flat().join('<br>');
                else if (err.response?.data?.message) msg = err.response.data.message;

                if (errorMsg) {
                    errorMsg.innerHTML = msg;
                    errorMsg.classList.remove('d-none');
                }
                btn.disabled = false;
                btn.innerHTML = 'Update';
            });
    });
}

// Delete Single
document.addEventListener('click', function (e) {
    const removeBtn = e.target.closest('.remove-item-btn');
    if (removeBtn) {
        const tr = removeBtn.closest('tr');
        const id = tr.querySelector('.id')?.getAttribute('data-id');
        const url = tr.getAttribute('data-url');

        if (!id || !url) return;

        const modal = new bootstrap.Modal(document.getElementById('deleteRecordModal'));
        modal.show();

        const deleteBtn = document.getElementById('delete-record');
        deleteBtn.onclick = function () {
            axios.delete(url)
                .then(res => {
                    Swal.fire('Deleted!', res.data.message || 'Deleted', 'success');
                    modal.hide();
                    setTimeout(() => window.location.reload(true), 800);
                })
                .catch(err => {
                    Swal.fire('Error', err.response?.data?.message || 'Failed', 'error');
                });
        };
    }
});

// Edit Button - Fill Modal
document.addEventListener('click', function (e) {
    const editBtn = e.target.closest('.edit-item-btn');
    if (editBtn) {
        const tr = editBtn.closest('tr');
        const id = tr.querySelector('.id')?.getAttribute('data-id');
        const staffId = tr.querySelector('.staffname')?.getAttribute('data-staffid');
        const classId = tr.querySelector('.sclass')?.getAttribute('data-schoolclassid');

        document.getElementById('edit-id-field').value = id;
        document.getElementById('edit-staffId').value = staffId || '';
        document.getElementById('edit-schoolclassid').value = classId || '';

        new bootstrap.Modal(document.getElementById('editModal')).show();
    }
});

// Image Preview
document.addEventListener('click', function (e) {
    const img = e.target.closest('.staff-image');
    if (img) {
        const src = img.getAttribute('data-image') || img.src;
        const name = img.getAttribute('data-teachername') || 'Staff';

        document.getElementById('preview-image').src = src;
        document.getElementById('preview-teachername').textContent = name;

        new bootstrap.Modal(document.getElementById('imageViewModal')).show();
    }
});

// List.js for search & pagination
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('principalsCommentList')) {
        new List('principalsCommentList', {
            valueNames: ['sn', 'staffname', 'sclass', 'schoolarm', 'session', 'datereg'],
            page: 10,
            pagination: true
        });
    }
});