<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_places', function (Blueprint $table) {
            $table->unsignedBigInteger('responsible_id')->nullable()->after('created_by');
            $table->foreign('responsible_id')->references('id')->on('users')->onDelete('set null');
        });

        // Set default responsible_id to created_by for existing records
        DB::statement('UPDATE work_places SET responsible_id = created_by WHERE responsible_id IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_places', function (Blueprint $table) {
            $table->dropForeign(['responsible_id']);
            $table->dropColumn('responsible_id');
        });
    }
};
