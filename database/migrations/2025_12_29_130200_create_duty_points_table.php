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
        Schema::create('duty_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('points')->default(0);
            $table->date('last_duty_date')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['branch_id', 'user_id'], 'idx_unique_branch_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duty_points');
    }
};
