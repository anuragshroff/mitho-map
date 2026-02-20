<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialOffer extends Model
{
    /** @use HasFactory<\Database\Factories\SpecialOfferFactory> */
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'title',
        'description',
        'discount_percentage',
        'discount_amount',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the restaurant that owns the special offer.
     */
    public function restaurant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
