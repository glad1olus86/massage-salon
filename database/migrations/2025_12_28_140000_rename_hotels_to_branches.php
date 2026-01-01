<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Расширяем существующую таблицу branches для Infinity CRM
     */
    public function up(): void
    {
        // Расширяем существующую таблицу branches
        Schema::table('branches', function (Blueprint $table) {
            $table->string('address')->nullable()->after('name');
            $table->string('phone')->nullable()->after('address');
            $table->string('email')->nullable()->after('phone');
            $table->unsignedBigInteger('responsible_id')->nullable()->after('created_by');
        });
        
        // Добавляем branch_id в rooms (оставляем hotel_id для обратной совместимости)
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('hotel_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
        
        // Добавляем branch_id в room_assignments (оставляем hotel_id для обратной совместимости)
        Schema::table('room_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('hotel_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем branch_id из room_assignments
        Schema::table('room_assignments', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
        
        // Удаляем branch_id из rooms
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
        
        // Удаляем добавленные колонки из branches
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['address', 'phone', 'email', 'responsible_id']);
        });
    }
};
