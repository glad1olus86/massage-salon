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
            // JOBSI Module Access (toggles)
            $table->boolean('module_workers')->default(1)->after('chatgpt');
            $table->boolean('module_workplaces')->default(1)->after('module_workers');
            $table->boolean('module_hotels')->default(1)->after('module_workplaces');
            $table->boolean('module_vehicles')->default(1)->after('module_hotels');
            $table->boolean('module_documents')->default(1)->after('module_vehicles');
            $table->boolean('module_cashbox')->default(1)->after('module_documents');
            $table->boolean('module_calendar')->default(1)->after('module_cashbox');
            $table->boolean('module_notifications')->default(1)->after('module_calendar');
            
            // JOBSI Limits (-1 = unlimited)
            $table->integer('max_workers')->default(-1)->after('module_notifications');
            $table->integer('max_hotels')->default(-1)->after('max_workers');
            $table->integer('max_vehicles')->default(-1)->after('max_hotels');
            $table->integer('max_document_templates')->default(5)->after('max_vehicles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'module_workers',
                'module_workplaces', 
                'module_hotels',
                'module_vehicles',
                'module_documents',
                'module_cashbox',
                'module_calendar',
                'module_notifications',
                'max_workers',
                'max_hotels',
                'max_vehicles',
                'max_document_templates',
            ]);
        });
    }
};
