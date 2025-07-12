<?php
namespace App\Http\Controllers;

use App\Models\CommandSchedule;
use App\Models\User;
use Cron\CronExpression;
use Faker\Factory as Faker;
use Illuminate\Http\Request;

class CronJobController extends Controller
{
    public function index()
    {
        $commandSchedules = CommandSchedule::active()->get();
        return response()->json([
            'success' => true,
            'message' => 'Cron jobs retrieved successfully.',
            'data'    => $commandSchedules,
        ]);
    }

    public function store(Request $request)
    {
        $request = array_merge([
            'is_active'      => 1,
            'is_overlapping' => 1,
            'type'           => 'LARAVEL_COMMAND',
            'environments'   => ['local', 'staging', 'production'],
        ], $request->all());
        $request = new Request($request);

        $validatorResponse = \Validator::make($request->all(), [
            'type'            => 'required|in:LARAVEL_COMMAND,SHELL_COMMAND',
            'name'            => 'required|string|max:255',
            'command'         => 'required|string',
            'schedule'        => ['required',
                function ($attribute, $value, $fail) {
                    if (! CronExpression::isValidExpression($value)) {
                        $fail('The ' . $attribute . ' is not a valid cron expression.');
                    }
                },
            ],
            'isActive'        => 'boolean',
            'isOverlapping'   => 'boolean',
            'pingUrlBefore'   => 'nullable|url',
            'pingUrlAfter'    => 'nullable|url',
            'monitorEmails'   => 'nullable|array',
            'monitorEmails.*' => 'email',
            'environments'    => 'nullable|array',
            'environments.*'  => 'string|in:local,staging,production',
            'description'     => 'nullable|string|max:255',
        ]);
        if ($validatorResponse->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validatorResponse->errors(),
            ], 422);
        }
        $validated = $validatorResponse->validated();

        $commandSchedule = CommandSchedule::updateOrCreate(
            ['command' => $validated['command']],
            [
                'name'            => $validated['name'],
                'command'         => $validated['command'],
                'schedule'        => $validated['schedule'],
                'is_active'       => $validated['isActive'],
                'is_overlapping'  => $validated['isOverlapping'],
                'ping_url_before' => $validated['pingUrlBefore'] ?? null,
                'ping_url_after'  => $validated['pingUrlAfter'] ?? null,
                'monitor_emails'  => implode(',', $validated['monitorEmails'] ?? null),
                'environments'    => implode(',', $validated['environments'] ?? null),
                'description'     => $validated['description'] ?? null,
            ]
        );

        if (! $commandSchedule) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create cron job.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cron job created successfully.',
            'data'    => $validated,
        ]);
    }

    public function createUsers()
    {
        $isUserCount = request()->input('isUserCount', false);
        $userCount   = User::count();
        $remaining   = 100 - $userCount;

        if ($isUserCount) {
            return response()->json([
                'success' => true,
                'message' => 'User count is ' . $userCount,
                'data'    => [
                    'userCount' => $userCount,
                    'remaining' => $remaining,
                ],
            ]);
        }

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
            return response()->json([
                'success' => true,
                'message' => "$remaining users created successfully.",
            ]);

        }
        return response()->json([
            'success' => true,
            'message' => 'User count is already ' . $userCount,
        ], 200);

    }

}
