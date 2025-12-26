<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->datetime('started_at');
            $table->datetime('ended_at')->nullable();
            $table->decimal('total_distance_km', 8, 2)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['vehicle_id', 'started_at']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_trips');
    }
};
