<?php

namespace App\Console\Commands;

use App\Jobs\SendOverdueTaskNotification;
use App\Models\Task;
use Illuminate\Console\Command;

class NotifyOverdueTasks extends Command
{
    protected $signature = 'tasks:notify-overdue';

    protected $description = 'Dispatch queued notifications for tasks that have become overdue and not yet been notified about.';

    public function handle(): int
    {
        $tasks = Task::overdue()
            ->where('overdue_notified', false)
            ->with('project.user')
            ->get();

        foreach ($tasks as $task) {
            SendOverdueTaskNotification::dispatch($task);
        }

        $this->info("Dispatched {$tasks->count()} overdue task notification(s).");

        return self::SUCCESS;
    }
}
