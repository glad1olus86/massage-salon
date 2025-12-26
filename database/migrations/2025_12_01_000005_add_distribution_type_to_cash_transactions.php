<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            // Тип выдачи: salary = зарплата сотруднику, transfer = передача средств (для дальнейших трат)
            $table->enum('distribution_type', ['salary', 'transfer'])->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropColumn('distribution_type');
        });
    }
};
