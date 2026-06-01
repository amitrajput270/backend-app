<?php

namespace App\Http\Livewire;

use App\Models\User;
use App\Traits\AlertHelper;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class SearchUser extends Component
{
    use WithPagination, WithFileUploads, AlertHelper;

    private const BATCH_SIZE = 50;
    private const MAX_USERS = 500;

    public string $search = '';
    public bool $selectAll = false;
    public $importFile;
    public array $numberOfPaginatorsRendered = [];
    public ?int $editingUserId = null;
    public string $editingName = '';
    public string $editingEmail = '';

    protected $listeners = ['deleteSelected', 'deleteUsers', 'cancelEdit', 'confirmSingleDelete', 'confirmBulkDelete'];

    protected function rules(): array
    {
        $rules = [
            'editingName' => 'required|string|max:255|min:3',
            'editingEmail' => 'required|email',
        ];

        if ($this->editingUserId) {
            $rules['editingEmail'] .= '|unique:users,email,' . $this->editingUserId;
        }

        return $rules;
    }

    protected $validationAttributes = [
        'editingName' => 'name',
        'editingEmail' => 'email address',
        'importFile' => 'import file',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingUserId', 'editingName', 'editingEmail']);
    }

    public function createUser(): void
    {
        $userCount = User::count();
        $remaining = self::MAX_USERS - $userCount;

        if ($remaining <= 0) {
            $this->alert([
                'icon' => 'info',
                'text' => 'User count has already reached the maximum of ' . self::MAX_USERS . '.'
            ]);
            return;
        }

        try {
            $users = $this->generateFakeUsers($remaining);

            foreach (array_chunk($users, self::BATCH_SIZE) as $chunk) {
                User::insert($chunk);
            }

            $this->alert([
                'icon' => 'success',
                'text' => "$remaining user(s) created successfully."
            ]);
        } catch (\Exception $e) {
            $this->alert([
                'icon' => 'error',
                'text' => 'Failed to create users: ' . $e->getMessage()
            ]);
        }
    }

    private function generateFakeUsers(int $count): array
    {
        $faker = Faker::create('en_IN');
        $users = [];
        $existingEmails = User::pluck('email')->flip();

        for ($i = 0; $i < $count; $i++) {
            $name = $faker->name();
            $username = strtolower(str_replace(' ', '.', $name));

            do {
                $email = $username . $faker->unique()->numberBetween(1000, 9999) . '@gmail.com';
            } while (isset($existingEmails[$email]));

            $users[] = [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('12345678'),
                'address' => $faker->streetAddress(),
                'city' => $faker->city(),
                'state' => $faker->state(),
                'country' => $faker->country(),
                'pincode' => $faker->numberBetween(110001, 855126),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $users;
    }

    public function editUser(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingUserId = $user->id;
        $this->editingName = $user->name;
        $this->editingEmail = $user->email;
    }

    public function updateUser(): void
    {
        $this->validate();

        if (!$this->editingUserId) {
            return;
        }

        try {
            $user = User::findOrFail($this->editingUserId);
            $user->update([
                'name' => $this->editingName,
                'email' => $this->editingEmail,
            ]);

            $this->cancelEdit();
            $this->alert([
                'icon' => 'success',
                'text' => 'User updated successfully.'
            ]);
        } catch (\Exception $e) {
            $this->alert([
                'icon' => 'error',
                'text' => 'Failed to update user: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Handle single user deletion with SweetAlert confirmation
     */
    public function confirmSingleDelete($userId): void
    {
        $this->dispatch('swal:confirm-single', [
            'userId' => $userId,
            'title' => 'Delete User?',
            'text' => "Are you sure you want to delete this user? This action cannot be undone!",
            'icon' => 'warning',
            'confirmButtonText' => 'Yes, delete it!',
            'cancelButtonText' => 'Cancel'
        ]);
    }

    /**
     * Handle bulk user deletion with SweetAlert confirmation
     */
    public function confirmBulkDelete($selectedUsers): void
    {
        // Handle the array properly
        if (is_array($selectedUsers) && isset($selectedUsers[0]) && is_array($selectedUsers[0])) {
            $selectedUsers = $selectedUsers[0];
        }

        $count = count($selectedUsers);

        $this->dispatch('swal:confirm-bulk', [
            'selectedUsers' => $selectedUsers,
            'title' => 'Delete Selected Users?',
            'text' => "Are you sure you want to delete {$count} selected user(s)? This action cannot be undone!",
            'icon' => 'warning',
            'confirmButtonText' => 'Yes, delete them!',
            'cancelButtonText' => 'Cancel'
        ]);
    }

    /**
     * Execute the actual deletion
     */
    public function deleteUsers($selectedUsers = null): void
    {
        // Handle different input formats
        if (is_string($selectedUsers)) {
            // Try to decode JSON
            $decoded = json_decode($selectedUsers, true);
            if (is_array($decoded)) {
                $selectedUsers = $decoded;
            } else {
                // Assume it's a comma-separated string
                $selectedUsers = explode(',', $selectedUsers);
            }
        }

        $userIds = $this->extractUserIds($selectedUsers);

        if (empty($userIds)) {
            $this->alert([
                'icon' => 'error',
                'text' => 'No users specified for deletion.'
            ]);
            return;
        }

        try {
            $deletedCount = User::whereIn('id', $userIds)->delete();

            $message = match ($deletedCount) {
                0 => 'No users were deleted. They may have already been removed.',
                1 => 'User deleted successfully.',
                default => "$deletedCount users deleted successfully."
            };

            $this->alert([
                'icon' => $deletedCount > 0 ? 'success' : 'info',
                'text' => $message
            ]);

            if ($deletedCount > 0) {
                $this->resetPage();
                $this->dispatch('userDeleted');
            }
        } catch (\Exception $e) {
            $this->alert([
                'icon' => 'error',
                'text' => 'Error deleting users: ' . $e->getMessage()
            ]);
        }
    }

    public function import(): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $path = null;

        try {
            $path = $this->importFile->store('imports');
            $fullPath = Storage::path($path);

            if (!file_exists($fullPath)) {
                $this->alert([
                    'icon' => 'error',
                    'text' => 'Uploaded file not found.'
                ]);
                return;
            }

            $result = $this->processCsvImport($fullPath);

            if (isset($result['error'])) {
                $this->alert([
                    'icon' => 'error',
                    'text' => $result['error']
                ]);
            } else {
                $this->alert(
                    $result['created'] > 0 || $result['updated'] > 0
                    ? ['icon' => 'success', 'text' => $result['message']]
                    : ['icon' => 'info', 'text' => $result['message']]
                );

                if ($result['created'] > 0 || $result['updated'] > 0) {
                    $this->resetPage();
                    $this->dispatch('importCompleted');
                }
            }

        } catch (\Exception $e) {
            $this->alert([
                'icon' => 'error',
                'text' => 'Import failed: ' . $e->getMessage()
            ]);
        } finally {
            if ($path && Storage::exists($path)) {
                Storage::delete($path);
            }
            $this->importFile = null;
        }
    }

    private function processCsvImport(string $filePath): array
    {
        $handle = @fopen($filePath, 'r');

        if ($handle === false) {
            return ['error' => 'Failed to open uploaded file.'];
        }

        try {
            $header = fgetcsv($handle);

            if ($header === false) {
                return ['error' => 'CSV file appears to be empty or invalid.'];
            }

            $header = array_map('strtolower', array_map('trim', $header));
            $nameIndex = array_search('name', $header);
            $emailIndex = array_search('email', $header);

            if ($nameIndex === false || $emailIndex === false) {
                return ['error' => 'CSV must contain both "name" and "email" columns. Found columns: ' . implode(', ', $header)];
            }

            $created = 0;
            $updated = 0;
            $skipped = 0;

            DB::beginTransaction();

            try {
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) <= max($nameIndex, $emailIndex)) {
                        $skipped++;
                        continue;
                    }

                    $name = trim($row[$nameIndex] ?? '');
                    $email = trim($row[$emailIndex] ?? '');

                    if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $skipped++;
                        continue;
                    }

                    $user = User::withTrashed()->where('email', $email)->first();

                    if ($user) {
                        if ($user->trashed()) {
                            $user->restore();
                            $user->update(['name' => $name]);
                            $updated++;
                        } else {
                            $skipped++;
                        }
                    } else {
                        User::create([
                            'name' => $name,
                            'email' => $email,
                            'password' => Hash::make('12345678'),
                        ]);
                        $created++;
                    }
                }

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            $message = $this->buildImportMessage($created, $updated, $skipped);

            return [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'message' => $message,
            ];

        } finally {
            fclose($handle);
        }
    }

    private function buildImportMessage(int $created, int $updated, int $skipped): string
    {
        $parts = [];

        if ($created > 0) {
            $parts[] = $created === 1 ? '1 new user imported' : "$created new users imported";
        }

        if ($updated > 0) {
            $parts[] = $updated === 1 ? '1 deleted user restored and updated' : "$updated deleted users restored and updated";
        }

        if (empty($parts)) {
            if ($skipped > 0) {
                return "No users were imported. $skipped record(s) were skipped due to invalid data or duplicates.";
            }
            return 'No valid records found to import. Please check your CSV file format.';
        }

        $message = ucfirst(implode(', ', $parts));

        if ($skipped > 0) {
            $message .= ". $skipped record(s) were skipped.";
        } else {
            $message .= " successfully.";
        }

        return $message;
    }

    public function export()
    {
        $users = $this->getUsersForExport();

        if ($users->isEmpty()) {
            $this->alert([
                'icon' => 'warning',
                'text' => 'No users found to export.'
            ]);
            return null;
        }

        $csvData = $this->generateCsvContent($users);
        $fileName = 'users_export_' . now()->format('Ymd_His') . '.csv';

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$fileName}\"")
            ->header('Cache-Control', 'private, max-age=0, must-revalidate');
    }

    private function getUsersForExport()
    {
        $idsParam = request()->query('ids');

        if ($idsParam) {
            $ids = \is_array($idsParam)
                ? array_filter($idsParam, 'is_numeric')
                : array_filter(explode(',', $idsParam), 'is_numeric');

            if (empty($ids)) {
                return collect();
            }

            return User::whereIn('id', $ids)->orderByDesc('id')->get();
        }

        $searchTerm = request()->query('search', $this->search);

        return User::where(function ($query) use ($searchTerm) {
            $query->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('email', 'like', "%{$searchTerm}%");
        })->orderByDesc('id')->get();
    }

    private function generateCsvContent($users): string
    {
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['ID', 'Name', 'Email', 'Created At']);

        foreach ($users as $index => $user) {
            fputcsv($csv, [
                $index + 1,
                $user->name,
                $user->email,
                $user->created_at,
            ]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return $content;
    }

    private function extractUserIds($data): array
    {
        return match (true) {
            is_numeric($data) => [$data],
            \is_array($data) && isset($data['userId']) => [$data['userId']],
            \is_array($data) && isset($data['selectedUsers']) => (array) $data['selectedUsers'],
            \is_string($data) && ($decoded = json_decode($data, true)) && \is_array($decoded) => $decoded,
            \is_array($data) => $data,
            default => [],
        };
    }



    public function render()
    {
        $search = trim($this->search ?? '');
        $users = User::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.search-user', [
            'users' => $users,
        ]);
    }
}