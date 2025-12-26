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
        Schema::create('user_billing_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('billing_period_id');
            $table->unsignedBigInteger('user_id')->nullable();
            
            // Action: user_added, user_removed, role_changed
            $table->string('action', 50);
            $table->string('role', 50);
            $table->string('previous_role', 50)->nullable();
            $table->text('details')->nullable();
            
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('billing_period_id')->references('id')->on('user_billing_periods')->onDelete('cascade');
            $table->index(['company_id', 'billing_period_id']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_billing_logs');
    }
};
