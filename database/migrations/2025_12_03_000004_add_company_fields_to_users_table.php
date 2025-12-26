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
            $table->string('company_address')->nullable()->after('avatar');
            $table->string('company_ico')->nullable()->after('company_address');
            $table->string('company_phone')->nullable()->after('company_ico');
            $table->string('company_bank_account')->nullable()->after('company_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['company_address', 'company_ico', 'company_phone', 'company_bank_account']);
        });
    }
};
