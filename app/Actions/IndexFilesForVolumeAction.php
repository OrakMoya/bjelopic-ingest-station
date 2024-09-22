<?php

namespace App\Actions;

use App\Models\File;
use App\Models\Volume;
use Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IndexFilesForVolumeAction
{
    public function handle(Volume $volume): int
    {
        return Artisan::call('index-files', ['--volume', $volume->display_name]);
    }
}
