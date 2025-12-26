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
        // Add Czech language if not exists
        $exists = DB::table('languages')->where('code', 'cs')->exists();
        
        if (!$exists) {
            DB::table('languages')->insert([
                'code' => 'cs',
                'full_name' => 'Čeština',
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
        DB::table('languages')->where('code', 'cs')->delete();
    }
};
