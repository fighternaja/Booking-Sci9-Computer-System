<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('building')->nullable()->after('location');
            $table->string('floor')->nullable()->after('building');
            $table->enum('status', ['available', 'maintenance', 'occupied', 'reserved'])->default('available')->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['building', 'floor', 'status']);
        });
    }
};
