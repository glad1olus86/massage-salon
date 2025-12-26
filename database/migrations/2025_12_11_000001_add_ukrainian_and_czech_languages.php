<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add Ukrainian language if not exists
        $ukExists = DB::table('languages')->where('code', 'uk')->exists();
        if (!$ukExists) {
            DB::table('languages')->insert([
                'code' => 'uk',
                'full_name' => 'Ukrainian',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add Czech language if not exists
        $csExists = DB::table('languages')->where('code', 'cs')->exists();
        if (!$csExists) {
            DB::table('languages')->insert([
                'code' => 'cs',
                'full_name' => 'Czech',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('languages')->where('code', 'uk')->delete();
        DB::table('languages')->where('code', 'cs')->delete();
    }
};
