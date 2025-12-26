<?php

namespace App\Services;

use App\Models\CashPeriod;
use App\Models\CashTransaction;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CashboxService
{
    protected CashHierarchyService $hierarchyService;
    protected CashboxNotificationService $notificationService;
    protected CashboxAuditService $auditService;

    public function __construct(
        CashHierarchyService $hierarchyService, 
        CashboxNotificationService $notificationService,
        CashboxAuditService $auditService
    ) {
        $this->hierarchyService = $hierarchyService;
        $this->notificationService = $notificationService;
        $this->auditService = $auditService;
    }

    /**
     * Create a deposit transaction (only Boss)
     * Requirement 4.1: Boss creates deposit, adds to period balance
     *
     * @param CashPeriod $period
     * @param User $boss
     * @param float $amount
     * @param string|null $comment
     * @return CashTransaction
     * @throws InvalidArgumentException
     */
    public function createDeposit(CashPeriod $period, User $boss, float $amount, ?string $comment = null): CashTransaction
    {
        $role = $this->hierarchyService->getUserCashboxRole($boss);
        
        if ($role !== CashHierarchyService::ROLE_BOSS) {
            throw new InvalidArgumentException('Only Boss can create deposits.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        $transaction = DB::transaction(function () use ($period, $boss, $amount, $comment) {
            $transaction = CashTransaction::create([
                'cash_period_id' => $period->id,
                'created_by' => $period->created_by,
                'type' => CashTransaction::TYPE_DEPOSIT,
                'sender_id' => null,
                'sender_type' => null,
                'recipient_id' => $boss->id,
                'recipient_type' => User::class,
                'amount' => $amount,
                'task' => null,
                'comment' => $comment,
                'status' => CashTransaction::STATUS_COMPLETED,
            ]);

            // Update period total
            $period->increment('total_deposited', $amount);

            return $transaction;
        });

        // Log to audit (Requirement 13.1, 13.2)
        $this->auditService->logDeposit($transaction);

        return $transaction;
    }


    /**
     * Create a distribution transaction
     * Requirement 5.1: Decreases sender balance, increases recipient balance
     * Requirement 5.3: Cannot distribute more than available balance
     *
     * @param CashPeriod $period
     * @param User $sender
     * @param User|Worker $recipient
     * @param float $amount
     * @param string|null $task
     * @param string|null $comment
     * @param string|null $distributionType 'salary' or 'transfer'
     * @return CashTransaction
     * @throws InvalidArgumentException
     */
    public function createDistribution(
        CashPeriod $period,
        User $sender,
        $recipient,
        float $amount,
        ?string $task = null,
        ?string $comment = null,
        ?string $distributionType = null
    ): CashTransaction {
        // Check hierarchy
        if (!$this->hierarchyService->canDistributeTo($sender, $recipient)) {
            throw new InvalidArgumentException('You cannot distribute money to this recipient.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        // Check frozen period (only Boss can edit frozen)
        if ($period->isFrozen()) {
            $role = $this->hierarchyService->getUserCashboxRole($sender);
            if ($role !== CashHierarchyService::ROLE_BOSS) {
                throw new InvalidArgumentException('Cannot create transactions in a frozen period.');
            }
        }

        // Check sender balance
        $balance = $this->getBalance($period, $sender);
        if ($balance['available'] < $amount) {
            throw new InvalidArgumentException('Insufficient balance. Available: ' . $balance['available']);
        }

        $transaction = DB::transaction(function () use ($period, $sender, $recipient, $amount, $task, $comment, $distributionType) {
            $recipientType = $recipient instanceof Worker ? Worker::class : User::class;
            $isToWorker = $recipientType === Worker::class;
            
            // Определяем статус: для работников и зарплат - сразу выполнено
            // Работники не распоряжаются деньгами, поэтому транзакция сразу завершена
            $status = ($isToWorker || $distributionType === CashTransaction::DISTRIBUTION_TYPE_SALARY)
                ? CashTransaction::STATUS_COMPLETED
                : CashTransaction::STATUS_PENDING;
            
            $transaction = CashTransaction::create([
                'cash_period_id' => $period->id,
                'created_by' => $period->created_by,
                'type' => CashTransaction::TYPE_DISTRIBUTION,
                'distribution_type' => $distributionType,
                'sender_id' => $sender->id,
                'sender_type' => User::class,
                'recipient_id' => $recipient->id,
                'recipient_type' => $recipientType,
                'amount' => $amount,
                'task' => $task,
                'comment' => $comment,
                'status' => $status,
            ]);

            return $transaction;
        });

        // Log to audit (Requirement 13.1, 13.2)
        $this->auditService->logDistribution($transaction);

        // Send notifications
        $this->notificationService->notifyMoneySent($transaction);
        $this->notificationService->notifyMoneyReceived($transaction);

        return $transaction;
    }

    /**
     * Create a refund transaction
     * Requirement 7.1: Decreases sender balance, increases recipient balance
     * Requirement 7.4: Can only refund to the person who gave the money
     *
     * @param CashPeriod $period
     * @param CashTransaction $originalTransaction The transaction being refunded
     * @param User $sender The one returning money
     * @param float $amount
     * @param string $comment Required comment with reason
     * @return CashTransaction
     * @throws InvalidArgumentException
     */
    public function createRefund(
        CashPeriod $period,
        CashTransaction $originalTransaction,
        User $sender,
        float $amount,
        string $comment
    ): CashTransaction {
        // Requirement 7.5: Cannot refund money given to Workers
        if ($originalTransaction->isToWorker()) {
            throw new InvalidArgumentException('Cannot refund money that was given to a Worker.');
        }

        // Verify sender is the recipient of original transaction
        if ($originalTransaction->recipient_id !== $sender->id || 
            $originalTransaction->recipient_type !== User::class) {
            throw new InvalidArgumentException('You can only refund money that was given to you.');
        }

        // Get original sender
        $originalSender = User::find($originalTransaction->sender_id);
        if (!$originalSender) {
            throw new InvalidArgumentException('Original sender not found.');
        }

        // Check hierarchy for refund
        if (!$this->hierarchyService->canRefundTo($sender, $originalSender)) {
            throw new InvalidArgumentException('You cannot refund money to this person.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        if (empty($comment)) {
            throw new InvalidArgumentException('Comment is required for refunds.');
        }

        // Check sender balance
        $balance = $this->getBalance($period, $sender);
        if ($balance['available'] < $amount) {
            throw new InvalidArgumentException('Insufficient balance for refund.');
        }

        // Check frozen period
        if ($period->isFrozen()) {
            $role = $this->hierarchyService->getUserCashboxRole($sender);
            if ($role !== CashHierarchyService::ROLE_BOSS) {
                throw new InvalidArgumentException('Cannot create transactions in a frozen period.');
            }
        }

        $transaction = DB::transaction(function () use ($period, $sender, $originalSender, $originalTransaction, $amount, $comment) {
            $transaction = CashTransaction::create([
                'cash_period_id' => $period->id,
                'created_by' => $period->created_by,
                'type' => CashTransaction::TYPE_REFUND,
                'sender_id' => $sender->id,
                'sender_type' => User::class,
                'recipient_id' => $originalSender->id,
                'recipient_type' => User::class,
                'amount' => $amount,
                'task' => null,
                'comment' => $comment,
                'status' => CashTransaction::STATUS_COMPLETED,
                'parent_transaction_id' => $originalTransaction->id,
            ]);

            return $transaction;
        });

        // Log to audit (Requirement 13.1, 13.2)
        $this->auditService->logRefund($transaction);

        // Send refund notification
        $this->notificationService->notifyMoneyRefunded($transaction);

        return $transaction;
    }


    /**
     * Create a self-salary transaction
     * Requirement 6.1: Manager can take salary once per period
     * Requirement 6.2: Block if already taken this period
     * Requirement 6.4: Boss has no limit
     *
     * @param CashPeriod $period
     * @param User $user
     * @param float $amount
     * @param string|null $comment
     * @return CashTransaction
     * @throws InvalidArgumentException
     */
    public function createSelfSalary(
        CashPeriod $period,
        User $user,
        float $amount,
        ?string $comment = null
    ): CashTransaction {
        // Check if user can distribute to self
        if (!$this->hierarchyService->canDistributeToSelf($user)) {
            throw new InvalidArgumentException('You cannot take self-salary.');
        }

        // Check if user has limit and already took salary this period
        if ($this->hierarchyService->hasSelfSalaryLimit($user)) {
            if ($this->hasSelfSalaryThisPeriod($period, $user)) {
                throw new InvalidArgumentException('You have already taken self-salary this period.');
            }
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        // Check balance
        $balance = $this->getBalance($period, $user);
        if ($balance['available'] < $amount) {
            throw new InvalidArgumentException('Insufficient balance for self-salary.');
        }

        // Check frozen period
        if ($period->isFrozen()) {
            $role = $this->hierarchyService->getUserCashboxRole($user);
            if ($role !== CashHierarchyService::ROLE_BOSS) {
                throw new InvalidArgumentException('Cannot create transactions in a frozen period.');
            }
        }

        $transaction = DB::transaction(function () use ($period, $user, $amount, $comment) {
            $transaction = CashTransaction::create([
                'cash_period_id' => $period->id,
                'created_by' => $period->created_by,
                'type' => CashTransaction::TYPE_SELF_SALARY,
                'sender_id' => $user->id,
                'sender_type' => User::class,
                'recipient_id' => $user->id,
                'recipient_type' => User::class,
                'amount' => $amount,
                'task' => 'Зарплата',
                'comment' => $comment,
                'status' => CashTransaction::STATUS_COMPLETED,
            ]);

            return $transaction;
        });

        // Log to audit (Requirement 13.1, 13.2)
        $this->auditService->logSelfSalary($transaction);

        return $transaction;
    }

    /**
     * Get balance for a user in a period
     * Requirement 10.1: Show received and sent amounts
     * 
     * Note: Salary distributions (distribution_type = 'salary') are NOT counted as received
     * because salary money leaves the business - it's personal money, not business funds.
     * Only 'transfer' and 'carryover' distributions count towards the recipient's business balance.
     *
     * @param CashPeriod $period
     * @param User|Worker $user
     * @return array ['received' => float, 'sent' => float, 'available' => float]
     */
    public function getBalance(CashPeriod $period, $user): array
    {
        $userType = $user instanceof Worker ? Worker::class : User::class;
        $userId = $user->id;

        // Calculate received from deposits
        $receivedFromDeposits = CashTransaction::where('cash_period_id', $period->id)
            ->where('recipient_id', $userId)
            ->where('recipient_type', $userType)
            ->where('type', CashTransaction::TYPE_DEPOSIT)
            ->sum('amount');

        // Calculate received from carryover (Boss only)
        $receivedFromCarryover = CashTransaction::where('cash_period_id', $period->id)
            ->where('recipient_id', $userId)
            ->where('recipient_type', $userType)
            ->where('type', CashTransaction::TYPE_CARRYOVER)
            ->sum('amount');

        // Calculate received from refunds
        $receivedFromRefunds = CashTransaction::where('cash_period_id', $period->id)
            ->where('recipient_id', $userId)
            ->where('recipient_type', $userType)
            ->where('type', CashTransaction::TYPE_REFUND)
            ->sum('amount');

        // Calculate received from distributions - 'transfer' and 'carryover' types count as business money
        // Salary distributions are personal money that left the business
        $receivedFromTransfers = CashTransaction::where('cash_period_id', $period->id)
            ->where('recipient_id', $userId)
            ->where('recipient_type', $userType)
            ->where('type', CashTransaction::TYPE_DISTRIBUTION)
            ->whereIn('distribution_type', [
                CashTransaction::DISTRIBUTION_TYPE_TRANSFER,
                CashTransaction::DISTRIBUTION_TYPE_CARRYOVER,
            ])
            ->sum('amount');

        $received = $receivedFromDeposits + $receivedFromCarryover + $receivedFromRefunds + $receivedFromTransfers;

        // Calculate sent amount (where user is sender)
        $sent = CashTransaction::where('cash_period_id', $period->id)
            ->where('sender_id', $userId)
            ->where('sender_type', $userType)
            ->whereIn('type', [
                CashTransaction::TYPE_DISTRIBUTION,
                CashTransaction::TYPE_REFUND,
                CashTransaction::TYPE_SELF_SALARY,
            ])
            ->sum('amount');

        return [
            'received' => (float) $received,
            'sent' => (float) $sent,
            'available' => (float) ($received - $sent),
        ];
    }

    /**
     * Check if user has already taken self-salary this period
     * Requirement 6.2: Block repeated self-salary for managers
     *
     * @param CashPeriod $period
     * @param User $user
     * @return bool
     */
    public function hasSelfSalaryThisPeriod(CashPeriod $period, User $user): bool
    {
        return CashTransaction::where('cash_period_id', $period->id)
            ->where('type', CashTransaction::TYPE_SELF_SALARY)
            ->where('sender_id', $user->id)
            ->where('sender_type', User::class)
            ->exists();
    }

    /**
     * Update transaction status
     * Requirement 8.1, 8.2: Status transitions
     *
     * @param CashTransaction $transaction
     * @param string $status
     * @return CashTransaction
     * @throws InvalidArgumentException
     */
    public function updateStatus(CashTransaction $transaction, string $status): CashTransaction
    {
        $allowedStatuses = [
            CashTransaction::STATUS_PENDING,
            CashTransaction::STATUS_IN_PROGRESS,
            CashTransaction::STATUS_COMPLETED,
            CashTransaction::STATUS_OVERDUE,
        ];

        if (!in_array($status, $allowedStatuses)) {
            throw new InvalidArgumentException('Invalid status.');
        }

        $oldStatus = $transaction->status;
        $transaction->update(['status' => $status]);

        // Log status change to audit (Requirement 13.1, 13.2)
        if ($oldStatus !== $status) {
            $this->auditService->logStatusChange($transaction, $oldStatus, $status);
        }

        // Notify when taken to work (status changed to in_progress)
        if ($oldStatus !== CashTransaction::STATUS_IN_PROGRESS && 
            $status === CashTransaction::STATUS_IN_PROGRESS) {
            $this->notificationService->notifyTakenToWork($transaction);
        }

        return $transaction->fresh();
    }

    /**
     * Get or create current period for a company
     *
     * @param int $companyId
     * @return CashPeriod
     */
    public function getOrCreateCurrentPeriod(int $companyId): CashPeriod
    {
        $year = (int) date('Y');
        $month = (int) date('m');

        $period = CashPeriod::where('created_by', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if (!$period) {
            // Create new period
            $period = CashPeriod::create([
                'created_by' => $companyId,
                'year' => $year,
                'month' => $month,
                'total_deposited' => 0,
                'is_frozen' => false,
            ]);

            // Carry over balances from previous period
            $this->carryOverBalances($companyId, $period);
        }

        return $period;
    }

    /**
     * Carry over balances from previous period to new period
     * Creates carryover transactions for all users with remaining balance
     *
     * @param int $companyId
     * @param CashPeriod $newPeriod
     * @return void
     */
    public function carryOverBalances(int $companyId, CashPeriod $newPeriod): void
    {
        // Get previous period
        $previousPeriod = $this->getPreviousPeriod($companyId, $newPeriod);
        
        if (!$previousPeriod) {
            return;
        }

        // Check if carryover already done for this period
        $carryoverExists = CashTransaction::where('cash_period_id', $newPeriod->id)
            ->where('type', CashTransaction::TYPE_CARRYOVER)
            ->exists();

        if ($carryoverExists) {
            return;
        }

        DB::transaction(function () use ($companyId, $previousPeriod, $newPeriod) {
            // Get all users who had transactions in previous period
            $userIds = CashTransaction::where('cash_period_id', $previousPeriod->id)
                ->where(function ($query) {
                    $query->where('recipient_type', User::class)
                          ->orWhere('sender_type', User::class);
                })
                ->selectRaw('DISTINCT CASE 
                    WHEN recipient_type = ? THEN recipient_id 
                    WHEN sender_type = ? THEN sender_id 
                    END as user_id', [User::class, User::class])
                ->pluck('user_id')
                ->filter()
                ->unique();

            $totalCarryover = 0;

            foreach ($userIds as $userId) {
                $user = User::find($userId);
                if (!$user) {
                    continue;
                }

                // Get balance from previous period
                $balance = $this->getBalance($previousPeriod, $user);
                $available = $balance['available'];

                if ($available <= 0) {
                    continue;
                }

                // Determine user role for transaction type
                $userRole = $this->hierarchyService->getUserCashboxRole($user);
                $isBoss = $userRole === CashHierarchyService::ROLE_BOSS;

                // Create carryover transaction in NEW period
                if ($isBoss) {
                    // For Boss: create as deposit (carryover type)
                    CashTransaction::create([
                        'cash_period_id' => $newPeriod->id,
                        'created_by' => $companyId,
                        'type' => CashTransaction::TYPE_CARRYOVER,
                        'distribution_type' => null,
                        'sender_id' => null,
                        'sender_type' => null,
                        'recipient_id' => $user->id,
                        'recipient_type' => User::class,
                        'amount' => $available,
                        'task' => __('Balance carryover from previous month'),
                        'comment' => __('Automatic system transfer from') . ' ' . $previousPeriod->name,
                        'status' => CashTransaction::STATUS_COMPLETED,
                    ]);

                    $totalCarryover += $available;
                } else {
                    // For Manager/Curator: create as distribution with carryover type
                    CashTransaction::create([
                        'cash_period_id' => $newPeriod->id,
                        'created_by' => $companyId,
                        'type' => CashTransaction::TYPE_DISTRIBUTION,
                        'distribution_type' => CashTransaction::DISTRIBUTION_TYPE_CARRYOVER,
                        'sender_id' => null, // System transfer
                        'sender_type' => null,
                        'recipient_id' => $user->id,
                        'recipient_type' => User::class,
                        'amount' => $available,
                        'task' => __('Balance carryover from previous month'),
                        'comment' => __('Automatic system transfer from') . ' ' . $previousPeriod->name,
                        'status' => CashTransaction::STATUS_COMPLETED,
                    ]);
                }

                // Create withdrawal transaction in OLD period to zero out balance
                CashTransaction::create([
                    'cash_period_id' => $previousPeriod->id,
                    'created_by' => $companyId,
                    'type' => CashTransaction::TYPE_DISTRIBUTION,
                    'distribution_type' => CashTransaction::DISTRIBUTION_TYPE_CARRYOVER,
                    'sender_id' => $user->id,
                    'sender_type' => User::class,
                    'recipient_id' => null, // Transfer to next period
                    'recipient_type' => null,
                    'amount' => $available,
                    'task' => __('Balance transfer to next month'),
                    'comment' => __('Automatic system transfer to') . ' ' . $newPeriod->name,
                    'status' => CashTransaction::STATUS_COMPLETED,
                ]);
            }

            // Update new period total_deposited with carryover amount
            if ($totalCarryover > 0) {
                $newPeriod->increment('total_deposited', $totalCarryover);
            }
        });
    }

    /**
     * Get previous period for a company
     *
     * @param int $companyId
     * @param CashPeriod $currentPeriod
     * @return CashPeriod|null
     */
    public function getPreviousPeriod(int $companyId, CashPeriod $currentPeriod): ?CashPeriod
    {
        $prevYear = $currentPeriod->year;
        $prevMonth = $currentPeriod->month - 1;

        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }

        return CashPeriod::where('created_by', $companyId)
            ->where('year', $prevYear)
            ->where('month', $prevMonth)
            ->first();
    }
}
