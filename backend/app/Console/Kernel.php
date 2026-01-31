<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Http\Controllers\Api\WaitlistController;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // ยกเลิกการจองอัตโนมัติ (ทุกนาที)
        // ตรวจสอบการจองที่ต้องเช็คอินแต่ไม่มาตามเวลาที่กำหนด
        $schedule->command('bookings:auto-cancel')->everyMinute();

        // ส่งอีเมลเตือน (ทุก 5 นาที)
        // ตรวจสอบทุก 5 นาทีเพื่อส่งเมลเตือนล่วงหน้า (ตามการตั้งค่า)
        $schedule->command('bookings:send-reminders')->everyFiveMinutes();

        // ตรวจสอบรายชื่อรอ (ทุกนาที)
        // ถ้ามีห้องว่าง จะจองให้อัตโนมัติสำหรับคนที่เปิด auto_book
        $schedule->call(function () {
            WaitlistController::processWaitlists();
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
