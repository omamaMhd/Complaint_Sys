<?php

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

/*

Route::middleware(['auth:web', 'role:officer'])->group(function () {
    Route::get('/officer/dashboard', [OfficerController::class, 'index']);
});

Route::middleware(['auth:web', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index']);
});


*/
 