<?php

namespace ValentinMorice\LaravelStripeRepository\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StripeProduct extends Model
{
    protected $fillable = [
        'key',
        'stripe_id',
        'name',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(StripePrice::class, 'product_id');
    }
}
