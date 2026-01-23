<?php

use App\Http\Controllers\KeyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::middleware('verifyIotRoomKey')->group(function () {
    Route::get('/test', function () {
        return response()->json([
            'message' => 'Hello World',
        ]);
    });

    Route::post('/change-key-status', [KeyController::class, 'changeKeyStatus']);
});
