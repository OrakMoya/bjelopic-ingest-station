<?php

namespace App\Console\Commands;

use App\Actions\IndexFilesForVolumeAction;
use App\Models\File;
use App\Models\Volume;
use Carbon\Carbon;
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
    protected $signature = 'app:index-files {--volume=%}';

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

        $this->info('Indexing files...');
        $volumes = Volume::select('id', 'display_name', 'absolute_path')
            ->where(
                'display_name',
                'LIKE',
                $this->option('volume')
            )
            ->get();

        if (!count($volumes) && $this->option('volume') != '%') {
            $this->error('Volume ' . $this->option('volume') . ' not found');
            return 1;
        }

        foreach ($volumes as $volume) {
            $disk = Storage::build([
                'driver' => 'local',
                'root' => $volume->absolute_path
            ]);

            $filesInDatabase = File::select('*')
                ->where('volume_id', '=', $volume->id)
                ->get()->toArray();
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

                array_push(
                    $missingFilesFromDatabase,
                    [
                        'filename' => $pathParts['basename'],
                        'path' => $pathParts['dirname'],
                        'volume_id' => $volume->id,
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
        }

        DB::enableQueryLog();
        return 0;
    }
}
