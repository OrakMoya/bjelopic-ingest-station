<?php

namespace App\Services;

use App\Exceptions\InvalidVolumeException;
use App\Models\Volume;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VolumeService
{
    public function addNewVolume(array $attributes): Volume
    {
        $absolute_path = $attributes['absolute_path'];
        $display_name = $attributes['display_name'];

        if (!is_dir($absolute_path)) {
            $message = 'Path for new volume at \'' . $absolute_path . '\' does not exist.';
            throw new InvalidVolumeException($message);
        }

        $volume = Volume::create([
            'display_name' => $display_name,
            'absolute_path' => $absolute_path
        ]);

        return $volume;
    }
}
