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
        Schema::create('shift_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_place_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('break_minutes')->default(0);
            $table->string('color', 7)->default('#3788d8');
            
            // Payment settings
            $table->enum('pay_type', ['per_shift', 'hourly'])->default('per_shift');
            $table->decimal('pay_rate', 10, 2)->nullable();
            $table->boolean('night_bonus_enabled')->default(false);
            $table->decimal('night_bonus_percent', 5, 2)->default(20);
            
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['work_place_id', 'created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_templates');
    }
};
