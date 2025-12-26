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
        Schema::create('user_billing_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->date('period_start');
            $table->date('period_end');
            
            // Current active counts
            $table->integer('current_managers')->default(0);
            $table->integer('current_curators')->default(0);
            
            // Maximum used during period (for anti-abuse protection)
            $table->integer('max_managers_used')->default(0);
            $table->integer('max_curators_used')->default(0);
            
            // Billing amounts
            $table->decimal('base_amount', 10, 2)->default(0);
            $table->decimal('additional_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            
            // Status: active, pending_payment, paid, failed
            $table->enum('status', ['active', 'pending_payment', 'paid', 'failed'])->default('active');
            $table->timestamp('paid_at')->nullable();
            
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['company_id', 'period_start']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_billing_periods');
    }
};
