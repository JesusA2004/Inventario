<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResponsiblePerson extends Model
{
    use HasFactory;

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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assetsInCharge(): HasMany
    {
        return $this->hasMany(Asset::class, 'current_responsible_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
