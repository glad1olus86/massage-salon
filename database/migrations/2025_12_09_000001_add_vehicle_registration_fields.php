<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->date('registration_date')->nullable()->after('vin_code');
            $table->integer('engine_volume')->nullable()->after('registration_date'); // объём двигателя в см³
            $table->decimal('passport_fuel_consumption', 4, 1)->nullable()->after('engine_volume'); // паспортный расход V.8
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['registration_date', 'engine_volume', 'passport_fuel_consumption']);
        });
    }
};
