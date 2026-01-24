<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class BookingSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_rooms()
    {
        $room = Room::factory()->create([
            'is_active' => true
        ]);

        $response = $this->get('/api/rooms');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => $room->name]);
    }

    public function test_user_can_create_booking()
    {
        $user = User::factory()->create(['role' => 'user']);
        $room = Room::factory()->create(['is_active' => true]);

        $startTime = Carbon::now()->addDay()->setHour(9)->setMinute(0);
        $endTime = $startTime->copy()->addHour();

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'room_id' => $room->id,
            'start_time' => $startTime->toIso8601String(),
            'end_time' => $endTime->toIso8601String(),
            'purpose' => 'Test Booking',
            'notes' => 'Testing'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);
    }

    public function test_cannot_book_overlapping_time()
    {
        $user = User::factory()->create(['role' => 'user']);
        $room = Room::factory()->create(['is_active' => true]);
        
        $startTime = Carbon::now()->addDay()->setHour(10)->setMinute(0);
        $endTime = $startTime->copy()->addHour();

        // Booking 1
        Booking::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'purpose' => 'First Booking',
            'status' => 'approved'
        ]);

        // Booking 2 (Overlapping - Same Time)
        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'room_id' => $room->id,
            'start_time' => $startTime->toIso8601String(),
            'end_time' => $endTime->toIso8601String(),
            'purpose' => 'Overlapping Booking',
        ]);

        $response->assertStatus(400);
    }

    public function test_admin_can_approve_booking()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $room = Room::factory()->create();

        $booking = Booking::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHour(),
            'purpose' => 'Pending Booking',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($admin)->postJson("/api/bookings/{$booking->id}/approve", [
            'approval_reason' => 'OK'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'approved'
        ]);
    }
}
