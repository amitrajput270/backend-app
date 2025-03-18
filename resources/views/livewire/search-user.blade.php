<div>
<div class="container mt-4">
    <h4 class="mb-3 user-list">Users List</h4>

    <div class="col-lg-3">
        @if (session()->has('message'))
            <div id="success-message" class="alert alert-success">
            {{ session('message') }}
            </div>
        @endif

        <input type="text" wire:model.debounce.500ms="search" class="form-control mb-3" placeholder="Search users...">
        <button wire:click="createUser" class="btn btn-primary btn-sm mb-3" wire:loading.attr="disabled">Create User</button>
        <button class="btn btn-danger btn-sm mb-3" id="deleteSelected" disabled>Delete Selected</button>
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
                <tr  wire:key="user-{{ $user->id }}">
                    <td><input type="checkbox" class="userCheckbox" value="{{ $user->id }}"></td>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        @if ($editingUserId === $user->id)
                        <input type="text" wire:model.defer="editingName" class="form-control" autofocus = "true">
                        @error('editingName') <span class="text-danger">{{ $message }}</span> @enderror
                    @else
                        {{ $user->name }}
                    @endif
                    </td>
                    <td>
                    @if ($editingUserId === $user->id)
                        <input type="email" wire:model.defer="editingEmail" class="form-control">
                        @error('editingEmail') <span class="text-danger">{{ $message }}</span> @enderror
                    @else
                        {{ $user->email }}
                    @endif
                    </td>
                    <td>{{ $user->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>
                    @if ($editingUserId === $user->id)
                        <button wire:click="updateUser" class="btn btn-success btn-sm">Save</button>
                        <button onclick="Livewire.emit('cancelEdit')" class="btn btn-secondary btn-sm">Cancel</button>
                    @else
                        <button wire:click="editUser({{ $user->id }})" class="btn btn-primary btn-sm">Edit</button>
                        <button onclick="confirmDelete({{ $user->id }})" class="btn btn-danger btn-sm">Delete</button>
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

    {{ $users->links('vendor.livewire.bootstrap') }}

</div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const selectAllCheckbox = document.getElementById("selectAll");
        const deleteButton = document.getElementById("deleteSelected");

        document.addEventListener("change", function (event) {
            if (event.target.classList.contains("userCheckbox")) {
                toggleDeleteButton();
                selectAllCheckbox.checked = document.querySelectorAll(".userCheckbox:checked").length === document.querySelectorAll(".userCheckbox").length;
            }
        });

        selectAllCheckbox.addEventListener("change", function () {
            document.querySelectorAll(".userCheckbox").forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            toggleDeleteButton();
        });

        function toggleDeleteButton() {
            deleteButton.disabled = document.querySelectorAll(".userCheckbox:checked").length === 0;
        }

        deleteButton.addEventListener("click", function () {
            const selectedUsers = [...document.querySelectorAll(".userCheckbox:checked")].map(checkbox => checkbox.value);
            if (selectedUsers.length > 0 && confirm("Are you sure you want to delete the selected users?")) {
                Livewire.emit("deleteSelected", JSON.stringify(selectedUsers));
                selectAllCheckbox.checked = false;
            }
        });
    });

    function confirmDelete(userId) {
        if (confirm("Are you sure you want to delete this user?")) {
            Livewire.emit('deleteUser', userId);
        }
    }
</script>
