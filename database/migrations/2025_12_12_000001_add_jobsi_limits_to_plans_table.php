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
            // Add only columns that don't exist
            if (!Schema::hasColumn('plans', 'max_roles')) {
                $table->integer('max_roles')->default(-1)->after('max_workers');
            }
            if (!Schema::hasColumn('plans', 'max_workplaces')) {
                $table->integer('max_workplaces')->default(-1)->after('max_hotels');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'max_roles')) {
                $table->dropColumn('max_roles');
            }
            if (Schema::hasColumn('plans', 'max_workplaces')) {
                $table->dropColumn('max_workplaces');
            }
        });
    }
};
