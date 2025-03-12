<div class="container mt-4">
    <h4 class="mb-3">Users List</h4>
    <div  class="col-lg-3">

    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif
    <div wire:loading.remove></div>
    <input type="text" wire:model.debounce.500ms="search" wire:loading.class.delay="opacity-50" class="form-control mb-3" placeholder="Search users...">
    <button wire:click="createUser" class="btn btn-primary btn-sm mb-3" wire:loading.class="opacity-50">Add User</button>
    </div>
    <table class="table table-striped table-bordered" wire:loading.class.delay="opacity-50">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Created At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @if(count($users) == 0)
                <tr>
                    <td colspan="5">No users found</td>
                </tr>
            @endif

            @foreach ($users as $index => $user)
                <tr>
                    <td>{{ ++$index}}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ date('Y-m-d H:i:s',strtotime($user->created_at)) }}</td>
                    <td>
                        <button wire:click="deleteUser({{ $user->id }})" class="btn btn-danger btn-sm">Delete</button>
                        <button wire:click="editUser({{ $user->id }})" class="btn btn-primary btn-sm">Edit</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $users->links('vendor.pagination.bootstrap-4') }}

</div>
