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
        Schema::create('booking_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // ชื่อเทมเพลต
            $table->foreignId('room_id')->nullable()->constrained()->onDelete('set null');
            $table->time('start_time')->nullable(); // เวลาเริ่มต้น (เช่น 09:00)
            $table->integer('duration')->nullable(); // ระยะเวลาเป็นนาที
            $table->string('purpose');
            $table->text('notes')->nullable();
            $table->boolean('is_default')->default(false); // เทมเพลตเริ่มต้น
            $table->timestamps();
            
            $table->index(['user_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_templates');
    }
};

