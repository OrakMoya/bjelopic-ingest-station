<?php

namespace App\Console\Commands;

use App\Events\FileIngestedEvent;
use App\Events\IngestCompleteEvent;
use App\Events\IngestEvent;
use App\Events\IngestStartedEvent;
use App\Helpers\IngestRuleFactory;
use App\Models\File;
use App\Models\IngestRule;
use App\Models\Project;
use App\Models\Volume;
use Illuminate\Console\Command;
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
    public function handle()
    {
        $project = null;
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

        if (!$project) {
            $this->error('Project with id ' . $projectId . ' not found.');
            return 1;
        }
        $projectVolume = Volume::find($project->volume_id);


        $ingestRuleModels = IngestRule::select('*')
            ->where('project_id', '=', $project->id)
            ->get();
        $ingestRules = [];
        foreach ($ingestRuleModels as $ingestRuleModel) {
            $ingestRules = array_merge($ingestRules, IngestRuleFactory::create(json_decode($ingestRuleModel->rules, true)));
        }
        $ingestVolumes = Volume::select('*')
            ->where('type', '=', 'ingest')
            ->get();


        IngestStartedEvent::dispatch(
            'Ingest started!',
            File::select('id', 'volume_id')
                ->whereIn(
                    'volume_id',
                    Volume::select('id', 'type')->where('type', '=', 'ingest')->pluck('id')
                )
                ->count()
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
                if (!$newProjectRelativePath) {
                    $this->error('New project relative path is null.');
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
                $this->info("---");

                $projectVolume->getDiskInstance()->makeDirectory($newVolumeRelativePath);

                rename($currentFullAbsolutePath, $newFullAbsolutePath);

                $file->path = $newVolumeRelativePath;
                $file->volume_id = $projectVolume->id;
                $file->save();

                FileIngestedEvent::dispatch($file);
            }
        }

        IngestCompleteEvent::dispatch('Ingest complete!');

        return 0;
    }
}
