<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_correct_aggregates(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->active()->create();
        Project::factory()->for($user)->archived()->create();

        Task::factory()->for($project)->done()->create();
        Task::factory()->for($project)->create(['status' => 'todo', 'due_date' => now()->addDays(2)]);
        Task::factory()->for($project)->overdue()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_projects', 2)
            ->assertJsonPath('data.active_projects', 1)
            ->assertJsonPath('data.total_tasks', 3)
            ->assertJsonPath('data.completed_tasks', 1)
            ->assertJsonPath('data.pending_tasks', 2)
            ->assertJsonPath('data.overdue_tasks', 1);
    }
}
