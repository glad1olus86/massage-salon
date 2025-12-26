<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_plate',
        'brand',
        'color',
        'vin_code',
        'registration_date',
        'engine_volume',
        'passport_fuel_consumption',
        'fuel_consumption',
        'photo',
        'tech_passport_front',
        'tech_passport_back',
        'assigned_type',
        'assigned_id',
        'created_by',
    ];

    protected $casts = [
        'fuel_consumption' => 'decimal:2',
        'passport_fuel_consumption' => 'decimal:1',
        'registration_date' => 'date',
        'engine_volume' => 'integer',
    ];

    /**
     * Scope for current user's vehicles (multi-tenancy)
     */
    public function scopeForCurrentUser($query)
    {
        return $query->where('created_by', Auth::user()->creatorId());
    }

    /**
     * Get the assigned person (Worker or User)
     */
    public function assignedPerson()
    {
        return $this->morphTo(__FUNCTION__, 'assigned_type', 'assigned_id');
    }

    /**
     * Get all technical inspections
     */
    public function inspections()
    {
        return $this->hasMany(TechnicalInspection::class)->orderBy('inspection_date', 'desc');
    }

    /**
     * Get the latest inspection
     */
    public function latestInspection()
    {
        return $this->hasOne(TechnicalInspection::class)->latestOfMany('inspection_date');
    }

    /**
     * Get inspection status: ok, soon, overdue
     */
    public function getInspectionStatusAttribute(): string
    {
        $latest = $this->latestInspection;
        
        if (!$latest) {
            return 'none';
        }

        $nextDate = Carbon::parse($latest->next_inspection_date);
        $today = Carbon::today();

        if ($nextDate->isPast()) {
            return 'overdue';
        }

        if ($nextDate->diffInDays($today) <= 30) {
            return 'soon';
        }

        return 'ok';
    }

    /**
     * Get inspection status label with days count
     */
    public function getInspectionStatusLabelAttribute(): string
    {
        $latest = $this->latestInspection;
        
        if (!$latest) {
            return __('No data');
        }

        $nextDate = Carbon::parse($latest->next_inspection_date);
        $today = Carbon::today();
        $days = $today->diffInDays($nextDate, false); // false = can be negative

        if ($days < 0) {
            // Overdue
            $overdueDays = abs($days);
            return __('Overdue') . ' ' . $overdueDays . ' ' . $this->pluralizeDays($overdueDays);
        }

        // Days until inspection
        return __('Until inspection') . ' - ' . $days . ' ' . $this->pluralizeDays($days);
    }

    /**
     * Pluralize days word based on current locale
     */
    protected function pluralizeDays(int $count): string
    {
        $locale = app()->getLocale();
        
        // For Slavic languages (ru, uk, cs) use proper pluralization
        if (in_array($locale, ['ru', 'uk'])) {
            $mod10 = $count % 10;
            $mod100 = $count % 100;
            
            if ($mod100 >= 11 && $mod100 <= 19) {
                return __('days_many'); // дней / днів
            }
            if ($mod10 === 1) {
                return __('days_one'); // день
            }
            if ($mod10 >= 2 && $mod10 <= 4) {
                return __('days_few'); // дня / дні
            }
            return __('days_many'); // дней / днів
        }
        
        if ($locale === 'cs') {
            if ($count === 1) {
                return __('days_one'); // den
            }
            if ($count >= 2 && $count <= 4) {
                return __('days_few'); // dny
            }
            return __('days_many'); // dní
        }
        
        // English - simple singular/plural
        return $count === 1 ? __('day') : __('days');
    }

    /**
     * Get inspection status badge class
     */
    public function getInspectionStatusBadgeAttribute(): string
    {
        return match ($this->inspection_status) {
            'overdue' => 'bg-danger',
            'soon' => 'bg-warning',
            'ok' => 'bg-success',
            'none' => 'bg-secondary',
        };
    }

    /**
     * Get assigned person name
     */
    public function getAssignedNameAttribute(): ?string
    {
        if (!$this->assigned_id || !$this->assigned_type) {
            return null;
        }

        // Load relation if not loaded
        if (!$this->relationLoaded('assignedPerson')) {
            $this->load('assignedPerson');
        }

        $person = $this->assignedPerson;
        
        if (!$person) {
            return null;
        }

        // Check if it's a Worker (has first_name/last_name)
        if (str_contains($this->assigned_type, 'Worker')) {
            return trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
        }

        // Otherwise it's a User
        return $person->name ?? null;
    }
}
