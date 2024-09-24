<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Volume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngestSidebarController extends Controller
{
    public function index()
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
}
