<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_ref',
        'user_id',
        'resource_id',
        'resource_type',
        'starts_at',
        'ends_at',
        'status',
        'amount',
        'currency',
        'payment_method',
        'metadata',
        'confirmed_at',
        'checked_out_at',
    ];

    protected $casts = [
        'status' => BookingStatus::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'metadata' => 'array',
        'amount' => 'integer',
    ];

    //generate booking reference
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_ref)) {
                $booking->booking_ref = self::generateRef();
            }
        });
    }

    private static function generateRef(): string
    {
        do {
            $ref = 'BK-' . strtoupper(Str::random(8));
        } while (self::where('booking_ref', $ref)->exists());

        return $ref;
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }


    //helper methods
    public function isFinished(): bool
    {
        return now()->greaterThan($this->ends_at);
    }

    public function isConfirmed(): bool
    {
        return $this->status === BookingStatus::CONFIRMED;
    }
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            BookingStatus::PENDING,
            BookingStatus::CONFIRMED,
        ]);
    }
}
