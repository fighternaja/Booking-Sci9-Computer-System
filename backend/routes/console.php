<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\Api\WaitlistController;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule tasks
Schedule::command('bookings:auto-cancel')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('bookings:send-reminders')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::call(function () {
    WaitlistController::processWaitlists();
})
    ->name('process-waitlists')
    ->everyFiveMinutes()
    ->withoutOverlapping();
