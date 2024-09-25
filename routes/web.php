<?php

use App\Helpers\IngestRuleFactory;
use App\Http\Controllers\FileExplorerController;
use App\Http\Controllers\IngestSidebarController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\VolumeController;
use App\Models\IngestRule;
use App\Models\File;
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
Route::get('/projects/{id}', [ProjectController::class, 'show'])
    ->name('projects.show');

Route::get('/ingest', [IngestSidebarController::class, 'index'] )
    ->name('ingest');

Route::post('/ingest', [IngestSidebarController::class, 'store'] )
    ->name('ingest.start');


Route::get('/files/{path}', [FileExplorerController::class, 'index'])
    ->where('path', '(.*)');

Route::get('/test', function () {
    $ingestRuleModels = IngestRule::all();
    $ingestRules = [];
    foreach($ingestRuleModels as $ingestRuleModel){
        $ingestRules = array_merge($ingestRules, IngestRuleFactory::create(json_decode($ingestRuleModel->rules, true)));
    }

    $files = File::all();
    foreach ($files as $file) {
        $path = null;
        foreach ($ingestRules as $ingestRule) {
            $path = $ingestRule->handle($file);
            if ($path) {
                dump($path);
                break;
            }
        }
    }



    return 0;
});
