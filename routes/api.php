<?php
 
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;
 
Route::middleware('verify.sso')->group(function () {
    Route::get('/v1/tickets', [TicketController::class, 'index']);
    Route::get('/v1/tickets/{id}', [TicketController::class, 'show']);
    Route::post('/v1/tickets', [TicketController::class, 'store']);
});
 



