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
        Schema::create('booking_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // ผู้ใช้ในระบบ
            $table->string('email')->nullable(); // อีเมลสำหรับผู้เข้าร่วมที่ไม่ได้เป็นสมาชิก
            $table->string('name')->nullable(); // ชื่อสำหรับผู้เข้าร่วมที่ไม่ได้เป็นสมาชิก
            $table->enum('status', ['pending', 'accepted', 'declined', 'attended'])->default('pending');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('attended_at')->nullable(); // เวลาที่เข้าร่วมจริง
            $table->boolean('is_required')->default(false); // ผู้เข้าร่วมที่จำเป็น
            $table->timestamps();
            
            $table->index(['booking_id', 'status']);
            $table->index('user_id');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_attendees');
    }
};

