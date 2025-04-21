<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('users');
});

Route::get('test', \App\Http\Livewire\Counter::class);
Route::any('search-user', \App\Http\Livewire\SearchUser::class)->name('search-user');

Route::get('demo', function (Request $request) {

    $a             = [2, 4, 67, 5, 4, 9, 10, 12, 8, 7, 4];
    $a             = array_unique($a);
    $firstLargest  = 0;
    $secondLargest = 0;
    $thirdLargest  = 0;

    foreach ($a as $key => $value) {
        if ($value > $firstLargest) {
            $thirdLargest  = $secondLargest;
            $secondLargest = $firstLargest;
            $firstLargest  = $value;
        } elseif ($value > $secondLargest) {
            $thirdLargest  = $secondLargest;
            $secondLargest = $value;
        } elseif ($value > $thirdLargest) {
            $thirdLargest = $value;
        }
    }

    return response()->json([
        'firstLargest'  => $firstLargest,
        'secondLargest' => $secondLargest,
        'thirdLargest'  => $thirdLargest,
    ]);

});
