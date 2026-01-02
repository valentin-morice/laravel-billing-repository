<?php

namespace ValentinMorice\LaravelBillingRepository\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $provider_id
 * @property string $name
 * @property string|null $description
 * @property bool $active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class BillingProduct extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'provider_id', 'name', 'description', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(BillingPrice::class, 'product_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
