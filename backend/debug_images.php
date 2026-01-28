<?php

use App\Models\Room;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rooms = Room::all(['id', 'name', 'image']);

foreach ($rooms as $room) {
    echo "ID: {$room->id} | Name: {$room->name} | Image: '{$room->image}'\n";
}
