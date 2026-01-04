<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;

/**
 * @property int $id
 * @property int $billing_price_id
 * @property string|null $tax_behavior
 * @property string|null $lookup_key
 */
class StripePriceFeatures extends Model
{
    protected $fillable = [
        'billing_price_id',
        'tax_behavior',
        'lookup_key',
    ];

    public function price(): BelongsTo
    {
        return $this->belongsTo(BillingPrice::class, 'billing_price_id');
    }
}
