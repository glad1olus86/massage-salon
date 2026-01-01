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
        Schema::create('room_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('massage_clients')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['branch_id', 'booking_date'], 'idx_booking_branch_date');
            $table->index(['room_id', 'booking_date'], 'idx_booking_room_date');
            $table->index(['user_id', 'booking_date'], 'idx_booking_user_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_bookings');
    }
};
