<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('license_plate', 20);
            $table->string('brand', 100);
            $table->string('color', 50)->nullable();
            $table->string('vin_code', 17)->nullable();
            $table->decimal('fuel_consumption', 5, 2)->nullable();
            $table->string('photo', 255)->nullable();
            $table->string('assigned_type')->nullable();
            $table->unsignedBigInteger('assigned_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('created_by');
            $table->index(['assigned_type', 'assigned_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
