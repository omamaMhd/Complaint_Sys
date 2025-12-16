<?php

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});


Route::post('/login', [AdminController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
 Route::post('/logout', [AdminController::class, 'logout']);
    
    //Route::get('/complaints/my', [ComplaintController::class, 'myComplaints']);
     Route::get('/departmentComplaints', [ComplaintController::class, 'departmentComplaints']) ->middleware('auth:employee');
    // Route::patch('/complaints/{id}/status', [ComplaintController::class, 'changeStatus'])
    //     ->middleware('auth:admin');
    // Route::get('/complaints/{id}/history', [ComplaintController::class, 'history']);

});
 