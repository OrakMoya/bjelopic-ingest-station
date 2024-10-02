<?php

namespace App\Jobs;

use App\Actions\IngestAction;
use App\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use App\Models\File;
use Throwable;

class IngestFilesJob implements ShouldQueue
{
    use Queueable, Batchable;

    public $retryAfter = 300;
    public $tries = 2;

    /**
     * Create a new job instance.
     * @param array<int, File> $files
     * @param array<int, string> $newPaths
     */
    public function __construct(
        public Project $project,
        public array $files,
        public array $newPaths,
        public int $totalFileCount = 0
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...
            return;
        }

        (new IngestAction())->performIngest($this->project, $this->files, $this->newPaths, $this->totalFileCount);
    }

    public function failed(?Throwable $exception): void
    {
        $this->batch()->cancel();
        Cache::forget('ingesting');
        Cache::lock('volumes')->forceRelease();

        throw $exception;
    }
}
