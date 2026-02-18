<?php

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});


// Route::middleware('web')->group(function () {
//     Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);
//     require __DIR__.'/auth.php';
// });
