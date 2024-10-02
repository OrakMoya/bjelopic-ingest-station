<?php

namespace App\Actions;

use App\Events\FileIngestedEvent;
use App\Events\IngestCompleteEvent;
use App\Events\IngestErrorEvent;
use App\Events\IngestStartedEvent;
use App\Exceptions\IngestException;
use App\Exceptions\InvalidVolumeException;
use App\Helpers\IngestRule as IngestRuleObject;
use App\Helpers\IngestRuleFactory;
use App\Jobs\IngestFilesJob;
use App\Models\File;
use App\Models\IngestRule as IngestRuleModel;
use App\Models\Project;
use App\Models\Volume;
use Concurrency;
use GuzzleHttp\Utils;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class IngestAction
{
    public function prepare(Project $project, Collection $files, array $newPaths): array
    {
        $jobs = [];
        foreach ($files as $file) {
            array_push($jobs, new IngestFilesJob($project, [$file], $newPaths, count($files)));
        }
        return $jobs;
    }

    public function run(Project $project): void
    {
        $volumeLock = Cache::lock('volumes');

        $volumeLock->block(5, function () use ($project) {
            $ingestVolumes = $this->getAllIngestVolumes();

            foreach ($ingestVolumes as $ingestVolume) {
                assert($ingestVolume->type == 'ingest');
            }


            // Check if there are actually any files to ingest
            $filesToIngest = $this->getFilesToIngest($ingestVolumes);
            $totalIngestFileCount = $filesToIngest->count();
            if ($totalIngestFileCount == 0) {
                $msg = 'No files to ingest.';
                IngestErrorEvent::dispatch($msg);

                throw new IngestException($msg);
            }


            $ingestRules = $this->getIngestRules($project);
            $newProjectRelativePaths = $this->getNewProjectRelativeFilePaths(
                $ingestRules,
                $filesToIngest,
            );

            $jobs = $this->prepare($project, $filesToIngest, $newProjectRelativePaths);
            $batch = Bus::batch($jobs)
                ->before(
                    function () use ($totalIngestFileCount) {
                        Cache::put('ingesting', true);
                        IngestStartedEvent::dispatch('Ingest started!', $totalIngestFileCount);
                    }
                )
                ->catch(
                    function (Batch $batch, Throwable $e) {
                        Cache::forget('ingesting');
                        IngestErrorEvent::dispatch($e->getMessage());
                    }
                )
                ->then(
                    function () {
                        Cache::forget('ingesting');
                        IngestCompleteEvent::dispatch('Ingest complete!');
                    }
                )
                ->dispatch();
        });
    }

    /**
     * Moves files to their proper locations inside a project. The key
     * for the newPaths array should be the file's ID.
     * @param array<int, File>|Collection<File> $files
     * @param array<int, string> $newPaths
     */
    public function performIngest(Project $project, array|Collection $files, array $newPaths, int $totalFileCount = 0): void
    {
        $volumeLock = Cache::lock('volumes');
        $volumeLock->block(5, function () use($project, $files, $newPaths, $totalFileCount) {

            $projectVolume = $project->volume ?? Volume::find($project->volume_id)->first();

            foreach ($files as $file) {
                $projectRelativePath = $newPaths[$file->id];
                assert($projectRelativePath);

                // Get current file's ingest volume
                $ingestVolume = $file->volume ?? Volume::where('id', '=', $file->volume_id)->first();
                if (is_null($ingestVolume)) {
                    $msg = 'Failed getting ingest volume for file.';
                    IngestErrorEvent::dispatch($msg);

                    throw new IngestException($msg);
                }

                $currentFullAbsolutePath = $ingestVolume->absolute_path . '/' . $file->path . '/' . $file->filename;
                $newVolumeRelativePath = $project->title . '/' . $projectRelativePath;
                $newFullVolumeRelativePath = $newVolumeRelativePath . '/' . $file->filename;
                $newFullAbsolutePath = $projectVolume->absolute_path . '/' . $newFullVolumeRelativePath;

                $writeSuccessful = $this->prepareTargetDirectory(
                    $projectVolume,
                    $newVolumeRelativePath
                );
                if (!$writeSuccessful) {
                    $msg = 'Failed creating directory \'' . $newVolumeRelativePath
                        . '\' in volume ' . $projectVolume->display_name;

                    throw new InvalidVolumeException($msg);
                }

                // Ingested file aleady exists at the target path in project
                $fileAlreadyExists = $projectVolume
                    ->getDiskInstance()
                    ->exists($newFullVolumeRelativePath);
                if ($fileAlreadyExists) {
                    $originalHash = hash_file('md5', $currentFullAbsolutePath);
                    $newHash = hash_file('md5', $newFullAbsolutePath);
                    if ($originalHash != $newHash) {
                        Log::info('File already exists but isnt equal. Recopying...');
                        unlink($newFullAbsolutePath);
                        $fileAlreadyExists = false;
                    } else {
                        Log::info('File already exists and is equal. Skipping...');
                        unlink($currentFullAbsolutePath);
                    }
                }

                // This gets checked again because the code block above may modify it
                if (!$fileAlreadyExists) {
                    $moveSuccessful = $this->moveFile($currentFullAbsolutePath, $newFullAbsolutePath);
                    if (!$moveSuccessful) {
                        $msg = 'Failed moving file ' . $file->filename .
                            ' to volume  ' . $projectVolume->display_name;

                        IngestErrorEvent::dispatch($msg);
                        throw new IngestException($msg);
                    }
                }

                try {
                    // Update file in database to point to new location
                    $file->path = $newVolumeRelativePath;
                    $file->volume_id = $projectVolume->id;
                    $file->save();
                } catch (\Throwable) {
                    // This is most likely caused by the file already being indexed
                    // by the time this gets ran so we just delete the old
                    // file's row in the database.
                    $file->delete();
                }
                if ($totalFileCount) {
                    FileIngestedEvent::dispatch($file, $totalFileCount);
                } else {
                    FileIngestedEvent::dispatch($file, count($files));
                }
            }
        });
    }



    /**
     * Takes a File model inside an ingest volume and an array of ingest
     * rules it should conform to and returns it's new path inside
     * of whatever project the ingest rules belong to.
     * @param array<int, IngestRuleObject> $ingestRules
     * @param callable|null $callback
     * @return string
     */
    public function getNewProjectRelativeFilePath(array $ingestRules, File|int $file, callable|null $callback = null): string
    {
        assert($file->filename);
        assert($file->path);
        assert($file->mimetype);

        $newProjectRelativePath = null;

        foreach ($ingestRules as $ingestRule) {
            $newProjectRelativePath = $ingestRule->handle($file);

            // Ingest rule handler arrived at a save operation
            if (gettype($newProjectRelativePath) == 'string') {
                break;
            }
        }

        // File matches no rules. Abort.
        if (is_bool($newProjectRelativePath) && !$newProjectRelativePath) {
            $msg = 'File ' . $file->filename . ' matches no rules. Aborting ingest.';

            IngestErrorEvent::dispatch($msg);
            throw new IngestException($msg);
        }


        // Get current file's ingest volume
        $ingestVolume = $file->volume ?? Volume::where('id', '=', $file->volume_id)->first();
        if (is_null($ingestVolume)) {
            $msg = 'Failed getting ingest volume for file.';
            IngestErrorEvent::dispatch($msg);

            throw new IngestException($msg);
        }

        if ($callback) {
            $callback($file, $newProjectRelativePath);
        }

        return $newProjectRelativePath;
    }

    /**
     * Takes an array of File models inside an ingest volume and an array of
     * ingest rules they should conform to and returns their new paths
     * inside of whatever project the ingest rules belong to. The
     * callback function is passed the processed file and it's
     * new path.
     * @param array<int, IngestRuleObject> $ingestRules
     * @param Collection<File> $files
     * @param callable|null $callback Something to call after processing each file.
     * @return array<int, string>
     */
    public function getNewProjectRelativeFilePaths(array $ingestRules, Collection $files, callable|null $callback = null): array
    {
        $returnData = [];
        foreach ($files as $file) {
            $returnData[$file->id] = $this->getNewProjectRelativeFilePath($ingestRules, $file, $callback);
        }

        return $returnData;
    }

    /**
     * @return array<int, IngestRuleObject>
     */
    public function getIngestRules(Project $project): array
    {
        $ingestRuleModels = IngestRuleModel::select('*')
            ->where('project_id', '=', $project->id)
            ->get();
        $ingestRules = [];

        foreach ($ingestRuleModels as $ingestRuleModel) {
            $ingestRules = array_merge($ingestRules, IngestRuleFactory::create(Utils::jsonDecode($ingestRuleModel->rules, true)));
        }

        return $ingestRules;
    }

    /**
     * @return Collection<Volume>
     */
    private function getAllIngestVolumes(): Collection
    {
        return Volume::select('*')
            ->where('type', '=', 'ingest')
            ->get();
    }

    /**
     * @return Collection<File>
     */
    private function getFilesToIngest(array|Collection $ingestVolumes): Collection
    {
        $volumeIds = [];

        if ($ingestVolumes instanceof Collection) {
            $volumeIds = $ingestVolumes->pluck('id');
        } else {
            foreach ($ingestVolumes as $volume) {
                array_push($volumeIds, $volume->id);
            }
        }

        $filesToIngest =  File::select('*')
            ->whereIn('volume_id', $volumeIds)
            ->orderBy('created_at', 'ASC')
            ->with('volume')
            ->get();

        return $filesToIngest;
    }

    private function prepareTargetDirectory(Volume $volume, string $volumeRelativePath): bool
    {
        return $volume
            ->getDiskInstance()
            ->makeDirectory($volumeRelativePath);
    }

    private function moveFile(string $currentFullAbsolutePath, string $newFullAbsolutePath): bool
    {
        $copySuccess = copy($currentFullAbsolutePath, $newFullAbsolutePath);
        if (!$copySuccess) return false;

        $originalHash = hash_file('md5', $currentFullAbsolutePath);
        $newHash = hash_file('md5', $newFullAbsolutePath);

        if ($originalHash == $newHash) {
            unlink($currentFullAbsolutePath);
            return true;
        }

        return false;
    }

    /**
     * @param Collection<File> $filesToIngest
     */
    private function getTotalSize(Collection $filesToIngest): int
    {
        $size = 0;
        foreach ($filesToIngest as $file) {
            $fileVolume = $file->volume ?? Volume::find($file->volume_id);
            assert($fileVolume);

            $size += $fileVolume->getDiskInstance()->size($file->path . '/' . $file->filename);
        }
        return $size;
    }
}
