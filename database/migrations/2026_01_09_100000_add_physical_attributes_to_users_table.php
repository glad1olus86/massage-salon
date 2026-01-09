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
        Schema::table('users', function (Blueprint $table) {
            $table->json('languages')->nullable()->after('nationality'); // Языки (массив)
            $table->integer('height')->nullable()->after('languages'); // Рост в см
            $table->integer('weight')->nullable()->after('height'); // Вес в кг
            $table->integer('breast_size')->nullable()->after('weight'); // Размер груди
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['languages', 'height', 'weight', 'breast_size']);
        });
    }
};
