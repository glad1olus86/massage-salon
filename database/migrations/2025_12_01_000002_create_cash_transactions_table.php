<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_period_id');
            $table->unsignedBigInteger('created_by');
            
            $table->enum('type', ['deposit', 'distribution', 'refund', 'self_salary']);
            
            // Полиморфный отправитель (null для deposit)
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('sender_type')->nullable();
            
            // Полиморфный получатель
            $table->unsignedBigInteger('recipient_id');
            $table->string('recipient_type');
            
            $table->decimal('amount', 15, 2);
            $table->string('task')->nullable();
            $table->text('comment')->nullable();
            
            $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue'])->default('pending');
            
            // Для возвратов - ссылка на исходную транзакцию
            $table->unsignedBigInteger('parent_transaction_id')->nullable();
            
            $table->timestamps();

            $table->foreign('cash_period_id')->references('id')->on('cash_periods')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_transaction_id')->references('id')->on('cash_transactions')->onDelete('set null');
            
            $table->index(['sender_id', 'sender_type']);
            $table->index(['recipient_id', 'recipient_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash_transactions');
    }
};
