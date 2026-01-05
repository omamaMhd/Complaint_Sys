<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

// Route::get('/', function () {
//     return 'Hello from server running on port ' . request()->getPort();
//    // return view('welcome');
// });
Route::get('/', function () {
    Log::info('Request handled by server', [
        'server_id' => config('app.server_id'),
        'time' => now()->toDateTimeString(),
    ]);

    return view('welcome');
});

Route::get('/health', function () {
    return response('OK', 200);
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
 