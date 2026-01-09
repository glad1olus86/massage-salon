<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MassageService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'price_15',
        'price_30',
        'price_45',
        'price_60',
        'price_90',
        'price_120',
        'price_180',
        'operator_share_15',
        'operator_share_30',
        'operator_share_45',
        'operator_share_60',
        'operator_share_90',
        'operator_share_120',
        'operator_share_180',
        'duration',
        'is_active',
        'is_extra',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_15' => 'decimal:2',
        'price_30' => 'decimal:2',
        'price_45' => 'decimal:2',
        'price_60' => 'decimal:2',
        'price_90' => 'decimal:2',
        'price_120' => 'decimal:2',
        'price_180' => 'decimal:2',
        'operator_share_15' => 'decimal:2',
        'operator_share_30' => 'decimal:2',
        'operator_share_45' => 'decimal:2',
        'operator_share_60' => 'decimal:2',
        'operator_share_90' => 'decimal:2',
        'operator_share_120' => 'decimal:2',
        'operator_share_180' => 'decimal:2',
        'is_active' => 'boolean',
        'is_extra' => 'boolean',
    ];

    /**
     * Get users (masseuses) who provide this service.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_massage_services')
            ->withPivot('custom_price')
            ->withTimestamps();
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ') . ' CZK';
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) {
            return '-';
        }
        
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;
        
        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}min";
        } elseif ($hours > 0) {
            return "{$hours}h";
        }
        
        return "{$minutes} min";
    }

    /**
     * Scope for active services.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for regular (non-extra) services.
     */
    public function scopeRegular($query)
    {
        return $query->where('is_extra', false);
    }

    /**
     * Scope for extra services.
     */
    public function scopeExtra($query)
    {
        return $query->where('is_extra', true);
    }

    /**
     * Scope for ordering.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the creator company.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
