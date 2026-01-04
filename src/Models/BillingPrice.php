<?php

namespace ValentinMorice\LaravelBillingRepository\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use ValentinMorice\LaravelBillingRepository\Stripe\Models\StripePriceFeatures;

/**
 * @property int $id
 * @property int $product_id
 * @property string $type
 * @property string $provider_id
 * @property int $amount
 * @property string $currency
 * @property array|null $recurring
 * @property string|null $nickname
 * @property array|null $metadata
 * @property int|null $trial_period_days
 * @property bool $active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read StripePriceFeatures|null $stripe
 */
class BillingPrice extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'type', 'provider_id', 'amount', 'currency', 'recurring', 'nickname', 'metadata', 'trial_period_days', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'recurring' => 'array', 'metadata' => 'array'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(BillingProduct::class, 'product_id');
    }

    public function stripe(): HasOne
    {
        return $this->hasOne(StripePriceFeatures::class, 'billing_price_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
