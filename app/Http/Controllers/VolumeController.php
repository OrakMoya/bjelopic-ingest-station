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
    public function index(): JsonResponse
    {
        $return_data = [];

        $volumes = Volume::all();
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
    public function show(string $id): void
    {
        //
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
    public function destroy(string $id): Response
    {
        $volume = Volume::find($id);

        if (!$volume) {
            abort(404, 'Volume with id ' . $id . ' not found');
        }

        $result = $volume->delete();
        if ($result) {
            return response(status: 200);
        } else {
            abort(500);
        }
    }
}
