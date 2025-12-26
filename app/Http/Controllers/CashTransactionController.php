<?php

namespace App\Http\Controllers;

use App\Models\CashPeriod;
use App\Models\CashTransaction;
use App\Models\User;
use App\Models\Worker;
use App\Services\CashboxService;
use App\Services\CashHierarchyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CashTransactionController extends Controller
{
    protected CashboxService $cashboxService;
    protected CashHierarchyService $hierarchyService;

    public function __construct(CashboxService $cashboxService, CashHierarchyService $hierarchyService)
    {
        $this->cashboxService = $cashboxService;
        $this->hierarchyService = $hierarchyService;
    }

    /**
     * Create a deposit transaction (only Boss)
     * Requirement 4.1: Boss creates deposit, adds to period balance
     */
    public function deposit(Request $request)
    {
        if (!Auth::user()->can('cashbox_deposit')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $validator = Validator::make($request->all(), [
            'period_id' => 'required|exists:cash_periods,id',
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $period = CashPeriod::find($request->period_id);
        
        // Check company ownership
        if ($period->created_by != Auth::user()->creatorId()) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $transaction = $this->cashboxService->createDeposit(
                $period,
                Auth::user(),
                (float) $request->amount,
                $request->comment
            );

            return response()->json([
                'success' => true,
                'message' => __('Money successfully deposited.'),
                'transaction' => $transaction,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Create a distribution transaction
     * Requirement 5.1: Decreases sender balance, increases recipient balance
     */
    public function distribute(Request $request)
    {
        if (!Auth::user()->can('cashbox_distribute')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $validator = Validator::make($request->all(), [
            'period_id' => 'required|exists:cash_periods,id',
            'recipient_id' => 'required|integer',
            'recipient_type' => 'required|in:user,worker',
            'amount' => 'required|numeric|min:0.01',
            'task' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:1000',
            'distribution_type' => 'nullable|in:salary,transfer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $period = CashPeriod::find($request->period_id);
        
        // Check company ownership
        if ($period->created_by != Auth::user()->creatorId()) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        // Get recipient
        $recipient = $request->recipient_type === 'worker'
            ? Worker::find($request->recipient_id)
            : User::find($request->recipient_id);

        if (!$recipient) {
            return response()->json(['error' => __('Recipient not found.')], 404);
        }

        // Check recipient belongs to same company
        if ($recipient->created_by != Auth::user()->creatorId()) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $transaction = $this->cashboxService->createDistribution(
                $period,
                Auth::user(),
                $recipient,
                (float) $request->amount,
                $request->task,
                $request->comment,
                $request->distribution_type
            );

            return response()->json([
                'success' => true,
                'message' => __('Money successfully distributed.'),
                'transaction' => $transaction,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Create a refund transaction
     * Requirement 7.1: Decreases sender balance, increases recipient balance
     */
    public function refund(Request $request)
    {
        if (!Auth::user()->can('cashbox_refund')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:cash_transactions,id',
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $originalTransaction = CashTransaction::find($request->transaction_id);
        
        // Check company ownership
        if ($originalTransaction->created_by != Auth::user()->creatorId()) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $period = $originalTransaction->cashPeriod;

        try {
            $transaction = $this->cashboxService->createRefund(
                $period,
                $originalTransaction,
                Auth::user(),
                (float) $request->amount,
                $request->comment
            );

            return response()->json([
                'success' => true,
                'message' => __('Money successfully refunded.'),
                'transaction' => $transaction,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Create a self-salary transaction (only Manager)
     * Requirement 6.1: Manager can take salary once per period
     */
    public function selfSalary(Request $request)
    {
        if (!Auth::user()->can('cashbox_self_salary')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $validator = Validator::make($request->all(), [
            'period_id' => 'required|exists:cash_periods,id',
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $period = CashPeriod::find($request->period_id);
        
        // Check company ownership
        if ($period->created_by != Auth::user()->creatorId()) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $transaction = $this->cashboxService->createSelfSalary(
                $period,
                Auth::user(),
                (float) $request->amount,
                $request->comment
            );

            return response()->json([
                'success' => true,
                'message' => __('Salary successfully paid.'),
                'transaction' => $transaction,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Update transaction status
     * Requirement 8.1, 8.2: Status transitions
     */
    public function updateStatus(Request $request, CashTransaction $transaction)
    {
        if (!Auth::user()->can('cashbox_access')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        // Check company ownership
        if ($transaction->created_by != Auth::user()->creatorId()) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        // Only recipient can change status (or Boss)
        $userRole = $this->hierarchyService->getUserCashboxRole(Auth::user());
        $isRecipient = $transaction->recipient_id == Auth::user()->id 
            && $transaction->recipient_type === User::class;
        
        if ($userRole !== CashHierarchyService::ROLE_BOSS && !$isRecipient) {
            return response()->json(['error' => __('Only recipient can change status.')], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,completed,overdue',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $transaction = $this->cashboxService->updateStatus($transaction, $request->status);

            return response()->json([
                'success' => true,
                'message' => __('Status successfully updated.'),
                'transaction' => $transaction,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Get transaction details
     */
    public function show(CashTransaction $transaction)
    {
        if (!Auth::user()->can('cashbox_access')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        // Check company ownership
        if ($transaction->created_by != Auth::user()->creatorId()) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $transaction->load(['sender', 'recipient']);

        $senderName = $transaction->sender ? $transaction->sender->name : __('Unknown');
        $recipientName = null;
        if ($transaction->recipient) {
            if ($transaction->recipient_type === Worker::class) {
                $recipientName = $transaction->recipient->first_name . ' ' . $transaction->recipient->last_name;
            } else {
                $recipientName = $transaction->recipient->name;
            }
        }

        return response()->json([
            'success' => true,
            'transaction' => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'distribution_type' => $transaction->distribution_type,
                'amount' => $transaction->amount,
                'formatted_amount' => formatCashboxCurrency($transaction->amount),
                'status' => $transaction->status,
                'task' => $transaction->task,
                'comment' => $transaction->comment,
                'sender_name' => $senderName,
                'recipient_name' => $recipientName,
                'created_at' => $transaction->created_at->format('d.m.Y H:i'),
            ],
        ]);
    }
}
