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
        Schema::create('cleaning_duties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id');
            $table->date('duty_date');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['branch_id', 'duty_date'], 'idx_unique_branch_duty_date');
            $table->index(['user_id', 'duty_date'], 'idx_duty_user_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleaning_duties');
    }
};
