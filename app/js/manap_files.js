document.addEventListener('DOMContentLoaded', function() {
    const page = document.getElementById('manapFilesPage');
    if (!page) {
        return;
    }

    const addModal = document.getElementById('addRecordModal');
    const editModal = document.getElementById('editRecordModal');
    const openAddModalBtn = document.getElementById('openAddModalBtn');
    const closeModalButtons = document.querySelectorAll('.closeModalBtn');
    const editButtons = document.querySelectorAll('.editRecordBtn');
    const previewButtons = document.querySelectorAll('.previewRecordBtn');
    const deleteButtons = document.querySelectorAll('.deleteRecordBtn');
    const deleteForm = document.getElementById('deleteRecordForm');
    const deleteRecordIdInput = document.getElementById('deleteRecordId');
    const limitSelect = document.getElementById('limitSelect');
    const filtersForm = document.getElementById('filtersForm');

    const editRecordId = document.getElementById('editRecordId');
    const editFileName = document.getElementById('editFileName');
    const editFilePath = document.getElementById('editFilePath');
    const currentFileName = document.getElementById('currentFileName');

    const previewModal = document.getElementById('previewModal');
    const previewTitle = document.getElementById('previewTitle');
    const previewContent = document.getElementById('previewContent');
    const closePreviewModalBtn = document.getElementById('closePreviewModalBtn');
    const closePreviewModalFooterBtn = document.getElementById('closePreviewModalFooterBtn');

    const successMessage = page.dataset.successMessage || '';
    const errorMessage = page.dataset.errorMessage || '';

    if (successMessage) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: successMessage,
            confirmButtonColor: '#3b82f6'
        });
    }

    if (errorMessage) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errorMessage,
            confirmButtonColor: '#ef4444'
        });
    }

    if (openAddModalBtn && addModal) {
        openAddModalBtn.addEventListener('click', function() {
            addModal.showModal();
        });
    }

    closeModalButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            if (addModal && addModal.open) {
                addModal.close();
            }
            if (editModal && editModal.open) {
                editModal.close();
            }
        });
    });

    if (limitSelect && filtersForm) {
        limitSelect.addEventListener('change', function() {
            const pageInput = filtersForm.querySelector('input[name="page"]');
            if (pageInput) {
                pageInput.value = '1';
            }
            filtersForm.submit();
        });
    }

    function openPreviewModal(documentId, fileName) {
        if (!previewModal || !previewTitle || !previewContent) {
            window.open(`preview_document.php?id=${documentId}`, '_blank');
            return;
        }

        previewTitle.textContent = fileName || 'Document Preview';
        previewContent.innerHTML = `<iframe src="preview_document.php?id=${documentId}" class="w-full h-[600px] border-0 rounded"></iframe>`;
        previewModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closePreviewModal() {
        if (!previewModal || !previewContent) {
            return;
        }

        previewModal.classList.add('hidden');
        previewContent.innerHTML = `
            <div class="flex items-center justify-center gap-3">
                <div class="animate-spin">
                    <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <span class="text-gray-600 dark:text-gray-300 font-medium">Loading preview...</span>
            </div>
        `;
        document.body.style.overflow = '';
    }

    previewButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const id = button.dataset.id;
            const fileName = button.dataset.fileName || 'Document Preview';
            if (!id) {
                return;
            }
            openPreviewModal(id, fileName);
        });
    });

    if (closePreviewModalBtn) {
        closePreviewModalBtn.addEventListener('click', closePreviewModal);
    }

    if (closePreviewModalFooterBtn) {
        closePreviewModalFooterBtn.addEventListener('click', closePreviewModal);
    }

    if (previewModal) {
        previewModal.addEventListener('click', function(event) {
            if (event.target === previewModal) {
                closePreviewModal();
            }
        });
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && previewModal && !previewModal.classList.contains('hidden')) {
            closePreviewModal();
        }
    });

    editButtons.forEach(function(button) {
        button.addEventListener('click', async function() {
            const id = button.dataset.id;
            if (!id) {
                return;
            }

            try {
                const response = await fetch('manap_files.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'get_manap_file',
                        id: id
                    })
                });

                const data = await response.json();
                if (!data.success || !data.record) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Record not found.',
                        confirmButtonColor: '#ef4444'
                    });
                    return;
                }

                editRecordId.value = data.record.id || '';
                editFileName.value = data.record.file_name || '';
                editFilePath.value = data.record.file_path || '';
                currentFileName.textContent = data.record.file_name ? `Current file: ${data.record.file_name}` : 'Current file: none';

                if (editModal) {
                    editModal.showModal();
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load record data.',
                    confirmButtonColor: '#ef4444'
                });
            }
        });
    });

    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const id = button.dataset.id;
            const fileName = button.dataset.fileName || 'this record';

            if (!id) {
                return;
            }

            Swal.fire({
                title: 'Delete MANAP file?',
                text: `Are you sure you want to delete ${fileName}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) {
                    deleteRecordIdInput.value = id;
                    deleteForm.submit();
                }
            });
        });
    });
});
