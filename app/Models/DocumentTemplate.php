<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DocumentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'content',
        'variables',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for current user's templates (multi-tenancy)
     */
    public function scopeForCurrentUser($query)
    {
        return $query->where('created_by', Auth::user()->creatorId());
    }

    /**
     * Scope for active templates only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the company that owns this template
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get formatted created date
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d.m.Y H:i');
    }

    /**
     * Get variables count
     */
    public function getVariablesCountAttribute()
    {
        return is_array($this->variables) ? count($this->variables) : 0;
    }

    /**
     * Check if template has specific variable
     */
    public function hasVariable(string $variable): bool
    {
        return is_array($this->variables) && in_array($variable, $this->variables);
    }
}
