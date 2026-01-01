<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Переименовываем таблицу workers в massage_clients для Infinity CRM
     */
    public function up(): void
    {
        // Переименовываем таблицу workers в massage_clients
        Schema::rename('workers', 'massage_clients');
        
        // Добавляем новые поля для клиентов массажного салона
        Schema::table('massage_clients', function (Blueprint $table) {
            // Убираем ненужные поля воркеров и добавляем поля клиентов
            $table->string('preferred_service')->nullable()->after('email'); // Предпочитаемая услуга
            $table->text('notes')->nullable()->after('preferred_service'); // Заметки о клиенте
            $table->string('source')->nullable()->after('notes'); // Откуда узнал (реклама, рекомендация и т.д.)
            $table->integer('visits_count')->default(0)->after('source'); // Количество визитов
            $table->decimal('total_spent', 15, 2)->default(0)->after('visits_count'); // Общая сумма потраченная
            $table->date('last_visit_date')->nullable()->after('total_spent'); // Дата последнего визита
            $table->string('status')->default('active')->after('last_visit_date'); // active, vip, blocked
        });
        
        // Обновляем room_assignments - переименовываем worker_id в massage_client_id
        Schema::table('room_assignments', function (Blueprint $table) {
            $table->renameColumn('worker_id', 'massage_client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем worker_id
        Schema::table('room_assignments', function (Blueprint $table) {
            $table->renameColumn('massage_client_id', 'worker_id');
        });
        
        // Удаляем добавленные поля
        Schema::table('massage_clients', function (Blueprint $table) {
            $table->dropColumn([
                'preferred_service',
                'notes', 
                'source',
                'visits_count',
                'total_spent',
                'last_visit_date',
                'status'
            ]);
        });
        
        // Переименовываем обратно
        Schema::rename('massage_clients', 'workers');
    }
};
