<?php

namespace App\Http\Livewire;

use App\Models\User;
use App\Traits\AlertHelper;
use Faker\Factory as Faker;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use SweetAlert2\Laravel\Traits\WithSweetAlert;




class SearchUser extends Component
{
    use WithPagination, WithFileUploads, WithSweetAlert, AlertHelper;

    public $search = '';
    public $selectAll = false;
    public $importFile;
    public $numberOfPaginatorsRendered = [];
    protected $listeners = ['deleteSelected', 'deleteUsers', 'cancelEdit'];
    public $editingUserId = null;
    public $editingName = '';
    public $editingEmail = '';

    protected $rules = [
        'editingName' => 'required|string|max:255|min:3',
        'editingEmail' => 'required|email',
    ];

    public function export()
    {
        // support exporting specific IDs (comma-separated or array) or fallback to search
        $idsParam = request()->query('ids');
        if ($idsParam) {
            if (is_array($idsParam)) {
                $ids = array_filter($idsParam, 'is_numeric');
            } else {
                $ids = array_filter(explode(',', $idsParam), 'is_numeric');
            }

            $users = User::whereIn('id', $ids)->orderBy('id', 'desc')->get();
        } else {
            $searchTerm = request()->query('search', $this->search);
            $users = User::where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            })
                ->orderBy('id', 'desc')
                ->get();
        }

        $csvData = "ID,Name,Email,Created At\n";
        foreach ($users as $key => $user) {
            $index = $key + 1;
            $csvData .= "{$index},\"{$user->name}\",\"{$user->email}\",\"{$user->created_at}\"\n";
        }

        $fileName = 'users_export_' . now()->format('Ymd_His') . '.csv';
        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$fileName}\"");
    }

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
            $faker = Faker::create('en_IN');
            $users = [];
            for ($i = 0; $i < $remaining; $i++) {
                $name = $faker->name();
                $city = $faker->city();
                $state = $faker->state();
                $country = $faker->country();
                $pincode = $faker->numberBetween(110001, 855126);
                $username = strtolower(str_replace(' ', '.', $name));
                $users[] = [
                    'name' => $name,
                    'email' => $username . $faker->unique()->numberBetween(1000, 9999) . '@gmail.com',
                    'password' => \Hash::make('12345678'),
                    'address' => $faker->streetAddress(),
                    'city' => $city,
                    'state' => $state,
                    'country' => $country,
                    'pincode' => $pincode,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            User::insert($users);
            $this->alert([
                'icon' => 'success',
                'text' => "$remaining users created successfully."
            ]);
        } else {
            $this->alert([
                'icon' => 'info',
                'text' => 'User count is already 100.'
            ]);
        }
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        $this->editingUserId = $user->id;
        $this->editingName = $user->name;
        $this->editingEmail = $user->email;
    }

    public function updateUser()
    {
        $this->rules['editingEmail'] = 'required|email|unique:users,email,' . $this->editingUserId;
        $this->validate();

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->update([
                'name' => $this->editingName,
                'email' => $this->editingEmail,
            ]);

            $this->reset(['editingUserId', 'editingName', 'editingEmail']);
            $this->alert([
                'icon' => 'success',
                'text' => "User updated successfully."
            ]);
        }
    }

    public function deleteUsers($selectedUsers = null)
    {
        // Extract user IDs from different possible input formats
        $userIds = $this->extractUserIds($selectedUsers);
        if (empty($userIds)) {
            $this->alert([
                'icon' => 'error',
                'text' => 'No users specified for deletion.'
            ]);
            return;
        }
        // Remove duplicates and validate IDs
        $userIds = array_unique(array_filter($userIds, 'is_numeric'));
        if (empty($userIds)) {
            $this->alert([
                'icon' => 'error',
                'text' => 'Invalid user IDs provided.'
            ]);
            return;
        }

        try {
            $deletedCount = User::whereIn('id', $userIds)->delete();
            if ($deletedCount > 0) {
                $message = $deletedCount === 1
                    ? 'User deleted successfully.'
                    : "$deletedCount users deleted successfully.";
                $this->alert([
                    'icon' => 'success',
                    'text' => $message
                ]);
            } else {
                $this->alert([
                    'icon' => 'info',
                    'text' => 'No users were deleted. They may have already been removed.'
                ]);
            }
            $this->resetPage();
        } catch (\Exception $e) {
            $this->alert([
                'icon' => 'error',
                'text' => 'Error deleting users: ' . $e->getMessage()
            ]);
        }
    }

    public function import()
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt',
        ]);

        try {

            $path = $this->importFile->store('imports');
            $fullPath = storage_path('app/' . $path);

            if (!file_exists($fullPath)) {
                $this->alert([
                    'icon' => 'error',
                    'text' => 'Uploaded file not found.'
                ]);
                return;
            }

            $handle = fopen($fullPath, 'r');
            if ($handle === false) {
                $this->alert([
                    'icon' => 'error',
                    'text' => 'Failed to open uploaded file.'
                ]);
                return;
            }

            $header = fgetcsv($handle);
            if ($header === false) {
                fclose($handle);
                $this->alert([
                    'icon' => 'error',
                    'text' => 'CSV file appears empty.'
                ]);
                return;
            }

            $created = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                if ($data === false)
                    continue;

                $name = $data['Name'] ?? $data['name'] ?? null;
                $email = $data['Email'] ?? $data['email'] ?? null;
                if (!$name || !$email)
                    continue;

                $userExists = User::withTrashed()->where('email', $email)->first();
                if ($userExists && $userExists->trashed() == true) {
                    $userExists->restore();
                    $userExists->update([
                        'name' => $name,
                    ]);
                    $created++;
                    continue;
                } elseif ($userExists && $userExists->trashed() == false) {
                    continue;
                }

                User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => \Hash::make('12345678'),
                ]);
                $created++;
            }

            fclose($handle);
            Storage::delete($path);

            if ($created <= 0) {
                $icon = 'info';
                $message = 'No users imported (duplicates skipped or no valid records found).';
            } else {
                $icon = 'success';
                $message = $created === 1 ? '1 user imported successfully.' : "$created users imported successfully.";
            }

            $this->alert([
                'icon' => $icon,
                'text' => $message
            ]);

            $this->importFile = null;
            $this->resetPage();

            $this->dispatch('importCompleted');
        } catch (\Exception $e) {
            $this->alert([
                'icon' => 'error',
                'text' => 'Import failed: ' . $e->getMessage()
            ]);
        }
    }

    private function extractUserIds($data): array
    {
        // Case 1: Single user ID passed as parameter from deleteUser event
        if (is_numeric($data)) {
            return [$data];
        }

        // Case 2: Single user ID in array format
        if (is_array($data) && isset($data['userId'])) {
            return [$data['userId']];
        }

        // Case 3: Multiple user IDs from deleteSelected event
        if (is_array($data) && isset($data['selectedUsers'])) {
            return $data['selectedUsers'];
        }

        // Case 4: JSON string from old format (backward compatibility)
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Case 5: Direct array of IDs
        if (is_array($data)) {
            return $data;
        }

        return [];
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
