<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBillingLog extends Model
{
    protected $fillable = [
        'company_id',
        'billing_period_id',
        'user_id',
        'action',
        'role',
        'previous_role',
        'details',
    ];

    /**
     * Action types
     */
    const ACTION_USER_ADDED = 'user_added';
    const ACTION_USER_REMOVED = 'user_removed';
    const ACTION_ROLE_CHANGED = 'role_changed';

    /**
     * Get the company that owns this log
     */
    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    /**
     * Get the billing period this log belongs to
     */
    public function billingPeriod()
    {
        return $this->belongsTo(UserBillingPeriod::class, 'billing_period_id');
    }

    /**
     * Get the user this log is about
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
