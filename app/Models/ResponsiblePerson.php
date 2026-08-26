<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResponsiblePerson extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'full_name',
        'position',
        'email',
        'phone',
        'notes',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function assetsInCharge(): HasMany
    {
        return $this->hasMany(Asset::class, 'current_responsible_id');
    }

    /**
     * @param  Builder<ResponsiblePerson>  $query
     * @return Builder<ResponsiblePerson>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
