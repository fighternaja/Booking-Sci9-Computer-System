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
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('recurring_booking_id')->nullable()->after('user_id')
                ->constrained('recurring_bookings')->onDelete('set null');
            $table->index('recurring_booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['recurring_booking_id']);
            $table->dropColumn('recurring_booking_id');
        });
    }
};

