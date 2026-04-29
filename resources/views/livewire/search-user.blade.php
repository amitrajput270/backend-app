@section('title', 'User Management')
<div>
    <div class="container mt-4">
        <h4 class="mb-3 user-list">Users List</h4>

        <div class="col-lg-6 -mb-2">
            @if (session()->has('message'))
            <div id="success-message" class="alert alert-success auto-hide-message" role="alert">
                {{ session('message') }}
            </div>
            @endif
            <input id="searchInput" type="text" wire:model.live.debounce.500ms="search" class="form-control mb-3" placeholder="Search users...">

            <button wire:click="createUser" class="btn btn-primary btn-sm mb-3" wire:loading.attr="disabled">Create User</button>
            <button class="btn btn-danger btn-sm mb-3" id="deleteSelected" disabled>Delete Selected</button>
            <button id="exportBtn" class="btn btn-success btn-sm mb-3">Export CSV</button>
            <button id="openImportBtn" class="btn btn-info btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#importModal">Import CSV</button>
        </div>

        <table class="table table-striped table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $index => $user)
                <tr wire:key="user-{{ $user->id }}">
                    <td><input type="checkbox" class="userCheckbox" value="{{ $user->id }}"></td>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        @if ($editingUserId === $user->id)
                        <input type="text" wire:model.live="editingName" class="form-control" autofocus="true">
                        @error('editingName') <span class="text-danger">{{ $message }}</span> @enderror
                        @else
                        {{ $user->name }}
                        @endif
                    </td>
                    <td>
                        @if ($editingUserId === $user->id)
                        <input type="email" wire:model.live="editingEmail" class="form-control">
                        @error('editingEmail') <span class="text-danger">{{ $message }}</span> @enderror
                        @else
                        {{ $user->email }}
                        @endif
                    </td>
                    <td>{{ $user->created_at->format('M j, Y g:i a') }}</td>
                    <td>
                        @if ($editingUserId === $user->id)
                        <button wire:click="updateUser" class="btn btn-success btn-sm">Save</button>
                        <button wire:click="cancelEdit" class="btn btn-secondary btn-sm">Cancel</button>
                        @else
                        <button wire:click="editUser({{ $user->id }})" class="btn btn-primary btn-sm">Edit</button>
                        <button onclick="confirmDelete('{{ $user->id }}')" class="btn btn-danger btn-sm">Delete</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center">No users found</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="d-flex justify-content-center">
            {{ $users->links('vendor.livewire.bootstrap') }}
        </div>
    </div>
    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Users</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="file" wire:model="importFile" accept=".csv" class="form-control">
                    @error('importFile') <span class="text-danger">{{ $message }}</span> @enderror
                    <div wire:loading wire:target="importFile" class="mt-2">Uploading...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="import" wire:loading.attr="disabled" class="btn btn-primary">Import</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
      // Export selected or filtered users
        (function() {
            const exportBtn = document.getElementById('exportBtn');
            if (!exportBtn) return;
            exportBtn.addEventListener('click', function(e) {
                const selected = [...document.querySelectorAll('.userCheckbox:checked')].map(c => c.value);
                const base = '{{ route('users.export') }}';
                if (selected.length > 0) {
                    // navigate with ids as comma-separated list
                    const url = base + '?ids=' + encodeURIComponent(selected.join(','));
                    window.location = url;
                    return;
                }

                // fallback to search param
                const search = document.getElementById('searchInput') ? document.getElementById('searchInput').value : '';
                window.location = base + '?search=' + encodeURIComponent(search || '');
            });
        })();
        // Fallback to open import modal programmatically if data attributes fail
        (function() {
            function setBackgroundInert(isInert) {
                const container = document.querySelector('.container');
                if (!container) return;
                try {
                    if (isInert) {
                        container.setAttribute('inert', '');
                        container.setAttribute('aria-hidden', 'true');
                    } else {
                        container.removeAttribute('inert');
                        container.removeAttribute('aria-hidden');
                    }
                } catch (err) {
                    // ignore if inert not supported
                }
            }

            function focusFirstInModal(modalEl) {
                try {
                    const focusable = modalEl.querySelector('input, select, textarea, button, [tabindex]:not([tabindex="-1"])');
                    if (focusable) focusable.focus();
                    else modalEl.focus();
                } catch (err) {
                    // ignore
                }
            }

            const modalEl = document.getElementById('importModal');
            if (!modalEl) return;

            // If Bootstrap is available, hook into its events to set inert state
            if (window.bootstrap && typeof bootstrap.Modal === 'function') {
                modalEl.addEventListener('show.bs.modal', () => setBackgroundInert(true));
                modalEl.addEventListener('shown.bs.modal', () => focusFirstInModal(modalEl));
                modalEl.addEventListener('hidden.bs.modal', () => setBackgroundInert(false));
            }

            const btn = document.getElementById('openImportBtn');
            if (!btn) return;

            btn.addEventListener('click', function(e) {
                // allow normal behavior if bootstrap works
                setTimeout(() => {
                    if (modalEl.classList.contains('show')) return;

                    if (window.bootstrap && typeof bootstrap.Modal === 'function') {
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.show();
                        return;
                    }

                    // Minimal fallback: add classes to display modal and update ARIA for accessibility
                    modalEl.classList.add('show', 'd-block');
                    modalEl.style.backgroundColor = 'rgba(0,0,0,0.5)';
                    try {
                        modalEl.setAttribute('aria-hidden', 'false');
                        modalEl.setAttribute('aria-modal', 'true');
                        setBackgroundInert(true);
                        focusFirstInModal(modalEl);
                    } catch (err) {
                        // ignore focus/aria errors
                    }
                }, 10);
            });

            // Hook up any buttons inside the modal that should dismiss it (works when Bootstrap missing)
            const dismissButtons = modalEl.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
            dismissButtons.forEach(btn => {
                btn.addEventListener('click', function(ev) {
                    ev.preventDefault();
                    if (window.bootstrap && typeof bootstrap.Modal === 'function') {
                        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modal.hide();
                        return;
                    }

                    // Fallback hide
                    modalEl.classList.remove('show', 'd-block');
                    modalEl.style.backgroundColor = '';
                    try {
                        modalEl.setAttribute('aria-hidden', 'true');
                        modalEl.removeAttribute('aria-modal');
                        setBackgroundInert(false);
                        // clear file input if present
                        const fileInput = modalEl.querySelector('input[type="file"]');
                        if (fileInput) fileInput.value = '';
                        // remove backdrop if any
                        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                        document.body.classList.remove('modal-open');
                    } catch (err) {
                        // ignore
                    }
                });
            });
        })();

    document.addEventListener('livewire:init', () => {
        const selectAllCheckbox = document.getElementById("selectAll");
        const deleteButton = document.getElementById("deleteSelected");

        Livewire.on('checkboxStateChanged', () => {
            toggleDeleteButton();
        });

        document.addEventListener("change", function(event) {
            if (event.target.classList.contains("userCheckbox")) {
                toggleDeleteButton();
                selectAllCheckbox.checked = document.querySelectorAll(".userCheckbox:checked").length === document.querySelectorAll(".userCheckbox").length;
            }
        });

        selectAllCheckbox.addEventListener("change", function() {
            document.querySelectorAll(".userCheckbox").forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            toggleDeleteButton();
        });

        function toggleDeleteButton() {
            const checkedBoxes = document.querySelectorAll(".userCheckbox:checked");
            deleteButton.disabled = checkedBoxes.length === 0;

            if (checkedBoxes.length > 0) {
                deleteButton.textContent = `Delete Selected (${checkedBoxes.length})`;
            } else {
                deleteButton.textContent = 'Delete Selected';
            }
        }

        deleteButton.addEventListener("click", function() {
            const selectedUsers = [...document.querySelectorAll(".userCheckbox:checked")].map(checkbox => checkbox.value);
            if (selectedUsers.length > 0 && confirm(`Are you sure you want to delete ${selectedUsers.length} selected user(s)?`)) {
                Livewire.dispatch('deleteUsers', {
                    selectedUsers: selectedUsers
                });
                selectAllCheckbox.checked = false;
                toggleDeleteButton();
            }
        });
    });

    function confirmDelete(userId) {
        if (confirm("Are you sure you want to delete this user?")) {
            Livewire.dispatch('deleteUsers', {
                selectedUsers: parseInt(userId)
            });
        }
    }

    // Auto-hide session success message when it appears (handles dynamic insertion)
    (function setupAutoHide() {
        function hideMsg(msg) {
            msg.style.transition = 'opacity 0.4s ease';
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 400);
        }

        function startTimer(msg) {
            // avoid multiple timers
            if (msg.dataset.autoHideStarted) return;
            msg.dataset.autoHideStarted = '1';
            setTimeout(() => hideMsg(msg), 5000);
        }

        const existing = document.getElementById('success-message');
        if (existing) startTimer(existing);

        const observer = new MutationObserver((mutations) => {
            for (const m of mutations) {
                for (const node of m.addedNodes) {
                    if (node.nodeType !== 1) continue;
                    if (node.id === 'success-message') {
                        startTimer(node);
                        // If an import or other action created a flash message, close modal too
                        if (typeof hideImportModal === 'function') {
                            hideImportModal();
                        }
                        continue;
                    }
                    const nested = node.querySelector && node.querySelector('#success-message');
                    if (nested) startTimer(nested);
                }
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    })();

    // Close import modal when Livewire signals completion (listen for both browser event and Livewire event)
    function hideImportModal() {
        const modalEl = document.getElementById('importModal');
        if (!modalEl) return;
        if (window.bootstrap && typeof bootstrap.Modal === 'function') {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();
            return;
        }

        // Minimal fallback: hide classes and restore ARIA and inert state
        modalEl.classList.remove('show', 'd-block');
        try {
            modalEl.setAttribute('aria-hidden', 'true');
            modalEl.removeAttribute('aria-modal');
            modalEl.style.backgroundColor = '';
            const container = document.querySelector('.container');
            if (container) {
                container.removeAttribute('inert');
                container.removeAttribute('aria-hidden');
            }
            // clear file input if present
            const fileInput = modalEl.querySelector('input[type="file"]');
            if (fileInput) fileInput.value = '';
            // remove backdrop and body modal-open class
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
        } catch (err) {
            // ignore
        }
    }

    window.addEventListener('importCompleted', hideImportModal);
    if (window.Livewire && typeof Livewire.on === 'function') {
        Livewire.on('importCompleted', hideImportModal);
    }
</script>

