<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_track_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('vehicle_trips')->onDelete('cascade');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed', 5, 1)->nullable()->comment('km/h');
            $table->decimal('accuracy', 5, 1)->nullable()->comment('meters');
            $table->datetime('recorded_at')->comment('timestamp from device');
            $table->datetime('synced_at')->nullable()->comment('when received by server');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['trip_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_track_points');
    }
};
