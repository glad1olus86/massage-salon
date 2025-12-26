<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add default notification settings for super admin (created_by = 1)
        $settings = [
            ['name' => 'notifications_enabled', 'value' => 'on', 'created_by' => 1],
            ['name' => 'notification_poll_interval', 'value' => '5', 'created_by' => 1], // minutes
            ['name' => 'notification_hotel_occupancy_threshold', 'value' => '50', 'created_by' => 1], // percent
            ['name' => 'notification_unemployed_days', 'value' => '3', 'created_by' => 1], // days
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['name' => $setting['name'], 'created_by' => $setting['created_by']],
                ['value' => $setting['value']]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('created_by', 1)
            ->whereIn('name', [
                'notifications_enabled',
                'notification_poll_interval', 
                'notification_hotel_occupancy_threshold',
                'notification_unemployed_days'
            ])
            ->delete();
    }
};
