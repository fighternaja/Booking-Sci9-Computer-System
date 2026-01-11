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
        Schema::create('waitlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->datetime('start_time');
            $table->datetime('end_time');
            $table->string('purpose');
            $table->text('notes')->nullable();
            $table->enum('status', ['waiting', 'notified', 'booked', 'cancelled'])->default('waiting');
            $table->boolean('auto_book')->default(false); // จองอัตโนมัติเมื่อห้องว่าง
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            
            $table->index(['room_id', 'start_time', 'end_time']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waitlists');
    }
};

