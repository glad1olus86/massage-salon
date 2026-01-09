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
        Schema::table('massage_services', function (Blueprint $table) {
            // Цены за разную длительность
            $table->decimal('price_15', 10, 2)->nullable()->after('price')->comment('Цена за 15 минут');
            $table->decimal('price_30', 10, 2)->nullable()->after('price_15')->comment('Цена за 30 минут');
            $table->decimal('price_45', 10, 2)->nullable()->after('price_30')->comment('Цена за 45 минут');
            $table->decimal('price_60', 10, 2)->nullable()->after('price_45')->comment('Цена за 60 минут');
            $table->decimal('price_90', 10, 2)->nullable()->after('price_60')->comment('Цена за 90 минут');
            $table->decimal('price_120', 10, 2)->nullable()->after('price_90')->comment('Цена за 120 минут');
            $table->decimal('price_180', 10, 2)->nullable()->after('price_120')->comment('Цена за 180 минут');
            
            // Доли оператора за новые длительности
            $table->decimal('operator_share_15', 10, 2)->nullable()->after('operator_share_120')->comment('Доля оператора за 15 минут');
            $table->decimal('operator_share_30', 10, 2)->nullable()->after('operator_share_15')->comment('Доля оператора за 30 минут');
            $table->decimal('operator_share_45', 10, 2)->nullable()->after('operator_share_30')->comment('Доля оператора за 45 минут');
            $table->decimal('operator_share_180', 10, 2)->nullable()->after('operator_share_45')->comment('Доля оператора за 180 минут');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('massage_services', function (Blueprint $table) {
            $table->dropColumn([
                'price_15', 'price_30', 'price_45', 'price_60', 'price_90', 'price_120', 'price_180',
                'operator_share_15', 'operator_share_30', 'operator_share_45', 'operator_share_180'
            ]);
        });
    }
};
