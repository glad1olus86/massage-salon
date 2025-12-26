<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technical_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->date('inspection_date');
            $table->date('next_inspection_date');
            $table->unsignedInteger('mileage')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('service_station', 255)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('vehicle_id');
            $table->index('created_by');
            $table->index('next_inspection_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technical_inspections');
    }
};
