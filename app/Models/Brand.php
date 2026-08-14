<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $brand): void {
            if ($brand->name) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
