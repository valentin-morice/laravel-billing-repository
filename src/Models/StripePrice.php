<?php

namespace ValentinMorice\LaravelStripeRepository\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string $type
 * @property string $stripe_id
 * @property int $amount
 * @property string $currency
 * @property array|null $recurring
 * @property bool $active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
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
