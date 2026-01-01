<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Создаём таблицу услуг массажного салона
     */
    public function up(): void
    {
        // Таблица услуг (админский список)
        Schema::create('massage_services', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название услуги
            $table->text('description')->nullable(); // Описание
            $table->decimal('price', 10, 2)->default(0); // Цена в CZK
            $table->integer('duration')->nullable(); // Длительность в минутах
            $table->boolean('is_active')->default(true); // Активна ли услуга
            $table->integer('sort_order')->default(0); // Порядок сортировки
            $table->integer('created_by'); // Компания-владелец
            $table->timestamps();
        });

        // Pivot таблица для связи пользователей (девочек) с услугами
        Schema::create('user_massage_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('massage_service_id');
            $table->decimal('custom_price', 10, 2)->nullable(); // Индивидуальная цена (если отличается)
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('massage_service_id')->references('id')->on('massage_services')->onDelete('cascade');
            
            $table->unique(['user_id', 'massage_service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_massage_services');
        Schema::dropIfExists('massage_services');
    }
};
