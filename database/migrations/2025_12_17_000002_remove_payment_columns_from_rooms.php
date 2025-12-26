<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Removes deprecated payment_type and partial_amount columns from rooms table.
     * Payment settings are now stored per-resident in room_assignments table.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (Schema::hasColumn('rooms', 'payment_type')) {
                $table->dropColumn('payment_type');
            }
            if (Schema::hasColumn('rooms', 'partial_amount')) {
                $table->dropColumn('partial_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->enum('payment_type', ['worker', 'agency', 'partial'])->default('worker')->after('monthly_price');
            $table->decimal('partial_amount', 10, 2)->nullable()->after('payment_type');
        });
    }
};
