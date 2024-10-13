<?php

use App\Http\Controllers\FileExplorerController;
use App\Http\Controllers\IngestDryRunController;
use App\Http\Controllers\IngestSidebarController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectIngestController;
use App\Http\Controllers\VolumeController;
use App\Http\Controllers\VolumeRefreshController;
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
Route::post('/settings/volumes/{volume}/refresh', [VolumeRefreshController::class, 'store'])
    ->name('settings.volumes.refresh');

Route::delete('/settings/volumes/{id}', [VolumeController::class, 'destroy'])
    ->name('settings.volumes.destroy');


Route::get('/projects', [ProjectController::class, 'index'])
    ->name('projects');
Route::post('/projects', [ProjectController::class, 'store'])
    ->name('projects.store');
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])
    ->name('projects.destroy');
Route::get('/projects/{project}', [ProjectController::class, 'show'])
    ->name('projects.show');
Route::get('/projects/{project}/ingestrules', [ProjectIngestController::class, 'index']);
Route::post('/projects/{project}/ingestrules', [ProjectIngestController::class, 'store']);

Route::get('/ingest', [IngestSidebarController::class, 'index'])
    ->name('ingest');
Route::get('/ingest/{file}', [IngestSidebarController::class, 'show'])
    ->name('ingest.details');

Route::post('/ingest', [IngestSidebarController::class, 'store'])
    ->name('ingest.start');
Route::delete('/ingest', [IngestSidebarController::class, 'destroy'])
    ->name('ingest.clear');

Route::get('/ingest/dryrun/{project}/{file}', [IngestDryRunController::class, 'show'])
    ->name('ingest.dryrun.forfile');
Route::get('/ingest/dryrun/{project}', [IngestDryRunController::class, 'index'])
    ->name('ingest.dryrun');


Route::get('/files/{path}', [FileExplorerController::class, 'index'])
    ->where('path', '(.*)');

Route::get('/test', function () {


    return 0;
});
