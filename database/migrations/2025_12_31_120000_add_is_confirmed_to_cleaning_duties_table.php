<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cleaning_duties', function (Blueprint $table) {
            $table->boolean('is_confirmed')->default(false)->after('is_manual');
        });
        
        // Утверждаем все существующие дежурства текущей недели и раньше
        DB::table('cleaning_duties')
            ->where('duty_date', '<=', now()->endOfWeek()->toDateString())
            ->update(['is_confirmed' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cleaning_duties', function (Blueprint $table) {
            $table->dropColumn('is_confirmed');
        });
    }
};
