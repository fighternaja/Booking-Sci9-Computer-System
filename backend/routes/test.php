<?php

use Illuminate\Support\Facades\Route;

// Test route to check settings
Route::get('/test-settings', function() {
    $settings = \App\Models\BookingSetting::getAll();
    
    if (empty($settings)) {
        $settings = \App\Models\BookingSetting::getDefaults();
    }
    
    return response()->json([
        'success' => true,
        'data' => $settings,
        'count' => count($settings)
    ]);
});
