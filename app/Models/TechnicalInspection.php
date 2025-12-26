<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TechnicalInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'inspection_date',
        'next_inspection_date',
        'mileage',
        'cost',
        'service_station',
        'description',
        'created_by',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'next_inspection_date' => 'date',
        'cost' => 'decimal:2',
        'mileage' => 'integer',
    ];

    /**
     * Scope for current user's inspections (multi-tenancy)
     */
    public function scopeForCurrentUser($query)
    {
        return $query->where('created_by', Auth::user()->creatorId());
    }

    /**
     * Get the vehicle
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get formatted inspection date
     */
    public function getFormattedInspectionDateAttribute(): string
    {
        return $this->inspection_date->format('d.m.Y');
    }

    /**
     * Get formatted next inspection date
     */
    public function getFormattedNextInspectionDateAttribute(): string
    {
        return $this->next_inspection_date->format('d.m.Y');
    }

    /**
     * Get formatted cost
     */
    public function getFormattedCostAttribute(): ?string
    {
        if ($this->cost === null) {
            return null;
        }
        return number_format($this->cost, 2, ',', ' ');
    }
}
