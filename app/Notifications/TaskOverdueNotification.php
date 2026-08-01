<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(public Task $task)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Task Overdue: '.$this->task->title)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('The following task is now overdue:')
            ->line('"'.$this->task->title.'" (Project: '.$this->task->project->name.')')
            ->line('Due date was: '.$this->task->due_date?->toFormattedDateString())
            ->action('View Task', url('/api/projects/'.$this->task->project_id.'/tasks/'.$this->task->id))
            ->line('Please update its status or reschedule it.');
    }
}
