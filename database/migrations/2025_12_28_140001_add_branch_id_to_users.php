<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Добавляем привязку пользователей (девочек-массажисток) к филиалам
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('created_by');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
