<?php

namespace App\Models;

use App\Enums\ResourceTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'capacity',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'type' => ResourceTypes::class,
        'is_active' => 'boolean',
        'capacity' => 'integer',
        'meta' => 'array',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
