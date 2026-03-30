<?php

namespace App\Http\Livewire;

use App\Models\User;
use Faker\Factory as Faker;
use Livewire\Component;
use Livewire\WithPagination;

class SearchUser extends Component
{
    use WithPagination;

    public $search        = '';
    public $selectAll     = false;
    public $numberOfPaginatorsRendered = [];
    protected $listeners  = ['deleteSelected', 'deleteUser', 'cancelEdit'];
    public $editingUserId = null;
    public $editingName   = '';
    public $editingEmail  = '';

    protected $rules = [
        'editingName'  => 'required|string|max:255|min:3',
        'editingEmail' => 'required|email',
    ];

    public function cancelEdit()
    {
        $this->editingUserId = null;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function createUser()
    {
        $userCount = User::count();
        $remaining = 100 - $userCount;

        if ($remaining > 0) {
            $faker = Faker::create();
            $users = [];

            for ($i = 0; $i < $remaining; $i++) {
                $users[] = [
                    'name'       => $faker->name,
                    'email'      => $faker->unique()->safeEmail,
                    'password'   => \Hash::make('12345678'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            User::insert($users);
            session()->flash('message', "$remaining users created successfully.");
        } else {
            session()->flash('message', 'User count is already 100.');
        }
    }

    public function editUser($id)
    {
        $user                = User::findOrFail($id);
        $this->editingUserId = $user->id;
        $this->editingName   = $user->name;
        $this->editingEmail  = $user->email;
    }

    public function updateUser()
    {
        $this->validate();

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->update([
                'name'  => $this->editingName,
                'email' => $this->editingEmail,
            ]);

            $this->reset(['editingUserId', 'editingName', 'editingEmail']);
            session()->flash('message', 'User updated successfully.');
        }
    }

    public function deleteUser($userId)
    {
        if ($user = User::find($userId)) {
            $user->delete();
            session()->flash('message', 'User deleted successfully.');
        } else {
            session()->flash('message', 'User not found.');
        }
    }

    public function deleteSelected($selectedUsers = null)
    {
        $userIds = json_decode($selectedUsers, true);
        if (! empty($userIds)) {
            User::whereIn('id', $userIds)->delete();
            session()->flash('message', count($userIds) . ' users deleted successfully.');
            $this->selectAll = false;
            $this->resetPage();
        }
    }

    public function render()
    {
        $searchTerm = $this->search;
        $users = User::where(function ($query) use ($searchTerm) {
            $query->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('email', 'like', "%{$searchTerm}%");
        })
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.search-user', compact('users'));
    }
}
