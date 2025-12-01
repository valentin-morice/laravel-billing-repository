<?php

namespace ValentinMorice\LaravelStripeRepository\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripePrice extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'stripe_id',
        'amount',
        'currency',
        'recurring',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'recurring' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(StripeProduct::class, 'product_id');
    }
}
