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
    protected $listeners  = ['deleteSelected', 'deleteUser'];
    public $editingUserId = null;
    public $editingName   = '';
    public $editingEmail  = '';

    protected $rules = [
        'editingName'  => 'required|string|max:255|min:3',
        'editingEmail' => 'required|email',
    ];

    public function updatingSearch()
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
                    'password'   => bcrypt('password'),
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

    public function deleteUser($id)
    {
        if ($user = User::find($id)) {
            $user->delete();
            session()->flash('message', 'User deleted successfully.');
        } else {
            session()->flash('message', 'User not found.');
        }
    }

    public function deleteSelected($userIds = null)
    {
        $userIds = json_decode($userIds, true);

        if (! empty($userIds)) {
            User::whereIn('id', $userIds)->delete();
            session()->flash('message', count($userIds) . ' users deleted successfully.');
            $this->selectAll = false;
            $this->resetPage();
        }
    }

    public function render()
    {
        $searchTerm = strtolower($this->search);

        $users = User::where('name', 'like', "%{$searchTerm}%")
            ->orWhere('email', 'like', "%{$searchTerm}%")
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.search-user', compact('users'));
    }
}
