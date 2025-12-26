<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // hotel_occupancy, worker_unemployed, etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // hotel_id, worker_id, percentage, etc.
            $table->string('link')->nullable(); // URL to redirect
            $table->boolean('is_read')->default(false);
            $table->integer('created_by'); // multi-tenancy
            $table->timestamps();
            
            $table->index(['created_by', 'is_read']);
            $table->index(['type', 'created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
    }
};
