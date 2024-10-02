<?php

use App\Jobs\IndexFilesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::call(function(){
    if(Cache::get('index:jobpresent', false)){
        return;
    }

    Cache::put('index:jobpresent', true);
    IndexFilesJob::dispatch();
})->everyFiveSeconds();
