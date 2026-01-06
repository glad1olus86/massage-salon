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
            $table->decimal('operator_share_60', 10, 2)->nullable()->after('price')->comment('Доля оператора за 60 минут');
            $table->decimal('operator_share_90', 10, 2)->nullable()->after('operator_share_60')->comment('Доля оператора за 90 минут');
            $table->decimal('operator_share_120', 10, 2)->nullable()->after('operator_share_90')->comment('Доля оператора за 120 минут');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('massage_services', function (Blueprint $table) {
            $table->dropColumn(['operator_share_60', 'operator_share_90', 'operator_share_120']);
        });
    }
};
