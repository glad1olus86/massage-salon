<?php

namespace App\Models;

use App\Models\Traits\HasResponsible;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory, HasResponsible;

    protected $fillable = ['name', 'address', 'phone', 'email', 'created_by', 'responsible_id'];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
