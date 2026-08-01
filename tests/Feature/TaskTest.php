<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_task_under_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/projects/{$project->id}/tasks", [
            'title' => 'Set up CI pipeline',
            'priority' => 'high',
            'status' => 'todo',
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $response->assertStatus(201)->assertJsonPath('data.title', 'Set up CI pipeline');
    }

    public function test_user_cannot_create_task_under_another_users_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for(User::factory())->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/projects/{$project->id}/tasks", ['title' => 'Nope'])
            ->assertStatus(403);
    }

    public function test_tasks_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        Task::factory()->count(2)->for($project)->done()->create();
        Task::factory()->count(3)->for($project)->create(['status' => 'todo']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks?status=done");

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_tasks_can_be_filtered_by_priority(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        Task::factory()->count(2)->for($project)->create(['priority' => 'high']);
        Task::factory()->count(3)->for($project)->create(['priority' => 'low']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks?priority=high");

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_tasks_can_be_searched_by_title(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        Task::factory()->for($project)->create(['title' => 'Fix login bug']);
        Task::factory()->for($project)->create(['title' => 'Write documentation']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks?search=login");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Fix login bug');
    }

    public function test_task_update_and_delete(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $task = Task::factory()->for($project)->create();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/projects/{$project->id}/tasks/{$task->id}", ['status' => 'done'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'done');

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }
}
