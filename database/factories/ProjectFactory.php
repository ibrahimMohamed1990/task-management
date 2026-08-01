<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->catchPhrase(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(Project::STATUSES),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => Project::STATUS_ACTIVE]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => Project::STATUS_COMPLETED]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => Project::STATUS_ARCHIVED]);
    }
}
