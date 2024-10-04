<?php

namespace App\Actions;

use App\Events\IndexEvent;
use App\Events\IngestIndexedEvent;
use App\Models\File;
use App\Models\Volume;
use Cache;
use DB;
use GuzzleHttp\Utils;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IndexFilesAction
{
    public function handle(Volume $volume, string $path): void
    {
        $lock = Cache::lock('volumes');
        $lock->get(function () use ($volume, $path) {
            $disk = $volume->getDiskInstance();
            $path = Str::replaceStart('/', '', $path);
            $path = Str::replaceStart('.', '', $path);

            $filesInDatabase = File::select('*')
                ->where('path', 'LIKE', $path . '%')
                ->where('volume_id', '=', $volume->id)
                ->get();

            $filesInVolume = $disk->allFiles($path);

            $tenSecondsAgo = Carbon::now()->subSeconds(10)->timestamp;
            $filesInVolume = array_reduce($filesInVolume, function ($previous, $current) use ($volume, $tenSecondsAgo) {
                $path = $volume->absolute_path . '/' . $current;
                $mtime = filemtime($path);
                $ctime = filectime($path);
                if (
                    $mtime < $tenSecondsAgo &&
                    $ctime < $tenSecondsAgo
                ) {
                    array_push($previous, $current);
                }
                return $previous;
            }, []);


            $missingFilesInDatabase = [];
            $extraFilesInDatabaseIds = [];

            foreach ($filesInDatabase as $fileInDatabase) {
                $fullPath =
                    ($fileInDatabase->path !== '.' ? $fileInDatabase->path  . '/' : '') . $fileInDatabase->filename;

                // A file in the database doesnt exist in the volume.
                $index = array_search($fullPath, $filesInVolume);
                if ($index === false) {
                    array_push($extraFilesInDatabaseIds, $fileInDatabase->id);
                } else {
                    // The file exists in the volume. Remove it from the array
                    // to speed up processing.
                    array_splice($filesInVolume, $index, 1);
                }
            }
            if (count($filesInVolume) || count($extraFilesInDatabaseIds)) {
                IndexEvent::dispatch('Index started!', true);
                Cache::put('index:running', true);
            }

            // Leftover files in this array don't exist in the database.
            $reader = \PHPExif\Reader\Reader::factory(\PHPExif\Reader\Reader::TYPE_EXIFTOOL);
            foreach ($filesInVolume as $fileInVolume) {
                $pathInfo = pathinfo($fileInVolume);
                $fullPath = $volume->absolute_path . '/' . $fileInVolume;
                $exif = [];
                $mimetype = mime_content_type($fullPath);
                try {
                    $exifType = $reader->read($fullPath);
                    $exif['raw'] = $exifType->getRawData();
                    $exif['data'] = $exifType->getData();
                    $mimetype = $exifType->getMimeType();
                } catch (\Throwable $th) {
                    Log::error('Error reading exif or mimetype of file ' . $fullPath);
                    Log::error($th->getMessage());
                }

                array_push(
                    $missingFilesInDatabase,
                    [
                        'filename' => $pathInfo['basename'],
                        'path' => $pathInfo['dirname'],
                        'mimetype' => $mimetype,
                        'exif' => Utils::jsonEncode($exif),
                        'volume_id' => $volume->id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]
                );
            }

            DB::table('files')->whereIn('id', $extraFilesInDatabaseIds)->delete();
            File::insert($missingFilesInDatabase);
            if (Cache::get('index:running', false)) {
                if ($volume->type == 'ingest') {
                    IngestIndexedEvent::dispatch();
                }
                IndexEvent::dispatch('Index complete!', false);
            }
        });
    }
}
