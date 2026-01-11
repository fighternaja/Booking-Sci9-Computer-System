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
            $table->boolean('requires_checkin')->default(false)->after('status');
            $table->timestamp('checked_in_at')->nullable()->after('requires_checkin');
            $table->integer('auto_cancel_minutes')->nullable()->after('checked_in_at'); // ยกเลิกอัตโนมัติถ้าไม่เช็คอินภายใน X นาที
            $table->timestamp('auto_cancelled_at')->nullable()->after('auto_cancel_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'requires_checkin',
                'checked_in_at',
                'auto_cancel_minutes',
                'auto_cancelled_at'
            ]);
        });
    }
};

