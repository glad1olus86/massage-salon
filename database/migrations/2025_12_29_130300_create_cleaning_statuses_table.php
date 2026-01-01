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
        Schema::create('cleaning_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cleaning_duty_id');
            $table->unsignedBigInteger('room_id')->nullable();
            $table->enum('area_type', ['room', 'common_area']);
            $table->enum('status', ['clean', 'dirty', 'in_progress'])->default('dirty');
            $table->unsignedBigInteger('cleaned_by')->nullable();
            $table->timestamp('cleaned_at')->nullable();
            $table->timestamps();

            $table->foreign('cleaning_duty_id')->references('id')->on('cleaning_duties')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
            $table->foreign('cleaned_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['cleaning_duty_id', 'area_type'], 'idx_duty_area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleaning_statuses');
    }
};
