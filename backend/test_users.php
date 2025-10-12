<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "Users in database:\n";
$users = User::all();
foreach ($users as $user) {
    echo "ID: {$user->id}, Email: {$user->email}, Role: {$user->role}\n";
}

echo "\nTesting login for admin@booking-system.com:\n";
$user = User::where('email', 'admin@booking-system.com')->first();
if ($user) {
    echo "User found: {$user->name}\n";
    echo "Password hash: {$user->password}\n";
    
    // Test password verification
    if (password_verify('admin001', $user->password)) {
        echo "Password verification: SUCCESS\n";
    } else {
        echo "Password verification: FAILED\n";
    }
} else {
    echo "User not found\n";
}
