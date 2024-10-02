<?php

namespace App\Jobs;

use App\Actions\IndexFilesAction;
use App\Actions\IngestAction;
use App\Models\Volume;
use Cache;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class IndexFilesJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $volumes = Volume::select('*')
            ->get();
        $indexAction = new IndexFilesAction();
        foreach ($volumes as $volume) {
            $indexAction->handle($volume, '/');
        }

        Cache::forget('index:running');
        Cache::forget('index:jobpresent');
    }

    public function failed(): void
    {
        Cache::forget('index:running');
        Cache::forget('index:jobpresent');
    }
}
