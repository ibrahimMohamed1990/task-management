<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    /**
     * List tasks for a project, with optional filtering by status/priority
     * and searching by title. Paginated.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorizeOwner($request, $project);

        $tasks = $project->tasks()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->query('priority')))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->query('search').'%'))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => TaskResource::collection($tasks),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    /**
     * Create a task under the given project.
     */
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->authorizeOwner($request, $project);

        $task = $project->tasks()->create($request->validated());

        return response()->json([
            'message' => 'Task created successfully.',
            'data' => new TaskResource($task),
        ], 201);
    }

    /**
     * Show a single task, scoped to its parent project.
     */
    public function show(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorizeOwner($request, $project);
        $this->ensureTaskBelongsToProject($project, $task);

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * Update a task.
     */
    public function update(UpdateTaskRequest $request, Project $project, Task $task): JsonResponse
    {
        $this->authorizeOwner($request, $project);
        $this->ensureTaskBelongsToProject($project, $task);

        $task->update($request->validated());

        return response()->json([
            'message' => 'Task updated successfully.',
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * Soft delete a task.
     */
    public function destroy(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorizeOwner($request, $project);
        $this->ensureTaskBelongsToProject($project, $task);

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }

    private function authorizeOwner(Request $request, Project $project): void
    {
        Gate::allowIf(fn () => $project->user_id === $request->user()->id);
    }

    private function ensureTaskBelongsToProject(Project $project, Task $task): void
    {
        abort_unless($task->project_id === $project->id, 404, 'Task not found in this project.');
    }
}
