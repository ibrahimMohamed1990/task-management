<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_project(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/projects', [
            'name' => 'New Website',
            'description' => 'Client landing page build',
            'status' => 'active',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Website');

        $this->assertDatabaseHas('projects', ['name' => 'New Website', 'user_id' => $user->id]);
    }

    public function test_user_can_only_list_their_own_projects(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Project::factory()->count(2)->for($user)->create();
        Project::factory()->count(3)->for($otherUser)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_cannot_view_another_users_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for(User::factory())->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}")
            ->assertStatus(403);
    }

    public function test_user_can_update_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/projects/{$project->id}", [
            'status' => 'completed',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.status', 'completed');
    }

    public function test_user_can_delete_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_project_requires_a_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/projects', ['description' => 'no name'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }
}
