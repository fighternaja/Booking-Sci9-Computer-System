<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $rooms = Room::all();

        if ($users->isEmpty() || $rooms->isEmpty()) {
            return;
        }

        // Create bookings for the current week
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();

        for ($i = 0; $i < 20; $i++) {
            $randomDay = $startDate->copy()->addDays(rand(0, 6));
            $startHour = rand(8, 16);
            $duration = rand(1, 3);
            
            $startTime = $randomDay->copy()->setHour($startHour)->setMinute(0)->setSecond(0);
            $endTime = $startTime->copy()->addHours($duration);

            Booking::create([
                'user_id' => $users->random()->id,
                'room_id' => $rooms->random()->id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'purpose' => 'การประชุมทดสอบระบบ ' . ($i + 1),
                'notes' => 'ทดสอบการจองอัตโนมัติ',
                'status' => collect(['pending', 'approved', 'rejected'])->random(),
            ]);
        }
    }
}
