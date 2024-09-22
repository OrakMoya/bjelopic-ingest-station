<?php

use App\Http\Controllers\FileExplorerController;
use App\Http\Controllers\VolumeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Hello');
});

Route::get('/settings', function () {
    return Inertia::render('Settings/Settings');
})
    ->name('settings');

Route::get('/settings/volumes', [VolumeController::class, 'index'])
    ->name('settings.volumes');
Route::post('/settings/volumes', [VolumeController::class, 'store'])
    ->name('settings.volumes.create');

Route::delete('/settings/volumes/{id}', [VolumeController::class, 'destroy'])
    ->name('settings.volumes.destroy');


Route::get('/files/{path}', [FileExplorerController::class, 'index'])
    ->where('path', '(.*)');
