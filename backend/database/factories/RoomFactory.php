<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'capacity' => $this->faker->numberBetween(10, 100),
            'location' => $this->faker->address(),
            'room_type' => 'classroom',
            'is_active' => true,
        ];
    }
}
