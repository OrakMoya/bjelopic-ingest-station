<?php

namespace App\Console\Commands;

use App\Actions\IndexFilesForVolumeAction;
use App\Events\IngestEvent;
use App\Events\IngestIndexedEvent;
use App\Models\File;
use App\Models\Volume;
use Carbon\Carbon;
use GuzzleHttp\Utils;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Log;
use Storage;

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
        DB::disableQueryLog();

        $target_volume = $this->option('volume');

        $this->info('Indexing files...');

        $volumes = null;
        $volumes = Volume::select('id', 'display_name', 'absolute_path', 'type');
        if ($target_volume) {
            $volumes->where(
                'display_name',
                '=',
                $target_volume
            );
        } else {
            $volumes = Volume::select(['id', 'display_name', 'absolute_path', 'type']);
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


                $exif = [];
                if (!$this->option('no-exif')) {
                    $reader = \PHPExif\Reader\Reader::factory(\PHPExif\Reader\Reader::TYPE_EXIFTOOL);

                    $path = $volume->absolute_path . '/' . $fileInVolume;
                    $exifType = $reader->read($path);

                    $exif['data'] = $exifType->getData();
                    $exif['raw_data'] = $exifType->getRawData();
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

        DB::enableQueryLog();
        return 0;
    }
}
