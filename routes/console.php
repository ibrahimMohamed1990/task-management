<?php

use App\Console\Commands\NotifyOverdueTasks;
use Illuminate\Support\Facades\Schedule;

Schedule::command(NotifyOverdueTasks::class)->dailyAt('08:00');
