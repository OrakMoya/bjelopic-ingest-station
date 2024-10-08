<?php

namespace App\Http\Controllers;

use App\Actions\IngestAction;
use App\Models\Project;
use App\Models\File;
use App\Models\Volume;
use Symfony\Component\HttpFoundation\JsonResponse;

class IngestDryRunController extends Controller
{
    public function index(Project $project, IngestAction $ingestAction)
    {
        $returnData = [];
        $ingestRules = $ingestAction->getIngestRules($project);
        $ingestFiles = File::select('*')
            ->whereIn(
                'volume_id',
                Volume::select('id', 'type')->where('type', 'ingest')->pluck('id')
            )
            ->with('volume')
            ->get();

        foreach ($ingestFiles as $ingestFile) {
            try {
                $pair = [
                    'file_id' => $ingestFile->id,
                    'targetDirectory' => $ingestAction->getNewProjectRelativeFilePath($ingestRules, $ingestFile, broadcast: false)
                ];
                array_push($returnData, $pair);
            } catch (\Throwable $th) {
                abort(422, $th->getMessage());
            }
        }

        return new JsonResponse($returnData);
    }

    public function show(Project $project, File $file, IngestAction $ingestAction)
    {
        $returnData = [];

        $ingestRules = $ingestAction->getIngestRules($project);

        try {
            $result = $ingestAction->getNewProjectRelativeFilePath($ingestRules, $file, broadcast: false);
        } catch (\Throwable $th) {
            abort(422, $th->getMessage());
        }

        $returnData['destination'] = $result;

        return new JsonResponse($returnData);
    }
}
