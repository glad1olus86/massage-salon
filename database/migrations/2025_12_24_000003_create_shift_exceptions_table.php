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
        Schema::create('shift_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->foreignId('shift_template_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('type', ['add', 'remove', 'replace']); // add shift, remove shift, replace with another
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->unique(['worker_id', 'date']);
            $table->index(['date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_exceptions');
    }
};
