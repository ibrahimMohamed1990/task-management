<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Return an aggregated summary of the authenticated user's
     * projects and tasks.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalProjects = $user->projects()->count();
        $activeProjects = $user->projects()->where('status', Project::STATUS_ACTIVE)->count();

        $taskQuery = Task::whereHas('project', fn ($q) => $q->where('user_id', $user->id));

        $totalTasks = (clone $taskQuery)->count();
        $completedTasks = (clone $taskQuery)->where('status', Task::STATUS_DONE)->count();
        $pendingTasks = (clone $taskQuery)->where('status', '!=', Task::STATUS_DONE)->count();
        $overdueTasks = (clone $taskQuery)->overdue()->count();

        return response()->json([
            'data' => [
                'total_projects' => $totalProjects,
                'active_projects' => $activeProjects,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'pending_tasks' => $pendingTasks,
                'overdue_tasks' => $overdueTasks,
            ],
        ]);
    }
}
