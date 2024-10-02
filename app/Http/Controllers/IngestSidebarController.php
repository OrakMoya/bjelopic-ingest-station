<?php

namespace App\Http\Controllers;

use App\Actions\IngestAction;
use App\Models\File;
use App\Models\Project;
use App\Models\Volume;
use Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IngestSidebarController extends Controller
{
    public function index(): JsonResponse
    {
        $returnData = [];


        $ingestFiles = File::select('id', 'filename', 'volume_id', 'created_at')
            ->whereIn(
                'volume_id',
                Volume::select('id', 'type')
                    ->where('type', '=', 'ingest')
                    ->pluck('id')
            )
            ->orderBy('created_at', 'ASC')
            ->get();
        $returnData['ingest_data'] = $ingestFiles;
        $returnData['ingesting'] = Cache::get('ingest:running', false);
        $returnData['ingest_file_count'] = Cache::get('ingest:filecount', 0);
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
}
