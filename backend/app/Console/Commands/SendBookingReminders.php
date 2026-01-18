<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\Setting;
use App\Mail\BookingReminder;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendBookingReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ส่งอีเมลเตือนล่วงหน้าก่อนการจองเริ่มต้น';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // ตรวจสอบว่าการแจ้งเตือนทางอีเมลเปิดใช้งานหรือไม่
        if (!Setting::get('email_notifications_enabled', false)) {
            $this->info('Email notifications are disabled. Skipping reminders.');
            return Command::SUCCESS;
        }

        // ดึงค่าจำนวนชั่วโมงที่เตือนก่อน (default 1 ชั่วโมง)
        $hoursBefore = Setting::get('reminder_before_hours', 1);
        
        $now = Carbon::now();
        $reminderTime = $now->copy()->addHours($hoursBefore);

        // หาการจองที่:
        // 1. สถานะเป็น approved
        // 2. ยังไม่ผ่านไปแล้ว (start_time > now)
        // 3. อยู่ในช่วงเวลาที่ต้องเตือน (start_time ใกล้เคียงกับ reminderTime)
        // 4. ยังไม่ถูกยกเลิก
        // 5. ยังไม่ส่งการเตือนไปแล้ว (ใช้ cache หรือ field ใหม่)

        $bookings = Booking::with(['user', 'room'])
            ->where('status', 'approved')
            ->where('start_time', '>', $now)
            ->where('start_time', '<=', $reminderTime->copy()->addMinutes(5)) // ภายใน 5 นาทีของ reminder time
            ->where('start_time', '>=', $reminderTime->copy()->subMinutes(5))
            ->get();

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($bookings as $booking) {
            // ตรวจสอบว่าเคยส่งการเตือนไปแล้วหรือยัง (ใช้ cache)
            $cacheKey = "booking_reminder_sent_{$booking->id}_{$hoursBefore}h";
            
            if (cache()->has($cacheKey)) {
                $skippedCount++;
                continue;
            }

            // ตรวจสอบว่ามีอีเมลหรือไม่
            if (!$booking->user || !$booking->user->email) {
                $this->warn("Skipping booking #{$booking->id}: User has no email");
                $skippedCount++;
                continue;
            }

            try {
                Mail::to($booking->user->email)->send(new BookingReminder($booking, $hoursBefore));
                
                // เก็บ cache ว่าได้ส่งการเตือนแล้ว (เก็บไว้ 24 ชั่วโมง)
                cache()->put($cacheKey, true, now()->addHours(24));
                
                $sentCount++;
                $this->info("Sent reminder for booking #{$booking->id} - User: {$booking->user->name}, Room: {$booking->room->name}");
            } catch (\Exception $e) {
                $this->error("Failed to send reminder for booking #{$booking->id}: " . $e->getMessage());
                \Log::error('Failed to send booking reminder', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("Sent {$sentCount} reminder(s), skipped {$skippedCount} booking(s)");
        
        return Command::SUCCESS;
    }
}

