<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class NotificationRule extends Model
{
    protected $fillable = [
        'name',
        'entity_type',
        'conditions',
        'period_from',
        'period_to',
        'severity',
        'is_active',
        'is_grouped',
        'created_by',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'is_grouped' => 'boolean',
        'period_from' => 'integer',
        'period_to' => 'integer',
    ];

    // Entity types
    const ENTITY_WORKER = 'worker';
    const ENTITY_ROOM = 'room';
    const ENTITY_HOTEL = 'hotel';
    const ENTITY_WORK_PLACE = 'work_place';
    const ENTITY_CASHBOX = 'cashbox';

    // Severity levels
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_DANGER = 'danger';

    /**
     * Get available entity types
     */
    public static function getEntityTypes(): array
    {
        return [
            self::ENTITY_WORKER => __('Worker'),
            self::ENTITY_ROOM => __('Room'),
            self::ENTITY_HOTEL => __('Hotel'),
            self::ENTITY_WORK_PLACE => __('Work Place'),
            self::ENTITY_CASHBOX => __('Cashbox'),
        ];
    }

    /**
     * Get severity levels
     */
    public static function getSeverityLevels(): array
    {
        return [
            self::SEVERITY_INFO => ['label' => __('Normal'), 'color' => 'info', 'icon' => 'ti-info-circle'],
            self::SEVERITY_WARNING => ['label' => __('Almost Critical'), 'color' => 'warning', 'icon' => 'ti-alert-triangle'],
            self::SEVERITY_DANGER => ['label' => __('Critical'), 'color' => 'danger', 'icon' => 'ti-alert-circle'],
        ];
    }

    /**
     * Get conditions for entity type
     */
    public static function getConditionsForEntity(string $entityType): array
    {
        return match($entityType) {
            self::ENTITY_WORKER => [
                'is_employed' => ['label' => __('Employed'), 'type' => 'boolean'],
                'not_employed' => ['label' => __('Not employed'), 'type' => 'boolean'],
                'is_housed' => ['label' => __('Living in hotel'), 'type' => 'boolean'],
                'not_housed' => ['label' => __('Not living in hotel'), 'type' => 'boolean'],
                'no_assignment' => ['label' => __('No assignment (no work, no housing)'), 'type' => 'boolean'],
            ],
            self::ENTITY_ROOM => [
                'is_full' => ['label' => __('Fully occupied'), 'type' => 'boolean'],
                'is_empty' => ['label' => __('Empty'), 'type' => 'boolean'],
                'is_partial' => ['label' => __('Partially occupied'), 'type' => 'boolean'],
                'occupancy_above' => ['label' => __('Occupancy above %'), 'type' => 'number', 'suffix' => '%'],
                'occupancy_below' => ['label' => __('Occupancy below %'), 'type' => 'number', 'suffix' => '%'],
            ],
            self::ENTITY_HOTEL => [
                'occupancy_above' => ['label' => __('Occupancy above %'), 'type' => 'number', 'suffix' => '%'],
                'occupancy_below' => ['label' => __('Occupancy below %'), 'type' => 'number', 'suffix' => '%'],
                'has_empty_rooms' => ['label' => __('Has empty rooms'), 'type' => 'boolean'],
                'no_empty_rooms' => ['label' => __('No empty rooms'), 'type' => 'boolean'],
            ],
            self::ENTITY_WORK_PLACE => [
                'has_no_workers' => ['label' => __('No workers'), 'type' => 'boolean'],
                'workers_below' => ['label' => __('Workers below'), 'type' => 'number'],
                'workers_above' => ['label' => __('Workers above'), 'type' => 'number'],
            ],
            self::ENTITY_CASHBOX => [
                'cashbox_money_received' => ['label' => __('Money received'), 'type' => 'event'],
                'cashbox_money_sent' => ['label' => __('Money sent'), 'type' => 'event'],
                'cashbox_money_refunded' => ['label' => __('Money refunded'), 'type' => 'event'],
                'cashbox_taken_to_work' => ['label' => __('Taken to work'), 'type' => 'event'],
            ],
            default => [],
        };
    }

    /**
     * Get template variables for entity type
     * Requirement 12.3: Template variables for cashbox notifications
     */
    public static function getTemplateVariablesForEntity(string $entityType): array
    {
        return match($entityType) {
            self::ENTITY_CASHBOX => [
                '{amount}' => __('Transaction amount'),
                '{sender_name}' => __('Sender name'),
                '{recipient_name}' => __('Recipient name'),
                '{comment}' => __('Comment'),
                '{task}' => __('Task'),
            ],
            default => [],
        };
    }

    /**
     * Scope for current user
     */
    public function scopeForCurrentUser($query)
    {
        return $query->where('created_by', Auth::user()->creatorId());
    }

    /**
     * Scope for active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get period display text
     */
    public function getPeriodTextAttribute(): string
    {
        if ($this->period_from == 0 && !$this->period_to) {
            return __('Immediately');
        }
        
        if ($this->period_to) {
            return $this->period_from . '-' . $this->period_to . ' ' . __('days');
        }
        
        return __('from') . ' ' . $this->period_from . ' ' . __('days');
    }

    /**
     * Get severity info
     */
    public function getSeverityInfoAttribute(): array
    {
        return self::getSeverityLevels()[$this->severity] ?? self::getSeverityLevels()[self::SEVERITY_INFO];
    }

    /**
     * Check if days count matches this rule's period
     */
    public function matchesPeriod(int $days): bool
    {
        if ($days < $this->period_from) {
            return false;
        }
        
        if ($this->period_to !== null && $days > $this->period_to) {
            return false;
        }
        
        return true;
    }
}
