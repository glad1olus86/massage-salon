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
        Schema::table('rooms', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['hotel_id']);
            
            // Make hotel_id nullable
            $table->unsignedBigInteger('hotel_id')->nullable()->change();
            
            // Re-add foreign key with ON DELETE SET NULL
            $table->foreign('hotel_id')
                ->references('id')
                ->on('hotels')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['hotel_id']);
            $table->unsignedBigInteger('hotel_id')->nullable(false)->change();
            $table->foreign('hotel_id')
                ->references('id')
                ->on('hotels')
                ->onDelete('cascade');
        });
    }
};
