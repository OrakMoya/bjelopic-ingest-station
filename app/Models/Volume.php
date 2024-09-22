<?php

namespace App\Models;

use App\Exceptions\InvalidVolumeException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Storage;

class Volume extends Model
{
    use HasFactory;

    protected $fillable = [
        'display_name',
        'absolute_path'
    ];


    public function getDiskInstance(): Filesystem
    {
        if (!is_dir($this->attributes['absolute_path'])) {
            $message = 'Missing path \'' . $this->attributes['absolute_path'] . '\' of volume \'' . $this->attributes['display_name'] . '\'';
            throw new InvalidVolumeException($message);
        }

        return Storage::build([
            'driver' => 'local',
            'root' => $this->attributes['absolute_path']
        ]);
    }
}
