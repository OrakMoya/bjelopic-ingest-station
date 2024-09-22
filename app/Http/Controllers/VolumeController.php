<?php

namespace App\Http\Controllers;

use App\Services\VolumeService;
use App\Http\Requests\CreateVolumeRequest;
use App\Models\Volume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VolumeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, VolumeService $volumeService): JsonResponse
    {
        $return_data = [];

        $volumes = [];
        if ($request->input('type')) {
            $volumes = Volume::select('*')
                ->where('type', '=', $request->input('type'))
                ->get();
        } else {
            $volumes = Volume::all();
        }

        foreach ($volumes as &$volume) {
            $volume['free_space'] = $volumeService->getFreeSpace($volume);
            $volume['total_space'] = $volumeService->getTotalSpace($volume);
        }


        $return_data['volumes'] = $volumes;

        return new JsonResponse($return_data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateVolumeRequest $request, VolumeService $volumeService): JsonResponse
    {
        $attributes = $request->validated();

        try {
            $volume = $volumeService->addNewVolume($attributes);
        } catch (\App\Exceptions\InvalidVolumeException $th) {
            return new JsonResponse([
                'message' => $th->getMessage(),
                'errors' => ['absolute_path' => $th->getMessage()]
            ], 422);
        }

        return new JsonResponse(['id' => $volume->id]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $volume = Volume::find($id);
        if (!$volume) {
            abort(404, 'Volume with id ' . $id . ' not found');
        }

        $returnData = [];
        $returnData['free_space'] = disk_free_space($volume->absolute_path);
        $return_data['total_space'] = disk_total_space($volume->absolute_path);

        return new JsonResponse($returnData);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, VolumeService $volumeService): Response
    {
        try {
            $volumeService->deleteVolume($id);
        } catch (\App\Exceptions\InvalidVolumeException $th) {
            abort(404, $th->getMessage());
        }

        return response(status: 200);
    }
}
