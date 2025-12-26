<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\NotificationRule;
use App\Models\SystemNotification;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Facades\Auth;

class CashboxNotificationService
{
    // Event types
    const EVENT_MONEY_RECEIVED = 'cashbox_money_received';
    const EVENT_MONEY_SENT = 'cashbox_money_sent';
    const EVENT_MONEY_REFUNDED = 'cashbox_money_refunded';
    const EVENT_TAKEN_TO_WORK = 'cashbox_taken_to_work';

    /**
     * Template variables available for cashbox notifications
     */
    public static function getTemplateVariables(): array
    {
        return [
            '{amount}' => __('Сумма транзакции'),
            '{sender_name}' => __('Имя отправителя'),
            '{recipient_name}' => __('Имя получателя'),
            '{comment}' => __('Комментарий'),
            '{task}' => __('Задача'),
        ];
    }

    /**
     * Notify about money received (distribution to recipient)
     * Requirement 12.2: cashbox_money_received event
     */
    public function notifyMoneyReceived(CashTransaction $transaction): void
    {
        $this->processEvent(self::EVENT_MONEY_RECEIVED, $transaction);
    }

    /**
     * Notify about money sent (distribution from sender)
     * Requirement 12.2: cashbox_money_sent event
     */
    public function notifyMoneySent(CashTransaction $transaction): void
    {
        $this->processEvent(self::EVENT_MONEY_SENT, $transaction);
    }

    /**
     * Notify about money refunded
     * Requirement 12.2: cashbox_money_refunded event
     */
    public function notifyMoneyRefunded(CashTransaction $transaction): void
    {
        $this->processEvent(self::EVENT_MONEY_REFUNDED, $transaction);
    }

    /**
     * Notify about transaction taken to work
     * Requirement 12.2: cashbox_taken_to_work event
     */
    public function notifyTakenToWork(CashTransaction $transaction): void
    {
        $this->processEvent(self::EVENT_TAKEN_TO_WORK, $transaction);
    }

    /**
     * Process event and create notifications based on rules
     */
    protected function processEvent(string $eventType, CashTransaction $transaction): void
    {
        $companyId = $transaction->created_by;

        // Get active rules for cashbox entity with this event
        $rules = NotificationRule::where('created_by', $companyId)
            ->where('is_active', true)
            ->where('entity_type', NotificationRule::ENTITY_CASHBOX)
            ->get();

        \Log::info('CashboxNotification: Processing event', [
            'eventType' => $eventType,
            'companyId' => $companyId,
            'rulesCount' => $rules->count(),
            'transaction_id' => $transaction->id,
        ]);

        foreach ($rules as $rule) {
            $matches = $this->ruleMatchesEvent($rule, $eventType);
            \Log::info('CashboxNotification: Rule check', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'conditions' => $rule->conditions,
                'matches' => $matches,
            ]);
            
            if ($matches) {
                $this->createNotification($rule, $transaction, $eventType);
            }
        }
    }

    /**
     * Check if rule matches the event type
     */
    protected function ruleMatchesEvent(NotificationRule $rule, string $eventType): bool
    {
        $conditions = $rule->conditions ?? [];
        
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            if ($field === $eventType) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create notification for the event
     * Requirement 12.3: Pass amount, sender, recipient, comment to notification
     */
    protected function createNotification(NotificationRule $rule, CashTransaction $transaction, string $eventType): void
    {
        $data = $this->buildNotificationData($transaction, $eventType);
        $message = $this->buildMessage($transaction, $eventType);
        $title = $this->buildTitle($rule, $eventType);
        $link = $this->buildLink($transaction);
        $targetUserId = $this->getTargetUserId($transaction, $eventType);

        // Only create notification if there's a valid target user
        if (!$targetUserId) {
            return;
        }

        // Add target user and distribution type to data for filtering
        $data['target_user_id'] = $targetUserId;
        $data['distribution_type'] = $transaction->distribution_type;
        
        // Include distribution_type in notification type for proper grouping
        // salary and transfer should be grouped separately
        $distributionSuffix = $transaction->distribution_type ? '_' . $transaction->distribution_type : '';
        $notificationType = 'cashbox_' . $eventType . $distributionSuffix;
        
        // If grouping is enabled, try to update existing unread notification
        if ($rule->is_grouped) {
            $existingNotification = SystemNotification::where('type', $notificationType)
                ->where('created_by', $transaction->created_by)
                ->where('is_read', false)
                ->whereRaw("JSON_EXTRACT(data, '$.target_user_id') = ?", [$targetUserId])
                ->first();
            
            if ($existingNotification) {
                // Update existing notification with new message
                $existingData = $existingNotification->data ?? [];
                $messages = $existingData['messages'] ?? [$existingNotification->message];
                $messages[] = $message;
                $count = count($messages);
                
                $existingData['messages'] = $messages;
                $existingData['count'] = $count;
                
                $existingNotification->update([
                    'title' => $title . ' (' . $count . ')',
                    'message' => implode("\n", array_slice($messages, -5)), // Show last 5 messages
                    'data' => $existingData,
                    'updated_at' => now(),
                ]);
                
                return;
            }
        }
        
        // Create new notification
        SystemNotification::create([
            'type' => $notificationType,
            'title' => $title,
            'message' => $message,
            'severity' => $rule->severity,
            'data' => $data,
            'link' => $link,
            'created_by' => $transaction->created_by,
        ]);
    }

    /**
     * Build notification data array
     * Requirement 12.3: Include amount, sender, recipient, comment, task
     */
    protected function buildNotificationData(CashTransaction $transaction, string $eventType): array
    {
        return [
            'transaction_id' => $transaction->id,
            'event_type' => $eventType,
            'amount' => $transaction->amount,
            'sender_id' => $transaction->sender_id,
            'sender_type' => $transaction->sender_type,
            'sender_name' => $this->getParticipantName($transaction->sender_id, $transaction->sender_type),
            'recipient_id' => $transaction->recipient_id,
            'recipient_type' => $transaction->recipient_type,
            'recipient_name' => $this->getParticipantName($transaction->recipient_id, $transaction->recipient_type),
            'comment' => $transaction->comment,
            'task' => $transaction->task,
            'period_id' => $transaction->cash_period_id,
        ];
    }

    /**
     * Build notification message
     * Format: "sender выдал recipient сумму" - neutral, no pronouns
     */
    protected function buildMessage(CashTransaction $transaction, string $eventType): string
    {
        // Use null to get currency from company settings
        $amount = formatCashboxCurrency($transaction->amount);
        $senderName = $this->getParticipantName($transaction->sender_id, $transaction->sender_type);
        $recipientName = $this->getParticipantName($transaction->recipient_id, $transaction->recipient_type);

        $message = match($eventType) {
            // "company выдал manager 1000 Kč" - recipient receives money
            self::EVENT_MONEY_RECEIVED => __(':sender выдал :recipient :amount', [
                'sender' => $senderName,
                'recipient' => $recipientName,
                'amount' => $amount,
            ]),
            // "company выдал manager 1000 Kč" - sender sent money
            self::EVENT_MONEY_SENT => __(':sender выдал :recipient :amount', [
                'sender' => $senderName,
                'recipient' => $recipientName,
                'amount' => $amount,
            ]),
            // "manager вернул company 500 Kč"
            self::EVENT_MONEY_REFUNDED => __(':sender вернул :recipient :amount', [
                'sender' => $senderName,
                'recipient' => $recipientName,
                'amount' => $amount,
            ]),
            // "manager взял в работу 1000 Kč"
            self::EVENT_TAKEN_TO_WORK => __(':recipient взял в работу :amount', [
                'recipient' => $recipientName,
                'amount' => $amount,
            ]),
            default => '',
        };

        // Add task if present
        if ($transaction->task) {
            $message .= ' | ' . __('Задача') . ': ' . $transaction->task;
        }

        // Add comment if present
        if ($transaction->comment) {
            $message .= ' | ' . $transaction->comment;
        }

        return $message;
    }

    /**
     * Build notification title
     */
    protected function buildTitle(NotificationRule $rule, string $eventType): string
    {
        if ($rule->name) {
            return $rule->name;
        }

        return match($eventType) {
            self::EVENT_MONEY_RECEIVED => __('Касса: Получение денег'),
            self::EVENT_MONEY_SENT => __('Касса: Выдача денег'),
            self::EVENT_MONEY_REFUNDED => __('Касса: Возврат денег'),
            self::EVENT_TAKEN_TO_WORK => __('Касса: Взято в работу'),
            default => __('Касса'),
        };
    }

    /**
     * Build link to cashbox period
     */
    protected function buildLink(CashTransaction $transaction): string
    {
        return route('cashbox.show', $transaction->cash_period_id);
    }

    /**
     * Get target user ID for notification based on event type
     * Returns the user who should receive the notification
     */
    protected function getTargetUserId(CashTransaction $transaction, string $eventType): ?int
    {
        return match($eventType) {
            // Money received - notify recipient (the one who receives money)
            self::EVENT_MONEY_RECEIVED => $this->getUserIdFromParticipant(
                $transaction->recipient_id, 
                $transaction->recipient_type
            ),
            // Money sent - notify sender (the one who sent money)
            self::EVENT_MONEY_SENT => $this->getUserIdFromParticipant(
                $transaction->sender_id, 
                $transaction->sender_type
            ),
            // Money refunded - notify recipient of refund (the one who gets money back)
            self::EVENT_MONEY_REFUNDED => $this->getUserIdFromParticipant(
                $transaction->recipient_id, 
                $transaction->recipient_type
            ),
            // Taken to work - notify sender (so they know recipient started working)
            self::EVENT_TAKEN_TO_WORK => $this->getUserIdFromParticipant(
                $transaction->sender_id, 
                $transaction->sender_type
            ),
            default => null,
        };
    }

    /**
     * Get User ID from participant (only Users can receive notifications)
     */
    protected function getUserIdFromParticipant(?int $id, ?string $type): ?int
    {
        if (!$id || !$type) {
            return null;
        }

        // Only User type can receive notifications
        // Check both full class name and short name for compatibility
        if ($type === User::class || $type === 'App\Models\User' || $type === 'user') {
            return $id;
        }

        return null;
    }

    /**
     * Get participant name
     */
    protected function getParticipantName(?int $id, ?string $type): string
    {
        if (!$id || !$type) {
            return __('Система');
        }

        if ($type === User::class) {
            $user = User::find($id);
            return $user ? $user->name : __('Неизвестный');
        }

        if ($type === Worker::class) {
            $worker = Worker::find($id);
            return $worker ? ($worker->first_name . ' ' . $worker->last_name) : __('Неизвестный');
        }

        return __('Неизвестный');
    }
}
