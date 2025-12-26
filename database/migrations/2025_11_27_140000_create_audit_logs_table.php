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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Кто совершил действие
            $table->string('event_type'); // Тип события (worker.created, worker.hired, etc)
            $table->text('description'); // Описание действия
            $table->string('subject_type')->nullable(); // Тип объекта (Worker, Room, WorkPlace, etc)
            $table->unsignedBigInteger('subject_id')->nullable(); // ID объекта
            $table->json('old_values')->nullable(); // Старые значения (для update)
            $table->json('new_values')->nullable(); // Новые значения
            $table->string('ip_address', 45)->nullable(); // IP адрес пользователя
            $table->text('user_agent')->nullable(); // Браузер/устройство
            $table->unsignedBigInteger('created_by'); // ID создателя (для multi-tenancy)
            $table->timestamps();

            // Индексы для быстрого поиска
            $table->index('user_id');
            $table->index('event_type');
            $table->index('subject_type');
            $table->index('created_at');
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_by');

            // Foreign keys
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
