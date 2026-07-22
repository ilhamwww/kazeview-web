<?php

use App\Http\Controllers\Api\ContentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes pada file ini otomatis di-prefix dengan "/api" oleh Laravel.
|
*/

Route::prefix('v1')->group(function () {
    // List & detail
    Route::get('/contents', [ContentController::class, 'index'])
        ->name('api.contents.index');

    Route::get('/contents/{id}/show', [ContentController::class, 'show'])
        ->whereNumber('id')
        ->name('api.contents.show');

    // Tambah (dilindungi API key)
    Route::post('/contents/store', [ContentController::class, 'store'])
        ->middleware('api.key')
        ->name('api.contents.store');

    // Edit (POST untuk dukungan multipart upload, PUT/PATCH juga didukung)
    Route::match(['put', 'patch', 'post'], '/contents/{id}/edit', [ContentController::class, 'update'])
        ->whereNumber('id')
        ->middleware('api.key')
        ->name('api.contents.edit');

    // Hapus
    Route::match(['delete', 'post'], '/contents/{id}/delete', [ContentController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware('api.key')
        ->name('api.contents.delete');
});
