<?php

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use App\Http\Controllers\Web\ShareMetaController;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/og', [ShareMetaController::class, 'homepage'])
    ->name('og.homepage');

Route::get('/share/projects/{identifier}', [ShareMetaController::class, 'project'])
    ->where('identifier', '.*')
    ->name('share.projects');

Route::get('/share/blog/{identifier}', [ShareMetaController::class, 'blog'])
    ->where('identifier', '.*')
    ->name('share.blog');


// Route::middleware('web')->group(function () {
//     Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);
//     require __DIR__.'/auth.php';
// });
