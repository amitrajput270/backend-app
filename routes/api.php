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
});
