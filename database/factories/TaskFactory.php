<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'priority' => fake()->randomElement(Task::PRIORITIES),
            'status' => fake()->randomElement(Task::STATUSES),
            'due_date' => fake()->optional(0.8)->dateTimeBetween('-10 days', '+30 days'),
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'due_date' => fake()->dateTimeBetween('-15 days', '-1 days'),
            'status' => fake()->randomElement([Task::STATUS_TODO, Task::STATUS_IN_PROGRESS]),
        ]);
    }

    public function done(): static
    {
        return $this->state(fn () => ['status' => Task::STATUS_DONE]);
    }
}
