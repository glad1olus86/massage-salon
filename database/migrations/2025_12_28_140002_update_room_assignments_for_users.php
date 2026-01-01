<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Добавляем возможность бронирования комнат пользователями (девочками)
     * вместо воркеров. Оставляем worker_id для обратной совместимости.
     */
    public function up(): void
    {
        Schema::table('room_assignments', function (Blueprint $table) {
            // Добавляем user_id для бронирования девочками
            $table->unsignedBigInteger('user_id')->nullable()->after('worker_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Добавляем поля для бронирования
            $table->text('notes')->nullable()->after('payment_amount'); // Заметки к бронированию
            $table->string('status')->default('active')->after('notes'); // active, completed, cancelled
        });
        
        // Делаем worker_id nullable через raw SQL (без doctrine/dbal)
        DB::statement('ALTER TABLE room_assignments MODIFY worker_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_assignments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'notes', 'status']);
        });
        
        // Возвращаем worker_id как NOT NULL
        DB::statement('ALTER TABLE room_assignments MODIFY worker_id BIGINT UNSIGNED NOT NULL');
    }
};
