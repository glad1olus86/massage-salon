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
        Schema::create('cash_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by');
            $table->integer('year');
            $table->integer('month');
            $table->decimal('total_deposited', 15, 2)->default(0);
            $table->boolean('is_frozen')->default(false);
            $table->timestamps();

            $table->unique(['created_by', 'year', 'month']);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash_periods');
    }
};
