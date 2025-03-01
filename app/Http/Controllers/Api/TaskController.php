<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        return response()->json([
            'statusCode' => 'TXN',
            'message'    => 'Tasks fetched successfully',
            'data'       => Task::all(),
        ]);
    }

    public function show($id)
    {
        if (! $task = Task::find($id)) {
            return response()->json([
                'statusCode' => 'ERR',
                'message'    => 'Task not found',
            ], 404);
        }
        return response()->json([
            'statusCode' => 'TXN',
            'message'    => 'Task fetched successfully',
            'data'       => $task,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'  => 'required|string|min:5|max:255',
            'status' => 'required|in:pending,in_progress,completed',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 'ERR',
                'message'    => 'Validation error',
                'data'       => $validator->errors(),
            ], 422);
        }
        $task = Task::create([
            'title'   => $request->get('title'),
            'user_id' => auth()->user()->id,
            'status'  => $request->get('status'),
        ]);

        return response()->json([
            'statusCode' => 'TXN',
            'message'    => 'Task created successfully',
            'data'       => $task,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (! $task = Task::find($id)) {
            return response()->json([
                'statusCode' => 'ERR',
                'message'    => 'Task not found',
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'title'  => 'required|string|min:5|max:255',
            'status' => 'required|in:pending,in_progress,completed',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 'ERR',
                'message'    => 'Validation error',
                'data'       => $validator->errors(),
            ], 422);
        }
        $task->update([
            'title'  => $request->get('title'),
            'status' => $request->get('status'),
        ]);
        return response()->json([
            'statusCode' => 'TXN',
            'message'    => 'Task updated successfully',
            'data'       => $task,
        ]);
    }

    public function destroy($id)
    {
        if (! $task = Task::find($id)) {
            return response()->json([
                'statusCode' => 'ERR',
                'message'    => 'Task not found',
            ], 404);
        }
        $task->delete();
        return response()->json([
            'statusCode' => 'TXN',
            'message'    => 'Task deleted successfully',
        ]);
    }

    // user tasks
    public function userTasks()
    {
        $usersWithPendingTasks = User::whereHas('tasks', function ($query) {
            $query->where('status', 'pending');
        })->withCount(['tasks as pendingTaskCount' => function ($query) {
            $query->where('status', 'pending');
        }])->get();

        $output = [];
        foreach ($usersWithPendingTasks as $user) {
            $output[] = [
                'name'             => $user->name,
                'pendingTaskCount' => $user->pendingTaskCount,
            ];
        }
        return response()->json([
            'statusCode' => 'TXN',
            'message'    => 'User tasks fetched successfully',
            'data'       => $output,
        ]);

        $userTask                 = User::with('tasks')->get();
        $userTaskWithpendingCount = [];
        $userTask->map(function ($user) use (&$userTaskWithpendingCount) {
            $userTaskWithpendingCount[] = [
                'user'         => $user,
                'pendingCount' => $user->tasks->where('status', 'pending')->count(),
            ];
        });

        return response()->json([
            'statusCode' => 'TXN',
            'message'    => 'User tasks fetched successfully',
            'data'       => $userTaskWithpendingCount,
        ]);

    }

}
