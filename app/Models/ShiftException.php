<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftException extends Model
{
    use HasFactory;

    const TYPE_ADD = 'add';       // Add extra shift on this day
    const TYPE_REMOVE = 'remove'; // Remove scheduled shift
    const TYPE_REPLACE = 'replace'; // Replace with different shift

    protected $fillable = [
        'worker_id',
        'date',
        'shift_template_id',
        'type',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the worker this exception belongs to.
     */
    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    /**
     * Get the shift template (for add/replace types).
     */
    public function shiftTemplate()
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    /**
     * Get the user who created this exception.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if this is an "add" exception.
     */
    public function isAdd(): bool
    {
        return $this->type === self::TYPE_ADD;
    }

    /**
     * Check if this is a "remove" exception.
     */
    public function isRemove(): bool
    {
        return $this->type === self::TYPE_REMOVE;
    }

    /**
     * Check if this is a "replace" exception.
     */
    public function isReplace(): bool
    {
        return $this->type === self::TYPE_REPLACE;
    }

    /**
     * Scope for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope for current user's company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('created_by', $companyId);
    }
}
