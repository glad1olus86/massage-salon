<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['worker_id', 'work_place_id', 'position_id', 'started_at', 'ended_at', 'created_by'];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
    ];

    /**
     * Get the worker for this assignment
     */
    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    /**
     * Get the work place for this assignment
     */
    public function workPlace()
    {
        return $this->belongsTo(WorkPlace::class);
    }

    /**
     * Get the position for this assignment
     */
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Scope to get only current (active) assignments
     */
    public function scopeCurrent($query)
    {
        return $query->whereNull('ended_at');
    }

    /**
     * Get the user who created this assignment.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
