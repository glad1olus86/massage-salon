<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add notification create interval setting (how often to create new notifications)
        DB::table('settings')->updateOrInsert(
            ['name' => 'notification_create_interval', 'created_by' => 1],
            ['value' => '60'] // Default 60 minutes
        );
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('name', 'notification_create_interval')
            ->where('created_by', 1)
            ->delete();
    }
};
