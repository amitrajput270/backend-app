<?php

use Illuminate\Support\Facades\Route;

Route::get('users', [App\Http\Controllers\Api\AuthController::class, 'index']);
Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/forget-password', [App\Http\Controllers\Api\AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [App\Http\Controllers\Api\AuthController::class, 'resetPassword']);

Route::middleware(['auth:api'])->group(function () {
    Route::get('/user', [App\Http\Controllers\Api\AuthController::class, 'profile']);
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::post('/refresh', [App\Http\Controllers\Api\AuthController::class, 'refresh']);

    // Task routes
    Route::get('/tasks', [App\Http\Controllers\Api\TaskController::class, 'index']);
    Route::get('/tasks/{id}', [App\Http\Controllers\Api\TaskController::class, 'show']);
    Route::post('/tasks', [App\Http\Controllers\Api\TaskController::class, 'store']);
    Route::put('/tasks/{id}', [App\Http\Controllers\Api\TaskController::class, 'update']);
    Route::delete('/tasks/{id}', [App\Http\Controllers\Api\TaskController::class, 'destroy']);
    Route::get('userTasks', [App\Http\Controllers\Api\TaskController::class, 'userTasks']);
});


Route::any('custom-webhook-gitguardian', function () {
    $payload = file_get_contents('php://input');
    $signature = hash_hmac('sha256', $payload, env('GITGUARDIAN_WEBHOOK_SECRET'));
    $headers = getallheaders();
    if (isset($headers['X-GitGuardian-Signature']) && hash_equals($signature, $headers['X-GitGuardian-Signature'])) {
        // Process the webhook payload
        echo "Webhook received and verified.";
    } else {
        http_response_code(403);
        echo "Invalid signature.";
    }
});

Route::get('hash-password', function () {
    $password = 'peb@123';
    $hashedPassword = Hash::make($password);
    return response()->json(['hashed_password' => $hashedPassword]);
});



Route::resource('cron-jobs', \App\Http\Controllers\CronJobController::class);
Route::any('create-users', [App\Http\Controllers\CronJobController::class, 'createUsers']);

Route::any('test', function () {});
