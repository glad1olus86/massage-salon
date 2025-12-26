<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('tech_passport_front', 255)->nullable()->after('photo'); // фото лицевой стороны техпаспорта
            $table->string('tech_passport_back', 255)->nullable()->after('tech_passport_front'); // фото обратной стороны техпаспорта
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['tech_passport_front', 'tech_passport_back']);
        });
    }
};
