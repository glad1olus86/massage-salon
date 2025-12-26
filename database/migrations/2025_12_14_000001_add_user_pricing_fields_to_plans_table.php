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
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('base_users_limit')->nullable()->after('max_users');
            $table->decimal('manager_price', 10, 2)->default(50.00)->after('base_users_limit');
            $table->decimal('curator_price', 10, 2)->default(30.00)->after('manager_price');
        });

        // Update existing plans with base_users_limit based on their names
        // Free Plan = 3, Small company = 4, Enterprise = 6
        \DB::table('plans')->where('name', 'like', '%Free%')->update(['base_users_limit' => 3]);
        \DB::table('plans')->where('name', 'like', '%Small%')->update(['base_users_limit' => 4]);
        \DB::table('plans')->where('name', 'like', '%Enterprise%')->update(['base_users_limit' => 6]);
        
        // For any other plans, set base_users_limit equal to max_users
        \DB::table('plans')
            ->whereNull('base_users_limit')
            ->update(['base_users_limit' => \DB::raw('max_users')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['base_users_limit', 'manager_price', 'curator_price']);
        });
    }
};
