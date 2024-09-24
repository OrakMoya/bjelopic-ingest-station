<?php

namespace App\Services;

use App;
use App\Exceptions\InvalidVolumeException;
use App\Models\Volume;
use Illuminate\Support\Str;
use Storage;

class VolumeService
{
    /**
     * @param array<string,string> $attributes
     */
    public function addNewVolume(array $attributes): Volume
    {
        $absolute_path = Str::chopEnd($attributes['absolute_path'], '/');
        $display_name = $attributes['display_name'];
        $type = $attributes['type'];

        if (!is_dir($absolute_path)) {
            $message = 'Path for new volume at \'' . $absolute_path . '\' does not exist.';
            throw new InvalidVolumeException($message);
        }

        $volume = Volume::create([
            'display_name' => $display_name,
            'absolute_path' => $absolute_path,
            'type' => $type,
        ]);

        return $volume;
    }


    /**
     * @param int $id
     */
    public function deleteVolume(int $id): bool
    {
        $volume = Volume::find($id);
        if (!$volume) {
            throw new InvalidVolumeException('Volume with id ' . $id . ' not found');
        }

        return $volume->delete();
    }

    /**
     * @param App\Models\Volume $volume
     * @param \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string  $path
     * @param \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string|array|null  $file
     * @param string|array|null  $name
     * @param mixed  $options
     */
    public function writeFile(Volume $volume, $path, $file, $name = null, $options = []): string|false
    {
        $disk = Storage::build([
            'driver' => 'local',
            'root' => $volume->absolute_path
        ]);

        return $disk->putFileAs($path, $file, $name, $options);
    }


    public function createDirectory(Volume $volume, string $path): bool
    {
        $disk = Storage::build([
            'driver' => 'local',
            'root' => $volume->absolute_path
        ]);
        return $disk->makeDirectory($path);
    }


    public function getFreeSpace(Volume $volume)
    {
        return disk_free_space($volume->absolute_path);
    }

    public function getTotalSpace(Volume $volume)
    {
        return disk_total_space($volume->absolute_path);
    }
}
