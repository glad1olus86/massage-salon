<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration to add cashbox_currency setting for companies
 * Requirement 11.1: Support currencies EUR, USD, PLN, CZK with EUR as default
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add default cashbox_currency setting for all existing companies
        $companies = DB::table('users')
            ->where('type', 'company')
            ->pluck('id');

        foreach ($companies as $companyId) {
            // Check if setting already exists
            $exists = DB::table('settings')
                ->where('name', 'cashbox_currency')
                ->where('created_by', $companyId)
                ->exists();

            if (!$exists) {
                DB::table('settings')->insert([
                    'name' => 'cashbox_currency',
                    'value' => 'EUR',
                    'created_by' => $companyId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')
            ->where('name', 'cashbox_currency')
            ->delete();
    }
};
