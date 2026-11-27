<?php

/**
 * Ficheiro PHP: console.php
 * PHP file: console.php
 */
use App\Models\Notification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', ['--model' => [Notification::class]])->daily();
Schedule::command('users:reactivate-suspended')->everyMinute();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
