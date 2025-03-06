<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('test', function () {
    $my_list = [
        ['ID' => 1, 'post_title' => 'Hello World'],
        ['ID' => 2, 'post_title' => 'Sample Page'],
    ];

    $pages = array_column($my_list, 'post_title');

    dd($pages);

});
