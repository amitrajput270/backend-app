// Optimized SweetAlert and UI interactions
(function () {
    'use strict';
    // DOM Elements
    let selectAllCheckbox = null;
    let deleteButton = null;
    let exportBtn = null;
    let modalEl = null;

    // Helper Functions
    const toggleDeleteButton = () => {
        if (!deleteButton) return;
        const checkedBoxes = document.querySelectorAll('.userCheckbox:checked');
        const count = checkedBoxes.length;

        deleteButton.disabled = count === 0;
        deleteButton.textContent = count > 0 ? `Delete Selected (${count})` : 'Delete Selected';
    };

    const updateSelectAllState = () => {
        if (!selectAllCheckbox) return;
        const allCheckboxes = document.querySelectorAll('.userCheckbox');
        const checkedCheckboxes = document.querySelectorAll('.userCheckbox:checked');
        selectAllCheckbox.checked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCheckboxes.length;
    };

    const clearAllCheckboxes = () => {
        document.querySelectorAll('.userCheckbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        if (selectAllCheckbox) selectAllCheckbox.checked = false;
        toggleDeleteButton();
    };

    // Modal Handlers
    const hideImportModal = () => {
        if (!modalEl) return;

        if (window.bootstrap?.Modal) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }

        modalEl.classList.remove('show', 'd-block');
        modalEl.style.backgroundColor = '';
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');

        // Reset file input
        const fileInput = modalEl.querySelector('input[type="file"]');
        if (fileInput) fileInput.value = '';
    };

    // Initialize Livewire Event Listeners
    document.addEventListener('livewire:init', () => {
        // Cache DOM elements
        selectAllCheckbox = document.getElementById('selectAll');
        deleteButton = document.getElementById('deleteSelected');
        exportBtn = document.getElementById('exportBtn');
        modalEl = document.getElementById('importModal');

        // Single Delete Confirmation
        Livewire.on('swal:confirm-single', (event) => {
            const data = event[0] || event;
            Swal.fire({
                title: data.title,
                text: data.text,
                icon: data.icon,
                theme: 'auto',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: data.confirmButtonText,
                cancelButtonText: data.cancelButtonText,
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    try {
                        await Livewire.dispatch('deleteUsers', { selectedUsers: [parseInt(data.userId)] });
                        return true;
                    } catch (error) {
                        Swal.showValidationMessage(`Delete failed: ${error.message || error}`);
                    }
                }
            }).then(result => {
                if (result.isConfirmed) clearAllCheckboxes();
            });
        });

        // Bulk Delete Confirmation
        Livewire.on('swal:confirm-bulk', (event) => {
            // Handle both array and object responses
            let data = event[0] || event;
            if (data.selectedUsers) {
                data = data;
            } else if (Array.isArray(event)) {
                data = event[0];
            }

            Swal.fire({
                title: data.title,
                text: data.text,
                icon: data.icon,
                theme: 'auto',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: data.confirmButtonText,
                cancelButtonText: data.cancelButtonText,
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    try {
                        await Livewire.dispatch('deleteUsers', { selectedUsers: data.selectedUsers });
                        return true;
                    } catch (error) {
                        Swal.showValidationMessage(`Delete failed: ${error.message || error}`);
                    }
                }
            }).then(result => {
                if (result.isConfirmed) clearAllCheckboxes();
            });
        });

        // User Deleted Event
        Livewire.on('userDeleted', () => {
            clearAllCheckboxes();
        });

        // Initialize checkbox handlers
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', () => {
                const isChecked = selectAllCheckbox.checked;
                document.querySelectorAll('.userCheckbox').forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
                toggleDeleteButton();
            });
        }

        // Individual checkbox change handler
        document.addEventListener('change', (e) => {
            if (e.target.classList?.contains('userCheckbox')) {
                toggleDeleteButton();
                updateSelectAllState();
            }
        });

        // Delete button handler
        if (deleteButton) {
            deleteButton.addEventListener('click', () => {
                const selectedUsers = Array.from(document.querySelectorAll('.userCheckbox:checked'))
                    .map(checkbox => checkbox.value);
                if (selectedUsers.length > 0) {
                    // Wrap the array in another array to pass as single parameter
                    Livewire.dispatch('confirmBulkDelete', [selectedUsers]);
                }
            });
        }

        // Export handler
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                const selected = Array.from(document.querySelectorAll('.userCheckbox:checked'))
                    .map(c => c.value);
                const base = exportBtn?.dataset?.exportUrl || '/';
                const url = selected.length > 0
                    ? `${base}?ids=${encodeURIComponent(selected.join(','))}`
                    : `${base}?search=${encodeURIComponent(document.getElementById('searchInput')?.value || '')}`;
                window.location.href = url;
            });
        }

        // Modal auto-hide on import completion
        Livewire.on('importCompleted', hideImportModal);
    });

    // Modal inert state handling
    const setBackgroundInert = (isInert) => {
        const container = document.querySelector('.container');
        if (!container) return;
        if (isInert) {
            container.setAttribute('inert', '');
            container.setAttribute('aria-hidden', 'true');
        } else {
            container.removeAttribute('inert');
            container.removeAttribute('aria-hidden');
        }
    };

    // Initialize modal handlers
    if (document.getElementById('importModal')) {
        const modalElement = document.getElementById('importModal');

        if (window.bootstrap?.Modal) {
            modalElement.addEventListener('show.bs.modal', () => setBackgroundInert(true));
            modalElement.addEventListener('hidden.bs.modal', () => setBackgroundInert(false));
        }

        // Close modal on outside click
        modalElement.addEventListener('click', (e) => {
            if (e.target === modalElement) {
                hideImportModal();
            }
        });
    }

    // Auto-hide session messages
    const setupAutoHideMessages = () => {
        const hideMessage = (msg) => {
            msg.style.transition = 'opacity 0.4s ease';
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 400);
        };

        const startTimer = (msg) => {
            if (msg.dataset.autoHideStarted) return;
            msg.dataset.autoHideStarted = '1';
            setTimeout(() => hideMessage(msg), 5000);
        };

        const existingMessage = document.getElementById('success-message');
        if (existingMessage) startTimer(existingMessage);

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1 && node.id === 'success-message') {
                        startTimer(node);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    };

    setupAutoHideMessages();

})();