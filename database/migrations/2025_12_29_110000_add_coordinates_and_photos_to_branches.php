<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // Координаты для карты
            $table->decimal('latitude', 10, 8)->nullable()->after('email');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            
            // Фото филиала (JSON массив путей до 10 фото)
            $table->json('photos')->nullable()->after('longitude');
            
            // Часы работы
            $table->string('working_hours')->nullable()->after('photos');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'photos', 'working_hours']);
        });
    }
};
