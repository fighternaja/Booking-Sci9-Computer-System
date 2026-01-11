<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use Carbon\Carbon;

class AutoCancelBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:auto-cancel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ยกเลิกการจองอัตโนมัติสำหรับการจองที่ต้องเช็คอินแต่ไม่ได้เช็คอิน';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // หาการจองที่:
        // 1. ต้องเช็คอิน (requires_checkin = true)
        // 2. ยังไม่เช็คอิน (checked_in_at = null)
        // 3. สถานะเป็น approved หรือ pending
        // 4. มี auto_cancel_minutes กำหนดไว้
        // 5. ผ่านเวลา auto_cancel_minutes แล้ว (start_time + auto_cancel_minutes < now)
        // 6. ยังไม่ถูกยกเลิกอัตโนมัติ (auto_cancelled_at = null)

        $bookings = Booking::where('requires_checkin', true)
            ->whereNull('checked_in_at')
            ->whereIn('status', ['approved', 'pending'])
            ->whereNotNull('auto_cancel_minutes')
            ->whereNull('auto_cancelled_at')
            ->whereRaw('DATE_ADD(start_time, INTERVAL auto_cancel_minutes MINUTE) < ?', [$now])
            ->get();

        $cancelledCount = 0;

        foreach ($bookings as $booking) {
            $cancelTime = Carbon::parse($booking->start_time)->addMinutes($booking->auto_cancel_minutes);
            
            if ($cancelTime->isPast()) {
                $booking->update([
                    'status' => 'cancelled',
                    'auto_cancelled_at' => $now
                ]);

                $cancelledCount++;

                // ส่งการแจ้งเตือน (สามารถเพิ่มได้ภายหลัง)
                $this->info("Cancelled booking #{$booking->id} - User: {$booking->user->name}, Room: {$booking->room->name}");
            }
        }

        $this->info("Auto-cancelled {$cancelledCount} booking(s)");

        return Command::SUCCESS;
    }
}

