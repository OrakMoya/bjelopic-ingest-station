<?php

namespace App\Http\Controllers;

use App\Actions\IngestAction;
use App\Models\File;
use App\Models\Project;
use App\Models\Volume;
use Cache;
use GuzzleHttp\Utils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IngestSidebarController extends Controller
{
    public function index(): JsonResponse
    {
        $returnData = [];


        $ingestFiles = File::select('id', 'filename', 'volume_id', 'created_at', 'ingest_ignore')
            ->whereIn(
                'volume_id',
                Volume::select('id', 'type')
                    ->where('type', '=', 'ingest')
                    ->pluck('id')
            )
            ->orderBy('created_at', 'ASC')
            ->get();
        $notIgnoredIngestFiles = $ingestFiles->filter(function($key){
            return !$key->ingest_ignore;
        });
        $returnData['ingest_data'] = [...$notIgnoredIngestFiles->toArray()];
        $returnData['ingesting'] = Cache::get('ingest:running', false);
        $returnData['ingest_file_count'] = Cache::get('ingest:filecount', 0);
        $returnData['ignored_ingest_file_count'] = $ingestFiles->count() - $notIgnoredIngestFiles->count();
        $returnData['indexing'] = Cache::get('index:running', false);

        return new JsonResponse($returnData);
    }

    public function store(Request $request): Response
    {
        $request->validate(
            ['id' => 'required|exists:projects']
        );
        $id = $request->id;

        defer(fn() => (new IngestAction())->run(Project::find($id)));
        return response(status: 200);
    }

    public function show(File $file){
        $returnData = [];
        $returnData['file'] = $file;
        $returnData['exif'] = Utils::jsonDecode($file->exif);
        unset($file->exif);
        return new JsonResponse($returnData);
    }

    public function destroy(){
        File::where('ingest_ignore', true)->update(['ingest_ignore' => false]);
    }
}
