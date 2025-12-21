<?php

namespace ValentinMorice\LaravelBillingRepository\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string $type
 * @property string $provider_id
 * @property int $amount
 * @property string $currency
 * @property array|null $recurring
 * @property string|null $nickname
 * @property bool $active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class BillingPrice extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'provider_id',
        'amount',
        'currency',
        'recurring',
        'nickname',
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
        return $this->belongsTo(BillingProduct::class, 'product_id');
    }
}
