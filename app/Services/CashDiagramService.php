<?php

namespace App\Services;

use App\Models\CashPeriod;
use App\Models\CashTransaction;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Collection;

class CashDiagramService
{
    protected CashHierarchyService $hierarchyService;
    
    /**
     * Minimum number of worker salary transactions to group into a list
     * Change this value to adjust when grouping starts (e.g., 5, 10, 50)
     */
    const SALARY_LIST_MIN_COUNT = 5;
    
    /**
     * Maximum number of worker salary transactions per list
     * Change this value to adjust list size (e.g., 20, 50, 100)
     */
    const SALARY_LIST_MAX_COUNT = 20;

    public function __construct(CashHierarchyService $hierarchyService)
    {
        $this->hierarchyService = $hierarchyService;
    }

    /**
     * Build tree structure for diagram visualization
     * Requirement 9.1: Display diagram as tree with participant nodes
     * Requirement 9.2: Show icon, name, amount, task, comment, status on each node
     * Requirement 9.3: Connect nodes with lines showing money flow direction
     *
     * @param CashPeriod $period
     * @return array
     */
    public function buildTree(CashPeriod $period): array
    {
        // Get all transactions for this period
        $transactions = CashTransaction::where('cash_period_id', $period->id)
            ->orderBy('created_at', 'asc')
            ->get();

        // Get all deposit transactions
        $deposits = $transactions->where('type', CashTransaction::TYPE_DEPOSIT)->values();
        
        $tree = [];
        
        // If no deposits, return empty tree
        if ($deposits->isEmpty()) {
            return [
                'period' => [
                    'id' => $period->id,
                    'name' => $period->name,
                    'year' => $period->year,
                    'month' => $period->month,
                    'total_deposited' => (float) $period->total_deposited,
                    'is_frozen' => $period->is_frozen,
                ],
                'nodes' => [],
            ];
        }
        
        // Track which transactions have been used to avoid duplicates
        $usedTransactionIds = collect();
        
        // Global counter for salary lists in this period
        $salaryListCounter = 1;
        
        // Combine all deposits into one node
        $firstDeposit = $deposits->first();
        $totalDeposited = $deposits->sum('amount');
        
        // Mark all deposits as used
        foreach ($deposits as $deposit) {
            $usedTransactionIds->push($deposit->id);
        }
        
        // Get all non-deposit transactions
        $allDistributions = $transactions->filter(function ($t) {
            return $t->type !== CashTransaction::TYPE_DEPOSIT;
        });
        
        // Build combined deposit node
        $node = $this->formatNode($firstDeposit, (float) $totalDeposited);
        $node['amount'] = (float) $totalDeposited;
        $node['original_amount'] = (float) $totalDeposited;
        
        // Add deposit history if multiple deposits
        if ($deposits->count() > 1) {
            $depositHistory = [];
            foreach ($deposits->take(5) as $dep) {
                $depositHistory[] = [
                    'amount' => (float) $dep->amount,
                    'date' => $dep->created_at->toIso8601String(),
                ];
            }
            $node['deposit_history'] = $depositHistory;
            $node['deposit_count'] = $deposits->count();
            $node['has_multiple_deposits'] = true;
        }
        
        // Track carryover by recipient for child transactions
        $recipientCarryovers = [];
        
        // Build children from all distributions
        $childNodes = [];
        $workerSalaryNodes = [];
        
        // Get direct distributions from the deposit recipient (boss)
        $directDistributions = $allDistributions->filter(function ($t) use ($firstDeposit, $usedTransactionIds) {
            if ($usedTransactionIds->contains($t->id)) {
                return false;
            }
            if ($t->type === CashTransaction::TYPE_REFUND) {
                return false;
            }
            return $t->sender_id === $firstDeposit->recipient_id &&
                   $t->sender_type === $firstDeposit->recipient_type;
        });
        
        foreach ($directDistributions as $child) {
            $usedTransactionIds->push($child->id);
            
            $childNode = $this->buildNodeWithChildren($child, $allDistributions, $usedTransactionIds, $recipientCarryovers, $salaryListCounter);
            
            // Check if this is a salary to a worker - collect for grouping
            if ($child->distribution_type === CashTransaction::DISTRIBUTION_TYPE_SALARY && 
                $child->recipient_type === Worker::class) {
                $workerSalaryNodes[] = $childNode;
            } else {
                $childNodes[] = $childNode;
            }
        }
        
        // Group worker salary nodes into lists if there are enough
        $childNodes = array_merge($childNodes, $this->groupWorkerSalaries($workerSalaryNodes, $salaryListCounter));
        
        // Add self_salary transactions
        $selfSalaries = $allDistributions->filter(function ($t) use ($firstDeposit, $usedTransactionIds) {
            if ($usedTransactionIds->contains($t->id)) {
                return false;
            }
            return $t->type === CashTransaction::TYPE_SELF_SALARY &&
                   $t->sender_id === $firstDeposit->recipient_id &&
                   $t->sender_type === $firstDeposit->recipient_type;
        });

        foreach ($selfSalaries as $selfSalary) {
            $usedTransactionIds->push($selfSalary->id);
            $childNodes[] = $this->formatNode($selfSalary, (float) $selfSalary->amount);
        }
        
        // Add refunds
        $refundNodes = [];
        $refundsReceived = 0;
        $refunds = $allDistributions->filter(function ($t) use ($firstDeposit, $usedTransactionIds) {
            if ($usedTransactionIds->contains($t->id)) {
                return false;
            }
            return $t->type === CashTransaction::TYPE_REFUND &&
                   $t->recipient_id === $firstDeposit->recipient_id &&
                   $t->recipient_type === $firstDeposit->recipient_type;
        });

        foreach ($refunds as $refund) {
            $usedTransactionIds->push($refund->id);
            $refundsReceived += $refund->amount;
            $refundNodes[] = $this->formatNode($refund);
        }
        
        $node['children'] = $childNodes;
        $node['refunds'] = $refundNodes;
        
        // Calculate remaining balance
        $spentAmount = $allDistributions->whereIn('type', [
            CashTransaction::TYPE_DISTRIBUTION,
            CashTransaction::TYPE_SELF_SALARY,
        ])->where('sender_id', $firstDeposit->recipient_id)
          ->where('sender_type', $firstDeposit->recipient_type)
          ->sum('amount');
        
        $node['current_balance'] = max(0, (float) ($totalDeposited + $refundsReceived - $spentAmount));
        
        $tree[] = $node;

        return [
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'year' => $period->year,
                'month' => $period->month,
                'total_deposited' => (float) $period->total_deposited,
                'is_frozen' => $period->is_frozen,
            ],
            'nodes' => $tree,
        ];
    }

    /**
     * Build a node with its children recursively
     *
     * @param CashTransaction $transaction
     * @param Collection $allTransactions
     * @param Collection $usedTransactionIds - track used transactions to avoid duplicates
     * @param array $recipientCarryovers - track carryover amounts by recipient
     * @param int $salaryListCounter - global counter for salary lists in this period
     * @return array
     */
    protected function buildNodeWithChildren(CashTransaction $transaction, Collection $allTransactions, Collection &$usedTransactionIds, array &$recipientCarryovers = [], int &$salaryListCounter = 1): array
    {
        // Get recipient key for carryover tracking
        $recipientKey = $transaction->recipient_type . '_' . $transaction->recipient_id;
        
        // Salary distributions are leaf nodes - no children should be built from them
        // Money paid as salary leaves the business, it's not available for further distribution
        // For salary, show full amount (it's the final destination, money is given to recipient)
        if ($transaction->distribution_type === CashTransaction::DISTRIBUTION_TYPE_SALARY) {
            $node = $this->formatNode($transaction, (float) $transaction->amount);
            // Reset carryover for this recipient after salary (money is spent from sender's perspective)
            $recipientCarryovers[$recipientKey] = 0;
            return $node;
        }

        // Find the time boundary for this transaction
        // Children belong to this transaction if created AFTER this transaction
        // and BEFORE the next transaction to the same recipient
        $transactionTime = $transaction->created_at;
        
        // Find next TRANSFER transaction to the same recipient (to determine time boundary)
        // Only transfer transactions can receive carryover, salary is a leaf node
        $nextToSameRecipient = $allTransactions->filter(function ($t) use ($transaction) {
            return $t->type === CashTransaction::TYPE_DISTRIBUTION &&
                   $t->distribution_type === CashTransaction::DISTRIBUTION_TYPE_TRANSFER &&
                   $t->recipient_id === $transaction->recipient_id &&
                   $t->recipient_type === $transaction->recipient_type &&
                   $t->created_at > $transaction->created_at;
        })->sortBy('created_at')->first();
        
        $nextTransactionTime = $nextToSameRecipient ? $nextToSameRecipient->created_at : null;

        // Find child transactions (where this transaction's recipient is the sender)
        // Only include transactions within the time window
        $children = $allTransactions->filter(function ($t) use ($transaction, $usedTransactionIds, $transactionTime, $nextTransactionTime) {
            // Skip already used transactions
            if ($usedTransactionIds->contains($t->id)) {
                return false;
            }
            
            // Skip deposits (they are root nodes)
            if ($t->type === CashTransaction::TYPE_DEPOSIT) {
                return false;
            }
            
            // Skip refunds (they go back up the tree)
            if ($t->type === CashTransaction::TYPE_REFUND) {
                return false;
            }
            
            // Skip self - prevent infinite loop (self_salary has same sender and recipient)
            if ($t->id === $transaction->id) {
                return false;
            }
            
            // Skip self_salary transactions as children - they are leaf nodes
            if ($t->type === CashTransaction::TYPE_SELF_SALARY) {
                return false;
            }
            
            // Match sender to recipient of parent transaction
            if ($t->sender_id !== $transaction->recipient_id ||
                $t->sender_type !== $transaction->recipient_type) {
                return false;
            }
            
            // Must be after this transaction
            if ($t->created_at < $transactionTime) {
                return false;
            }
            
            // If there's a next transaction to same recipient, must be before it
            if ($nextTransactionTime && $t->created_at >= $nextTransactionTime) {
                return false;
            }
            
            return true;
        });

        // Calculate how much this recipient spent from this transaction
        $spentFromThis = 0;
        $childNodes = [];
        $workerSalaryNodes = []; // Collect worker salary nodes for potential grouping
        
        foreach ($children as $child) {
            $usedTransactionIds->push($child->id);
            $spentFromThis += $child->amount;
            
            $childNode = $this->buildNodeWithChildren($child, $allTransactions, $usedTransactionIds, $recipientCarryovers, $salaryListCounter);
            
            // Check if this is a salary to a worker - collect for grouping
            if ($child->distribution_type === CashTransaction::DISTRIBUTION_TYPE_SALARY && 
                $child->recipient_type === Worker::class) {
                $workerSalaryNodes[] = $childNode;
            } else {
                $childNodes[] = $childNode;
            }
        }
        
        // Group worker salary nodes into lists if there are enough
        $childNodes = array_merge($childNodes, $this->groupWorkerSalaries($workerSalaryNodes, $salaryListCounter));
        
        // Add self_salary transactions as special children (leaf nodes)
        // Only include transactions within the time window
        $selfSalaries = $allTransactions->filter(function ($t) use ($transaction, $usedTransactionIds, $transactionTime, $nextTransactionTime) {
            if ($usedTransactionIds->contains($t->id)) {
                return false;
            }
            
            if ($t->type !== CashTransaction::TYPE_SELF_SALARY ||
                $t->sender_id !== $transaction->recipient_id ||
                $t->sender_type !== $transaction->recipient_type ||
                $t->id === $transaction->id) {
                return false;
            }
            
            // Must be after this transaction
            if ($t->created_at < $transactionTime) {
                return false;
            }
            
            // If there's a next transaction to same recipient, must be before it
            if ($nextTransactionTime && $t->created_at >= $nextTransactionTime) {
                return false;
            }
            
            return true;
        });

        foreach ($selfSalaries as $selfSalary) {
            $usedTransactionIds->push($selfSalary->id);
            $spentFromThis += $selfSalary->amount;
            // Self salary - show full amount (it's the final destination, money is taken as salary)
            $childNodes[] = $this->formatNode($selfSalary, (float) $selfSalary->amount);
        }

        // Add refunds as special children
        $refundsReceived = 0;
        $refundNodes = [];
        $refunds = $allTransactions->filter(function ($t) use ($transaction, $usedTransactionIds) {
            if ($usedTransactionIds->contains($t->id)) {
                return false;
            }
            
            return $t->type === CashTransaction::TYPE_REFUND &&
                   $t->parent_transaction_id === $transaction->id;
        });

        foreach ($refunds as $refund) {
            $usedTransactionIds->push($refund->id);
            $refundsReceived += $refund->amount;
            $refundNodes[] = $this->formatNode($refund);
        }
        
        // Calculate remaining balance for this recipient
        // Available = received amount + previous carryover + refunds - spent
        $previousCarryover = $recipientCarryovers[$recipientKey] ?? 0;
        
        // Total amount available = transaction amount + carryover from previous
        $totalAvailable = $transaction->amount + $previousCarryover;
        $remaining = $totalAvailable + $refundsReceived - $spentFromThis;
        
        // Create node - we'll set current_balance after determining carryover
        $node = $this->formatNode($transaction, max(0, $remaining));
        $node['children'] = $childNodes;
        $node['refunds'] = $refundNodes;
        
        // If there's carryover received, update the displayed amount to include it
        if ($previousCarryover > 0) {
            $node['carryover_received'] = (float) $previousCarryover;
            // Update amount to show total (original + carryover)
            $node['amount'] = (float) $totalAvailable;
        }
        
        // Store carryover for next transaction to this recipient
        // Only set carryover_to_next if there IS a next transaction to this recipient
        if ($nextToSameRecipient && $remaining > 0) {
            $recipientCarryovers[$recipientKey] = $remaining;
            $node['carryover_to_next'] = (float) $remaining;
            $node['current_balance'] = 0; // Money moved to next transaction
        } else {
            $node['current_balance'] = max(0, $remaining);
            $recipientCarryovers[$recipientKey] = 0;
        }

        return $node;
    }

    /**
     * Format a transaction as a node for the diagram
     * Requirement 9.2: Show icon, name, amount, task, comment, status
     *
     * @param CashTransaction $transaction
     * @param float|null $currentBalance - актуальный остаток (если null, равен amount)
     * @return array
     */
    public function formatNode(CashTransaction $transaction, ?float $currentBalance = null): array
    {
        $sender = $this->formatParticipant(
            $transaction->sender_id,
            $transaction->sender_type
        );
        
        $recipient = $this->formatParticipant(
            $transaction->recipient_id,
            $transaction->recipient_type
        );

        return [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'distribution_type' => $transaction->distribution_type,
            'distribution_type_label' => $this->getDistributionTypeLabel($transaction->distribution_type),
            'sender' => $sender,
            'recipient' => $recipient,
            'amount' => (float) $transaction->amount,
            'original_amount' => (float) $transaction->amount,
            'current_balance' => $currentBalance ?? (float) $transaction->amount,
            'task' => $transaction->task,
            'comment' => $transaction->comment,
            'status' => $transaction->status,
            'status_label' => $this->getStatusLabel($transaction->status),
            'created_at' => $transaction->created_at->toIso8601String(),
            'children' => [],
            'refunds' => [],
        ];
    }

    /**
     * Format a participant (sender or recipient) for display
     *
     * @param int|null $id
     * @param string|null $type
     * @return array|null
     */
    protected function formatParticipant(?int $id, ?string $type): ?array
    {
        if (!$id || !$type) {
            return null;
        }

        if ($type === User::class) {
            $user = User::find($id);
            if (!$user) {
                return null;
            }

            $role = $this->hierarchyService->getUserCashboxRole($user);

            return [
                'id' => $user->id,
                'type' => 'user',
                'name' => $user->name,
                'role' => $role,
                'role_label' => $this->getRoleLabel($role),
                'icon' => $this->getRoleIcon($role),
                'avatar' => $user->profile,
            ];
        }

        if ($type === Worker::class) {
            $worker = Worker::find($id);
            if (!$worker) {
                return null;
            }

            return [
                'id' => $worker->id,
                'type' => 'worker',
                'name' => $worker->first_name . ' ' . $worker->last_name,
                'role' => CashHierarchyService::ROLE_WORKER,
                'role_label' => $this->getRoleLabel(CashHierarchyService::ROLE_WORKER),
                'icon' => $this->getRoleIcon(CashHierarchyService::ROLE_WORKER),
                'avatar' => null,
            ];
        }

        return null;
    }

    /**
     * Get human-readable label for role
     *
     * @param string|null $role
     * @return string
     */
    protected function getRoleLabel(?string $role): string
    {
        return match ($role) {
            CashHierarchyService::ROLE_BOSS => __('Director'),
            CashHierarchyService::ROLE_MANAGER => __('Manager'),
            CashHierarchyService::ROLE_CURATOR => __('Curator'),
            CashHierarchyService::ROLE_WORKER => __('Worker'),
            default => __('Unknown'),
        };
    }

    /**
     * Get icon class for role
     *
     * @param string|null $role
     * @return string
     */
    protected function getRoleIcon(?string $role): string
    {
        return match ($role) {
            CashHierarchyService::ROLE_BOSS => 'ti ti-crown',
            CashHierarchyService::ROLE_MANAGER => 'ti ti-user-star',
            CashHierarchyService::ROLE_CURATOR => 'ti ti-user-check',
            CashHierarchyService::ROLE_WORKER => 'ti ti-user',
            default => 'ti ti-user',
        };
    }

    /**
     * Get human-readable label for status
     *
     * @param string $status
     * @return string
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            CashTransaction::STATUS_PENDING => __('Pending'),
            CashTransaction::STATUS_IN_PROGRESS => __('In Progress'),
            CashTransaction::STATUS_COMPLETED => __('Completed'),
            CashTransaction::STATUS_OVERDUE => __('Overdue'),
            default => __('Unknown'),
        };
    }

    /**
     * Get human-readable label for distribution type
     *
     * @param string|null $distributionType
     * @return string|null
     */
    protected function getDistributionTypeLabel(?string $distributionType): ?string
    {
        return match ($distributionType) {
            CashTransaction::DISTRIBUTION_TYPE_SALARY => __('Salary'),
            CashTransaction::DISTRIBUTION_TYPE_TRANSFER => __('Transfer'),
            default => null,
        };
    }

    /**
     * Get flat list of all transactions for a period (alternative view)
     *
     * @param CashPeriod $period
     * @return Collection
     */
    public function getTransactionsList(CashPeriod $period): Collection
    {
        return CashTransaction::where('cash_period_id', $period->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($transaction) {
                return $this->formatNode($transaction);
            });
    }

    /**
     * Get summary statistics for a period
     *
     * @param CashPeriod $period
     * @return array
     */
    public function getPeriodSummary(CashPeriod $period): array
    {
        $transactions = CashTransaction::where('cash_period_id', $period->id)->get();

        $totalDeposited = $transactions
            ->where('type', CashTransaction::TYPE_DEPOSIT)
            ->sum('amount');

        $totalDistributed = $transactions
            ->where('type', CashTransaction::TYPE_DISTRIBUTION)
            ->sum('amount');

        $totalRefunded = $transactions
            ->where('type', CashTransaction::TYPE_REFUND)
            ->sum('amount');

        $totalSelfSalary = $transactions
            ->where('type', CashTransaction::TYPE_SELF_SALARY)
            ->sum('amount');

        return [
            'total_deposited' => (float) $totalDeposited,
            'total_distributed' => (float) $totalDistributed,
            'total_refunded' => (float) $totalRefunded,
            'total_self_salary' => (float) $totalSelfSalary,
            'transactions_count' => $transactions->count(),
            'pending_count' => $transactions->where('status', CashTransaction::STATUS_PENDING)->count(),
            'in_progress_count' => $transactions->where('status', CashTransaction::STATUS_IN_PROGRESS)->count(),
            'completed_count' => $transactions->where('status', CashTransaction::STATUS_COMPLETED)->count(),
        ];
    }
    
    /**
     * Group worker salary nodes into lists if there are enough
     * 
     * @param array $workerSalaryNodes
     * @param int &$salaryListCounter - global counter for unique list numbers in period
     * @return array
     */
    protected function groupWorkerSalaries(array $workerSalaryNodes, int &$salaryListCounter): array
    {
        $count = count($workerSalaryNodes);
        
        // If less than minimum, return as individual nodes
        if ($count < self::SALARY_LIST_MIN_COUNT) {
            return $workerSalaryNodes;
        }
        
        $result = [];
        
        // Split into chunks of max size
        $chunks = array_chunk($workerSalaryNodes, self::SALARY_LIST_MAX_COUNT);
        
        foreach ($chunks as $chunk) {
            // Calculate total amount for this list
            $totalAmount = 0;
            $recipients = [];
            $transactionIds = [];
            
            foreach ($chunk as $node) {
                $totalAmount += $node['original_amount'] ?? $node['amount'];
                $transactionIds[] = $node['id'];
                $recipients[] = [
                    'name' => $node['recipient']['name'] ?? __('Unknown'),
                    'amount' => $node['original_amount'] ?? $node['amount'],
                    'id' => $node['recipient']['id'] ?? null,
                ];
            }
            
            // Use global counter for unique list number
            $listNumber = $salaryListCounter;
            
            // Create a grouped list node
            $result[] = [
                'id' => 'salary_list_' . $listNumber,
                'type' => 'salary_list',
                'distribution_type' => 'salary',
                'distribution_type_label' => __('Salary List'),
                'sender' => null,
                'recipient' => [
                    'id' => null,
                    'type' => 'list',
                    'name' => __('Salary List') . ' №' . $listNumber,
                    'role' => 'worker',
                    'role_label' => __('Workers'),
                    'icon' => 'ti ti-users',
                ],
                'amount' => (float) $totalAmount,
                'original_amount' => (float) $totalAmount,
                'current_balance' => (float) $totalAmount,
                'task' => count($chunk) . ' ' . __('workers'),
                'comment' => null,
                'status' => 'completed',
                'status_label' => __('Completed'),
                'created_at' => $chunk[0]['created_at'] ?? now()->toIso8601String(),
                'children' => [],
                'refunds' => [],
                'is_salary_list' => true,
                'salary_list_number' => $listNumber,
                'salary_recipients' => $recipients,
                'transaction_ids' => $transactionIds,
            ];
            
            // Increment global counter for next list
            $salaryListCounter++;
        }
        
        return $result;
    }
    
    /**
     * Pluralize Russian word
     */
    protected function pluralize(int $count, string $one, string $few, string $many): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;
        
        if ($mod100 >= 11 && $mod100 <= 19) {
            return $many;
        }
        
        if ($mod10 === 1) {
            return $one;
        }
        
        if ($mod10 >= 2 && $mod10 <= 4) {
            return $few;
        }
        
        return $many;
    }

    /**
     * Build tree structure for manager view
     * Shows: incoming salary/transfers, outgoing distributions to curators with their branches
     *
     * @param CashPeriod $period
     * @param User $manager
     * @return array
     */
    public function buildTreeForManager(CashPeriod $period, User $manager): array
    {
        $transactions = CashTransaction::where('cash_period_id', $period->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $nodes = [];
        $salaryListCounter = 1;
        $usedTransactionIds = collect();
        $carryoverFromPrevious = 0;

        // Get incoming transactions (where manager is recipient)
        $incoming = $this->getIncomingTransactions($transactions, $manager)->values();
        
        foreach ($incoming as $index => $transaction) {
            $usedTransactionIds->push($transaction->id);
            
            $node = $this->formatNode($transaction, (float) $transaction->amount);
            $node['is_incoming'] = true;
            
            // For transfer transactions, build children (outgoing from this money)
            // Only include transactions within this transfer's time window
            if ($transaction->distribution_type === CashTransaction::DISTRIBUTION_TYPE_TRANSFER) {
                // Get time boundaries for this incoming transaction
                $transactionTime = $transaction->created_at;
                $nextIncoming = $incoming->get($index + 1);
                $nextIncomingTime = $nextIncoming ? $nextIncoming->created_at : null;
                
                $children = $this->buildManagerOutgoingBranchTimeBounded(
                    $transactions, 
                    $manager, 
                    $transactionTime,
                    $nextIncomingTime,
                    $salaryListCounter,
                    $usedTransactionIds
                );
                $node['children'] = $children;
                
                // Calculate spent amount
                $spent = collect($children)->sum('original_amount');
                
                // Total available = transaction amount + carryover from previous
                $totalAvailable = $transaction->amount + $carryoverFromPrevious;
                $remaining = $totalAvailable - $spent;
                
                // Add carryover info if received from previous
                if ($carryoverFromPrevious > 0) {
                    $node['carryover_received'] = (float) $carryoverFromPrevious;
                    $node['amount'] = (float) $totalAvailable;
                }
                
                // If there's a next incoming and remaining balance, carryover goes there
                if ($nextIncoming && $remaining > 0) {
                    $node['carryover_to_next'] = (float) $remaining;
                    $node['current_balance'] = 0;
                    $carryoverFromPrevious = $remaining;
                } else {
                    $node['current_balance'] = max(0, (float) $remaining);
                    $carryoverFromPrevious = 0;
                }
            }
            
            $nodes[] = $node;
        }

        // If no incoming transfers but has outgoing, show outgoing as root
        if (empty($incoming->where('distribution_type', CashTransaction::DISTRIBUTION_TYPE_TRANSFER)->count())) {
            $outgoing = $this->buildManagerOutgoingBranch($transactions, $manager, $salaryListCounter);
            foreach ($outgoing as $outNode) {
                $outNode['is_outgoing_root'] = true;
                $nodes[] = $outNode;
            }
        }

        return [
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'year' => $period->year,
                'month' => $period->month,
                'total_deposited' => (float) $period->total_deposited,
                'is_frozen' => $period->is_frozen,
            ],
            'nodes' => $nodes,
            'view_mode' => 'manager',
        ];
    }

    /**
     * Build tree structure for curator view
     * Shows: incoming salary/transfers, outgoing worker salaries
     *
     * @param CashPeriod $period
     * @param User $curator
     * @return array
     */
    public function buildTreeForCurator(CashPeriod $period, User $curator): array
    {
        $transactions = CashTransaction::where('cash_period_id', $period->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $nodes = [];
        $salaryListCounter = 1;
        $usedTransactionIds = collect();
        $carryoverFromPrevious = 0;

        // Get incoming transactions (where curator is recipient)
        $incoming = $this->getIncomingTransactions($transactions, $curator)->values();
        
        foreach ($incoming as $index => $transaction) {
            $usedTransactionIds->push($transaction->id);
            
            $node = $this->formatNode($transaction, (float) $transaction->amount);
            $node['is_incoming'] = true;
            
            // For transfer transactions, build children (worker salaries)
            // Only include transactions within this transfer's time window
            if ($transaction->distribution_type === CashTransaction::DISTRIBUTION_TYPE_TRANSFER) {
                // Get time boundaries for this incoming transaction
                $transactionTime = $transaction->created_at;
                $nextIncoming = $incoming->get($index + 1);
                $nextIncomingTime = $nextIncoming ? $nextIncoming->created_at : null;
                
                $children = $this->buildCuratorOutgoingBranchTimeBounded(
                    $transactions, 
                    $curator, 
                    $transactionTime,
                    $nextIncomingTime,
                    $salaryListCounter,
                    $usedTransactionIds
                );
                $node['children'] = $children;
                
                // Calculate spent amount
                $spent = collect($children)->sum(function ($child) {
                    return $child['original_amount'] ?? $child['amount'];
                });
                
                // Total available = transaction amount + carryover from previous
                $totalAvailable = $transaction->amount + $carryoverFromPrevious;
                $remaining = $totalAvailable - $spent;
                
                // Add carryover info if received from previous
                if ($carryoverFromPrevious > 0) {
                    $node['carryover_received'] = (float) $carryoverFromPrevious;
                    $node['amount'] = (float) $totalAvailable;
                }
                
                // If there's a next incoming and remaining balance, carryover goes there
                if ($nextIncoming && $remaining > 0) {
                    $node['carryover_to_next'] = (float) $remaining;
                    $node['current_balance'] = 0;
                    $carryoverFromPrevious = $remaining;
                } else {
                    $node['current_balance'] = max(0, (float) $remaining);
                    $carryoverFromPrevious = 0;
                }
            }
            
            $nodes[] = $node;
        }

        // If no incoming transfers but has outgoing, show outgoing as root
        if (empty($incoming->where('distribution_type', CashTransaction::DISTRIBUTION_TYPE_TRANSFER)->count())) {
            $outgoing = $this->buildCuratorOutgoingBranch($transactions, $curator, $salaryListCounter);
            foreach ($outgoing as $outNode) {
                $outNode['is_outgoing_root'] = true;
                $nodes[] = $outNode;
            }
        }

        return [
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'year' => $period->year,
                'month' => $period->month,
                'total_deposited' => (float) $period->total_deposited,
                'is_frozen' => $period->is_frozen,
            ],
            'nodes' => $nodes,
            'view_mode' => 'curator',
        ];
    }

    /**
     * Get incoming transactions for a user (salary and transfers where user is recipient)
     */
    protected function getIncomingTransactions(Collection $transactions, User $user): Collection
    {
        return $transactions->filter(function ($t) use ($user) {
            // Must be distribution type
            if ($t->type !== CashTransaction::TYPE_DISTRIBUTION) {
                return false;
            }
            
            // User must be recipient
            if ($t->recipient_id !== $user->id || $t->recipient_type !== User::class) {
                return false;
            }
            
            // Only salary or transfer
            return in_array($t->distribution_type, [
                CashTransaction::DISTRIBUTION_TYPE_SALARY,
                CashTransaction::DISTRIBUTION_TYPE_TRANSFER,
            ]);
        })->values();
    }

    /**
     * Build outgoing branch for manager (distributions to curators and their worker salaries)
     */
    protected function buildManagerOutgoingBranch(Collection $transactions, User $manager, int &$salaryListCounter): array
    {
        $children = [];
        $usedIds = collect();

        // Get distributions where manager is sender
        $outgoing = $transactions->filter(function ($t) use ($manager) {
            if ($t->type !== CashTransaction::TYPE_DISTRIBUTION) {
                return false;
            }
            return $t->sender_id === $manager->id && $t->sender_type === User::class;
        });

        foreach ($outgoing as $transaction) {
            if ($usedIds->contains($transaction->id)) {
                continue;
            }
            $usedIds->push($transaction->id);

            $node = $this->formatNode($transaction, (float) $transaction->amount);
            
            // If recipient is a user (curator), build their branch
            if ($transaction->recipient_type === User::class) {
                $curatorId = $transaction->recipient_id;
                $curatorBranch = $this->buildCuratorBranchForManager($transactions, $curatorId, $salaryListCounter, $usedIds);
                $node['children'] = $curatorBranch;
                
                // Calculate curator's remaining balance
                $spent = collect($curatorBranch)->sum(function ($child) {
                    return $child['original_amount'] ?? $child['amount'];
                });
                $node['current_balance'] = max(0, (float) $transaction->amount - $spent);
            }
            
            $children[] = $node;
        }

        return $children;
    }

    /**
     * Build outgoing branch for manager with time boundaries
     * Only includes transactions created after startTime and before endTime
     */
    protected function buildManagerOutgoingBranchTimeBounded(
        Collection $transactions, 
        User $manager, 
        $startTime,
        $endTime,
        int &$salaryListCounter,
        Collection &$usedTransactionIds
    ): array {
        $children = [];

        // Get distributions where manager is sender within time window
        $outgoing = $transactions->filter(function ($t) use ($manager, $startTime, $endTime, $usedTransactionIds) {
            // Skip already used transactions
            if ($usedTransactionIds->contains($t->id)) {
                return false;
            }
            
            if ($t->type !== CashTransaction::TYPE_DISTRIBUTION) {
                return false;
            }
            if ($t->sender_id !== $manager->id || $t->sender_type !== User::class) {
                return false;
            }
            
            // Must be after start time
            if ($t->created_at < $startTime) {
                return false;
            }
            
            // If there's an end time, must be before it
            if ($endTime && $t->created_at >= $endTime) {
                return false;
            }
            
            return true;
        });

        foreach ($outgoing as $transaction) {
            $usedTransactionIds->push($transaction->id);

            $node = $this->formatNode($transaction, (float) $transaction->amount);
            
            // If recipient is a user (curator), build their branch with time bounds
            if ($transaction->recipient_type === User::class) {
                $curatorId = $transaction->recipient_id;
                $curatorBranch = $this->buildCuratorBranchForManagerTimeBounded(
                    $transactions, 
                    $curatorId, 
                    $startTime,
                    $endTime,
                    $salaryListCounter, 
                    $usedTransactionIds
                );
                $node['children'] = $curatorBranch;
                
                // Calculate curator's remaining balance
                $spent = collect($curatorBranch)->sum(function ($child) {
                    return $child['original_amount'] ?? $child['amount'];
                });
                $node['current_balance'] = max(0, (float) $transaction->amount - $spent);
            }
            
            $children[] = $node;
        }

        return $children;
    }

    /**
     * Build curator's branch as seen by manager (worker salaries from this curator)
     */
    protected function buildCuratorBranchForManager(Collection $transactions, int $curatorId, int &$salaryListCounter, Collection &$usedIds): array
    {
        $workerSalaryNodes = [];

        // Get worker salary distributions from this curator
        $curatorDistributions = $transactions->filter(function ($t) use ($curatorId) {
            if ($t->type !== CashTransaction::TYPE_DISTRIBUTION) {
                return false;
            }
            if ($t->sender_id !== $curatorId || $t->sender_type !== User::class) {
                return false;
            }
            // Only worker salaries
            return $t->recipient_type === Worker::class && 
                   $t->distribution_type === CashTransaction::DISTRIBUTION_TYPE_SALARY;
        });

        foreach ($curatorDistributions as $transaction) {
            if ($usedIds->contains($transaction->id)) {
                continue;
            }
            $usedIds->push($transaction->id);
            $workerSalaryNodes[] = $this->formatNode($transaction, (float) $transaction->amount);
        }

        // Group into salary lists if enough
        return $this->groupWorkerSalaries($workerSalaryNodes, $salaryListCounter);
    }

    /**
     * Build curator's branch as seen by manager with time boundaries
     */
    protected function buildCuratorBranchForManagerTimeBounded(
        Collection $transactions, 
        int $curatorId, 
        $startTime,
        $endTime,
        int &$salaryListCounter, 
        Collection &$usedIds
    ): array {
        $workerSalaryNodes = [];

        // Get worker salary distributions from this curator within time window
        $curatorDistributions = $transactions->filter(function ($t) use ($curatorId, $startTime, $endTime, $usedIds) {
            // Skip already used transactions
            if ($usedIds->contains($t->id)) {
                return false;
            }
            
            if ($t->type !== CashTransaction::TYPE_DISTRIBUTION) {
                return false;
            }
            if ($t->sender_id !== $curatorId || $t->sender_type !== User::class) {
                return false;
            }
            
            // Must be after start time
            if ($t->created_at < $startTime) {
                return false;
            }
            
            // If there's an end time, must be before it
            if ($endTime && $t->created_at >= $endTime) {
                return false;
            }
            
            // Only worker salaries
            return $t->recipient_type === Worker::class && 
                   $t->distribution_type === CashTransaction::DISTRIBUTION_TYPE_SALARY;
        });

        foreach ($curatorDistributions as $transaction) {
            $usedIds->push($transaction->id);
            $workerSalaryNodes[] = $this->formatNode($transaction, (float) $transaction->amount);
        }

        // Group into salary lists if enough
        return $this->groupWorkerSalaries($workerSalaryNodes, $salaryListCounter);
    }

    /**
     * Build outgoing branch for curator (worker salaries only)
     */
    protected function buildCuratorOutgoingBranch(Collection $transactions, User $curator, int &$salaryListCounter): array
    {
        $workerSalaryNodes = [];

        // Get worker salary distributions from this curator
        $outgoing = $transactions->filter(function ($t) use ($curator) {
            if ($t->type !== CashTransaction::TYPE_DISTRIBUTION) {
                return false;
            }
            if ($t->sender_id !== $curator->id || $t->sender_type !== User::class) {
                return false;
            }
            // Only worker salaries
            return $t->recipient_type === Worker::class && 
                   $t->distribution_type === CashTransaction::DISTRIBUTION_TYPE_SALARY;
        });

        foreach ($outgoing as $transaction) {
            $workerSalaryNodes[] = $this->formatNode($transaction, (float) $transaction->amount);
        }

        // Group into salary lists if enough
        return $this->groupWorkerSalaries($workerSalaryNodes, $salaryListCounter);
    }

    /**
     * Build outgoing branch for curator with time boundaries
     * Only includes transactions created after startTime and before endTime
     */
    protected function buildCuratorOutgoingBranchTimeBounded(
        Collection $transactions, 
        User $curator, 
        $startTime,
        $endTime,
        int &$salaryListCounter,
        Collection &$usedTransactionIds
    ): array {
        $workerSalaryNodes = [];

        // Get worker salary distributions from this curator within time window
        $outgoing = $transactions->filter(function ($t) use ($curator, $startTime, $endTime, $usedTransactionIds) {
            // Skip already used transactions
            if ($usedTransactionIds->contains($t->id)) {
                return false;
            }
            
            if ($t->type !== CashTransaction::TYPE_DISTRIBUTION) {
                return false;
            }
            if ($t->sender_id !== $curator->id || $t->sender_type !== User::class) {
                return false;
            }
            
            // Must be after start time
            if ($t->created_at < $startTime) {
                return false;
            }
            
            // If there's an end time, must be before it
            if ($endTime && $t->created_at >= $endTime) {
                return false;
            }
            
            // Only worker salaries
            return $t->recipient_type === Worker::class && 
                   $t->distribution_type === CashTransaction::DISTRIBUTION_TYPE_SALARY;
        });

        foreach ($outgoing as $transaction) {
            $usedTransactionIds->push($transaction->id);
            $workerSalaryNodes[] = $this->formatNode($transaction, (float) $transaction->amount);
        }

        // Group into salary lists if enough
        return $this->groupWorkerSalaries($workerSalaryNodes, $salaryListCounter);
    }
}
