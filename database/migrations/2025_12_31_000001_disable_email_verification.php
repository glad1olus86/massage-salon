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
        // Disable email verification in settings
        DB::table('settings')
            ->where('name', 'email_verification')
            ->update(['value' => 'off']);
        
        // Mark all existing users as verified
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-enable email verification
        DB::table('settings')
            ->where('name', 'email_verification')
            ->update(['value' => 'on']);
    }
};
