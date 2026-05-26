<?php

use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware(['auth.apikey'])->group(function () {
        Route::get('/tickets', [TicketController::class, 'index']);      
        Route::get('/tickets/{id}', [TicketController::class, 'show']);  
        Route::post('/tickets', [TicketController::class, 'store']);   
        Route::redirect('v1/documentation', url('api/documentation'));  
    });
});