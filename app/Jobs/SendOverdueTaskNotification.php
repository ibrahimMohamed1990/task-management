<?php

namespace App\Jobs;

use App\Models\Task;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOverdueTaskNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Task $task)
    {
    }

    public function handle(): void
    {
        // Reload to make sure the task is still overdue at the time the job runs.
        $task = $this->task->fresh(['project.user']);

        if (! $task || ! $task->is_overdue || $task->overdue_notified) {
            return;
        }

        $owner = $task->project?->user;

        if ($owner) {
            $owner->notify(new TaskOverdueNotification($task));
        }

        $task->update(['overdue_notified' => true]);
    }
}
