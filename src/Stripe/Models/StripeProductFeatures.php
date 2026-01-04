<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

/**
 * @property int $id
 * @property int $billing_product_id
 * @property string|null $tax_code
 * @property string|null $statement_descriptor
 */
class StripeProductFeatures extends Model
{
    protected $fillable = [
        'billing_product_id',
        'tax_code',
        'statement_descriptor',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(BillingProduct::class, 'billing_product_id');
    }
}
