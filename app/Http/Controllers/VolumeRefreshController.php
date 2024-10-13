<?php

namespace App\Http\Controllers;

use App\Models\Volume;
use GuzzleHttp\Utils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VolumeRefreshController extends Controller
{
    public function store(Volume $volume)
    {
        $returnData = [];
        try {
            $volume->getDiskInstance();
        } catch (\App\Exceptions\InvalidVolumeException $th) {
            $returnData['message'] = $th->getMessage();
            return new JsonResponse($returnData, status: 500);
        }
        $volume['free_space'] = disk_free_space($volume->absolute_path);
        $volume['total_space'] = disk_total_space($volume->absolute_path);

        $returnData['volume'] = $volume;

        return new JsonResponse($returnData);
    }
}
