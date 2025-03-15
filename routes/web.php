<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('users');
});

Route::get('test', \App\Http\Livewire\Counter::class);

Route::any('search-user', \App\Http\Livewire\SearchUser::class)->name('search-user');
