<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('operator_id')->nullable()->after('branch_id');
            $table->foreign('operator_id')->references('id')->on('users')->onDelete('set null');
            $table->index('operator_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['operator_id']);
            $table->dropIndex(['operator_id']);
            $table->dropColumn('operator_id');
        });
    }
};
