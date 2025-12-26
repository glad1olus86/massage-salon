<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Position extends Model
{
    use HasFactory;

    protected $fillable = ['work_place_id', 'name', 'created_by'];

    /**
     * Get the work place for this position
     */
    public function workPlace()
    {
        return $this->belongsTo(WorkPlace::class);
    }

    /**
     * Get all work assignments for this position
     */
    public function workAssignments()
    {
        return $this->hasMany(WorkAssignment::class);
    }

    /**
     * Get current (active) work assignments
     */
    public function currentAssignments()
    {
        return $this->hasMany(WorkAssignment::class)->whereNull('ended_at');
    }

    /**
     * Get count of current workers
     */
    public function getCurrentWorkerCountAttribute()
    {
        return $this->currentAssignments()->count();
    }

    /**
     * Scope for current user (multi-tenancy)
     */
    public function scopeForCurrentUser($query)
    {
        return $query->where('created_by', Auth::user()->creatorId());
    }

    /**
     * Get the user who created this position
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
