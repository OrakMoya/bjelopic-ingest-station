<?php

namespace App\Console\Commands;

use App\Actions\IndexFilesForVolumeAction;
use App\Events\IndexEvent;
use App\Events\IngestEvent;
use App\Events\IngestIndexedEvent;
use App\Models\File;
use App\Models\Volume;
use Carbon\Carbon;
use GuzzleHttp\Utils;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IndexFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:index-files {--volume=} {--no-exif} {--fresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex files for volume/s';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {

        if (Cache::get('ingesting', false)) {
            $msg = 'Ingest going on, skipping indexing...';
            $this->info($msg);
            return 0;
        }

        $result = 0;
        $volumesLock = Cache::lock('volumes');

        // Having the actual function separate and in a closure inside
        // the get() method ensures the lock is released even if
        // indexing throws.
        if (!$volumesLock->get(function () use (&$result) {
            $result = $this->index();
        })) {
            $this->info('Unable to lock volumes. Skipping indexing...');
            return 0;
        }
        return $result;
    }

    public function index(): int
    {
        DB::disableQueryLog();

        $target_volume = $this->option('volume');

        $this->info('Indexing files...');

        $volumes = Volume::select('*');
        if ($target_volume) {
            $volumes = $volumes->where(
                'display_name',
                '=',
                $target_volume
            );
        } else {
            $volumes = $volumes->where('disable_index', '=', false);
        }
        $volumes = $volumes->get();
        if (!count($volumes) && $target_volume != '%') {
            $this->error('Volume ' . $this->option('volume') . ' not found');
            return 1;
        }


        foreach ($volumes as $volume) {
            $disk = $volume->getDiskInstance();

            $filesInDatabase = File::select('*')
                ->where('volume_id', '=', $volume->id);
            if ($this->option('fresh')) {
                $filesInDatabase->delete();
                $filesInDatabase = [];
            } else {
                $filesInDatabase = $filesInDatabase->get()->toArray();
            }

            $filesInVolume = $disk->allFiles();
            $tenSecondsAgo = Carbon::now()->subSeconds(10);
            $filesInVolume = array_filter($filesInVolume, function ($file) use ($tenSecondsAgo, $volume) {
                try {
                    $fileCreationTime = filectime($volume->absolute_path .  '/' . $file);
                    $fileModifiedTime = filemtime($volume->absolute_path . '/' . $file);
                    return $fileCreationTime < $tenSecondsAgo->timestamp
                        && $fileModifiedTime < $tenSecondsAgo->timestamp;
                } catch (\Throwable) {
                }
                return false;
            });

            $missingFilesFromDatabase = [];

            $this->info('Indexing volume ' . $volume->display_name);
            $this->info(count($filesInDatabase) . ' files present in database');
            $this->info(count($filesInVolume) . ' files present in volume');

            foreach ($filesInVolume as $fileInVolume) {
                $databaseFileEquivalentIndex = null;
                $pathParts = pathinfo($fileInVolume);

                // Check if file already exists in database
                foreach ($filesInDatabase as $index => $fileInDatabase) {
                    if (
                        $fileInDatabase['filename'] == $pathParts['basename']
                        && $fileInDatabase['path'] == $pathParts['dirname']
                    ) {
                        $databaseFileEquivalentIndex = $index;
                        break;
                    }
                }
                // Remove accounted for file from database array.
                // This leaves only the files that don't have a matching file in the volume.
                if (!is_null($databaseFileEquivalentIndex)) {
                    array_splice($filesInDatabase, $databaseFileEquivalentIndex, 1);
                    continue;
                }
                if (count($filesInDatabase) != count($filesInVolume) && !Cache::get('indexing', false)) {
                    Cache::put('indexing', true);
                    IndexEvent::dispatch('Index started! Adding', true);
                }


                $path = $volume->absolute_path. '/' . $fileInVolume;
                $mimetype = mime_content_type($path);
                $exif = [];
                if (!$this->option('no-exif')) {
                    $reader = \PHPExif\Reader\Reader::factory(\PHPExif\Reader\Reader::TYPE_EXIFTOOL);

                    $exifType = $reader->read($path);

                    $exif['data'] = $exifType->getData();
                    $exif['raw_data'] = $exifType->getRawData();
                    $mimetype = $exifType->getMimeType();
                }

                array_push(
                    $missingFilesFromDatabase,
                    [
                        'filename' => $pathParts['basename'],
                        'path' => $pathParts['dirname'],
                        'volume_id' => $volume->id,
                        'exif' => Utils::jsonEncode($exif),
                        'mimetype' => mime_content_type($volume->absolute_path . '/' . $fileInVolume),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]
                );
            }

            $this->info('Inserting non-indexed files...');

            File::insert($missingFilesFromDatabase);

            $this->info('Mass deleting missing files');

            // Mass delete missing files from database
            if (count($filesInDatabase)) {
                if (count($filesInDatabase) != count($filesInVolume) && !Cache::get('indexing', false)) {
                    Cache::put('indexing', true);
                    IndexEvent::dispatch('Index started! Missing', true);
                }

                $this->warn(count($filesInDatabase) . ' missing files. Deleting entries...');

                $missingFilesFromDatabaseIds = [];
                foreach ($filesInDatabase as $missingFile) {
                    array_push($missingFilesFromDatabaseIds, $missingFile['id']);
                }

                DB::table('files')->whereIn('id', $missingFilesFromDatabaseIds)->delete();
            }

            if ($volume->type == "ingest" && count($missingFilesFromDatabase)) {
                $this->info('volume type is ingest. Firing event');
                IngestIndexedEvent::dispatch();
            }
        }

        if (Cache::get('indexing', false)) {
            Cache::delete('indexing');
            IndexEvent::dispatch('Index complete!', false);
        }

        DB::enableQueryLog();
        return 0;
    }
}
