<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_massage_services', function (Blueprint $table) {
            $table->boolean('is_extra')->default(false)->after('custom_price');
        });
    }

    public function down(): void
    {
        Schema::table('user_massage_services', function (Blueprint $table) {
            $table->dropColumn('is_extra');
        });
    }
};
