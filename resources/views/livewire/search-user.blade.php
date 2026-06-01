@section('title', 'User Management')

<div>
    <div class="container mt-4">
        <h4 class="mb-3 user-list">Users List</h4>

        {{-- Search and Actions Section --}}
        <div class="row mb-3">
            <div class="col-lg-6">
                <input type="text" wire:model.live.debounce.500ms="search" class="form-control"
                    placeholder="Search users by name or email...">
            </div>
            <div class="col-lg-6 left-0 text-lg-end mt-2 mt-lg-0">
                <div class="btn-group" role="group">
                    <button wire:click="createUser" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="createUser">Create User</span>
                        <span wire:loading wire:target="createUser">Creating...</span>
                    </button>

                    <button class="btn btn-danger btn-sm" id="deleteSelected" disabled>
                        Delete Selected
                    </button>

                    <button id="exportBtn" class="btn btn-success btn-sm">
                        Export CSV
                    </button>

                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal"
                        data-bs-target="#importModal">
                        Import CSV
                    </button>
                </div>
            </div>
        </div>

        {{-- Success Message --}}
        @if (session()->has('message'))
            <div class="alert alert-success alert-dismissible fade show auto-hide-message" role="alert">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Users Table --}}
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center" width="50"><input type="checkbox" id="selectAll"></th>
                        <th width="80">ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th width="200">Created At</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td class="text-center">
                                <input type="checkbox" class="userCheckbox" value="{{ $user->id }}">
                            </td>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @if ($editingUserId === $user->id)
                                    <input type="text" wire:model="editingName"
                                        class="form-control form-control-sm @error('editingName') is-invalid @enderror"
                                        autofocus>
                                    @error('editingName')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @else
                                    {{ $user->name }}
                                @endif
                            </td>
                            <td>
                                @if ($editingUserId === $user->id)
                                    <input type="email" wire:model="editingEmail"
                                        class="form-control form-control-sm @error('editingEmail') is-invalid @enderror">
                                    @error('editingEmail')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @else
                                    {{ $user->email }}
                                @endif
                            </td>
                            <td>{{ $user->created_at?->format('M j, Y g:i a') }}</td>
                            <td class="text-center">
                                @if ($editingUserId === $user->id)
                                    <button wire:click="updateUser" class="btn btn-success btn-sm" wire:loading.attr="disabled">
                                        Save
                                    </button>
                                    <button wire:click="cancelEdit" class="btn btn-secondary btn-sm">
                                        Cancel
                                    </button>
                                @else
                                    <button wire:loading.remove wire:target="editUser" wire:click="editUser({{ $user->id }})"
                                        class="btn btn-primary btn-sm">
                                        Edit
                                        <span wire:loading wire:target="editUser">
                                            <span class="spinner-border spinner-border-sm" role="status"></span>
                                        </span>
                                    </button>


                                    <button wire:click="confirmSingleDelete({{ $user->id }})" class="btn btn-danger btn-sm">
                                        Delete
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-people"></i> No users found
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-3">
            {{ $users->links('vendor.livewire.bootstrap') }}
        </div>
    </div>

    {{-- Import Modal --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Users from CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="importFile" class="form-label">Choose CSV File</label>
                        <input type="file" wire:model="importFile" id="importFile" accept=".csv"
                            class="form-control @error('importFile') is-invalid @enderror">
                        @error('importFile')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            CSV should contain 'name' and 'email' columns
                        </small>
                    </div>

                    <div wire:loading wire:target="importFile" class="alert alert-info">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                        Uploading file...
                    </div>

                    <div wire:loading wire:target="import" class="alert alert-info">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                        Importing users...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="import" wire:loading.attr="disabled" class="btn btn-primary">
                        Import Users
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/components/livewire-search-user.js') }}"> </script>

<style>
    /* Custom styles for better UX */
    .auto-hide-message {
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }

    .btn-group .btn {
        margin-right: 5px;
    }

    /* Loading states */
    .btn[wire\:loading] {
        cursor: not-allowed;
        opacity: 0.7;
    }

    /* Responsive table */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 14px;
        }

        .btn-group {
            flex-wrap: wrap;
            gap: 5px;
        }

        .btn-group .btn {
            margin-right: 0;
        }
    }

    /* Modal animations */
    .modal.fade .modal-dialog {
        transform: scale(0.8);
        transition: transform 0.2s ease-out;
    }

    .modal.show .modal-dialog {
        transform: scale(1);
    }
</style>