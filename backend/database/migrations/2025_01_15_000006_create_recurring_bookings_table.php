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
        Schema::create('recurring_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->time('start_time'); // เวลาเริ่มต้น (เช่น 09:00)
            $table->time('end_time'); // เวลาสิ้นสุด (เช่น 10:00)
            $table->string('purpose');
            $table->text('notes')->nullable();
            $table->enum('recurrence_type', ['daily', 'weekly', 'monthly', 'custom'])->default('weekly');
            $table->json('recurrence_pattern')->nullable(); // สำหรับ custom pattern
            $table->date('start_date'); // วันที่เริ่มจองซ้ำ
            $table->date('end_date')->nullable(); // วันที่สิ้นสุดการจองซ้ำ (null = ไม่สิ้นสุด)
            $table->integer('max_occurrences')->nullable(); // จำนวนครั้งสูงสุด (null = ไม่จำกัด)
            $table->json('days_of_week')->nullable(); // สำหรับ weekly: [1,3,5] = จันทร์, พุธ, ศุกร์
            $table->integer('day_of_month')->nullable(); // สำหรับ monthly: วันที่ของเดือน
            $table->integer('interval')->default(1); // ทุก X วัน/สัปดาห์/เดือน
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index(['room_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_bookings');
    }
};

