<?php
namespace App\Http\Livewire;

use App\Models\User;
use Faker\Factory as Faker;
use Livewire\Component;
use Livewire\WithPagination;

class SearchUser extends Component
{
    use WithPagination;
    public $search = '';
    protected $faker;

    public function __construct()
    {
        $this->faker = Faker::create();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function createUser()
    {
        $userCount = User::count();
        if ($userCount < 100) {
            $val = 100 - $userCount;
            for ($i = 0; $i < $val; $i++) {
                User::create([
                    'name'     => $this->faker->name,
                    'email'    => $this->faker->unique()->safeEmail,
                    'password' => bcrypt($this->faker->password),
                ]);
            }
            session()->flash('message', 'User created successfully with ' . $val . ' count.');
        } else {
            session()->flash('message', 'User count is already 100.');
        }
    }

    public function editUser($id)
    {
        $user = User::find($id);
        $user->update([
            'name'     => $this->faker->name,
            'email'    => $this->faker->unique()->safeEmail,
            'password' => bcrypt($this->faker->password),
        ]);
        session()->flash('message', 'User updated successfully.');
    }

    public function deleteUser($id)
    {
        if ($user = User::find($id)) {
            $user->delete();
            session()->flash('message', 'User deleted successfully.');
            return;
        }
        session()->flash('message', 'User not found.');
    }

    public function render()
    {
        $users = User::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($this->search) . '%'],
            'OR', 'LOWER(email) LIKE ?', ['%' . strtolower($this->search) . '%']
        )->paginate(10);

        if ($users->isNotEmpty() && $this->search != '') {
            session()->flash('message', 'User found successfully with search term: ' . $this->search);
        } elseif ($this->search != '') {
            session()->flash('message', 'User not found with search term: ' . $this->search);
        }

        return view('livewire.search-user', compact('users'));
    }
}
