<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('massage_orders', function (Blueprint $table) {
            $table->id();
            
            // Клиент (может быть из БД или просто имя)
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('client_name')->nullable(); // Если клиент не в БД
            
            // Массажистка (user)
            $table->unsignedBigInteger('employee_id')->nullable();
            
            // Филиал
            $table->unsignedBigInteger('branch_id')->nullable();
            
            // Услуга
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('service_name')->nullable(); // Если услуга не в БД
            
            // Дата и время
            $table->date('order_date');
            $table->time('order_time')->nullable();
            $table->integer('duration')->nullable(); // в минутах
            
            // Финансы
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('tip', 10, 2)->default(0);
            $table->string('payment_method')->nullable(); // cash, card, transfer
            
            // Статус
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            
            // Примечания
            $table->text('notes')->nullable();
            
            // Кто создал запись
            $table->unsignedBigInteger('created_by')->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('client_id')->references('id')->on('massage_clients')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('service_id')->references('id')->on('massage_services')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('massage_orders');
    }
};
