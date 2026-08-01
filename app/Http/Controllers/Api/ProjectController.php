<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    /**
     * List the authenticated user's projects (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $projects = $request->user()
            ->projects()
            ->withCount('tasks')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => ProjectResource::collection($projects),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
            ],
        ]);
    }

    /**
     * Create a new project owned by the authenticated user.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $request->user()->projects()->create($request->validated());

        return response()->json([
            'message' => 'Project created successfully.',
            'data' => new ProjectResource($project),
        ], 201);
    }

    /**
     * Show a single project (with its tasks) owned by the authenticated user.
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorizeOwner($request, $project);

        $project->load('tasks');

        return response()->json([
            'data' => new ProjectResource($project),
        ]);
    }

    /**
     * Update a project owned by the authenticated user.
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorizeOwner($request, $project);

        $project->update($request->validated());

        return response()->json([
            'message' => 'Project updated successfully.',
            'data' => new ProjectResource($project),
        ]);
    }

    /**
     * Soft delete a project owned by the authenticated user.
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorizeOwner($request, $project);

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }

    /**
     * Ensure the authenticated user owns the given project.
     */
    private function authorizeOwner(Request $request, Project $project): void
    {
        Gate::allowIf(fn () => $project->user_id === $request->user()->id);
    }
}
