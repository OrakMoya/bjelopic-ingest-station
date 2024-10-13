<?php

namespace App\Jobs;

use App\Actions\IngestAction;
use App\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use App\Models\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class IngestFilesJob implements ShouldQueue
{
    use Queueable, Batchable;

    public $retryAfter = 300;
    public $tries = 1;

    /**
     * Create a new job instance.
     * @param array<int, File> $files
     * @param array<int, string> $newPaths
     * @param array<int,mixed> $ingestSettings
     */
    public function __construct(
        public Project $project,
        public array $files,
        public array $newPaths,
        public int $totalFileCount = 0,
        public array $ingestSettings = []
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

        try {
            (new IngestAction())->performIngest($this->project, $this->files, $this->newPaths, $this->totalFileCount, $this->ingestSettings);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw $th;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->batch()->cancel();
        Cache::forget('ingesting');
        Cache::lock('volumes')->forceRelease();

        throw $exception;
    }
}
