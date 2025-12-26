<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('payment_type')->default('worker')->after('price'); // worker, agency, partial
            $table->decimal('partial_amount', 10, 2)->nullable()->after('payment_type');
        });
        
        // Rename price column to monthly_price
        Schema::table('rooms', function (Blueprint $table) {
            $table->renameColumn('price', 'monthly_price');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->renameColumn('monthly_price', 'price');
            $table->dropColumn(['payment_type', 'partial_amount']);
        });
    }
};
