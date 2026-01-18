<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'version' => '1.0.0'
    ]);
});

// API documentation or info route
Route::get('/api/info', function () {
    return response()->json([
        'name' => 'Booking System API',
        'version' => '1.0.0',
        'endpoints' => [
            'base_url' => url('/api'),
            'authentication' => 'Sanctum',
            'documentation' => 'See API routes in routes/api.php'
        ]
    ]);
});
