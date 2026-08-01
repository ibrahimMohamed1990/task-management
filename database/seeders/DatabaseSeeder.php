<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // A predictable demo user for quick manual testing / Postman collection.
        $demoUser = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->seedProjectsAndTasksFor($demoUser);

        // A handful of extra random users, each with their own projects/tasks,
        // to prove data isolation between users.
        User::factory()
            ->count(4)
            ->create()
            ->each(fn (User $user) => $this->seedProjectsAndTasksFor($user));
    }

    private function seedProjectsAndTasksFor(User $user): void
    {
        Project::factory()
            ->count(3)
            ->for($user)
            ->create()
            ->each(function (Project $project) {
                Task::factory()->count(4)->for($project)->create();
                Task::factory()->count(2)->for($project)->overdue()->create();
                Task::factory()->count(2)->for($project)->done()->create();
            });
    }
}
