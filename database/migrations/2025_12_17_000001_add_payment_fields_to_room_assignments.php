<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds payment fields to room_assignments table to track individual worker payment settings.
     * Migrates existing data from rooms table.
     */
    public function up(): void
    {
        // Step 1: Add new columns to room_assignments
        Schema::table('room_assignments', function (Blueprint $table) {
            $table->enum('payment_type', ['agency', 'worker'])->default('agency')->after('check_out_date');
            $table->decimal('payment_amount', 10, 2)->nullable()->after('payment_type');
        });

        // Step 2: Migrate existing data from rooms to room_assignments
        // For each active assignment, copy payment settings from the room
        DB::statement("
            UPDATE room_assignments ra
            INNER JOIN rooms r ON ra.room_id = r.id
            SET 
                ra.payment_type = CASE 
                    WHEN r.payment_type = 'worker' THEN 'worker'
                    WHEN r.payment_type = 'partial' THEN 'worker'
                    ELSE 'agency'
                END,
                ra.payment_amount = CASE 
                    WHEN r.payment_type = 'worker' THEN r.monthly_price
                    WHEN r.payment_type = 'partial' THEN r.partial_amount
                    ELSE NULL
                END
            WHERE ra.check_out_date IS NULL
        ");

        // Step 3: Create payment history table
        Schema::create('room_assignment_payment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_assignment_id')->constrained('room_assignments')->onDelete('cascade');
            $table->enum('payment_type', ['agency', 'worker']);
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->string('changed_by_name')->nullable(); // Store name for history
            $table->integer('changed_by')->nullable(); // User ID who made the change
            $table->text('comment')->nullable();
            $table->timestamps();
            
            $table->index('room_assignment_id');
        });

        // Step 4: Create initial history records for existing assignments
        DB::statement("
            INSERT INTO room_assignment_payment_history (room_assignment_id, payment_type, payment_amount, changed_by_name, changed_by, comment, created_at, updated_at)
            SELECT 
                ra.id,
                ra.payment_type,
                ra.payment_amount,
                'System Migration',
                ra.created_by,
                'Initial payment settings migrated from room',
                NOW(),
                NOW()
            FROM room_assignments ra
            WHERE ra.check_out_date IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_assignment_payment_history');
        
        Schema::table('room_assignments', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'payment_amount']);
        });
    }
};
