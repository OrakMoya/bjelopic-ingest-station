<?php

namespace App\Services;

use App\Exceptions\InvalidVolumeException;
use App\Models\Project;
use App\Models\Volume;

class ProjectService
{

    private $project_structure =
    [
        'Delivery',
        'Graphics',
        'Location Audio and ADR',
        'Music',
        'Project Files and Backups',
        'Raw Footage',
        'Reference',
        'Renders',
        'Stills',
        'Transcoded Footage',
        'Workflow'
    ];

    public function createNewProject(array $attributes): Project
    {
        $volumeId = $attributes['volume_id'];
        $volume = Volume::find($attributes['volume_id']);
        $title = $attributes['title'];

        if (!$volume) {
            throw new InvalidVolumeException('Volume with id ' . $volumeId . ' not found');
        }
        if (!$volume->is_alive) {
            throw new InvalidVolumeException('Volume with id ' . $volumeId . ' is not alive');
        }

        $volumeService = new VolumeService();

        if ($volumeService->createDirectory($volume, $title)) {
            foreach ($this->project_structure as $directory) {
                $volumeService->createDirectory($volume, $title . '/' . $directory);
            }
        }

        return Project::create([
            'title' => $title,
            'volume_id' => $volumeId,
        ]);
    }
}
