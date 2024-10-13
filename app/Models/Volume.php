<?php

namespace App\Models;

use App\Events\VolumesChangedEvent;
use App\Exceptions\InvalidVolumeException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Volume extends Model
{
    use HasFactory;

    protected $fillable = [
        'display_name',
        'absolute_path',
        'type'
    ];


    public function getDiskInstance(): Filesystem
    {
        if (!is_dir($this->attributes['absolute_path'])) {
            $message = 'Missing path \'' . $this->attributes['absolute_path'] . '\' of volume \'' . $this->attributes['display_name'] . '\'';
            if ($this->attributes['is_alive'] == true) {
                $this->attributes['is_alive'] = false;
                $this->save();

                VolumesChangedEvent::dispatch();
                Log::info(var_export($this, true));
            }
            Log::error($message);

            throw new InvalidVolumeException($message);
        }

        $instance =  Storage::build([
            'driver' => 'local',
            'root' => $this->attributes['absolute_path']
        ]);

        if ($instance->fileMissing('.ingeststation')) {
            if ($this->attributes['is_alive'] == true) {
                $this->attributes['is_alive'] = false;
                $this->save();

                VolumesChangedEvent::dispatch();
                Log::info("2");
            }
            $message = 'Missing volume identifier file in volume ' . $this->attributes['display_name'];
            Log::error($message);
            throw new InvalidVolumeException($message);
        }

        if ($this->attributes['is_alive'] == false) {
            $this->attributes['is_alive'] = true;
            $this->save();

            VolumesChangedEvent::dispatch();
            Log::info("3");

        }


        return $instance;
    }


    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'volume_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'volume_id', 'id');
    }
}
