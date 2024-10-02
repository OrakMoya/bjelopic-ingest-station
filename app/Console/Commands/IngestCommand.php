<?php

namespace App\Console\Commands;

use App\Events\FileIngestedEvent;
use App\Events\IngestCompleteEvent;
use App\Events\IngestErrorEvent;
use App\Events\IngestEvent;
use App\Events\IngestStartedEvent;
use App\Helpers\IngestRuleFactory;
use App\Models\File;
use App\Models\IngestRule;
use App\Models\Project;
use App\Models\Volume;
use Exception;
use GuzzleHttp\Utils;
use Illuminate\Console\Command;
use Log;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class IngestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ingest {--project-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $project = null;

        // Prompt for project if not specified
        if ($projectId = $this->option('project-id')) {
            $project = Project::find($projectId);
        } else {
            $projects = Project::all();
            if (!$projects->count()) {
                $this->error('No available projects.');
                return 1;
            }

            $options = [];
            $default = null;
            foreach ($projects as $project) {
                if (!$default) $default = $project->id;
                $options[$project->id] = $project->title;
            }

            $selectedProjectId = select(
                label: 'Select a project.',
                options: $options,
                default: $default
            );

            $project = $projects->find($selectedProjectId);
        }

        // Abort if project is null
        if (!$project) {
            $msg = 'Project with id ' . $projectId . ' not found.';

            $this->error($msg);
            Log::error($msg);
            IngestErrorEvent::dispatch($msg);

            return 1;
        }

        $projectVolume = Volume::find($project->volume_id);
        if (!$projectVolume) {
            $msg = 'Failed getting project volume.' .
                ' Project id: ' . $project->id .
                ' Volume id: ' . $project->volume_id;

            $this->error($msg);
            Log::error($msg);
            IngestErrorEvent::dispatch($msg);

            return 1;
        }


        // Get ingest rules for project
        $ingestRuleModels = IngestRule::select('*')
            ->where('project_id', '=', $project->id)
            ->get();
        $ingestRules = [];

        try {
            foreach ($ingestRuleModels as $ingestRuleModel) {
                $ingestRules = array_merge($ingestRules, IngestRuleFactory::create(Utils::jsonDecode($ingestRuleModel->rules, true)));
            }
        } catch (Exception $e) {
            $msg = 'Failed parsing ingest rule. '. $e->getMessage();

            $this->error($msg);
            Log::error($e);
            IngestErrorEvent::dispatch($msg);

            return 1;
        }

        if (count($ingestRules) == 0) {
            $msg = 'Project ' . $project->title .
                ' (' . $project->id . ')' .
                ' has no defined ingest rules.';

            $this->error($msg);
            Log::error($msg);
            IngestErrorEvent::dispatch($msg);

            return 1;
        }

        $ingestVolumes = Volume::select('*')
            ->where('type', '=', 'ingest')
            ->get();

        if (count($ingestRules) == 0) {
            $msg = 'No available ingest volumes.';

            $this->error($msg);
            Log::error($msg);
            IngestErrorEvent::dispatch($msg);

            return 1;
        }

        // Get number of files needed to ingest for progress indicators
        $totalIngestFileCount = File::select('id', 'volume_id')
            ->whereIn(
                'volume_id',
                Volume::select('id', 'type')->where('type', '=', 'ingest')->pluck('id')
            )
            ->count();

        if ($totalIngestFileCount == 0) {
            $msg = 'No files to ingest.';

            $this->error($msg);
            Log::error($msg);
            IngestErrorEvent::dispatch($msg);

            return 1;
        }


        IngestStartedEvent::dispatch(
            'Ingest started!',
            $totalIngestFileCount
        );

        foreach ($ingestVolumes as $ingestVolume) {

            $filesInIngestVolume = File::select('*')
                ->where('volume_id', '=', $ingestVolume->id)
                ->orderBy('created_at', 'ASC')
                ->get();

            foreach ($filesInIngestVolume as $file) {
                $newProjectRelativePath = null;

                foreach ($ingestRules as $ingestRule) {
                    $newProjectRelativePath = $ingestRule->handle($file);
                    if (gettype($newProjectRelativePath) == 'string') {
                        break;
                    }
                }

                // Abort if ingesting file failed.
                if (!$newProjectRelativePath) {
                    $msg = 'New project relative path is null.';

                    $this->error($msg);
                    Log::error($msg);
                    IngestErrorEvent::dispatch($msg);

                    return 1;
                }


                $currentFullAbsolutePath = $ingestVolume->absolute_path . '/' . $file->path . '/' . $file->filename;
                $newVolumeRelativePath = $project->title . '/' . $newProjectRelativePath;
                $newFullVolumeRelativePath = $newVolumeRelativePath . '/' . $file->filename;
                $newFullAbsolutePath = $projectVolume->absolute_path . '/' . $newFullVolumeRelativePath;

                $this->info($newProjectRelativePath);
                $this->info($currentFullAbsolutePath);
                $this->info($newFullVolumeRelativePath);
                $this->info($newFullAbsolutePath);
                $this->info("");

                $writeSuccessful = $projectVolume->getDiskInstance()->makeDirectory($newVolumeRelativePath);
                if (!$writeSuccessful) {
                    $msg = 'Failed writing to volume with id ' . $projectVolume->id;

                    $this->error($msg);
                    Log::error($msg);
                    IngestErrorEvent::dispatch($msg);

                    return 1;
                }

                $fileAlreadyExists = $projectVolume->getDiskInstance()->exists($newFullVolumeRelativePath);
                if ($fileAlreadyExists) {
                    $msg = 'Ingested file already exists in project. ';

                    $this->error($msg);
                    Log::error($msg);
                    IngestErrorEvent::dispatch($msg);

                    return 1;
                }

                $moveSuccessful = rename($currentFullAbsolutePath, $newFullAbsolutePath);
                if (!$moveSuccessful) {
                    $msg = 'Failed moving file with id ' . $file->id .
                        ' to volume with id ' . $projectVolume->id;

                    $this->error($msg);
                    Log::error($msg);
                    IngestErrorEvent::dispatch($msg);

                    return 1;
                }

                $file->path = $newVolumeRelativePath;
                $file->volume_id = $projectVolume->id;
                $file->save();

                FileIngestedEvent::dispatch($file, $totalIngestFileCount);
            }
        }

        IngestCompleteEvent::dispatch('Ingest complete!');

        return 0;
    }
}
