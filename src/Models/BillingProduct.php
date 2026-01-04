<?php

namespace ValentinMorice\LaravelBillingRepository\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use ValentinMorice\LaravelBillingRepository\Stripe\Models\StripeProductFeatures;

/**
 * @property int $id
 * @property string $key
 * @property string $provider_id
 * @property string $name
 * @property string|null $description
 * @property array|null $metadata
 * @property bool $active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read StripeProductFeatures|null $stripe
 */
class BillingProduct extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'provider_id', 'name', 'description', 'metadata', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'metadata' => 'array'];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(BillingPrice::class, 'product_id');
    }

    public function stripe(): HasOne
    {
        return $this->hasOne(StripeProductFeatures::class, 'billing_product_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
