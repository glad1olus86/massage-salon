<?php

namespace App\Models;

use App\Models\Traits\HasResponsible;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Worker extends Model
{
    use HasFactory, HasResponsible;

    protected $fillable = [
        'first_name',
        'last_name',
        'dob',
        'gender',
        'nationality',
        'registration_date',
        'phone',
        'email',
        'document_photo',
        'photo',
        'created_by',
        'responsible_id',
    ];

    protected $casts = [
        'dob' => 'date',
        'registration_date' => 'date',
    ];


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the current active room assignment for this worker.
     */
    public function currentAssignment()
    {
        return $this->hasOne(RoomAssignment::class)
            ->whereNull('check_out_date')
            ->with(['room', 'hotel']);
    }

    /**
     * Get all room assignments for this worker (historical).
     */
    public function assignments()
    {
        return $this->hasMany(RoomAssignment::class);
    }

    /**
     * Get all room assignments for this worker.
     */
    public function roomAssignments()
    {
        return $this->hasMany(RoomAssignment::class);
    }

    /**
     * Get the current active work assignment for this worker.
     */
    public function currentWorkAssignment()
    {
        return $this->hasOne(WorkAssignment::class)
            ->whereNull('ended_at')
            ->with(['workPlace']);
    }

    /**
     * Get all work assignments for this worker.
     */
    public function workAssignments()
    {
        return $this->hasMany(WorkAssignment::class);
    }

    /**
     * Get all shift schedules for this worker.
     */
    public function shiftSchedules()
    {
        return $this->hasMany(ShiftSchedule::class);
    }

    /**
     * Get all attendance records for this worker.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
