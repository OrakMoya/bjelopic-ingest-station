<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Volume;
use Artisan;
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

        return new JsonResponse($returnData);
    }

    public function store(Request $request): Response
    {
        $request->validate(
            ['id'=>'required|exists:projects']
        );
        $id = $request->id;
        defer(function () use ($id) {
            Artisan::call('app:ingest', ['--project-id' => $id]);
        });

        return response(status: 200);
    }
}
