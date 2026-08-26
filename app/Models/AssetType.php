<?php

namespace App\Models;

use App\Enums\ResponsibilityProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'icon',
        'active',
        'responsibility_profile',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'responsibility_profile' => ResponsibilityProfile::class,
        ];
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * @param  Builder<AssetType>  $query
     * @return Builder<AssetType>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
