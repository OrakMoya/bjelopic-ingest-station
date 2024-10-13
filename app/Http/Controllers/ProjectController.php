<?php

namespace App\Http\Controllers;

use App\Events\ProjectCreatedEvent;
use App\Events\ProjectsChangedEvent;
use App\Http\Requests\CreateProjectRequest;
use App\Models\IngestRule;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|Response
    {
        $returnData = [];
        $projects = Project::with('volume:id,display_name')->get();

        $returnData['projects'] = $projects;

        if($request->wantsJson()){
            return new JsonResponse($returnData);
        }

        return Inertia::render('Projects', $returnData);
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
    public function store(CreateProjectRequest $request, ProjectService $projectService): RedirectResponse
    {
        $validated = $request->validated();

        $project = null;
        try {
            $project = $projectService->createNewProject($validated);
        } catch (\App\Exceptions\InvalidVolumeException $th) {
            return redirect()->back()->withErrors(['volume' => $th->getMessage()]);
        }

        ProjectsChangedEvent::dispatch();

        return redirect()->back()->with(['message' => 'Project created', 'id' => $project->id]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): Response
    {
        $returnData = [];
        $returnData['project'] = $project;
        return Inertia::render('Project/Overview', $returnData);
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
    public function destroy(Project $project): RedirectResponse
    {
        IngestRule::where('project_id', $project->id)
        ->delete();
        $project->delete();
        return redirect(to: '/projects');
    }
}
