<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SystemNotification extends Model
{
    protected $fillable = [
        'type',
        'title', 
        'message',
        'data',
        'link',
        'severity',
        'is_read',
        'created_by',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
    ];

    // Notification types
    const TYPE_HOTEL_OCCUPANCY = 'hotel_occupancy';
    const TYPE_WORKER_UNEMPLOYED = 'worker_unemployed';

    // Severity levels
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_DANGER = 'danger';

    /**
     * Scope for current user's notifications (multi-tenancy)
     * For cashbox notifications, also check target_user_id in data
     */
    public function scopeForCurrentUser($query)
    {
        $userId = Auth::user()->id;
        $companyId = Auth::user()->creatorId();
        
        return $query->where(function ($q) use ($userId, $companyId) {
            // Standard company-wide notifications
            $q->where('created_by', $companyId)
              ->where(function ($subQ) use ($userId) {
                  // Either no target_user_id (broadcast to company)
                  // Or target_user_id matches current user
                  $subQ->whereNull('data->target_user_id')
                       ->orWhereJsonContains('data->target_user_id', $userId)
                       ->orWhereRaw("JSON_EXTRACT(data, '$.target_user_id') = ?", [$userId]);
              });
        });
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark as read
     */
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Get icon based on type/severity
     */
    public function getIconAttribute()
    {
        // For custom rules, use severity-based icons
        if (str_starts_with($this->type, 'custom_rule_')) {
            return match($this->severity) {
                self::SEVERITY_WARNING => 'ti ti-alert-triangle',
                self::SEVERITY_DANGER => 'ti ti-alert-circle',
                default => 'ti ti-info-circle',
            };
        }

        return match($this->type) {
            self::TYPE_HOTEL_OCCUPANCY => 'ti ti-building',
            self::TYPE_WORKER_UNEMPLOYED => 'ti ti-user-off',
            default => 'ti ti-bell',
        };
    }

    /**
     * Get color based on type/severity
     */
    public function getColorAttribute()
    {
        // For custom rules, use severity
        if (str_starts_with($this->type, 'custom_rule_') && $this->severity) {
            return $this->severity;
        }

        return match($this->type) {
            self::TYPE_HOTEL_OCCUPANCY => 'warning',
            self::TYPE_WORKER_UNEMPLOYED => 'danger',
            default => 'info',
        };
    }

    /**
     * Get translated message
     * Translates message parts dynamically based on stored data or by parsing original message
     */
    public function getTranslatedMessageAttribute(): string
    {
        $data = $this->data ?? [];
        
        // If we have new format data with conditions, use it
        if (!empty($data['conditions'])) {
            return $this->buildTranslatedMessage($data);
        }
        
        // Fallback: parse and translate original message
        return $this->translateOriginalMessage($this->message);
    }

    /**
     * Build translated message from stored data
     */
    protected function buildTranslatedMessage(array $data): string
    {
        $parts = [];
        
        // Worker name
        if (!empty($data['worker_name'])) {
            $parts[] = $data['worker_name'];
        }

        // Conditions
        if (!empty($data['conditions'])) {
            foreach ($data['conditions'] as $condition) {
                $field = $condition['field'] ?? '';
                $part = match($field) {
                    'is_employed' => __('employed at ":place"', ['place' => $data['work_place_name'] ?? '']),
                    'not_employed' => __('not employed'),
                    'is_housed' => __('staying at hotel ":hotel"', ['hotel' => $data['hotel_name'] ?? '']),
                    'not_housed' => __('not staying at hotel'),
                    'no_assignment' => __('without assignment'),
                    default => null,
                };
                if ($part) {
                    $parts[] = $part;
                }
            }
        }

        // Days
        if (!empty($data['days']) && $data['days'] > 0) {
            $parts[] = __(':days days', ['days' => $data['days']]);
        }
        
        return !empty($parts) ? implode(' - ', $parts) : $this->message;
    }

    /**
     * Translate original message by parsing and replacing known patterns
     */
    protected function translateOriginalMessage(string $message): string
    {
        $result = $message;
        
        // Replace "not employed"
        $result = str_replace('- not employed -', '- ' . __('not employed') . ' -', $result);
        $result = preg_replace('/- not employed$/', '- ' . __('not employed'), $result);
        
        // Replace "employed at" with place name
        $result = preg_replace_callback(
            '/- employed at "([^"]+)"/',
            fn($m) => '- ' . __('employed at ":place"', ['place' => $m[1]]),
            $result
        );
        
        // Replace "at hotel" with hotel name
        $result = preg_replace_callback(
            '/- at hotel "([^"]+)"/',
            fn($m) => '- ' . __('staying at hotel ":hotel"', ['hotel' => $m[1]]),
            $result
        );
        
        // Replace "not staying at hotel"
        $result = str_replace('- not staying at hotel', '- ' . __('not staying at hotel'), $result);
        
        // Replace "without assignment"
        $result = str_replace('- without assignment', '- ' . __('without assignment'), $result);
        
        // Replace "X days" at the end
        $result = preg_replace_callback(
            '/- (\d+) days?$/',
            fn($m) => '- ' . __(':days days', ['days' => $m[1]]),
            $result
        );
        
        return $result;
    }
}
