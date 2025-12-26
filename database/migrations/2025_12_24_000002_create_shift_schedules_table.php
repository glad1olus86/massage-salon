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
        Schema::create('shift_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_template_id')->constrained()->onDelete('cascade');
            $table->json('work_days'); // [1,2,3,4,5] = Mon-Fri
            $table->date('valid_from');
            $table->date('valid_until')->nullable(); // NULL = indefinite
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['worker_id', 'valid_from', 'valid_until']);
            $table->index(['shift_template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_schedules');
    }
};
