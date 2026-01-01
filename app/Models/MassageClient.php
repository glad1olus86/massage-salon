<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MassageClient extends Model
{
    use HasFactory;

    protected $table = 'massage_clients';

    // Статусы клиентов
    const STATUS_ACTIVE = 'active';
    const STATUS_VIP = 'vip';
    const STATUS_BLOCKED = 'blocked';

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
        'preferred_service',
        'notes',
        'source',
        'visits_count',
        'total_spent',
        'last_visit_date',
        'status',
        'created_by',
        'responsible_id',
    ];

    protected $casts = [
        'dob' => 'date',
        'registration_date' => 'date',
        'last_visit_date' => 'date',
        'total_spent' => 'decimal:2',
    ];

    /**
     * Get full name of the client.
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get status options for forms.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => __('Активный'),
            self::STATUS_VIP => __('VIP'),
            self::STATUS_BLOCKED => __('Заблокирован'),
        ];
    }

    /**
     * Get the creator of this client.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the responsible user for this client.
     */
    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    /**
     * Get all orders for this client.
     */
    public function orders()
    {
        return $this->hasMany(MassageOrder::class, 'client_id');
    }

    /**
     * Get all room bookings for this client.
     */
    public function roomBookings()
    {
        return $this->hasMany(RoomAssignment::class, 'massage_client_id');
    }

    /**
     * Check if client is VIP.
     */
    public function isVip(): bool
    {
        return $this->status === self::STATUS_VIP;
    }

    /**
     * Check if client is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    /**
     * Increment visits count.
     */
    public function incrementVisits(): void
    {
        $this->increment('visits_count');
        $this->update(['last_visit_date' => now()]);
    }

    /**
     * Add to total spent.
     */
    public function addSpent(float $amount): void
    {
        $this->increment('total_spent', $amount);
    }

    /**
     * Scope for active clients.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for VIP clients.
     */
    public function scopeVip($query)
    {
        return $query->where('status', self::STATUS_VIP);
    }

    /**
     * Scope for searching by name or phone.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }
}
