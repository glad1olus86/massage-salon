<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    use HasFactory;

    const TYPE_DEPOSIT = 'deposit';
    const TYPE_DISTRIBUTION = 'distribution';
    const TYPE_REFUND = 'refund';
    const TYPE_SELF_SALARY = 'self_salary';
    const TYPE_CARRYOVER = 'carryover';             // Перенос остатка с прошлого месяца

    // Типы выдачи денег
    const DISTRIBUTION_TYPE_SALARY = 'salary';      // Зарплата сотруднику (конечная выдача)
    const DISTRIBUTION_TYPE_TRANSFER = 'transfer';  // Передача средств (для дальнейших трат)
    const DISTRIBUTION_TYPE_CARRYOVER = 'carryover'; // Перенос с прошлого месяца

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_OVERDUE = 'overdue';

    protected $fillable = [
        'cash_period_id',
        'created_by',
        'type',
        'distribution_type',
        'sender_id',
        'sender_type',
        'recipient_id',
        'recipient_type',
        'amount',
        'task',
        'comment',
        'status',
        'parent_transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the cash period this transaction belongs to.
     */
    public function cashPeriod()
    {
        return $this->belongsTo(CashPeriod::class);
    }

    /**
     * Get the company that owns this transaction.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the sender (polymorphic).
     */
    public function sender()
    {
        return $this->morphTo();
    }

    /**
     * Get the recipient (polymorphic).
     */
    public function recipient()
    {
        return $this->morphTo();
    }

    /**
     * Get the parent transaction (for refunds).
     */
    public function parentTransaction()
    {
        return $this->belongsTo(CashTransaction::class, 'parent_transaction_id');
    }

    /**
     * Get child transactions (refunds of this transaction).
     */
    public function childTransactions()
    {
        return $this->hasMany(CashTransaction::class, 'parent_transaction_id');
    }

    /**
     * Scope for deposit transactions.
     */
    public function scopeDeposits($query)
    {
        return $query->where('type', self::TYPE_DEPOSIT);
    }

    /**
     * Scope for distribution transactions.
     */
    public function scopeDistributions($query)
    {
        return $query->where('type', self::TYPE_DISTRIBUTION);
    }

    /**
     * Scope for refund transactions.
     */
    public function scopeRefunds($query)
    {
        return $query->where('type', self::TYPE_REFUND);
    }

    /**
     * Scope for self salary transactions.
     */
    public function scopeSelfSalaries($query)
    {
        return $query->where('type', self::TYPE_SELF_SALARY);
    }

    /**
     * Scope for pending status.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for in progress status.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope for completed status.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Check if transaction is a deposit.
     */
    public function isDeposit(): bool
    {
        return $this->type === self::TYPE_DEPOSIT;
    }

    /**
     * Check if transaction is a distribution.
     */
    public function isDistribution(): bool
    {
        return $this->type === self::TYPE_DISTRIBUTION;
    }

    /**
     * Check if transaction is a refund.
     */
    public function isRefund(): bool
    {
        return $this->type === self::TYPE_REFUND;
    }

    /**
     * Check if recipient is a Worker (final, no refund possible).
     */
    public function isToWorker(): bool
    {
        return $this->recipient_type === Worker::class;
    }

    /**
     * Check if this is a salary distribution (final, auto-completed).
     */
    public function isSalaryDistribution(): bool
    {
        return $this->distribution_type === self::DISTRIBUTION_TYPE_SALARY;
    }

    /**
     * Check if this is a transfer distribution (for further spending).
     */
    public function isTransferDistribution(): bool
    {
        return $this->distribution_type === self::DISTRIBUTION_TYPE_TRANSFER;
    }

    /**
     * Check if this is a carryover transaction (balance from previous month).
     */
    public function isCarryover(): bool
    {
        return $this->type === self::TYPE_CARRYOVER || $this->distribution_type === self::DISTRIBUTION_TYPE_CARRYOVER;
    }

    /**
     * Scope for carryover transactions.
     */
    public function scopeCarryovers($query)
    {
        return $query->where('type', self::TYPE_CARRYOVER);
    }
}
