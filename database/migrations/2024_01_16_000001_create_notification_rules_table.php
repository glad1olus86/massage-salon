<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('entity_type'); // worker, room, hotel, work_place
            $table->json('conditions'); // JSON с условиями
            $table->integer('period_from')->default(0); // от X дней
            $table->integer('period_to')->nullable(); // до Y дней (null = бесконечно)
            $table->string('severity')->default('info'); // info, warning, danger
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            $table->index('created_by');
            $table->index('is_active');
            $table->index('entity_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
