<?php

use App\Http\Controllers\FileExplorerController;
use App\Http\Controllers\IngestSidebarController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectIngestController;
use App\Http\Controllers\VolumeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Hello');
});

Route::get('/settings', function () {
    return Inertia::render('Settings');
})
    ->name('settings');

Route::get('/settings/volumes', [VolumeController::class, 'index'])
    ->name('settings.volumes');
Route::post('/settings/volumes', [VolumeController::class, 'store'])
    ->name('settings.volumes.store');

Route::delete('/settings/volumes/{id}', [VolumeController::class, 'destroy'])
    ->name('settings.volumes.destroy');


Route::get('/projects', [ProjectController::class, 'index'])
    ->name('projects');
Route::post('/projects', [ProjectController::class, 'store'])
    ->name('projects.store');
Route::get('/projects/{project}', [ProjectController::class, 'show'])
    ->name('projects.show');
Route::get('/projects/{project}/ingestrules', [ProjectIngestController::class, 'index']);
Route::post('/projects/{project}/ingestrules', [ProjectIngestController::class, 'store']);

Route::get('/ingest', [IngestSidebarController::class, 'index'])
    ->name('ingest');

Route::post('/ingest', [IngestSidebarController::class, 'store'])
    ->name('ingest.start');


Route::get('/files/{path}', [FileExplorerController::class, 'index'])
    ->where('path', '(.*)');

Route::get('/test', function () {

    $reader = \PHPExif\Reader\Reader::factory(\PHPExif\Reader\Reader::TYPE_EXIFTOOL);
    dd($reader->read('/home/orakmoya/out.wav'));

    return 0;
});
