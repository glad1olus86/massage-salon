<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CashTransaction;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Facades\Auth;

/**
 * Service for logging cashbox operations to audit log
 * Requirements: 13.1, 13.2 - Log all operations with who, when, what, amount, to whom
 */
class CashboxAuditService
{
    // Event types for cashbox operations
    const EVENT_DEPOSIT = 'cashbox.deposit';
    const EVENT_DISTRIBUTION = 'cashbox.distribution';
    const EVENT_REFUND = 'cashbox.refund';
    const EVENT_SELF_SALARY = 'cashbox.self_salary';
    const EVENT_STATUS_CHANGE = 'cashbox.status_change';

    /**
     * Log a deposit operation
     *
     * @param CashTransaction $transaction
     * @return AuditLog
     */
    public function logDeposit(CashTransaction $transaction): AuditLog
    {
        $recipient = $this->getParticipantName($transaction->recipient_id, $transaction->recipient_type);
        $amount = $this->formatAmount($transaction->amount, $transaction->created_by);

        $description = sprintf(
            '%s внёс %s в кассу',
            $recipient,
            $amount
        );

        return $this->createLog(
            self::EVENT_DEPOSIT,
            $description,
            $transaction,
            null,
            [
                'amount' => $transaction->amount,
                'recipient_id' => $transaction->recipient_id,
                'recipient_type' => $transaction->recipient_type,
                'comment' => $transaction->comment,
                'period_id' => $transaction->cash_period_id,
            ]
        );
    }

    /**
     * Log a distribution operation
     *
     * @param CashTransaction $transaction
     * @return AuditLog
     */
    public function logDistribution(CashTransaction $transaction): AuditLog
    {
        $sender = $this->getParticipantName($transaction->sender_id, $transaction->sender_type);
        $recipient = $this->getParticipantName($transaction->recipient_id, $transaction->recipient_type);
        $amount = $this->formatAmount($transaction->amount, $transaction->created_by);

        $description = sprintf(
            '%s выдал %s пользователю %s',
            $sender,
            $amount,
            $recipient
        );

        if ($transaction->task) {
            $description .= sprintf(' (задача: %s)', $transaction->task);
        }

        return $this->createLog(
            self::EVENT_DISTRIBUTION,
            $description,
            $transaction,
            null,
            [
                'amount' => $transaction->amount,
                'sender_id' => $transaction->sender_id,
                'sender_type' => $transaction->sender_type,
                'recipient_id' => $transaction->recipient_id,
                'recipient_type' => $transaction->recipient_type,
                'task' => $transaction->task,
                'comment' => $transaction->comment,
                'period_id' => $transaction->cash_period_id,
            ]
        );
    }

    /**
     * Log a refund operation
     *
     * @param CashTransaction $transaction
     * @return AuditLog
     */
    public function logRefund(CashTransaction $transaction): AuditLog
    {
        $sender = $this->getParticipantName($transaction->sender_id, $transaction->sender_type);
        $recipient = $this->getParticipantName($transaction->recipient_id, $transaction->recipient_type);
        $amount = $this->formatAmount($transaction->amount, $transaction->created_by);

        $description = sprintf(
            '%s вернул %s пользователю %s',
            $sender,
            $amount,
            $recipient
        );

        if ($transaction->comment) {
            $description .= sprintf(' (причина: %s)', $transaction->comment);
        }

        return $this->createLog(
            self::EVENT_REFUND,
            $description,
            $transaction,
            null,
            [
                'amount' => $transaction->amount,
                'sender_id' => $transaction->sender_id,
                'sender_type' => $transaction->sender_type,
                'recipient_id' => $transaction->recipient_id,
                'recipient_type' => $transaction->recipient_type,
                'comment' => $transaction->comment,
                'parent_transaction_id' => $transaction->parent_transaction_id,
                'period_id' => $transaction->cash_period_id,
            ]
        );
    }

    /**
     * Log a self-salary operation
     *
     * @param CashTransaction $transaction
     * @return AuditLog
     */
    public function logSelfSalary(CashTransaction $transaction): AuditLog
    {
        $user = $this->getParticipantName($transaction->sender_id, $transaction->sender_type);
        $amount = $this->formatAmount($transaction->amount, $transaction->created_by);

        $description = sprintf(
            '%s взял себе зарплату %s',
            $user,
            $amount
        );

        return $this->createLog(
            self::EVENT_SELF_SALARY,
            $description,
            $transaction,
            null,
            [
                'amount' => $transaction->amount,
                'user_id' => $transaction->sender_id,
                'comment' => $transaction->comment,
                'period_id' => $transaction->cash_period_id,
            ]
        );
    }

    /**
     * Log a status change operation
     *
     * @param CashTransaction $transaction
     * @param string $oldStatus
     * @param string $newStatus
     * @return AuditLog
     */
    public function logStatusChange(CashTransaction $transaction, string $oldStatus, string $newStatus): AuditLog
    {
        $user = Auth::user() ? Auth::user()->name : 'Система';
        $statusLabels = [
            'pending' => 'Ожидает',
            'in_progress' => 'В работе',
            'completed' => 'Выполнено',
            'overdue' => 'Просрочено',
        ];

        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;

        $description = sprintf(
            '%s изменил статус транзакции #%d с "%s" на "%s"',
            $user,
            $transaction->id,
            $oldLabel,
            $newLabel
        );

        return $this->createLog(
            self::EVENT_STATUS_CHANGE,
            $description,
            $transaction,
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );
    }

    /**
     * Create an audit log entry
     *
     * @param string $eventType
     * @param string $description
     * @param CashTransaction $transaction
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return AuditLog
     */
    protected function createLog(
        string $eventType,
        string $description,
        CashTransaction $transaction,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'event_type' => $eventType,
            'description' => $description,
            'subject_type' => CashTransaction::class,
            'subject_id' => $transaction->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_by' => $transaction->created_by,
        ]);
    }

    /**
     * Get participant name by ID and type
     *
     * @param int|null $id
     * @param string|null $type
     * @return string
     */
    protected function getParticipantName(?int $id, ?string $type): string
    {
        if (!$id || !$type) {
            return 'Система';
        }

        if ($type === User::class || $type === 'App\Models\User') {
            $user = User::find($id);
            return $user ? $user->name : 'Неизвестный пользователь';
        }

        if ($type === Worker::class || $type === 'App\Models\Worker') {
            $worker = Worker::find($id);
            return $worker ? ($worker->first_name . ' ' . $worker->last_name) : 'Неизвестный работник';
        }

        return 'Неизвестный';
    }

    /**
     * Format amount with currency
     *
     * @param float $amount
     * @param int $companyId
     * @return string
     */
    protected function formatAmount(float $amount, int $companyId): string
    {
        if (function_exists('formatCashboxCurrency')) {
            return formatCashboxCurrency($amount, $companyId);
        }
        
        return number_format($amount, 2, '.', ' ') . ' €';
    }
}
